import { initHotelsMap } from "../hotels-map";
import { initHotelsFilters, initHotelsMapToggle } from "./hotels-filters";

/**
 * Панель фильтров приезжает с сервера заново, поэтому перед подменой запоминаем,
 * что было раскрыто и куда прокручено: иначе выбор галочки схлопывает открытый
 * список курортов и отматывает панель наверх.
 */
const keepPanelState = (root) => {
  const panel = root.querySelector(".js-hotels-filters-panel");

  const state = {
    open: [...root.querySelectorAll(".hotels-filters__more[data-key]")]
      .filter((details) => details.open)
      .map((details) => details.dataset.key),
    scroll: panel ? panel.scrollTop : 0,
    focus: document.activeElement?.closest(".js-hotels-filters") ? document.activeElement.name : "",
  };

  return () => {
    state.open.forEach((key) => {
      const details = root.querySelector(`.hotels-filters__more[data-key="${key}"]`);
      if (details) details.open = true;
    });

    const next = root.querySelector(".js-hotels-filters-panel");
    if (next) next.scrollTop = state.scroll;

    if (state.focus) {
      root.querySelector(`.js-hotels-filters [name="${state.focus}"]`)?.focus();
    }
  };
};

/**
 * Подменяет только результаты: список, счётчик, пагинацию и карту.
 *
 * Панель фильтров остаётся прежней — вместе с фокусом и введённым текстом.
 * Возвращает false, если разметки не хватило: тогда зовущий перерисует всё.
 */
const RESULT_PARTS = [
  ".js-hotels-rows",
  ".hotels-catalog__counter",
  ".hotels-catalog__more",
  ".hotels-catalog__empty",
  ".js-hotels-map-data",
];

const swapResults = (root, html) => {
  const parsed = new DOMParser().parseFromString(html, "text/html");
  const list = parsed.querySelector(".js-hotels-rows");
  const current = root.querySelector(".js-hotels-rows");

  if (!list || !current) return false;

  RESULT_PARTS.forEach((selector) => {
    const next = parsed.querySelector(selector);
    const node = root.querySelector(selector);

    if (node && next) {
      node.replaceWith(next.cloneNode(true));
      return;
    }

    // Блока больше нет в ответе (кончились страницы, ушла заглушка) — убираем.
    if (node && !next) {
      node.remove();
    }
  });

  return true;
};

/**
 * Каталог отелей страны из хаба: смена страницы и курорта без перезагрузки.
 *
 * Ссылки в разметке настоящие — поисковик и переход по прямому адресу работают
 * как раньше. JS перехватывает клик, подменяет содержимое каталога и правит
 * адрес через history, поэтому «назад» в браузере тоже возвращает список.
 *
 * Обработчик — inc/requests/ajax-hotels-api-catalog.php (action bsi_hotels_api_catalog).
 */

/** Страница, курорт и фильтры из адреса каталога: /country/{c}/hotel/kurort/{r}/page/2/?stars=5 */
const parseUrl = (href, baseUrl) => {
  const url = new URL(href, window.location.origin);
  const path = url.pathname;
  const base = new URL(baseUrl, window.location.origin).pathname;

  if (!path.startsWith(base)) return null;

  const rest = path.slice(base.length).replace(/^\/|\/$/g, "");
  const parts = rest ? rest.split("/") : [];

  let resort = "";
  let page = 1;

  for (let i = 0; i < parts.length; i += 1) {
    if (parts[i] === "kurort" && parts[i + 1]) {
      resort = parts[i + 1];
      i += 1;
    } else if (parts[i] === "page" && parts[i + 1]) {
      page = parseInt(parts[i + 1], 10) || 1;
      i += 1;
    }
  }

  return { resort, page, query: url.search.replace(/^\?/, "") };
};

