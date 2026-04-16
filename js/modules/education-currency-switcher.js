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
   * Update price display for elements with data attributes
   * @param {HTMLElement} element - Element with price data attributes
   * @param {boolean} showOriginal - Whether to show original currency price
   */
  const updateElementPrice = (element, showOriginal) => {
    const priceRub = element.dataset.priceRub;
    const priceOriginal = element.dataset.priceOriginal;
    const priceCurrency = element.dataset.priceCurrency;

    if (!priceRub) return;

    // Determine which price to show
    let displayPrice, displayCurrency;

    if (showOriginal && priceOriginal && priceCurrency) {
      // Show original price in original currency
      displayPrice = parseFloat(priceOriginal);
      displayCurrency = priceCurrency;
    } else {
      // Show converted price in RUB
      displayPrice = parseInt(priceRub);
      displayCurrency = 'RUB';
    }

    // Extract prefix ("от ") and suffix (duration "/ 1-5 недель") from current text
    const currentText = element.textContent.trim();
    const hasFrom = currentText.startsWith('от ');
    const prefix = hasFrom ? 'от ' : '';

    // Extract duration suffix (everything after " / ")
    let suffix = '';
    const durationMatch = currentText.match(/\s*\/\s*.+$/);
    if (durationMatch) {
      suffix = durationMatch[0];  // " / 1-5 недель"
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

    // Update modal total price
    document.querySelectorAll('.js-modal-total').forEach(el => {
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
  };

  return {
    init,
    updateAllPrices,
    formatPrice,
  };
})();
