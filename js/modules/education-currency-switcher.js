/**
 * Education Currency Switcher
 * Handles currency selection and conversion for education program prices
 */

export const EducationCurrencySwitcher = (() => {
  const STORAGE_KEY = 'bsi_education_currency';
  const CURRENCY_SYMBOLS = {
    RUB: '₽',
    USD: '$',
    EUR: '€',
    GBP: '£',
  };

  // Получаем курсы из глобальной переменной или используем дефолтные
  let exchangeRates = window.bsiEducationExchangeRates || {
    RUB: 1,
    USD: 100,
    EUR: 110,
    GBP: 130,
  };

  /**
   * Format price based on currency
   * @param {number} value - Price value
   * @param {string} currency - Currency code (RUB, USD, EUR, GBP)
   * @returns {string} Formatted price
   */
  const formatPrice = (value, currency) => {
    if (currency === 'RUB') {
      // Format RUB with spaces: 75 000 ₽
      return Number(value).toLocaleString('ru-RU') + ' ' + CURRENCY_SYMBOLS[currency];
    } else {
      // Format other currencies: 1,000.00 $
      return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }) + ' ' + CURRENCY_SYMBOLS[currency];
    }
  };

  /**
   * Convert price from RUB to target currency
   * @param {number} priceRub - Price in RUB
   * @param {string} targetCurrency - Target currency
   * @returns {number|null} Converted price or null
   */
  const convertPrice = (priceRub, targetCurrency) => {
    if (targetCurrency === 'RUB' || !exchangeRates[targetCurrency]) {
      return priceRub;
    }
    return Math.round((priceRub / exchangeRates[targetCurrency]) * 100) / 100;
  };

  /**
   * Update price display for elements with data attributes
   * @param {HTMLElement} element - Element with price data attributes
   * @param {string} currency - Target currency
   */
  const updateElementPrice = (element, currency) => {
    const priceRub = element.dataset.priceRub;

    if (!priceRub) return;

    // Determine which price to show
    let displayPrice, displayCurrency;

    if (currency === 'RUB') {
      displayPrice = parseInt(priceRub);
      displayCurrency = 'RUB';
    } else {
      // If original currency data exists and matches the requested currency
      const priceOriginal = element.dataset.priceOriginal;
      const priceCurrency = element.dataset.priceCurrency;

      if (priceOriginal && priceCurrency === currency) {
        displayPrice = parseFloat(priceOriginal);
        displayCurrency = currency;
      } else {
        // Convert from RUB to target currency
        displayPrice = convertPrice(parseInt(priceRub), currency);
        displayCurrency = currency;
      }
    }

    // Update the element's text content while preserving the "от" prefix if it exists
    const currentText = element.textContent.trim();
    const hasFrom = currentText.startsWith('от ');
    const prefix = hasFrom ? 'от ' : '';

    element.textContent = prefix + formatPrice(displayPrice, displayCurrency);
  };

  /**
   * Update all prices on the page
   * @param {string} currency - Target currency
   */
  const updateAllPrices = (currency) => {
    // Update price buttons in catalog cards
    document.querySelectorAll('.education-card__btn-book').forEach(el => {
      updateElementPrice(el, currency);
    });

    // Update prices in program cards
    document.querySelectorAll('.education-program-card__price').forEach(el => {
      updateElementPrice(el, currency);
    });
  };

  /**
   * Initialize currency switcher
   */
  const init = () => {
    // Restore saved currency preference
    const savedCurrency = localStorage.getItem(STORAGE_KEY) || 'RUB';

    // Set select elements to saved value and listen for changes
    document.querySelectorAll('.js-education-currency-select').forEach(select => {
      select.value = savedCurrency;

      select.addEventListener('change', (e) => {
        const selectedCurrency = e.target.value;
        localStorage.setItem(STORAGE_KEY, selectedCurrency);
        updateAllPrices(selectedCurrency);

        // Update all select elements to maintain consistency
        document.querySelectorAll('.js-education-currency-select').forEach(s => {
          s.value = selectedCurrency;
        });
      });
    });

    // Apply saved currency to all prices on load
    if (savedCurrency !== 'RUB') {
      updateAllPrices(savedCurrency);
    }
  };

  return {
    init,
    updateAllPrices,
    convertPrice,
    formatPrice,
  };
})();
