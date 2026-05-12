import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";

export function initEventSinglePage() {
  const host = document.querySelector("[data-event-calendar]");
  if (!host) return;

  let dates = [];
  try {
    dates = JSON.parse(host.getAttribute("data-dates") || "[]");
  } catch (_e) {
    return;
  }
  if (!Array.isArray(dates) || dates.length === 0) return;

  dates = [...new Set(dates.map(String))].sort();

  flatpickr(host, {
    inline: true,
    locale: Russian,
    dateFormat: "d.m.Y",
    enable: dates,
    clickOpens: false,
    showMonths: window.innerWidth < 768 ? 1 : 2,
    defaultDate: dates[0],
  });
}
