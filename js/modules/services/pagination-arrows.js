/**
 * Стрелки пагинации для каталогов, которые рисуют её на клиенте.
 *
 * Разметка совпадает с bsi_pagination_arrow() (inc/helpers/pagination.php):
 * шеврон Lucide + подпись. Стили — scss/ui/pagination.scss.
 */

const chevron = (direction) =>
  '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" ' +
  'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ' +
  'stroke-linejoin="round" class="ui-pagination__icon" aria-hidden="true" focusable="false">' +
  (direction === "prev" ? '<path d="m15 18-6-6 6-6"/>' : '<path d="m9 18 6-6-6-6"/>') +
  "</svg>";

export const PAGINATION_PREV = chevron("prev") + '<span class="ui-pagination__label">Назад</span>';

export const PAGINATION_NEXT = '<span class="ui-pagination__label">Вперёд</span>' + chevron("next");
