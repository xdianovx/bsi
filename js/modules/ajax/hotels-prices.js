/**
 * Догрузка цен каталога отелей.
 *
 * Хаб помечает отель `prices_status: updating`, пока тот стоит в очереди на
 * обновление цен. Очередь одна, отель занимает 5-10 секунд, и при длинной
 * очереди своей цены он может ждать пару минут — поэтому опрашиваем долго и
 * часто, но дёшево: эндпоинт отдаёт цены только названных отелей.
 *
 * Ждём только `updating`. `stale` значит «цены старые и никто их не грузит» —
 * такие карточки показывают то, что есть, и в опрос не попадают.
 */

/* Полсекунды: запрос отдаёт цены только названных отелей и стоит хабу
   доли миллисекунды, зато цена встаёт в карточку сразу, как хаб её посчитал. */
const INTERVAL = 500;
const LIMIT_MS = 3 * 60 * 1000;

/**
 * Опрос один на страницу. Каталог перерисовывает карточки — догрузка по
 * скроллу, фильтры, курорты, — и после каждой перерисовки initHotelsPrices()
 * зовут заново: она подхватывает новые ждущие карточки и продлевает лимит.
 */
let timer = null;
let asking = false;
let inflight = false;
let startedAt = 0;
let listening = false;

/** Карточки, которые ждут обновления цен: хаб держит их в очереди. */
const waiting = () => [
  ...document.querySelectorAll('[data-hotels-prices] .api-row[data-prices-status="updating"]'),
];

const ajaxUrl = () => window.ajax?.url || window.ajaxurl || "/wp-admin/admin-ajax.php";

const setStatus = (card, status) => {
  card.dataset.pricesStatus = status || "";
};

/**
 * Цена приехала: на месте крутилки — сумма и подпись «за ночь».
 *
 * Пока отель остаётся в очереди, при цене держим пометку «уточняется»:
 * снять её раньше времени значит выдать промежуточную цену за окончательную.
 */
const priceLabel = (waiting) => (waiting ? "за ночь · уточняется" : "за ночь");

const showPrice = (card, price, waiting) => {
  /* Карточка без цены показывает «По запросу»: пока хаб держит очередь на
     всю базу, ждущий спиннер врал бы про скорость. Приехавшая цена встаёт
     на место этой подписи. */
  const slot = card.querySelector(".api-row__price-loading, .api-row__price-empty");

  if (slot) {
    const value = document.createElement("span");
    value.className = "api-row__price";
    value.textContent = `от ${price}`;

    const label = document.createElement("span");
    label.className = "api-row__price-label";
    label.textContent = priceLabel(waiting);

    slot.replaceWith(value, label);
    return;
  }

  const value = card.querySelector(".api-row__price");
  const label = card.querySelector(".api-row__price-label");

  if (value) {
    value.textContent = `от ${price}`;
  }
  if (label) {
    label.textContent = priceLabel(waiting);
  }
};

/** Ждать больше нечего, а цены нет: крутилка уступает место подписи. */
const showEmpty = (card) => {
  const label = card.querySelector(".api-row__price-label");
  if (label) {
    label.textContent = priceLabel(false);
  }

  const slot = card.querySelector(".api-row__price-loading");
  if (!slot) {
    return;
  }

  const empty = document.createElement("span");
  empty.className = "api-row__price-empty";
  empty.textContent = "По запросу";

  slot.replaceWith(empty);
};

const stop = () => {
  if (timer) {
    clearTimeout(timer);
    timer = null;
  }
  asking = false;

  waiting().forEach((card) => {
    setStatus(card, "stale");
    showEmpty(card);
  });
};

const ask = async () => {
  timer = null;

  const cards = waiting();
  if (!cards.length) {
    asking = false;
    return;
  }

  const body = new URLSearchParams();
  body.set("action", "bsi_hotels_api_prices");
  if (document.querySelector("[data-hotels-prices]")?.dataset.instant) {
    body.set("instant", "1");
  }
  cards.forEach((card) => body.append("id[]", card.dataset.hotel || ""));

  let payload = null;
  inflight = true;
  try {
    const response = await fetch(ajaxUrl(), {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body,
      credentials: "same-origin",
    });
    payload = await response.json();
  } catch (error) {
    payload = null;
  }
  inflight = false;

  if (!payload?.success) {
    stop();
    return;
  }

  const prices = payload.data.prices || {};

  cards.forEach((card) => {
    const entry = prices[String(card.dataset.hotel || "")];
    if (!entry) {
      return;
    }

    if (entry.price) {
      showPrice(card, entry.price, entry.waiting);
    }

    if (!entry.waiting) {
      setStatus(card, entry.status);
      if (!entry.price) {
        showEmpty(card);
      }
    }
  });

  if (!waiting().length) {
    asking = false;
    return;
  }

  if (Date.now() - startedAt > LIMIT_MS) {
    stop();
    return;
  }

  // Свернули вкладку, пока ждали ответ, — продолжим, когда вернутся.
  if (document.hidden) {
    return;
  }

  timer = setTimeout(ask, INTERVAL);
};

/* Вкладку свернули — хаб не дёргаем: цены всё равно никто не смотрит. */
const onVisibility = () => {
  if (document.hidden) {
    if (timer) {
      clearTimeout(timer);
      timer = null;
    }
    return;
  }

  // Лимит и пустую очередь проверит сам ask — иначе опрос повиснет в «asking».
  if (asking && !timer && !inflight) {
    timer = setTimeout(ask, INTERVAL);
  }
};

export const initHotelsPrices = () => {
  if (!waiting().length) {
    return;
  }

  startedAt = Date.now();

  if (!listening) {
    document.addEventListener("visibilitychange", onVisibility);
    listening = true;
  }

  // Опрос уже идёт — новые карточки он заберёт следующим запросом.
  if (asking) {
    return;
  }

  asking = true;
  timer = setTimeout(ask, INTERVAL);
};
