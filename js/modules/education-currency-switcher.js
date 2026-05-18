/**
 * Education Currency Switcher
 * Toggles display between RUB (converted) and original currency prices
 */

export const EducationCurrencySwitcher = (() => {
  const STORAGE_KEY = 'bsi_education_show_original_currency';
  const CURRENCY_SYMBOLS = {
    RUB: '₽',
    USD: '$',
    EUR: '€',
    GBP: '£',
  };

  /**
   * Format price based on currency
   * @param {number} value - Price value
   * @param {string} currency - Currency code (RUB, USD, EUR, GBP)
   * @returns {string} Formatted price
   */
  const formatPrice = (value, currency) => {
    const code = (currency || 'RUB').toString().toUpperCase();
    const sym = CURRENCY_SYMBOLS[code] || code;
    if (code === 'RUB') {
      return Number(value).toLocaleString('ru-RU') + ' ' + sym;
    }
    return Number(value).toLocaleString('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }) + ' ' + sym;
  };

  /**
   * Update price display for elements with data attributes
   * @param {HTMLElement} element - Element with price data attributes
   * @param {boolean} showOriginal - Whether to show original currency price
   */
  const updateElementPrice = (element, showOriginal) => {
    const priceRub = element.dataset.priceRub;
    const priceOriginal = element.dataset.priceOriginal;
    const priceCurrency = (element.dataset.priceCurrency || '').toUpperCase();

    if (!priceRub) return;

    // Determine which price to show
    let displayPrice, displayCurrency;

    if (showOriginal && priceOriginal && priceCurrency) {
      displayPrice = parseFloat(String(priceOriginal).replace(/\s/g, '').replace(',', '.'));
      displayCurrency = priceCurrency;
    } else {
      displayPrice = parseInt(String(priceRub).replace(/\s/g, ''), 10);
      displayCurrency = 'RUB';
    }

    // Extract prefix ("от ") and suffix (duration "/ 1-5 недель")
    const hasFrom = element.dataset.hasFrom === 'true';
    const prefix = hasFrom ? 'от ' : '';

    // Get suffix from cached data attribute, or extract from text if not cached
    let suffix = element.dataset.priceSuffix || '';
    if (!suffix) {
      const currentText = element.textContent.trim();
      const durationMatch = currentText.match(/\s*\/\s*.+$/);
      if (durationMatch) {
        suffix = durationMatch[0];  // " / 1-5 недель"
        element.dataset.priceSuffix = suffix;
      }
    }

    element.textContent = prefix + formatPrice(displayPrice, displayCurrency) + suffix;
  };

  /**
   * Update all prices on the page
   * @param {boolean} showOriginal - Whether to show original currency prices
   */
  const updateAllPrices = (showOriginal) => {
    // Update price buttons in catalog cards
    document.querySelectorAll('.education-card__btn-book').forEach(el => {
      updateElementPrice(el, showOriginal);
    });

    // Update prices in program cards
    document.querySelectorAll('.education-program-card__price').forEach(el => {
      updateElementPrice(el, showOriginal);
    });

    // Update price on education detail page
    document.querySelectorAll('.js-education-price').forEach(el => {
      updateElementPrice(el, showOriginal);
    });

    document.querySelectorAll('.js-event-price').forEach(el => {
      updateElementPrice(el, showOriginal);
    });
  };

  /**
   * Initialize currency switcher
   */
  const init = () => {
    // Restore saved preference
    const savedPreference = localStorage.getItem(STORAGE_KEY) === 'true';

    // Set checkbox elements to saved value and listen for changes
    document.querySelectorAll('.js-education-show-original-currency').forEach(checkbox => {
      checkbox.checked = savedPreference;

      checkbox.addEventListener('change', (e) => {
        const isChecked = e.target.checked;
        localStorage.setItem(STORAGE_KEY, isChecked.toString());
        updateAllPrices(isChecked);

        // Update all checkboxes to maintain consistency
        document.querySelectorAll('.js-education-show-original-currency').forEach(cb => {
          cb.checked = isChecked;
        });
      });
    });

    // Apply saved preference to all prices on load
    if (savedPreference) {
      updateAllPrices(true);
    }

    // Listen for content updates after AJAX loads (БАГ 4)
    document.addEventListener('education:content-updated', () => {
      const currentPreference = localStorage.getItem(STORAGE_KEY) === 'true';
      updateAllPrices(currentPreference);
    });
  };

  return {
    init,
    updateAllPrices,
    formatPrice,
  };
})();
