/**
 * Price Loader Service
 *
 * Пакет: get_batch_tour_prices (PriceLoaderService) + fallback: excursion_prices с URL кнопки.
 *
 * @module priceLoader
 */

const AJAX_URL = () => window.ajax?.url || window.ajaxurl || "/wp-admin/admin-ajax.php";
/** Один чанк к серверу get_batch_tour_prices, чтобы не раздувать POST. */
const BATCH_SIZE = 20;

/**
 * Как в PriceLoaderService::fetchTourPrice — без дат Само отдаёт другой набор строк;
 * минимум по полю price тогда часто ловит мусор (сотни рублей вместо реальной цены).
 */
function defaultExcursionCheckinRange() {
  const start = new Date();
  start.setHours(0, 0, 0, 0);
  const end = new Date(start);
  end.setMonth(end.getMonth() + 3);
  const ymd = (d) =>
    `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, "0")}${String(d.getDate()).padStart(2, "0")}`;
  return { CHECKIN_BEG: ymd(start), CHECKIN_END: ymd(end) };
}

/**
 * Парсит ВСЕ нужные параметры из booking URL
 */
export function parseBookingParams(href) {
  if (!href) return null;
  try {
    const url = new URL(href, window.location.origin);
    const p = url.searchParams;
    const townFromInc = p.get("TOWNFROMINC");
    const stateInc = p.get("STATEINC");
    const tours = p.get("TOURINC");
    if (!townFromInc || !stateInc || !tours) return null;

    return {
      TOWNFROMINC: townFromInc,
      STATEINC: stateInc,
      TOURS: tours,
      CHECKIN_BEG: p.get("CHECKIN_BEG") || "",
      CHECKIN_END: p.get("CHECKIN_END") || "",
      NIGHTS_FROM: p.get("NIGHTS_FROM") || "",
      NIGHTS_TILL: p.get("NIGHTS_TILL") || "",
      ADULT: p.get("ADULT") || "2",
      CHILD: p.get("CHILD") || "0",
      CURRENCY: p.get("CURRENCY") || "1",
    };
  } catch {
    // ignore
  }
  return null;
}

/**
 * Находит минимальную цену из массива цен SAMO
 */
function findMinPrice(prices) {
  if (!Array.isArray(prices) || prices.length === 0) return null;
  let min = null;
  for (const item of prices) {
    const val = item.convertedPriceNumber || item.convertedPrice || item.price || 0;
    const num = typeof val === "number" ? val : parseFloat(String(val).match(/[\d.]+/)?.[0] || 0);
    if (num > 0 && (min === null || num < min)) min = num;
  }
  return min;
}

/**
 * Загружает min цену тура — один вызов excursion_prices с параметрами из URL
 */
export async function fetchTourMinPrice(params) {
  try {
    const defaults = defaultExcursionCheckinRange();
    // Само часто кладёт CHECKIN_BEG === CHECKIN_END (один YYYYMMDD) при 11 ночах — тогда API даёт пусто.
    const beg = params.CHECKIN_BEG || "";
    const end = params.CHECKIN_END || "";
    const skipUrlDates = beg && end && beg === end;
    const merged = {
      ...params,
      CHECKIN_BEG: skipUrlDates ? defaults.CHECKIN_BEG : (params.CHECKIN_BEG || defaults.CHECKIN_BEG),
      CHECKIN_END: skipUrlDates ? defaults.CHECKIN_END : (params.CHECKIN_END || defaults.CHECKIN_END),
      NIGHTS_FROM: params.NIGHTS_FROM || "1",
      NIGHTS_TILL: params.NIGHTS_TILL || "30",
      CURRENCY: "1",
    };

    const body = new URLSearchParams({
      action: "bsi_samo",
      method: "excursion_prices",
      ...merged,
    });

    const res = await fetch(AJAX_URL(), {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: body.toString(),
      credentials: "same-origin",
    });

    const json = await res.json();
    if (!json?.success) return null;

    const samoData = json.data?.SearchExcursion_PRICES;
    if (!samoData) return null;

    const prices = samoData.prices
      ? (Array.isArray(samoData.prices) ? samoData.prices : [samoData.prices])
      : (Array.isArray(samoData) ? samoData : null);

    return findMinPrice(prices);
  } catch {
    return null;
  }
}

