/**
 * Фильтры каталога отелей: отбор без перезагрузки страницы.
 *
 * Форма настоящая и работает обычным GET — JS только перехватывает отправку,
 * складывает выбранное в адрес и просит у сервера новый список. Поэтому ссылкой
 * с фильтрами можно поделиться, а «назад» возвращает прежний отбор.
 */

/** Собирает адрес каталога из формы: наборы через запятую, пустое опускаем. */
export const filtersUrl = (form) => {
  const data = new FormData(form);
  const params = new URLSearchParams();

  const city = data.getAll("city[]").filter(Boolean);
  if (city.length) params.set("city", city[0]);

  const stars = data.getAll("stars[]").filter(Boolean);
  if (stars.length) params.set("stars", stars.join(","));

  const amenities = data.getAll("amenities[]").filter(Boolean);
  if (amenities.length) params.set("amenities", amenities.join(","));

  const types = data.getAll("type[]").filter(Boolean);
  if (types.length) params.set("type", types.join(","));

  ["beach_line", "q"].forEach((name) => {
    const value = (data.get(name) || "").toString().trim();
    if (value) params.set(name, value);
  });

  ["has_photo", "has_prices", "adults_only"].forEach((name) => {
    if (data.get(name)) params.set(name, "1");
  });

  const sort = data.get("sort");
  if (sort && sort !== "name-asc") params.set("sort", sort.toString());

  const query = params.toString();

  // Отбор всегда начинается с первой страницы.
  return form.getAttribute("action") + (query ? `?${query}` : "");
};

/* Галочки ставят сериями, а в поиске печатают — ждём паузу, чтобы уходил
   один запрос вместо очереди. Для набора текста пауза длиннее. */
const CLICK_DELAY = 400;
const TYPE_DELAY = 700;

/**
 * Отбор курортов по подстроке прямо в панели: все курорты уже в разметке,
 * спрашивать сервер не о чем.
 *
 * Пока в поле что-то набрано, хвост под «Ещё курорты» раскрыт и сама шторка
 * спрятана — иначе половина совпадений осталась бы за ней.
 */
const filterResorts = (input) => {
  const group = input.closest(".js-hotels-resorts");
  if (!group) return;

  const query = input.value.trim().toLowerCase();
  const tail = group.querySelector(".hotels-filters__more");
  let found = 0;

  group.querySelectorAll(".hotels-filters__resort").forEach((link) => {
    // «Все курорты» — сброс, а не курорт: в отборе не участвует.
    const reset = !link.querySelector("i");
    const name = (link.textContent || "").trim().toLowerCase();
    const hit = query === "" || name.includes(query);

    link.hidden = query !== "" && (reset || !hit);
    if (hit && !reset) found += 1;
  });

  if (tail) {
    tail.open = query !== "" ? true : tail.open;
    tail.classList.toggle("is-searching", query !== "");
  }

  const empty = group.querySelector(".js-hotels-resorts-empty");
  if (empty) empty.hidden = !(query !== "" && found === 0);
};

/**
 * @param {(href: string) => Promise<boolean>} load подмена каталога из hotels-api-catalog.js
 */
export const initHotelsFilters = (root, load) => {
  let timer = null;

  /**
   * @param {boolean} [partial] менять только выдачу, не трогая панель фильтров.
   *   Нужно набору в поиске: иначе поле пересоздаётся на каждой паузе и
   *   каретка уезжает в начало.
   */
  const apply = (form, partial = false) => {
    const href = filtersUrl(form);

    load(href, true, false, partial).then((ok) => {
      if (!ok) window.location.href = href;
    });
  };

  const applyLater = (form, delay, partial = false) => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => apply(form, partial), delay);
  };

  root.addEventListener("submit", (event) => {
    const form = event.target.closest(".js-hotels-filters");
    if (!form) return;

    event.preventDefault();
    window.clearTimeout(timer);
    document.body.classList.remove("is-hotels-filters-open");
    apply(form);
  });

  // Любая галочка сразу меняет выдачу — кнопка «Показать» не нужна.
  root.addEventListener("change", (event) => {
    const form = event.target.closest(".js-hotels-filters");
    if (!form || !event.target.matches("input, select")) return;

    /* Курорт остаётся один: хаб отбирает по одному городу, и выбор второго
       снимает прежний. Галочки — потому что так привычнее в фильтрах. */
    if (event.target.classList.contains("js-hotels-city") && event.target.checked) {
      form.querySelectorAll(".js-hotels-city").forEach((input) => {
        if (input !== event.target) input.checked = false;
      });
    }

    applyLater(form, CLICK_DELAY);
  });

  root.addEventListener("input", (event) => {
    // Поиск по курортам отбирает готовый список на месте, выдачу не трогает.
    if (event.target.classList.contains("js-hotels-resort-find")) {
      filterResorts(event.target);
      return;
    }

    const form = event.target.closest(".js-hotels-filters");
    if (!form || event.target.type !== "search") return;

    applyLater(form, TYPE_DELAY, true);
  });

  root.addEventListener("click", (event) => {
    if (event.target.closest(".js-hotels-filters-toggle")) {
      root.querySelector(".js-hotels-filters-panel")?.classList.toggle("is-open");
      document.body.classList.toggle("is-hotels-filters-open");
      return;
    }

    if (event.target.closest(".js-hotels-filters-close")) {
      root.querySelector(".js-hotels-filters-panel")?.classList.remove("is-open");
      document.body.classList.remove("is-hotels-filters-open");
    }
  });
};

/** Полноэкранная карта на узком экране. */
export const initHotelsMapToggle = (root) => {
  root.addEventListener("click", (event) => {
    const button = event.target.closest(".js-hotels-map-open");
    if (!button) return;

    const open = document.body.classList.toggle("is-hotels-map-open");
    button.textContent = open ? "Показать список" : "На карте";

    // Карта, нарисованная в скрытом блоке, не знает своих размеров.
    window.dispatchEvent(new Event("resize"));
  });
};
