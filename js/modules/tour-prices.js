// tour-prices.js
// Модуль для работы с ценами экскурсионных туров из API Самотура

import { dropdown } from "./forms/dropdown.js";
import { createDayRange } from "./forms/day-range.js";
import { peopleCounter } from "./gtm-people-counter.js";
import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  searchPlaceholderValue: "Поиск...",
};

export const tourPrices = () => {
  const section = document.querySelector(".tour-prices-section");
  if (!section) return;

  const wrap = section.querySelector(".tour-prices__wrap");
  if (!wrap) return;

  const tourId = wrap.getAttribute("data-tour-id");
  const townFromInc = wrap.getAttribute("data-town-from-inc");
  const stateInc = wrap.getAttribute("data-state-inc");
  const tours = wrap.getAttribute("data-tours");

  if (!tourId || !townFromInc || !stateInc || !tours) {
    console.error("Tour prices: missing required data attributes");
    return;
  }

  // tour-prices__list находится вне tour-prices__wrap, но внутри section
  // Ищем его в section или в родительском элементе tour-prices-gtm
  const pricesList = section.querySelector("#tour-prices-list") || wrap.querySelector("#tour-prices-list");
  const filtersWrap = wrap.querySelector(".tour-prices__filters-wrap");
  const starFilter = wrap.querySelector("#tour-star-filter");
  const nightsSelect = filtersWrap?.querySelector(".gtm-nights-select");
  const datepicker = filtersWrap?.querySelector(".gtm-datepicker");
  const personsSelect = filtersWrap?.querySelector(".gtm-persons-select");
  // Кнопка находится внутри wrap, но вне filtersWrap
  const bookBtn = wrap.querySelector(".tour-prices__book-btn");

  // Детальная проверка элементов для отладки
  if (!pricesList) {
    console.error("Tour prices: missing #tour-prices-list");
    return;
  }
  if (!starFilter) {
    console.error("Tour prices: missing #tour-star-filter");
    return;
  }
  if (!bookBtn) {
    console.error("Tour prices: missing .tour-prices__book-btn", {
      wrap,
      foundInWrap: wrap.querySelector(".tour-prices__book-btn"),
      foundInSection: section.querySelector(".tour-prices__book-btn"),
    });
    return;
  }

  let nightsData = null;
  let allPricesData = []; // Храним все загруженные цены для фильтрации
  let currentStarFilter = ""; // Текущий выбранный фильтр звездности
  let starFilterChoice = null; // Экземпляр Choices для фильтра звездности

  // Параметры поиска
  const searchParams = {
    nightsFrom: 7,
    nightsTill: 7,
    checkInBeg: "20260308",
    checkInEnd: "20260315",
    adult: 2,
    child: 0,
    currency: 1, // RUB
  };

  // AJAX helper
  async function samoAjax(method, params = {}) {
    const body = new URLSearchParams({
      action: "bsi_samo",
      method,
      ...params,
    });

    const res = await fetch(ajax.url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body,
    });

    const json = await res.json();
    if (!json.success) {
      throw new Error(json.data?.message || "AJAX error");
    }
    return json.data;
  }

  // Форматирование числа с разделителями тысяч
  function formatPrice(price) {
    if (!price || isNaN(price)) return "0";
    return parseInt(price, 10).toLocaleString("ru-RU");
  }

  // Отправка GTM события
  function sendGTMEvent(eventName, data = {}) {
    if (typeof window.dataLayer === "undefined") {
      window.dataLayer = [];
    }
    window.dataLayer.push({
      event: eventName,
      tour_id: parseInt(tourId, 10),
      ...data,
    });
  }

  // Обновление выпадающего списка ночей на основе доступных значений из API
  function updateNightsDropdown() {
    if (!filtersWrap || !nightsSelect || !nightsData) return;

    const dayGrid = nightsSelect.querySelector(".day-grid");
    if (!dayGrid) return;

    // Определяем доступные ночи
    let availableNights = [];
    if (nightsData.available && Array.isArray(nightsData.available)) {
      // Используем список доступных ночей из API
      availableNights = nightsData.available;
    } else {
      // Если нет списка, используем диапазон от from до till
      for (let i = nightsData.from; i <= nightsData.till; i++) {
        availableNights.push(i);
      }
    }

    // Получаем все существующие элементы из HTML
    const existingItems = Array.from(dayGrid.querySelectorAll(".day-item"));
    const maxDays = 30; // Максимальное количество дней

    // Если элементов нет, создаем их все
    if (existingItems.length === 0) {
      for (let i = 1; i <= maxDays; i++) {
        const dayItem = document.createElement("div");
        dayItem.className = "day-item";
        dayItem.textContent = i;
        dayGrid.appendChild(dayItem);
      }
    }

    // Обновляем состояние всех элементов (активные/неактивные)
    const allItems = Array.from(dayGrid.querySelectorAll(".day-item"));
    allItems.forEach((dayItem) => {
      const dayNumber = parseInt(dayItem.textContent, 10);

      // Помечаем как неактивный, если день недоступен
      if (availableNights.includes(dayNumber)) {
        dayItem.classList.remove("is-disabled");
      } else {
        dayItem.classList.add("is-disabled");
      }
    });

    // Инициализируем createDayRange с новыми элементами
    createDayRange({
      rootEl: filtersWrap || wrap,
      gridSelector: ".gtm-nights-select .day-grid",
      defaultStartDay: searchParams.nightsFrom,
      defaultEndDay: searchParams.nightsTill,
      onChange: ({ startDay, endDay, reason }) => {
        if (startDay && endDay && reason !== "init") {
          searchParams.nightsFrom = startDay;
          searchParams.nightsTill = endDay;
          const nightsValue = nightsSelect.querySelector(".gtm-nights-select-value");
          if (nightsValue) {
            if (startDay === endDay) {
              nightsValue.textContent = `${startDay} ночей`;
            } else {
              nightsValue.textContent = `${startDay} - ${endDay} ночей`;
            }
          }
          // Перезагружаем цены при изменении ночей
          reloadPrices();
        }
      },
    });
  }

  // Обновление фильтра звездности на основе загруженных цен
  function updateStarFilter(prices) {
    if (!starFilterChoice) return;

    const starGroups = new Set();

    prices.forEach((price) => {
      const star = price.star || price.starAlt || price.starGroup || "";
      if (star) {
        starGroups.add(star);
      }
    });

    // Сортировка звездности (2*+, 3*+, 4*)
    const sortedStars = Array.from(starGroups).sort((a, b) => {
      const aNum = parseInt(a.replace(/[^\d]/g, "")) || 0;
      const bNum = parseInt(b.replace(/[^\d]/g, "")) || 0;
      return aNum - bNum;
    });

    // Формируем массив опций для Choices (убираем "+" из отображаемого текста)
    // Добавляем "Все отели" как первую опцию (проверяем, что она еще не добавлена)
    const starChoices = sortedStars.map((star) => ({ value: star, label: star.replace(/\+/g, "") }));
    const choices = [{ value: "", label: "Все отели" }, ...starChoices];

    // Проверяем на дубликаты по value
    const uniqueChoices = choices.filter((choice, index, self) => index === self.findIndex((c) => c.value === choice.value));

    // Сохраняем текущее выбранное значение перед обновлением
    const currentValue = starFilterChoice.getValue(true);

    // Обновляем Choices (очищаем и choices, и store, чтобы избежать дублирования)
    starFilterChoice.clearChoices();
    starFilterChoice.clearStore();
    starFilterChoice.setChoices(uniqueChoices, "value", "label", true);

    // Восстанавливаем предыдущее значение или устанавливаем "Все отели" по умолчанию
    if (currentValue && uniqueChoices.some((c) => c.value === currentValue)) {
      // Если предыдущее значение все еще доступно, восстанавливаем его
      starFilterChoice.setChoiceByValue(currentValue);
      currentStarFilter = currentValue;
    } else {
      // Иначе устанавливаем "Все отели" по умолчанию
      starFilterChoice.setChoiceByValue("");
      currentStarFilter = "";
    }
  }

  // Функция для отображения звездности
  function renderStars(rating) {
    if (!rating) return "";
    const numRating = parseInt(rating.toString().replace(/[^\d]/g, "")) || 0;
    if (numRating === 0) return "";

    let starsHtml = '<div class="stars-rating rating-stars" data-rating="' + numRating + '">';
    for (let i = 1; i <= 5; i++) {
      const filled = i <= numRating ? "filled" : "";
      starsHtml += `
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" 
             fill="${i <= numRating ? "currentColor" : "none"}" 
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
             class="lucide lucide-star-icon lucide-star ${filled}">
          <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>
        </svg>
      `;
    }
    starsHtml += "</div>";
    return starsHtml;
  }

  // Функция для форматирования даты
  function formatDate(dateStr) {
    if (!dateStr || dateStr.length !== 8) return "";
    const year = dateStr.substring(0, 4);
    const month = dateStr.substring(4, 6);
    const day = dateStr.substring(6, 8);
    return `${day}.${month}.${year}`;
  }

  // Фильтрация и отображение цен
  function displayPrices(prices = allPricesData) {
    // Убираем класс загрузки
    pricesList.classList.remove("is-loading");

    if (prices.length === 0) {
      pricesList.innerHTML = '<div class="tour-prices__no-price">Результатов нет</div>';
      return;
    }

    // Фильтруем по выбранной звездности
    let filteredPrices = prices;
    if (currentStarFilter) {
      filteredPrices = prices.filter((price) => {
        const priceStar = price.star || price.starAlt || price.starGroup || "";
        return priceStar === currentStarFilter;
      });
    }

    // Фильтруем цены с валидной ценой
    const validPrices = filteredPrices.filter((price) => {
      const priceValue = price.convertedPriceNumber || price.convertedPrice || price.price || 0;
      let numPrice = 0;
      if (typeof priceValue === "number") {
        numPrice = priceValue;
      } else if (typeof priceValue === "string") {
        const match = priceValue.match(/[\d.]+/);
        if (match) {
          numPrice = parseFloat(match[0]);
        }
      }
      return !isNaN(numPrice) && numPrice > 0;
    });

    if (validPrices.length === 0) {
      pricesList.innerHTML = '<div class="tour-prices__no-price">Результатов нет</div>';
      return;
    }

    // Группируем цены по звездности и собираем данные
    const pricesByStar = {};

    validPrices.forEach((price) => {
      // Получаем звездность
      const star = price.star || price.starAlt || price.starGroup || "Не указано";
      const starKey = price.starGroupKey || price.starKey || star;

      // Получаем цену в рублях
      let numPrice = 0;
      const priceValue = price.convertedPriceNumber || price.convertedPrice || price.price || 0;

      if (typeof priceValue === "number") {
        numPrice = priceValue;
      } else if (typeof priceValue === "string") {
        // Извлекаем число из строки "241091.13 RUB"
        const match = priceValue.match(/[\d.]+/);
        if (match) {
          numPrice = parseFloat(match[0]);
        }
      }

      if (!isNaN(numPrice) && numPrice > 0) {
        if (!pricesByStar[star]) {
          pricesByStar[star] = {
            star: star,
            starKey: starKey,
            minPrice: numPrice,
            prices: [],
            hotels: new Set(),
            towns: new Set(),
            meals: new Set(),
            rooms: new Set(),
            nights: new Set(),
          };
        }

        // Сохраняем минимальную цену
        if (numPrice < pricesByStar[star].minPrice) {
          pricesByStar[star].minPrice = numPrice;
        }

        // Собираем уникальные значения
        pricesByStar[star].prices.push(price);
        const hotel = price.hotel || price.hotelAlt || "";
        const town = price.town || price.townAlt || "";
        const meal = price.meal || price.mealAlt || price.mealGroup || "";
        const room = price.room || price.roomAlt || "";
        const nights = price.nights || price.hnights || "";

        if (hotel) pricesByStar[star].hotels.add(hotel);
        if (town) pricesByStar[star].towns.add(town);
        if (meal) pricesByStar[star].meals.add(meal);
        if (room) pricesByStar[star].rooms.add(room);
        if (nights) pricesByStar[star].nights.add(nights);
      }
    });

    // Сортируем по звездности (2*, 3*, 4*)
    const sortedStars = Object.values(pricesByStar).sort((a, b) => {
      const aNum = parseInt(a.star.replace(/[^\d]/g, "")) || 0;
      const bNum = parseInt(b.star.replace(/[^\d]/g, "")) || 0;
      return aNum - bNum;
    });

    // Обновление UI - выводим список по звездности
    if (sortedStars.length > 0) {
      let html = '<div class="tour-prices__stars-list">';
      sortedStars.forEach((item) => {
        // Формируем заголовок
        const hotelsArray = Array.from(item.hotels);
        const townsArray = Array.from(item.towns);
        const hotelsCount = hotelsArray.length;
        const townsCount = townsArray.length;

        // Извлекаем число звездности из item.star (например "3*" -> "3")
        const starNumber = parseInt(item.star.replace(/[^\d]/g, "")) || 0;

        // Функция для правильного склонения слова "звезда"
        function getStarWord(count) {
          const lastDigit = count % 10;
          const lastTwoDigits = count % 100;

          // Исключения для 11-14
          if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
            return "звезд";
          }

          // 1, 21, 31... звезда
          if (lastDigit === 1) {
            return "звезда";
          }

          // 2, 3, 4, 22, 23, 24... звезды
          if (lastDigit >= 2 && lastDigit <= 4) {
            return "звезды";
          }

          // 5, 6, 7, 8, 9, 0, 10, 20... звезд
          return "звезд";
        }

        // Формируем заголовок: "Отель" + число звездности + правильное склонение
        let title = "Отель";
        if (starNumber > 0) {
          const starWord = getStarWord(starNumber);
          title = `Отель ${starNumber} ${starWord}`;
        }

        // Формируем данные для отображения
        const commonData = [];
        if (townsCount === 1 && townsArray[0]) {
          commonData.push({ label: "Город", value: townsArray[0] });
        }
        if (item.meals.size === 1) {
          const meal = Array.from(item.meals)[0];
          if (meal) commonData.push({ label: "Питание", value: meal });
        }
        if (item.rooms.size === 1) {
          const room = Array.from(item.rooms)[0];
          if (room) commonData.push({ label: "Номер", value: room });
        }
        if (item.nights.size === 1) {
          const nights = Array.from(item.nights)[0];
          if (nights) commonData.push({ label: "Ночей", value: nights });
        }

        html += `
          <div class="tour-prices__star-item">
            <div class="tour-prices__star-header">
              <div class="tour-prices__star-title">
                ${title}
              </div>
              <div class="tour-prices__star-price">от ${formatPrice(item.minPrice)} ₽</div>
            </div>
            ${
              commonData.length > 0
                ? `
              <div class="tour-prices__star-body">
                ${commonData
                  .map(
                    (data) => `
                  <div class="tour-prices__star-field">
                    <span class="tour-prices__star-label">${data.label}:</span>
                    <span class="tour-prices__star-value">${data.value}</span>
                  </div>
                `
                  )
                  .join("")}
              </div>
            `
                : ""
            }
          </div>
        `;
      });
      html += "</div>";
      pricesList.innerHTML = html;

      // Отправляем GTM событие
      sendGTMEvent("tour_prices_viewed", {
        stars_count: sortedStars.length,
        prices_count: validPrices.length,
        min_price: Math.min(
          ...validPrices.map((p) => {
            const priceValue = p.convertedPriceNumber || p.convertedPrice || p.price || 0;
            return typeof priceValue === "number" ? priceValue : parseFloat(String(priceValue).match(/[\d.]+/)?.[0] || 0);
          })
        ),
      });
    } else {
      pricesList.innerHTML = '<div class="tour-prices__no-price">Результатов нет</div>';
    }
  }

  // Загрузка доступных ночей
  async function loadNights() {
    try {
      const data = await samoAjax("excursion_nights", {
        TOWNFROMINC: townFromInc,
        STATEINC: stateInc,
        TOURS: tours,
      });

      // Обработка JSON структуры: SearchExcursion_NIGHTS
      if (data?.SearchExcursion_NIGHTS) {
        const nightsDataObj = data.SearchExcursion_NIGHTS;
        if (nightsDataObj.nights?.night) {
          const nights = Array.isArray(nightsDataObj.nights.night) ? nightsDataObj.nights.night : [nightsDataObj.nights.night];
          if (nights.length > 0) {
            // Преобразуем массив ночей в числа и находим min/max
            const nightsNumbers = nights.map((n) => parseInt(n, 10)).filter((n) => !isNaN(n));
            if (nightsNumbers.length > 0) {
              nightsData = {
                from: Math.min(...nightsNumbers),
                till: Math.max(...nightsNumbers),
                available: nightsNumbers.sort((a, b) => a - b), // Список доступных ночей
              };
            }
          }
        }
        // Если есть default, используем его
        if (nightsDataObj.default && !nightsData) {
          nightsData = {
            from: parseInt(nightsDataObj.default.from || 7, 10),
            till: parseInt(nightsDataObj.default.till || 7, 10),
            available: null, // Если нет списка, используем диапазон
          };
        }
      }

      // Если данных нет, используем значения по умолчанию
      if (!nightsData) {
        nightsData = { from: 7, till: 7, available: null };
      }

      // Обновляем выпадающий список ночей с доступными значениями
      updateNightsDropdown();
    } catch (error) {
      console.error("Error loading nights:", error);
      nightsData = { from: 7, till: 7, available: null }; // Значения по умолчанию
      updateNightsDropdown();
    }
  }

  // Формат даты: YYYYMMDD
  function formatDateYYYYMMDD(date) {
    if (!date) return "";
    const d = date instanceof Date ? date : new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}${month}${day}`;
  }

  // Загрузка цен
  async function loadPrices(forceRefresh = false) {
    if (!nightsData) {
      await loadNights(forceRefresh);
    }

    // Добавляем класс загрузки
    pricesList.classList.add("is-loading");

    try {
      const params = {
        TOWNFROMINC: townFromInc,
        STATEINC: stateInc,
        TOURS: tours,
        CHECKIN_BEG: searchParams.checkInBeg,
        CHECKIN_END: searchParams.checkInEnd,
        NIGHTS_FROM: searchParams.nightsFrom,
        NIGHTS_TILL: searchParams.nightsTill,
        ADULT: searchParams.adult,
        CHILD: searchParams.child,
        CURRENCY: searchParams.currency,
      };

      // Добавляем флаг принудительного обновления кэша
      if (forceRefresh) {
        params._force_refresh = true;
      }

      const data = await samoAjax("excursion_prices", params);

      // Обработка JSON структуры: SearchExcursion_PRICES.prices[]
      let prices = [];

      if (data?.SearchExcursion_PRICES?.prices) {
        prices = Array.isArray(data.SearchExcursion_PRICES.prices)
          ? data.SearchExcursion_PRICES.prices
          : [data.SearchExcursion_PRICES.prices];
      }

      // Сохраняем все цены
      allPricesData = prices;

      // Обновляем фильтр звездности
      updateStarFilter(prices);

      // Отображаем цены
      displayPrices(prices);
    } catch (error) {
      console.error("Error loading prices:", error);
      pricesList.classList.remove("is-loading");
      pricesList.innerHTML = '<div class="tour-prices__error">Ошибка загрузки цен</div>';
    }
  }

  // Функция для перезагрузки цен при изменении параметров
  async function reloadPrices() {
    pricesList.classList.add("is-loading");
    pricesList.innerHTML = "";

    // Отправляем GTM событие
    sendGTMEvent("tour_prices_search", {
      nights_from: searchParams.nightsFrom,
      nights_till: searchParams.nightsTill,
      checkin_beg: searchParams.checkInBeg,
      checkin_end: searchParams.checkInEnd,
      adult: searchParams.adult,
      child: searchParams.child,
    });

    // При изменении параметров принудительно обновляем кэш
    await loadPrices(true);
  }

  // Инициализация фильтров
  function initFilters() {
    // Dropdown для ночей и людей - инициализируем сразу
    // Важно: используем селектор относительно wrap, но элементы находятся внутри filtersWrap
    if (nightsSelect) {
      // Проверяем, что элемент найден
      const nightsContainer = document.querySelector(".tour-prices__wrap .gtm-nights-select");
      if (nightsContainer) {
        dropdown(".tour-prices__wrap .gtm-nights-select");
      } else {
        console.error("Tour prices: nights container not found", {
          nightsSelect,
          selector: ".tour-prices__wrap .gtm-nights-select",
        });
      }
    }

    if (personsSelect) {
      const personsContainer = document.querySelector(".tour-prices__wrap .gtm-persons-select");
      if (personsContainer) {
        dropdown(".tour-prices__wrap .gtm-persons-select");
      } else {
        console.error("Tour prices: persons container not found", {
          personsSelect,
          selector: ".tour-prices__wrap .gtm-persons-select",
        });
      }
    }

    // Выбор диапазона ночей будет инициализирован после загрузки доступных ночей из API
    // через функцию updateNightsDropdown() в loadNights()

    // Выбор дат
    if (datepicker) {
      const today = new Date();
      const nextWeek = new Date();
      nextWeek.setDate(today.getDate() + 7);

      // Парсим начальные даты из searchParams
      const parseDate = (dateStr) => {
        if (!dateStr || dateStr.length !== 8) return null;
        const year = parseInt(dateStr.substring(0, 4));
        const month = parseInt(dateStr.substring(4, 6)) - 1;
        const day = parseInt(dateStr.substring(6, 8));
        return new Date(year, month, day);
      };

      const defaultStart = parseDate(searchParams.checkInBeg) || today;
      const defaultEnd = parseDate(searchParams.checkInEnd) || nextWeek;

      flatpickr(datepicker, {
        mode: "range",
        minDate: "today",
        locale: Russian,
        dateFormat: "d.m",
        defaultDate: [defaultStart, defaultEnd],
        onChange: (selectedDates) => {
          if (selectedDates.length === 2) {
            searchParams.checkInBeg = formatDateYYYYMMDD(selectedDates[0]);
            searchParams.checkInEnd = formatDateYYYYMMDD(selectedDates[1]);
            // Перезагружаем цены при изменении дат
            reloadPrices();
          }
        },
      });
    }

    // Счетчик людей
    if (personsSelect) {
      peopleCounter({
        rootSelector: ".tour-prices__wrap .gtm-persons-select",
        outputSelector: ".tour-prices__wrap .gtm-people-total",
        maxAdults: 4,
        maxChildren: 3,
        onChange: ({ adults, children }) => {
          searchParams.adult = adults;
          searchParams.child = children;
          // Перезагружаем цены при изменении количества людей
          reloadPrices();
        },
      });
    }

    // Инициализация Choices для фильтра звездности
    if (starFilter) {
      // Очищаем исходные опции из HTML перед инициализацией Choices
      // чтобы избежать дублирования с опциями, которые добавим через setChoices
      starFilter.innerHTML = "";

      starFilterChoice = new Choices(starFilter, {
        ...CHOICES_RU,
        searchEnabled: false,
        shouldSort: false,
      });

      // Обработчик изменения фильтра звездности через Choices
      starFilterChoice.passedElement.element.addEventListener("change", () => {
        currentStarFilter = starFilterChoice.getValue(true) || "";

        // Отправляем GTM событие
        sendGTMEvent("tour_star_filter_changed", {
          star_filter: currentStarFilter || "all",
        });

        // Фильтруем и отображаем цены из уже загруженных данных
        if (allPricesData.length > 0) {
          displayPrices(allPricesData);
        }
      });
    }
  }

  // Инициализация: загрузка данных при загрузке страницы
  async function init() {
    // Инициализируем фильтры (включая dropdown)
    initFilters();

    // Загружаем ночи для получения доступного диапазона
    await loadNights();

    // Устанавливаем начальные значения ночей из API
    if (nightsData) {
      searchParams.nightsFrom = nightsData.from;
      searchParams.nightsTill = nightsData.till;

      // Обновляем отображение ночей
      const nightsValue = nightsSelect?.querySelector(".gtm-nights-select-value");
      if (nightsValue) {
        if (nightsData.from === nightsData.till) {
          nightsValue.textContent = `${nightsData.from} ночей`;
        } else {
          nightsValue.textContent = `${nightsData.from} - ${nightsData.till} ночей`;
        }
      }
    }

    // Загружаем цены автоматически при инициализации (используем кэш)
    pricesList.classList.add("is-loading");
    pricesList.innerHTML = "";
    await loadPrices(false);

    // Функция для формирования URL бронирования
    function buildBookingUrl() {
      const baseUrl = "https://online.bsigroup.ru/search_excursion";
      const params = new URLSearchParams();

      // Базовые параметры из data-атрибутов
      params.append("TOWNFROMINC", townFromInc);
      params.append("STATEINC", stateInc);
      params.append("TOURINC", tours); // TOURINC в URL соответствует TOURS в API

      // Даты
      params.append("CHECKIN_BEG", searchParams.checkInBeg);
      params.append("CHECKIN_END", searchParams.checkInEnd);

      // Ночи
      params.append("NIGHTS_FROM", searchParams.nightsFrom);
      params.append("NIGHTS_TILL", searchParams.nightsTill);

      // Люди
      params.append("ADULT", searchParams.adult);
      params.append("CHILD", searchParams.child);

      // Валюта (1 = RUB, 2 = USD, 3 = EUR)
      params.append("CURRENCY", searchParams.currency || 1);

      // Если выбрана звездность, находим ID отелей с этой звездностью
      if (currentStarFilter && allPricesData.length > 0) {
        const filteredHotels = allPricesData
          .filter((price) => {
            const priceStar = price.star || price.starAlt || price.starGroup || "";
            return priceStar === currentStarFilter;
          })
          .map((price) => {
            // Пробуем разные варианты полей для ID отеля
            return price.hotelInc || price.hotelId || price.hotelKey || price.hotel?.inc || price.hotel?.id || "";
          })
          .filter((id) => id && id !== "");

        // Если есть уникальные ID отелей, добавляем первый
        if (filteredHotels.length > 0) {
          const uniqueHotels = [...new Set(filteredHotels)];
          if (uniqueHotels.length === 1) {
            params.append("HOTELS", uniqueHotels[0]);
          }
        }
      }

      // Дополнительные параметры
      params.append("MEALS_ANY", "1");
      params.append("ROOMS_ANY", "1");
      params.append("FREIGHT", "1");
      params.append("PRICEPAGE", "1");
      params.append("DOLOAD", "1");

      return `${baseUrl}?${params.toString()}`;
    }

    // Обработчик кнопки "Забронировать"
    bookBtn.addEventListener("click", () => {
      const bookingUrl = buildBookingUrl();

      // Отправляем GTM событие
      sendGTMEvent("tour_booking_clicked", {
        booking_url: bookingUrl,
        star_filter: currentStarFilter || "all",
        nights_from: searchParams.nightsFrom,
        nights_till: searchParams.nightsTill,
        checkin_beg: searchParams.checkInBeg,
        checkin_end: searchParams.checkInEnd,
        adult: searchParams.adult,
        child: searchParams.child,
      });

      // Открываем ссылку в новой вкладке
      window.open(bookingUrl, "_blank", "noopener,noreferrer");
    });
  }

  // Запуск инициализации
  init();
};
