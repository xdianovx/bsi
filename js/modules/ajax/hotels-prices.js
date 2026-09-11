/**
 * Догрузка цен каталога отелей.
 *
 * Хаб помечает отель `prices_status: updating`, пока тот стоит в очереди на
 * обновление цен. Очередь одна, отель занимает 5-10 секунд, и при длинной
 * очереди своей цены он может ждать пару минут — поэтому опрашиваем долго, но
 * дёшево: эндпоинт отдаёт цены только названных отелей.
 *
 * Ждём только `updating`. `stale` значит «цены старые и никто их не грузит» —
 * такие карточки показывают то, что есть, и в опрос не попадают.
 */

/* Та же иконка, что рисует bsi_hotel_view_badge('limited', …) на сервере. */
const ZAP_ICON =
  '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>';

const INTERVAL = 4000;
const LIMIT_MS = 3 * 60 * 1000;

export const initHotelsPrices = () => {
  const root = document.querySelector("[data-hotels-prices]");
  if (!root) {
    return;
  }

  /** Карточки, которые ждут обновления цен: хаб держит их в очереди. */
  const waiting = () => [...root.querySelectorAll('.api-row[data-prices-status="updating"]')];

  /** Карточки без цены: у части из них цена есть в прайсе оператора. */
  const priceless = () => [...root.querySelectorAll(".api-row")].filter(
    (card) => card.querySelector(".api-row__price-empty") && !card.dataset.priceAsked,
  );

  if (!waiting().length && !priceless().length) {
    return;
  }

  const ajaxUrl = window.ajax?.url || window.ajaxurl || "/wp-admin/admin-ajax.php";
  const startedAt = Date.now();
  let timer = null;

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

  /**
   * Цены к продаже нет, но она есть в прайсе: оператор держит стоп-продажу на
   * прогретое окно. Показываем её с меткой — за окном места обычно находятся.
   */
  const showPriceList = (card, price) => {
    const slot = card.querySelector(".api-row__price-empty");
    if (!slot) {
      return;
    }

    const value = document.createElement("span");
    value.className = "api-row__price";
    value.textContent = `от ${price}`;

    const badge = document.createElement("span");
    badge.className = "hp-badge hp-badge--limited api-row__price-badge";
    badge.title = "На ближайшие даты мест нет. Выберите даты — проверим наличие у оператора";
    badge.innerHTML = ZAP_ICON;
    badge.append("Мало мест");

    slot.replaceWith(value, badge);
  };

  /** Ждать больше нечего, а цены нет: подпись уже стоит, снимаем только пометку. */
  const showEmpty = (card) => {
    const label = card.querySelector(".api-row__price-label");
    if (label) {
      label.textContent = priceLabel(false);
    }
  };

  const stop = () => {
    if (timer) {
      clearTimeout(timer);
      timer = null;
    }

    waiting().forEach((card) => {
      setStatus(card, "stale");
      showEmpty(card);
    });
  };

  const ask = async () => {
    /* Спрашиваем и тех, кто в очереди, и тех, у кого цены нет совсем: у части
       вторых она лежит в прайсе оператора. Вторых спрашиваем один раз —
       прайс не меняется, пока хаб не пересчитает отель. */
    const pending = priceless();
    pending.forEach((card) => {
      card.dataset.priceAsked = "1";
    });

    const cards = [...new Set([...waiting(), ...pending])];
    if (!cards.length) {
      return;
    }

    const body = new URLSearchParams();
    body.set("action", "bsi_hotels_api_prices");
    if (root.dataset.instant) {
      body.set("instant", "1");
    }
    cards.forEach((card) => body.append("id[]", card.dataset.hotel || ""));

    let payload = null;
    try {
      const response = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body,
        credentials: "same-origin",
      });
      payload = await response.json();
    } catch (error) {
      payload = null;
    }

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
      } else if (entry.priceList) {
        showPriceList(card, entry.priceList);
      }

      if (!entry.waiting) {
        setStatus(card, entry.status);
        if (!entry.price && !entry.priceList) {
          showEmpty(card);
        }
      }
    });

    if (!waiting().length) {
      return;
    }

    if (Date.now() - startedAt > LIMIT_MS) {
      stop();
      return;
    }

    timer = setTimeout(ask, INTERVAL);
  };

  /* Первый запрос сразу: карточки без цены ждут прайс, и держать их пустыми
     лишние секунды незачем. Дальше — по интервалу. */
  ask();

  /* Вкладку свернули — хаб не дёргаем: цены всё равно никто не смотрит. */
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
      return;
    }

    if (!timer && waiting().length && Date.now() - startedAt < LIMIT_MS) {
      timer = setTimeout(ask, INTERVAL);
    }
  });
};
