/**
 * Price Loader Service
 * 
 * Загрузка цен туров из Samotour API через bsi_samo.
 * Парсит все параметры напрямую из booking URL (href кнопки).
 * Кэширует min цену на сервере через save_tour_min_price.
 * 
 * @module priceLoader
 */

const AJAX_URL = () => window.ajax?.url || window.ajaxurl || '/wp-admin/admin-ajax.php';
const MAX_TOURS = 10;

/**
 * Парсит ВСЕ нужные параметры из booking URL
 */
function parseBookingParams(href) {
  if (!href) return null;
  try {
    const url = new URL(href, window.location.origin);
    const p = url.searchParams;
    const townFromInc = p.get('TOWNFROMINC');
    const stateInc = p.get('STATEINC');
    const tours = p.get('TOURINC');
    if (!townFromInc || !stateInc || !tours) return null;

    return {
      TOWNFROMINC: townFromInc,
      STATEINC: stateInc,
      TOURS: tours,
      CHECKIN_BEG: p.get('CHECKIN_BEG') || '',
      CHECKIN_END: p.get('CHECKIN_END') || '',
      NIGHTS_FROM: p.get('NIGHTS_FROM') || '',
      NIGHTS_TILL: p.get('NIGHTS_TILL') || '',
      ADULT: p.get('ADULT') || '2',
      CHILD: p.get('CHILD') || '0',
      CURRENCY: p.get('CURRENCY') || '1',
    };
  } catch {}
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
    const num = typeof val === 'number' ? val : parseFloat(String(val).match(/[\d.]+/)?.[0] || 0);
    if (num > 0 && (min === null || num < min)) min = num;
  }
  return min;
}

/**
 * Загружает min цену тура — один вызов excursion_prices с параметрами из URL
 */
async function fetchTourMinPrice(params) {
  try {
    const body = new URLSearchParams({
      action: 'bsi_samo',
      method: 'excursion_prices',
      ...params,
    });

    const res = await fetch(AJAX_URL(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
      credentials: 'same-origin',
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
 * Сохраняет отображаемую цену в серверный кэш (та же величина, что показываем пользователю)
 */
function savePriceToCache(tourId, displayPrice) {
  if (displayPrice <= 0) return;
  fetch(AJAX_URL(), {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({
      action: 'save_tour_min_price',
      tour_id: tourId,
      min_price: displayPrice,
    }),
    credentials: 'same-origin',
  }).catch(() => {});
}

/**
 * Отобразить цены в карточках туров.
 * 
 * 1. Пропускает элементы с data-price-loaded (из серверного кэша)
 * 2. Парсит ВСЕ параметры из href кнопки (всё зашито в ссылке)
 * 3. Вызывает excursion_prices → min цена → отображает + кэширует
 */
export const displayTourPrices = async (container, params = {}) => {
  if (!container) return;

  const priceElements = container.querySelectorAll('[data-tour-price]');
  if (priceElements.length === 0) return;

  const toLoad = [];
  priceElements.forEach(el => {
    if (el.hasAttribute('data-price-loaded')) {
      el.classList.add('price-loaded');
      return;
    }
    toLoad.push(el);
  });

  if (toLoad.length === 0) return;

  const limited = toLoad.slice(0, MAX_TOURS);
  limited.forEach(el => el.classList.add('is-loading'));

  await Promise.all(limited.map(async (el) => {
    const tourId = el.dataset.tourId;
    const href = el.getAttribute('href');
    const bookingParams = parseBookingParams(href);

    if (!tourId || !bookingParams) {
      el.classList.remove('is-loading');
      el.textContent = 'По запросу';
      el.classList.add('price-unavailable');
      return;
    }

    const minPrice = await fetchTourMinPrice(bookingParams);
    el.classList.remove('is-loading');

    if (minPrice !== null) {
      const displayPrice = Math.round(minPrice / 2);
      const formatted = new Intl.NumberFormat('ru-RU').format(displayPrice);
      el.textContent = `${formatted} ₽ / чел`;
      el.classList.add('price-loaded');
      el.setAttribute('data-price-loaded', '');
      savePriceToCache(tourId, displayPrice);
    } else {
      el.textContent = 'По запросу';
      el.classList.add('price-unavailable');
    }
  }));

  toLoad.slice(MAX_TOURS).forEach(el => {
    el.textContent = 'По запросу';
    el.classList.add('price-unavailable');
  });
};

/**
 * Форматировать цену для отображения
 */
export const formatPrice = (priceData) => {
  if (!priceData) return '';
  const prefix = priceData.show_from ? 'от ' : '';
  return `${prefix}${priceData.price_formatted} ${priceData.currency}`;
};