export const initHotelsApiCatalog = () => {
  const root = document.querySelector(".js-hotels-catalog");
  if (!root || typeof ajax === "undefined") return;

  const countryId = root.dataset.country;
  const baseUrl = root.dataset.url;
  let request = 0;

  /** Запрос каталога к серверу; страницу, курорт и фильтры передаём явно. */
  const fetchCatalog = async (page, resort, query = "") => {
    const body = new URLSearchParams({
      action: "bsi_hotels_api_catalog",
      country_id: countryId,
      page: String(page),
      resort: resort || "",
      filters: query,
    });

    const response = await fetch(ajax.url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: body.toString(),
      credentials: "same-origin",
    });

    return response.json();
  };

  // Сколько раз переспрашиваем каталог и с какой паузой между подходами.
  const RETRY_LIMIT = 8;
  const RETRY_PAUSE = 3000;

  const giveUp = (pending) => {
    pending.innerHTML =
      '<p class="country-hotels__message">Каталог отелей сейчас недоступен. ' +
      "Подберём отель по запросу — напишите нам.</p>";
  };

  /**
   * Хаб не успел ответить при рендере страницы — вместо каталога стоят заглушки.
   * Спрашиваем снова: сервер отвечает `pending`, пока хаб греет выдачу, и одним
   * длинным запросом это не переждать — веб-сервер рвёт соединение.
   */
  const retryPending = async (attempt = 1) => {
    const pending = root.querySelector(".js-hotels-retry");
    if (!pending) return;

    try {
      const json = await fetchCatalog(
        pending.dataset.page || 1,
        pending.dataset.resort,
        window.location.search.replace(/^\?/, ""),
      );

      if (json?.success && json.data?.html) {
        const restore = keepPanelState(root);
        root.innerHTML = json.data.html;
        restore();
        initHotelsMap();
        return;
      }

      if (json?.success && json.data?.pending && attempt < RETRY_LIMIT) {
        window.setTimeout(() => retryPending(attempt + 1), RETRY_PAUSE);
        return;
      }

      giveUp(pending);
    } catch (error) {
      if (attempt < RETRY_LIMIT) {
        window.setTimeout(() => retryPending(attempt + 1), RETRY_PAUSE);
        return;
      }

      giveUp(pending);
    }
  };

  /**
   * @param {boolean} [scroll] подвести список под верх окна. Нужно переходу по
   *   страницам, где новые карточки начинаются выше экрана. Смена фильтров
   *   этого не делает: панель и карта должны остаться там, где на них смотрят.
   */
  const load = async (href, push, scroll = false, partial = false) => {
    const target = parseUrl(href, baseUrl);
    if (!target) return false;

    const ticket = ++request;

    /* Гасим только список карточек: фильтры остаются живыми — по ним
       продолжают кликать, пока грузится выдача. */
    const rows = root.querySelector(".js-hotels-rows") || root;
    rows.classList.add("is-loading");

    try {
      const json = await fetchCatalog(target.page, target.resort, target.query);

      // Пока ждали ответ, пользователь мог кликнуть дальше — старый ответ не нужен.
      if (ticket !== request) return true;

      if (!json?.success || !json.data?.html) {
        throw new Error("empty response");
      }

      /* Набор в поиске меняет только выдачу. Перерисовывать вместе с ней
         панель фильтров нельзя: поле пересоздаётся, каретка прыгает в начало,
         и продолжать печатать невозможно. */
      if (partial && swapResults(root, json.data.html)) {
        initHotelsMap();
      } else {
        const restore = keepPanelState(root);
        root.innerHTML = json.data.html;
        restore();
        initHotelsMap();
      }

      // Сервер вернул заглушки — доспрашиваем каталог тем же путём.
      if (root.querySelector(".js-hotels-retry")) {
        retryPending();
      }

      if (push) {
        window.history.pushState({ hotelsCatalog: true }, "", href);
      }

      if (scroll) {
        const top = root.getBoundingClientRect().top + window.scrollY - 100;
        window.scrollTo({ top, behavior: "smooth" });
      }

      return true;
    } catch (error) {
      // Не смогли подгрузить — уходим по ссылке обычным переходом.
      return false;
    } finally {
      if (ticket === request) rows.classList.remove("is-loading");
    }
  };

  /**
   * Следующая страница добавляется к показанным, а не заменяет их: так работает
   * и кнопка «Показать ещё», и автоподгрузка при подходе к концу списка.
   */
  const appendNext = async () => {
    const more = root.querySelector(".js-hotels-more");
    if (!more || more.classList.contains("is-loading")) return;

    /* Следующая порция берётся по номеру страницы: постраничных адресов у
       каталога нет, и в истории остаётся один URL. */
    const page = Number(more.dataset.nextPage || 0);
    const current = parseUrl(window.location.href, baseUrl);
    if (!page || !current) return;

    more.classList.add("is-loading");

    try {
      const json = await fetchCatalog(page, current.resort, current.query);
      if (!json?.success || !json.data?.html) throw new Error("empty response");

      const parsed = new DOMParser().parseFromString(json.data.html, "text/html");
      const rows = parsed.querySelector(".js-hotels-rows");
      const list = root.querySelector(".js-hotels-rows");

      if (!rows || !list) throw new Error("no rows");

      list.append(...rows.children);

      // Кнопку заменяем на пришедшую: в ней номер следующей порции.
      const nextMore = parsed.querySelector(".js-hotels-more");
      if (nextMore) {
        more.replaceWith(nextMore);
      } else {
        more.remove();
      }
    } catch (error) {
      more.classList.remove("is-loading");
    }
  };

  // Автоподгрузка: следим за кнопкой, она стоит в конце списка.
  const watcher = new IntersectionObserver(
    (entries) => {
      if (entries.some((entry) => entry.isIntersecting)) appendNext();
    },
    { rootMargin: "400px" },
  );

  const watchMore = () => {
    const more = root.querySelector(".js-hotels-more");
    if (more) watcher.observe(more);
  };

  new MutationObserver(watchMore).observe(root, { childList: true, subtree: true });
  watchMore();

  root.addEventListener("click", (event) => {
    const more = event.target.closest(".hotels-catalog__more-btn");
    if (more) {
      event.preventDefault();
      appendNext();
      return;
    }

    const link = event.target.closest(".country-hotels__pagination a, .country-resorts-filter a, .country-resorts-filter__item");

    if (!link || !link.href || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
      return;
    }

    if (!parseUrl(link.href, baseUrl)) return;

    event.preventDefault();
    load(link.href, true, true).then((ok) => {
      if (!ok) window.location.href = link.href;
    });
  });

  window.addEventListener("popstate", () => {
    load(window.location.href, false);
  });

  initHotelsFilters(root, load);
  initHotelsMapToggle(root.parentElement || document.body);

  retryPending();
};
