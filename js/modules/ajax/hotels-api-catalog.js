/**
 * Каталог отелей страны из хаба: смена страницы и курорта без перезагрузки.
 *
 * Ссылки в разметке настоящие — поисковик и переход по прямому адресу работают
 * как раньше. JS перехватывает клик, подменяет содержимое каталога и правит
 * адрес через history, поэтому «назад» в браузере тоже возвращает список.
 *
 * Обработчик — inc/requests/ajax-hotels-api-catalog.php (action bsi_hotels_api_catalog).
 */

/** Страница и курорт из адреса каталога: /country/{c}/hotel/kurort/{r}/page/2/ */
const parseUrl = (href, baseUrl) => {
  const path = new URL(href, window.location.origin).pathname;
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

  return { resort, page };
};

export const initHotelsApiCatalog = () => {
  const root = document.querySelector(".js-hotels-catalog");
  if (!root || typeof ajax === "undefined") return;

  const countryId = root.dataset.country;
  const baseUrl = root.dataset.url;
  let request = 0;

  const load = async (href, push) => {
    const target = parseUrl(href, baseUrl);
    if (!target) return false;

    const ticket = ++request;
    root.classList.add("is-loading");

    try {
      const body = new URLSearchParams({
        action: "bsi_hotels_api_catalog",
        country_id: countryId,
        page: String(target.page),
        resort: target.resort,
      });

      const response = await fetch(ajax.url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await response.json();

      // Пока ждали ответ, пользователь мог кликнуть дальше — старый ответ не нужен.
      if (ticket !== request) return true;

      if (!json?.success || !json.data?.html) {
        throw new Error("empty response");
      }

      root.innerHTML = json.data.html;

      if (push) {
        window.history.pushState({ hotelsCatalog: true }, "", href);
      }

      const top = root.getBoundingClientRect().top + window.scrollY - 100;
      window.scrollTo({ top, behavior: "smooth" });

      return true;
    } catch (error) {
      // Не смогли подгрузить — уходим по ссылке обычным переходом.
      return false;
    } finally {
      if (ticket === request) root.classList.remove("is-loading");
    }
  };

  root.addEventListener("click", (event) => {
    const link = event.target.closest(".country-hotels__pagination a, .country-resorts-filter a, .country-resorts-filter__item");

    if (!link || !link.href || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
      return;
    }

    if (!parseUrl(link.href, baseUrl)) return;

    event.preventDefault();
    load(link.href, true).then((ok) => {
      if (!ok) window.location.href = link.href;
    });
  });

  window.addEventListener("popstate", () => {
    load(window.location.href, false);
  });
};
