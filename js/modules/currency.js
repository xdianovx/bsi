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

let ratesData = null;
let currentRates = { USD: null, EUR: null, RUB: null };
let baseISO = "RUB";
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
  }
}

function updateHeaderPrices() {
  const currenciesContainer = document.querySelector(".header__currencies");
  if (!currenciesContainer) return;

  const items = currenciesContainer.querySelectorAll(".currency-item");
  items.forEach((item) => {
    const codeEl = item.querySelector(".currency-item__title");
    const valueEl = item.querySelector(".currency-item__value");
    if (!codeEl || !valueEl) return;
    const code = codeEl.textContent.trim();
    const rate = currentRates[code];
    if (rate !== null && rate !== undefined) {
      valueEl.textContent = formatRate(rate);
    } else {
      valueEl.textContent = "";
    }
  });

  if (baseISO !== "RUB" && currentRates.RUB !== null) {
    let rubItem = currenciesContainer.querySelector('.currency-item[data-currency="RUB"]');
    if (!rubItem) {
      const selectElement = currenciesContainer.querySelector(".currency-select");
      if (selectElement) {
        rubItem = document.createElement("div");
        rubItem.className = "currency-item";
        rubItem.setAttribute("data-currency", "RUB");
        rubItem.innerHTML = `
          <div class="currency-item__title">RUB</div>
          <div class="currency-item__value numfont"></div>
        `;
        currenciesContainer.insertBefore(rubItem, selectElement);
      }
    }
    if (rubItem) {
      const valueEl = rubItem.querySelector(".currency-item__value");
      if (valueEl) {
        valueEl.textContent = formatRate(currentRates.RUB);
      }
    }
  } else {
    const rubItem = currenciesContainer.querySelector('.currency-item[data-currency="RUB"]');
    if (rubItem) {
      rubItem.remove();
    }
  }
}

function populateCurrencyDropdown(rates) {
  const panel = document.querySelector(".currency-select .js-dropdown-panel");
  if (!panel) return;

  const allowedCurrencies = ["RUB", "GBP", "CHF", "CNY", "JPY", "KZT", "BYN"];

  panel.innerHTML = "";

  allowedCurrencies.forEach((iso) => {
    if (iso === "RUB" || rates[iso]) {
      const button = document.createElement("button");
      button.className = "currency-option";
      button.setAttribute("data-iso", iso);
      button.textContent = iso;
      panel.appendChild(button);
    }
  });
}

function calculateRateInBaseCurrency(baseISO, targetISO) {
  if (!ratesData || !ratesData.rates) return null;

  if (baseISO === "RUB") {
    const targetRate = ratesData.rates[targetISO];
    if (!targetRate) return null;
    return targetRate.value / targetRate.nominal;
  }

  if (targetISO === "RUB") {
    const baseRate = ratesData.rates[baseISO];
    if (!baseRate) return null;
    const baseValue = baseRate.value / baseRate.nominal;
    return 1 / baseValue;
  }

  const baseRate = ratesData.rates[baseISO];
  const targetRate = ratesData.rates[targetISO];

  if (!baseRate || !targetRate) return null;

  const baseValue = baseRate.value / baseRate.nominal;
  const targetValue = targetRate.value / targetRate.nominal;

  return targetValue / baseValue;
}

async function loadCurrencyRates(baseISO) {
  if (!ratesData || !ratesData.rates) return;

  const usdRate = calculateRateInBaseCurrency(baseISO, "USD");
  const eurRate = calculateRateInBaseCurrency(baseISO, "EUR");

  if (usdRate !== null) {
    currentRates.USD = usdRate;
  }
  if (eurRate !== null) {
    currentRates.EUR = eurRate;
  }

  if (baseISO !== "RUB") {
    const rubRate = calculateRateInBaseCurrency(baseISO, "RUB");
    if (rubRate !== null) {
      currentRates.RUB = rubRate;
    }
  } else {
    currentRates.RUB = null;
  }

  updateHeaderPrices();
}

export async function initCurrency() {
  const select = document.querySelector(".currency-select");
  if (!select) return;
  const currentEl = select.querySelector(".currency-current");
  const panel = select.querySelector(".js-dropdown-panel");
  if (!currentEl || !panel) return;

  try {
    ratesData = await APIService.getCBRRates();

    if (!ratesData || !ratesData.rates) return;

    populateCurrencyDropdown(ratesData.rates);

    baseISO = getStoredCurrency();
    const allowedCurrencies = ["RUB", "GBP", "CHF", "CNY", "JPY", "KZT", "BYN"];
    if (!allowedCurrencies.includes(baseISO) || (baseISO !== "RUB" && !ratesData.rates[baseISO])) {
      baseISO = "RUB";
    }
    currentEl.textContent = baseISO;

    await loadCurrencyRates(baseISO);

    dropdownInstance = dropdown(".currency-select");

    const options = panel.querySelectorAll(".currency-option");
    options.forEach((btn) => {
      btn.addEventListener("click", async (e) => {
        e.preventDefault();
        const iso = btn.getAttribute("data-iso");
        if (!iso || (iso !== "RUB" && !ratesData.rates[iso])) return;

        baseISO = iso;
        currentEl.textContent = iso;
        setStoredCurrency(iso);

        if (dropdownInstance) {
          dropdownInstance.close();
        }

        await loadCurrencyRates(baseISO);
      });
    });
  } catch (err) {
  }
}
