// modules/currency.js
import { APIService } from "./api-service";

let ratesRUB = null; // карта курсов: { RUB:1, USD:79.39, EUR:92.09 }
let baseISO = "RUB";

function formatRate(num) {
  return num.toFixed(2).replace(".", ",");
}

function updateHeaderPrices() {
  if (!ratesRUB) return;

  const items = document.querySelectorAll(".header__currencies .currency-item");
  items.forEach((item) => {
    const codeEl = item.querySelector(".currency-item__title");
    const valueEl = item.querySelector(".currency-item__value");
    if (!codeEl || !valueEl) return;
    const code = codeEl.textContent.trim(); // e.g. "USD"
    const rTo = ratesRUB[code];
    const rBase = ratesRUB[baseISO];
    if (!rTo || !rBase) return;
    const rate = code === baseISO ? 1 : rTo / rBase;
    valueEl.textContent = formatRate(rate);
  });
}

export async function initCurrency() {
  const select = document.querySelector(".currency-select");
  if (!select) return;
  const currentEl = select.querySelector(".currency-current");
  const options = select.querySelectorAll(".currency-option");
  if (!currentEl || options.length === 0) return;

  try {
    const resp = await APIService.getCurrencyRate();
    const rates = resp.Currency_TodayRates;
    const dateKey = Object.keys(rates)[0];
    const dayRates = rates[dateKey];
    ratesRUB = { RUB: 1 };
    Object.keys(dayRates).forEach((iso) => {
      const rate = parseFloat(dayRates[iso].rate);
      if (!isNaN(rate)) {
        ratesRUB[iso] = rate;
      }
    });

    baseISO = currentEl.textContent.trim() || "RUB";
    if (!ratesRUB[baseISO]) baseISO = "RUB";

    updateHeaderPrices();

    options.forEach((btn) => {
      btn.addEventListener("click", () => {
        const iso = btn.getAttribute("data-iso");
        if (!iso || !ratesRUB[iso]) return;
        baseISO = iso;
        currentEl.textContent = iso;
        updateHeaderPrices();
      });
    });
  } catch (err) {
    console.error("Currency init error:", err);
  }
}