/**
 * @param {object} p
 * @returns {string|null}
 */
function formatServerPriceRow(p) {
  if (!p || typeof p !== "object") return null;
  if (p.price === undefined || p.price === null) return null;
  const showFrom = p.show_from !== false;
  const prefix = showFrom ? "от " : "";
  const formatted = p.price_formatted ?? "";
  return `${prefix}${formatted} ₽ / чел`;
}

/**
 * @param {number[]} tourIds
 * @returns {Promise<Record<string, object>>}
 */
async function fetchBatchTourPricesFromServer(tourIds) {
  if (tourIds.length === 0) return {};
  console.log("[priceLoader] batch request →", tourIds);
  try {
    const body = new URLSearchParams();
    body.set("action", "get_batch_tour_prices");
    tourIds.forEach((id) => {
      body.append("tour_ids[]", String(id));
    });
    const res = await fetch(AJAX_URL(), {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: body.toString(),
      credentials: "same-origin",
    });
    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch {
      console.warn("[priceLoader] batch parse error, raw:", text.slice(0, 300));
      return {};
    }
    console.log("[priceLoader] batch response:", json);
    if (!json?.success || !json.data?.prices) {
      console.warn("[priceLoader] batch no prices, success:", json?.success, "data:", json?.data);
      return {};
    }
    return json.data.prices;
  } catch (e) {
    console.error("[priceLoader] batch fetch error:", e);
    return {};
  }
}

function markPriceFailed(el) {
  el.classList.remove("is-loading");
  if (!el.hasAttribute("data-price-loaded")) {
    el.textContent = "По запросу";
    el.classList.add("price-unavailable");
  }
}

/**
 * Загрузка цен карточек туров одним серверным батчем (PriceLoaderService, кеш 3ч).
 * Сервер сам ходит в Samo и ретраит по доступным датам, поэтому клиентский фолбэк
 * не нужен: при пустом ответе показываем «По запросу». Это убирает залп запросов
 * к admin-ajax.php, из-за которого хостер банил IP по rate-limit.
 *
 * @param {ParentNode} container
 */
export async function loadTourPricesBatch(container) {
  if (!container) return;

  const priceElements = container.querySelectorAll("[data-tour-price]");
  if (priceElements.length === 0) return;

  const toLoad = [];
  priceElements.forEach((el) => {
    if (el.hasAttribute("data-price-loaded")) {
      el.classList.add("price-loaded");
      return;
    }
    toLoad.push(el);
  });
  if (toLoad.length === 0) return;

  toLoad.forEach((el) => el.classList.add("is-loading"));

  try {
    const byId = new Map();
    toLoad.forEach((el) => {
      const id = el.dataset.tourId;
      if (!id) return;
      if (!byId.has(id)) byId.set(id, []);
      byId.get(id).push(el);
    });

    const uniqueIds = [...byId.keys()]
      .map((s) => parseInt(s, 10))
      .filter((n) => n > 0);

    const merged = {};
    for (let i = 0; i < uniqueIds.length; i += BATCH_SIZE) {
      const chunk = uniqueIds.slice(i, i + BATCH_SIZE);
      const part = await fetchBatchTourPricesFromServer(chunk);
      Object.assign(merged, part);
    }

    toLoad.forEach((el) => {
      const id = el.dataset.tourId;
      if (!id) {
        el.classList.remove("is-loading");
        return;
      }
      const data = merged[id] ?? merged[String(id)] ?? null;
      const text = formatServerPriceRow(data);
      if (text) {
        el.textContent = text;
        el.classList.add("price-loaded");
        el.setAttribute("data-price-loaded", "");
        el.classList.remove("is-loading");
      } else {
        markPriceFailed(el);
      }
    });
  } catch {
    toLoad.forEach((el) => markPriceFailed(el));
  }
}

/**
 * Отобразить цены в карточках туров: один серверный батч, без клиентского фолбэка.
 */
export const displayTourPrices = async (container) => {
  await loadTourPricesBatch(container);
};

/**
 * Форматировать цену для отображения
 */
export const formatPrice = (priceData) => {
  if (!priceData) return "";
  const prefix = priceData.show_from ? "от " : "";
  return `${prefix}${priceData.price_formatted} ${priceData.currency}`;
};
