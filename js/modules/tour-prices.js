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
    return;
  }

  const pricesList = section.querySelector("#tour-prices-list") || wrap.querySelector("#tour-prices-list");
  const filtersWrap = wrap.querySelector(".tour-prices__filters-wrap");
  const starFilter = wrap.querySelector("#tour-star-filter");
  const nightsSelect = filtersWrap?.querySelector(".gtm-nights-select");
  const datepicker = filtersWrap?.querySelector(".gtm-datepicker");
  const personsSelect = filtersWrap?.querySelector(".gtm-persons-select");
  const bookBtn = wrap.querySelector(".tour-prices__book-btn");

  if (!pricesList || !starFilter || !bookBtn) {
    return;
  }

  pricesList.classList.add("is-loading");
  pricesList.innerHTML = "";

  let nightsData = null;
  let allPricesData = [];
  let currentStarFilter = "";
  let starFilterChoice = null;
  let availableDates = [];
  let datePickerInstance = null;

  function formatDateYYYYMMDD(date) {
    if (!date) return "";
    const d = date instanceof Date ? date : new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}${month}${day}`;
  }

  function parseDateFromYYYYMMDD(dateStr) {
    if (!dateStr || dateStr.length !== 8) return null;
    const year = parseInt(dateStr.substring(0, 4));
    const month = parseInt(dateStr.substring(4, 6)) - 1;
    const day = parseInt(dateStr.substring(6, 8));
    return new Date(year, month, day);
  }

  function parseValidDates(validDatesStr, startDateStr) {
    if (!validDatesStr || !startDateStr) return [];

    const startDate = parseDateFromYYYYMMDD(startDateStr);
    if (!startDate) return [];

    const dates = [];
    for (let i = 0; i < validDatesStr.length; i++) {
      if (validDatesStr[i] === "1") {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + i);
        dates.push(formatDateYYYYMMDD(date));
      }
    }
    return dates;
  }

  const searchParams = {
    nightsFrom: 7,
    nightsTill: 7,
    checkInBeg: "",
    checkInEnd: "",
    adult: 2,
    child: 0,
    currency: 1,
  };

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

  function formatPrice(price) {
    if (!price || isNaN(price)) return "0";
    return parseInt(price, 10).toLocaleString("ru-RU");
  }

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

  function buildBookingUrlForHotel(hotelId, starRating = null) {
    const baseUrl = "https://online.bsigroup.ru/search_excursion";
    const params = new URLSearchParams();

    params.append("TOWNFROMINC", townFromInc);
    params.append("STATEINC", stateInc);
    params.append("TOURINC", tours);
    params.append("CHECKIN_BEG", searchParams.checkInBeg);
    params.append("CHECKIN_END", searchParams.checkInEnd);
    params.append("NIGHTS_FROM", searchParams.nightsFrom);
    params.append("NIGHTS_TILL", searchParams.nightsTill);
    params.append("ADULT", searchParams.adult);
    params.append("CHILD", searchParams.child);
    params.append("CURRENCY", searchParams.currency || 1);

    if (starRating) {
      const starNumber = parseInt(starRating.replace(/[^\d]/g, "")) || 0;
      if (starNumber > 0) {
        params.append("STAR", starNumber);
      }
    }

    if (hotelId) {
      params.append("HOTELS", hotelId);
    } else if (currentStarFilter && allPricesData.length > 0) {
      const filteredHotels = allPricesData
        .filter((price) => {
          const priceStar = price.star || price.starAlt || price.starGroup || "";
          return priceStar === currentStarFilter;
        })
        .map((price) => {
          return price.hotelInc || price.hotelId || price.hotelKey || price.hotel?.inc || price.hotel?.id || "";
        })
        .filter((id) => id && id !== "");

      if (filteredHotels.length > 0) {
        const uniqueHotels = [...new Set(filteredHotels)];
        if (uniqueHotels.length === 1) {
          params.append("HOTELS", uniqueHotels[0]);
        }
      }
    }

    params.append("MEALS_ANY", "1");
    params.append("ROOMS_ANY", "1");
    params.append("FREIGHT", "1");
    params.append("PRICEPAGE", "1");
    params.append("DOLOAD", "1");

    return `${baseUrl}?${params.toString()}`;
  }

  function updateNightsDropdown() {
    if (!filtersWrap || !nightsSelect || !nightsData) return;

    const dayGrid = nightsSelect.querySelector(".day-grid");
    if (!dayGrid) return;

    let availableNights = [];
    if (nightsData.available && Array.isArray(nightsData.available)) {
      availableNights = nightsData.available;
    } else {
      for (let i = nightsData.from; i <= nightsData.till; i++) {
        availableNights.push(i);
      }
    }

    const existingItems = Array.from(dayGrid.querySelectorAll(".day-item"));
    const maxDays = 30;

    if (existingItems.length === 0) {
      for (let i = 1; i <= maxDays; i++) {
        const dayItem = document.createElement("div");
        dayItem.className = "day-item";
        dayItem.textContent = i;
        dayGrid.appendChild(dayItem);
      }
    }

    const allItems = Array.from(dayGrid.querySelectorAll(".day-item"));
    allItems.forEach((dayItem) => {
      const dayNumber = parseInt(dayItem.textContent, 10);
      if (availableNights.includes(dayNumber)) {
        dayItem.classList.remove("is-disabled");
      } else {
        dayItem.classList.add("is-disabled");
      }
    });

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
          reloadPrices();
        }
      },
    });
  }

  function updateStarFilter(prices) {
    if (!starFilterChoice) return;

    const starGroups = new Set();

    prices.forEach((price) => {
      const star = price.star || price.starAlt || price.starGroup || "";
      if (star) {
        starGroups.add(star);
      }
    });

    const sortedStars = Array.from(starGroups).sort((a, b) => {
      const aNum = parseInt(a.replace(/[^\d]/g, "")) || 0;
      const bNum = parseInt(b.replace(/[^\d]/g, "")) || 0;
      return aNum - bNum;
    });

    const starChoices = sortedStars.map((star) => ({ value: star, label: star.replace(/\+/g, "") }));
    const choices = [{ value: "", label: "Все отели" }, ...starChoices];
    const uniqueChoices = choices.filter((choice, index, self) => index === self.findIndex((c) => c.value === choice.value));

    const currentValue = starFilterChoice.getValue(true);

    starFilterChoice.clearChoices();
    starFilterChoice.clearStore();
    starFilterChoice.setChoices(uniqueChoices, "value", "label", true);

    if (currentValue && uniqueChoices.some((c) => c.value === currentValue)) {
      starFilterChoice.setChoiceByValue(currentValue);
      currentStarFilter = currentValue;
    } else {
      starFilterChoice.setChoiceByValue("");
      currentStarFilter = "";
    }
  }

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

  function formatDate(dateStr) {
    if (!dateStr || dateStr.length !== 8) return "";
    const year = dateStr.substring(0, 4);
    const month = dateStr.substring(4, 6);
    const day = dateStr.substring(6, 8);
    return `${day}.${month}.${year}`;
  }

  function displayPrices(prices = allPricesData) {
    if (!searchParams.checkInBeg || !searchParams.checkInEnd) {
      return;
    }

    pricesList.classList.remove("is-loading");

    if (prices.length === 0) {
      pricesList.innerHTML = '<div class="tour-prices__no-price">Результатов нет</div>';
      return;
    }

    let filteredPrices = prices;
    if (currentStarFilter) {
      filteredPrices = prices.filter((price) => {
        const priceStar = price.star || price.starAlt || price.starGroup || "";
        return priceStar === currentStarFilter;
      });
    }

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

    const pricesByStar = {};

    validPrices.forEach((price) => {
      const star = price.star || price.starAlt || price.starGroup || "Не указано";
      const starKey = price.starGroupKey || price.starKey || star;

      let numPrice = 0;
      const priceValue = price.convertedPriceNumber || price.convertedPrice || price.price || 0;

      if (typeof priceValue === "number") {
        numPrice = priceValue;
      } else if (typeof priceValue === "string") {
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
            minPriceItem: price,
            prices: [],
            hotels: new Set(),
            towns: new Set(),
            meals: new Set(),
            rooms: new Set(),
            nights: new Set(),
          };
        }

        if (numPrice < pricesByStar[star].minPrice) {
          pricesByStar[star].minPrice = numPrice;
          pricesByStar[star].minPriceItem = price;
        }

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

    const sortedStars = Object.values(pricesByStar).sort((a, b) => {
      const aNum = parseInt(a.star.replace(/[^\d]/g, "")) || 0;
      const bNum = parseInt(b.star.replace(/[^\d]/g, "")) || 0;
      return aNum - bNum;
    });

    if (sortedStars.length > 0) {
      let html = '<div class="tour-prices__stars-list">';
      sortedStars.forEach((item) => {
        const hotelsArray = Array.from(item.hotels);
        const townsArray = Array.from(item.towns);
        const hotelsCount = hotelsArray.length;
        const townsCount = townsArray.length;
        const starNumber = parseInt(item.star.replace(/[^\d]/g, "")) || 0;

        function getStarWord(count) {
          const lastDigit = count % 10;
          const lastTwoDigits = count % 100;

          if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
            return "звезд";
          }
          if (lastDigit === 1) {
            return "звезда";
          }
          if (lastDigit >= 2 && lastDigit <= 4) {
            return "звезды";
          }
          return "звезд";
        }

        let title = "Отель";
        if (starNumber > 0) {
          const starWord = getStarWord(starNumber);
          title = `Отель ${starNumber} ${starWord}`;
        }

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

        const hotelId = item.minPriceItem?.hotelInc || item.minPriceItem?.hotelId || item.minPriceItem?.hotelKey || item.minPriceItem?.hotel?.inc || item.minPriceItem?.hotel?.id || "";

        html += `
          <div class="tour-prices__star-item" data-hotel-id="${hotelId}" data-star-rating="${item.star}">
            <div class="tour-prices__star-header">
              <div class="tour-prices__star-title">
                ${title}
              </div>
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
            <div class="tour-prices__star-footer">
              <div class="tour-prices__star-price">от ${formatPrice(item.minPrice)} ₽</div>
              <button class="btn btn-accent sm tour-prices__card-book-btn" type="button" data-hotel-id="${hotelId}">
                Забронировать
              </button>
            </div>
          </div>
        `;
      });
      html += "</div>";
      pricesList.innerHTML = html;

      const cardBookBtns = pricesList.querySelectorAll(".tour-prices__card-book-btn");
      cardBookBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
          const hotelId = btn.getAttribute("data-hotel-id");
          const starItem = btn.closest(".tour-prices__star-item");
          const starRating = starItem?.getAttribute("data-star-rating") || null;
          const bookingUrl = buildBookingUrlForHotel(hotelId, starRating);

          sendGTMEvent("tour_card_booking_clicked", {
            booking_url: bookingUrl,
            hotel_id: hotelId,
            star_rating: starRating,
            star_filter: currentStarFilter || "all",
            nights_from: searchParams.nightsFrom,
            nights_till: searchParams.nightsTill,
            checkin_beg: searchParams.checkInBeg,
            checkin_end: searchParams.checkInEnd,
            adult: searchParams.adult,
            child: searchParams.child,
          });

          window.open(bookingUrl, "_blank", "noopener,noreferrer");
        });
      });

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

  async function loadAvailableDates(forceRefresh = false) {
    try {
      const params = {
        TOWNFROMINC: townFromInc,
        STATEINC: stateInc,
        TOURS: tours,
      };
      if (forceRefresh) {
        params._force_refresh = true;
      }
      const data = await samoAjax("excursion_all", params);

      if (data?.SearchExcursion_ALL?.CHECKIN_BEG) {
        const checkInBeg = data.SearchExcursion_ALL.CHECKIN_BEG;

        if (checkInBeg.validDates && checkInBeg.startDate) {
          const parsedDates = parseValidDates(checkInBeg.validDates, checkInBeg.startDate);
          availableDates = Array.isArray(parsedDates) ? parsedDates : [];

          if (availableDates.length > 0) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            let nearestDate = null;
            for (const dateStr of availableDates) {
              const dateObj = parseDateFromYYYYMMDD(dateStr);
              if (dateObj && dateObj >= today) {
                nearestDate = dateStr;
                break;
              }
            }

            if (!nearestDate) {
              nearestDate = availableDates[0];
            }

            searchParams.checkInBeg = nearestDate;

            const nightsCount = nightsData?.from || searchParams.nightsFrom || 7;
            const startDateObj = parseDateFromYYYYMMDD(nearestDate);
            if (startDateObj) {
              const endDateObj = new Date(startDateObj);
              endDateObj.setDate(startDateObj.getDate() + nightsCount);
              searchParams.checkInEnd = formatDateYYYYMMDD(endDateObj);
            } else {
              searchParams.checkInEnd = nearestDate;
            }

            updateDatePicker();
            reloadPrices();
          }
        }
      }
    } catch (error) {
      const today = new Date();
      const endDate = new Date(today);
      endDate.setDate(today.getDate() + 7);
      searchParams.checkInBeg = formatDateYYYYMMDD(today);
      searchParams.checkInEnd = formatDateYYYYMMDD(endDate);
    }
  }

  function updateDatePicker() {
    if (!datePickerInstance || !datepicker) return;

    if (!Array.isArray(availableDates) || availableDates.length === 0) {
      return;
    }

    const firstAvailableDate = parseDateFromYYYYMMDD(availableDates[0]);
    const lastAvailableDate = parseDateFromYYYYMMDD(availableDates[availableDates.length - 1]);

    if (firstAvailableDate && lastAvailableDate) {
      datePickerInstance.set("minDate", firstAvailableDate);
      datePickerInstance.set("maxDate", lastAvailableDate);

      let startDate = null;
      let endDate = null;

      if (searchParams.checkInBeg && searchParams.checkInEnd) {
        startDate = parseDateFromYYYYMMDD(searchParams.checkInBeg);
        endDate = parseDateFromYYYYMMDD(searchParams.checkInEnd);
      } else {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        let nearestDate = null;
        for (const dateStr of availableDates) {
          const dateObj = parseDateFromYYYYMMDD(dateStr);
          if (dateObj && dateObj >= today) {
            nearestDate = dateObj;
            break;
          }
        }

        if (!nearestDate) {
          nearestDate = firstAvailableDate;
        }

        endDate = new Date(nearestDate);
        endDate.setDate(nearestDate.getDate() + 7);

        if (endDate > lastAvailableDate) {
          endDate.setTime(lastAvailableDate.getTime());
        }

        startDate = nearestDate;
        searchParams.checkInBeg = formatDateYYYYMMDD(nearestDate);
        searchParams.checkInEnd = formatDateYYYYMMDD(endDate);
      }

      if (startDate && endDate) {
        if (startDate < firstAvailableDate) {
          startDate = firstAvailableDate;
        }
        if (endDate > lastAvailableDate) {
          endDate = lastAvailableDate;
        }
        if (startDate > endDate) {
          endDate = startDate;
        }

        datePickerInstance.setDate([startDate, endDate], true);
        datePickerInstance.jumpToDate(startDate);
      }
    }
  }

  async function loadNights() {
    try {
      const data = await samoAjax("excursion_nights", {
        TOWNFROMINC: townFromInc,
        STATEINC: stateInc,
        TOURS: tours,
      });

      if (data?.SearchExcursion_NIGHTS) {
        const nightsDataObj = data.SearchExcursion_NIGHTS;
        if (nightsDataObj.nights?.night) {
          const nights = Array.isArray(nightsDataObj.nights.night) ? nightsDataObj.nights.night : [nightsDataObj.nights.night];
          if (nights.length > 0) {
            const nightsNumbers = nights.map((n) => parseInt(n, 10)).filter((n) => !isNaN(n));
            if (nightsNumbers.length > 0) {
              nightsData = {
                from: Math.min(...nightsNumbers),
                till: Math.max(...nightsNumbers),
                available: nightsNumbers.sort((a, b) => a - b),
              };
            }
          }
        }
        if (nightsDataObj.default && !nightsData) {
          nightsData = {
            from: parseInt(nightsDataObj.default.from || 7, 10),
            till: parseInt(nightsDataObj.default.till || 7, 10),
            available: null,
          };
        }
      }

      if (!nightsData) {
        nightsData = { from: 7, till: 7, available: null };
      }

      updateNightsDropdown();
    } catch (error) {
      nightsData = { from: 7, till: 7, available: null };
      updateNightsDropdown();
    }
  }

  async function loadPrices(forceRefresh = false) {
    if (!nightsData) {
      await loadNights(forceRefresh);
    }

    if (!searchParams.checkInBeg || !searchParams.checkInEnd) {
      return;
    }

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

      if (forceRefresh) {
        params._force_refresh = true;
      }

      const data = await samoAjax("excursion_prices", params);

      let prices = [];

      if (data?.SearchExcursion_PRICES?.prices) {
        prices = Array.isArray(data.SearchExcursion_PRICES.prices)
          ? data.SearchExcursion_PRICES.prices
          : [data.SearchExcursion_PRICES.prices];
      }

      allPricesData = prices;
      updateStarFilter(prices);
      displayPrices(prices);
    } catch (error) {
      pricesList.classList.remove("is-loading");
      pricesList.innerHTML = '<div class="tour-prices__error">Ошибка загрузки цен</div>';
    }
  }

  async function reloadPrices() {
    pricesList.classList.add("is-loading");
    pricesList.innerHTML = "";

    sendGTMEvent("tour_prices_search", {
      nights_from: searchParams.nightsFrom,
      nights_till: searchParams.nightsTill,
      checkin_beg: searchParams.checkInBeg,
      checkin_end: searchParams.checkInEnd,
      adult: searchParams.adult,
      child: searchParams.child,
    });

    await loadPrices(true);
  }

  function initFilters() {
    if (nightsSelect) {
      const nightsContainer = document.querySelector(".tour-prices__wrap .gtm-nights-select");
      if (nightsContainer) {
        dropdown(".tour-prices__wrap .gtm-nights-select");
      }
    }

    if (personsSelect) {
      const personsContainer = document.querySelector(".tour-prices__wrap .gtm-persons-select");
      if (personsContainer) {
        dropdown(".tour-prices__wrap .gtm-persons-select");
      }
    }

    if (datepicker) {
      datePickerInstance = flatpickr(datepicker, {
        mode: "range",
        locale: Russian,
        dateFormat: "d.m",
        onChange: (selectedDates) => {
          if (selectedDates.length === 2) {
            searchParams.checkInBeg = formatDateYYYYMMDD(selectedDates[0]);
            searchParams.checkInEnd = formatDateYYYYMMDD(selectedDates[1]);
            reloadPrices();
          }
        },
      });
    }

    if (personsSelect) {
      peopleCounter({
        rootSelector: ".tour-prices__wrap .gtm-persons-select",
        outputSelector: ".tour-prices__wrap .gtm-people-total",
        maxAdults: 3,
        maxChildren: 3,
        onChange: ({ adults, children }) => {
          searchParams.adult = adults;
          searchParams.child = children;
          reloadPrices();
        },
      });
    }

    if (starFilter) {
      starFilter.innerHTML = "";

      starFilterChoice = new Choices(starFilter, {
        ...CHOICES_RU,
        searchEnabled: false,
        shouldSort: false,
      });

      starFilterChoice.passedElement.element.addEventListener("change", () => {
        currentStarFilter = starFilterChoice.getValue(true) || "";

        sendGTMEvent("tour_star_filter_changed", {
          star_filter: currentStarFilter || "all",
        });

        if (allPricesData.length > 0 && searchParams.checkInBeg && searchParams.checkInEnd) {
          displayPrices(allPricesData);
        }
      });
    }
  }

  async function init() {
    initFilters();

    await loadNights();
    await loadAvailableDates();

    if (nightsData) {
      searchParams.nightsFrom = nightsData.from;
      searchParams.nightsTill = nightsData.till;

      const nightsValue = nightsSelect?.querySelector(".gtm-nights-select-value");
      if (nightsValue) {
        if (nightsData.from === nightsData.till) {
          nightsValue.textContent = `${nightsData.from} ночей`;
        } else {
          nightsValue.textContent = `${nightsData.from} - ${nightsData.till} ночей`;
        }
      }
    }

    function buildBookingUrl() {
      return buildBookingUrlForHotel(null);
    }

    bookBtn.addEventListener("click", () => {
      const bookingUrl = buildBookingUrl();

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

      window.open(bookingUrl, "_blank", "noopener,noreferrer");
    });
  }

  init();
};
