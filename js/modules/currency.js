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
};

let ratesData = null;
let currentRates = { USD: null, EUR: null, RUB: null };
let baseISO = "RUB";
let dropdownInstances = [];
let currencySelectEls = [];

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

function updateCurrencyPrices(container) {
  if (!container) return;

  const items = container.querySelectorAll(".currency-item");
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
}

function updateBaseCurrencyLabels() {
  currencySelectEls.forEach((selectEl) => {
    const currentEl = selectEl.querySelector(".currency-current");
    if (currentEl) {
      currentEl.textContent = baseISO;
    }
  });
}

function updateRubItem(container) {
  if (!container) return;

  const selectElement = container.querySelector(".currency-select");
  if (!selectElement) return;

  const parent = selectElement.parentElement || container;

  if (baseISO !== "RUB" && currentRates.RUB !== null) {
    let rubItem = parent.querySelector('.currency-item[data-currency="RUB"]');
    if (!rubItem) {
      rubItem = document.createElement("div");
      rubItem.className = "currency-item";
      rubItem.setAttribute("data-currency", "RUB");
      rubItem.innerHTML = `
        <div class="currency-item__title">RUB</div>
        <div class="currency-item__value numfont"></div>
      `;
      parent.insertBefore(rubItem, selectElement);
    }

    const valueEl = rubItem.querySelector(".currency-item__value");
    if (valueEl) {
      valueEl.textContent = formatRate(currentRates.RUB);
    }
  } else {
    const rubItem = parent.querySelector('.currency-item[data-currency="RUB"]');
    if (rubItem) {
      rubItem.remove();
    }
  }
}

function updateHeaderPrices() {
  const headerContainer = document.querySelector(".header__currencies");
  if (headerContainer) {
    updateCurrencyPrices(headerContainer);
    updateRubItem(headerContainer);
  }

  const mobileNavContainer = document.querySelector(".mobile-nav__currencies");
  if (mobileNavContainer) {
    updateCurrencyPrices(mobileNavContainer);
    updateRubItem(mobileNavContainer);
  }

  const footerContainer = document.querySelector(".footer__currencies");
  if (footerContainer) {
    updateCurrencyPrices(footerContainer);
    updateRubItem(footerContainer);
  }
}

function populateCurrencyDropdown(panel, rates) {
  const allowedCurrencies = ["RUB", "GBP", "CHF", "CNY", "JPY", "KZT"];

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
  currencySelectEls = Array.from(document.querySelectorAll(".currency-select"));
  if (!currencySelectEls.length) return;

  try {
    ratesData = await APIService.getCBRRates();

    if (!ratesData || !ratesData.rates) return;

    baseISO = getStoredCurrency();
    const allowedCurrencies = ["RUB", "GBP", "CHF", "CNY", "JPY", "KZT"];
    if (!allowedCurrencies.includes(baseISO) || (baseISO !== "RUB" && !ratesData.rates[baseISO])) {
      baseISO = "RUB";
    }
    updateBaseCurrencyLabels();

    await loadCurrencyRates(baseISO);

    dropdownInstances = currencySelectEls.map((selectEl) => dropdown(selectEl)).filter(Boolean);

    currencySelectEls.forEach((selectEl) => {
      const panel = selectEl.querySelector(".js-dropdown-panel");
      if (!panel) return;

      populateCurrencyDropdown(panel, ratesData.rates);

      const options = panel.querySelectorAll(".currency-option");
      options.forEach((btn) => {
        btn.addEventListener("click", async (e) => {
          e.preventDefault();
          const iso = btn.getAttribute("data-iso");
          if (!iso || (iso !== "RUB" && !ratesData.rates[iso])) return;

          baseISO = iso;
          setStoredCurrency(iso);
          updateBaseCurrencyLabels();

          dropdownInstances.forEach((d) => d && d.close && d.close());

          await loadCurrencyRates(baseISO);
        });
      });
    });
  } catch (err) {
  }
}
