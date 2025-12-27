// modules/currency.js
import { APIService } from "./api-service";
import { dropdown } from "./forms/dropdown.js";

const currencySymbols = {
  RUB: "₽",
  USD: "$",
  EUR: "€",
  GBP: "£",
  CHF: "Fr",
  CNY: "¥",
  JPY: "¥",
  KZT: "₸",
  BYN: "Br",
};

let currencyMap = null;
let currentRates = { USD: null, EUR: null };
let baseISO = "RUB";
let baseCurrencyId = 1;
let dropdownInstance = null;

function formatRate(num) {
  return num.toFixed(2).replace(".", ",");
}

function getCurrencySymbol(iso) {
  return currencySymbols[iso] || "";
}

function getStoredCurrency() {
  try {
    return localStorage.getItem("selectedCurrency") || "RUB";
  } catch {
    return "RUB";
  }
}

function setStoredCurrency(iso) {
  try {
    localStorage.setItem("selectedCurrency", iso);
  } catch {
    // Ignore localStorage errors
  }
}

function updateHeaderPrices() {
  const items = document.querySelectorAll(".header__currencies .currency-item");
  items.forEach((item) => {
    const codeEl = item.querySelector(".currency-item__title");
    const valueEl = item.querySelector(".currency-item__value");
    if (!codeEl || !valueEl) return;
    const code = codeEl.textContent.trim();
    const rate = currentRates[code];
    if (rate !== null && rate !== undefined) {
      valueEl.textContent = formatRate(rate);
    }
  });
}

function populateCurrencyDropdown(currencies) {
  const panel = document.querySelector(".currency-select .js-dropdown-panel");
  if (!panel) return;

  const allowedCurrencies = ["RUB", "GBP", "CHF", "CNY", "JPY", "KZT", "BYN"];

  panel.innerHTML = "";

  currencies
    .filter((currency) => allowedCurrencies.includes(currency.currencyISO))
    .forEach((currency) => {
      const iso = currency.currencyISO;
      const symbol = getCurrencySymbol(iso);
      const button = document.createElement("button");
      button.className = "currency-option";
      button.setAttribute("data-iso", iso);
      button.textContent = `${iso} ${symbol}`;
      panel.appendChild(button);
    });
}

async function loadCurrencyRates(baseISO) {
  if (!currencyMap) return;

  const baseId = currencyMap[baseISO]?.id;
  if (!baseId) return;

  const usdId = currencyMap["USD"]?.id;
  const eurId = currencyMap["EUR"]?.id;

  if (!usdId || !eurId) return;

  try {
    const resp = await APIService.getCurrencyRates([eurId, usdId], baseId);
    const rates = resp.Currency_RATES || [];

    rates.forEach((rateItem) => {
      const iso = rateItem.currencyISO;
      if (iso === "USD" || iso === "EUR") {
        const rate = parseFloat(rateItem.rate);
        if (!isNaN(rate)) {
          currentRates[iso] = rate;
        }
      }
    });

    updateHeaderPrices();
  } catch (err) {
    // Error handling without console output
  }
}

export async function initCurrency() {
  const select = document.querySelector(".currency-select");
  if (!select) return;
  const currentEl = select.querySelector(".currency-current");
  const panel = select.querySelector(".js-dropdown-panel");
  if (!currentEl || !panel) return;

  try {
    const currenciesResp = await APIService.getCurrencyCurrencies();
    const currencies = currenciesResp.Currency_CURRENCIES || [];

    if (currencies.length === 0) return;

    currencyMap = {};
    currencies.forEach((currency) => {
      currencyMap[currency.currencyISO] = {
        id: currency.id,
        name: currency.name,
        iso: currency.currencyISO,
      };
    });

    populateCurrencyDropdown(currencies);

    baseISO = getStoredCurrency();
    if (!currencyMap[baseISO]) {
      baseISO = "RUB";
    }
    baseCurrencyId = currencyMap[baseISO]?.id || 1;
    currentEl.textContent = baseISO;

    await loadCurrencyRates(baseISO);

    dropdownInstance = dropdown(".currency-select");

    const options = panel.querySelectorAll(".currency-option");
    options.forEach((btn) => {
      btn.addEventListener("click", async (e) => {
        e.preventDefault();
        const iso = btn.getAttribute("data-iso");
        if (!iso || !currencyMap[iso]) return;

        baseISO = iso;
        baseCurrencyId = currencyMap[iso].id;
        currentEl.textContent = iso;
        setStoredCurrency(iso);

        if (dropdownInstance) {
          dropdownInstance.close();
        }

        await loadCurrencyRates(baseISO);
      });
    });
  } catch (err) {
    // Error handling without console output
  }
}
