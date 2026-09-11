/**
 * Догрузка цен каталога отелей.
 *
 * Хаб помечает отель `prices_status: updating`, когда обновляет его цены —
 * такой отель приезжает в выдаче без цены, а через десяток-другой секунд цена
 * у него появляется. Страница к тому моменту уже отрисована, поэтому карточка
 * остаётся с «Считаем» до перезагрузки.
 *
 * Здесь мы переспрашиваем ту же страницу каталога (эндпоинт
 * `bsi_hotels_api_prices`) и подставляем цены на место крутилок. Опрос
 * прекращается, как только ждать больше нечего или кончились попытки:
 * бесконечно долбить хаб из каждой открытой вкладки нельзя.
 */

const INTERVAL = 8000;
const MAX_TRIES = 8;

export const initHotelsPrices = () => {
  const root = document.querySelector("[data-hotels-prices]");
  if (!root) {
    return;
  }

  const pending = () => [...root.querySelectorAll(".api-row__price-loading")];
  if (!pending().length) {
    return;
  }

  const ajaxUrl = window.ajax?.url || window.ajaxurl || "/wp-admin/admin-ajax.php";
  let tries = 0;
  let timer = null;

  /** Цена приехала: крутилка уступает место сумме и подписи «за ночь». */
  const showPrice = (slot, price) => {
    const value = document.createElement("span");
    value.className = "api-row__price";
    value.textContent = `от ${price}`;

    const label = document.createElement("span");
    label.className = "api-row__price-label";
    label.textContent = "за ночь";

    slot.replaceWith(value, label);
  };

  /** Ждать больше нечего — обычная подпись вместо крутилки. */
  const showEmpty = (slot) => {
    const empty = document.createElement("span");
    empty.className = "api-row__price-empty";
    empty.textContent = "Цену уточним по запросу";

    slot.replaceWith(empty);
  };

  const fill = (prices) => {
    pending().forEach((slot) => {
      const card = slot.closest(".api-row");
      const entry = prices[String(card?.dataset.hotel || "")];

      if (!entry) {
        return;
      }

      if (entry.price) {
        showPrice(slot, entry.price);
        return;
      }

      if (!entry.updating) {
        showEmpty(slot);
      }
    });
  };

  const stop = () => {
    if (timer) {
      clearTimeout(timer);
      timer = null;
    }

    /* Цена не приехала за отведённое время — не держим гостя крутилкой. */
    pending().forEach(showEmpty);
  };

  const ask = async () => {
    tries += 1;

    const body = new URLSearchParams({
      action: "bsi_hotels_api_prices",
      country: root.dataset.country || "",
      paged: root.dataset.paged || "1",
      resort: root.dataset.resort || "",
      filters: root.dataset.filters || "",
    });

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

    fill(payload.data.prices || {});

    if (!pending().length || tries >= MAX_TRIES) {
      stop();
      return;
    }

    timer = setTimeout(ask, INTERVAL);
  };

  timer = setTimeout(ask, INTERVAL);

  /* Вкладку свернули — хаб не дёргаем: цены всё равно никто не смотрит. */
  document.addEventListener("visibilitychange", () => {
    if (document.hidden && timer) {
      clearTimeout(timer);
      timer = null;
    } else if (!document.hidden && !timer && tries < MAX_TRIES && pending().length) {
      timer = setTimeout(ask, INTERVAL);
    }
  });
};
