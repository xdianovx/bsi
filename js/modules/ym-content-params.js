/**
 * Параметры визита для Яндекс.Метрики на карточках контента.
 *
 * Цели показывают, на что оставили заявку. Чтобы видеть, что просто смотрели —
 * какие туры, экскурсии и места вообще открывают, — на single-страницах
 * отправляем параметры визита. В отчёте «Параметры визита» появляется дерево
 * content → тип → страна → название, рядом с ним считается конверсия в заявку.
 *
 * Разметка: <body data-ym-type="sight" data-ym-title="…" data-ym-country="…">
 * (см. bsi_ym_content_params_attrs() в inc/seo.php).
 */

const YM_ID = 108341897;

export function initYmContentParams() {
  const el = document.querySelector("[data-ym-type]");
  if (!el || typeof window.ym !== "function") return;

  const type = el.dataset.ymType;
  const title = el.dataset.ymTitle;
  if (!type || !title) return;

  const country = el.dataset.ymCountry || "Без страны";

  /* Вложенная структура, а не плоские ключи: Метрика строит по ней дерево
     с раскрытием тип → страна → название. */
  window.ym(YM_ID, "params", {
    content: {
      [type]: {
        [country]: title,
      },
    },
  });
}
