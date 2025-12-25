import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { peopleCounter } from "../gtm-people-counter.js";
import { dropdown } from "./dropdown.js";
import { createDayRange } from "./day-range.js";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  searchPlaceholderValue: "Поиск...",
};

export const fitForm = () => {
  const form = document.getElementById("simple-form");
  if (!form) return;

  const clientTypeInput = document.getElementById("client_type");
  const corporateFields = document.querySelector(".fit-form__corporate-fields");
  const clientTabs = document.querySelectorAll(".fit-form__client-tab");
  let peopleCounterInstance = null;
  let datePickerInstance = null;
  let dayRangeInstance = null;
  let choicesInstances = {};

  // Переключение типа клиента
  function initClientTypeTabs() {
    // Функция для обновления состояния полей
    function updateClientTypeFields(clientType) {
      if (clientTypeInput) {
        clientTypeInput.value = clientType;
      }

      if (corporateFields) {
        if (clientType === "corporate") {
          corporateFields.style.display = "block";
          // Делаем поля обязательными
          const companyName = document.getElementById("company_name");
          const inn = document.getElementById("inn");
          if (companyName) companyName.setAttribute("required", "required");
          if (inn) inn.setAttribute("required", "required");
        } else {
          corporateFields.style.display = "none";
          // Убираем обязательность
          const companyName = document.getElementById("company_name");
          const inn = document.getElementById("inn");
          if (companyName) companyName.removeAttribute("required");
          if (inn) inn.removeAttribute("required");
        }
      }
    }

    // Инициализация начального состояния (активный таб по умолчанию)
    const activeTab = document.querySelector(".fit-form__client-tab.active");
    if (activeTab) {
      const initialClientType = activeTab.getAttribute("data-client-type");
      updateClientTypeFields(initialClientType);
    }

    // Обработчики кликов на табы
    clientTabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        const clientType = tab.getAttribute("data-client-type");

        // Обновляем активный таб
        clientTabs.forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");

        // Обновляем поля
        updateClientTypeFields(clientType);
      });
    });

    // Инициализация начального состояния (по умолчанию corporate)
    const defaultType = clientTypeInput ? clientTypeInput.value : "corporate";
    if (corporateFields && defaultType === "corporate") {
      corporateFields.style.display = "block";
      const companyName = document.getElementById("company_name");
      const inn = document.getElementById("inn");
      if (companyName) companyName.setAttribute("required", "required");
      if (inn) inn.setAttribute("required", "required");
    }
  }

  // Инициализация ChoicesJS для всех select полей
  function initChoices() {
    const selects = {
      country: ".fit-form__country-select",
    };

    Object.keys(selects).forEach((key) => {
      const selector = selects[key];
      const element = document.querySelector(selector);
      if (element && !choicesInstances[key]) {
        choicesInstances[key] = new Choices(element, {
          ...CHOICES_RU,
          searchEnabled: key === "country",
          shouldSort: false,
        });
      }
    });
  }

  // Форматирование чисел для бюджета
  function formatBudgetNumber(value) {
    // Убираем все нецифровые символы
    let numbers = value.replace(/\D/g, "");

    // Если строка пустая, возвращаем пустую строку
    if (!numbers) {
      return "";
    }

    // Убираем ведущие нули, но если осталась только "0", оставляем её
    // Если после удаления ведущих нулей ничего не осталось, значит были только нули - возвращаем пустую строку
    numbers = numbers.replace(/^0+/, "");

    // Если после удаления ведущих нулей ничего не осталось, возвращаем пустую строку
    if (!numbers) {
      return "";
    }

    // Форматируем с пробелами каждые 3 цифры
    return numbers.replace(/\B(?=(\d{3})+(?!\d))/g, " ");
  }

  // Инициализация форматирования бюджета
  function initBudgetFormatting() {
    const budgetInput = document.getElementById("budget");
    if (budgetInput) {
      budgetInput.addEventListener("input", (e) => {
        const cursorPosition = e.target.selectionStart;
        const oldValue = e.target.value;
        const oldLength = oldValue.length;

        // Форматируем значение
        const formatted = formatBudgetNumber(e.target.value);
        e.target.value = formatted;

        // Вычисляем новую позицию курсора
        const newLength = formatted.length;
        const lengthDiff = newLength - oldLength;

        // Подсчитываем количество пробелов до курсора в старом значении
        const spacesBeforeCursor = (oldValue.substring(0, cursorPosition).match(/\s/g) || []).length;
        // Подсчитываем количество пробелов до курсора в новом значении
        const newSpacesBeforeCursor = (formatted.substring(0, cursorPosition + lengthDiff).match(/\s/g) || []).length;

        // Корректируем позицию курсора с учетом разницы в пробелах
        const newCursorPosition = Math.max(
          0,
          Math.min(formatted.length, cursorPosition + lengthDiff + (newSpacesBeforeCursor - spacesBeforeCursor))
        );
        e.target.setSelectionRange(newCursorPosition, newCursorPosition);
      });

      budgetInput.addEventListener("blur", (e) => {
        if (e.target.value) {
          e.target.value = formatBudgetNumber(e.target.value);
        }
      });
    }
  }

  // Инициализация выбора звездности отеля
  function initHotelStars() {
    const starButtons = document.querySelectorAll(".fit-form__star-btn");
    const hiddenInput = document.getElementById("hotel_stars");

    // Функция для обновления визуального отображения звезд
    function updateStarsVisual(button, rating) {
      const starsRating = button.querySelector(".stars-rating");
      if (!starsRating) return;

      const stars = starsRating.querySelectorAll("svg");
      stars.forEach((star, index) => {
        const starNum = index + 1;
        if (starNum <= rating) {
          star.setAttribute("fill", "#ffd700");
          star.setAttribute("stroke", "#ffd700");
          star.classList.add("filled");
        } else {
          star.setAttribute("fill", "none");
          star.setAttribute("stroke", "currentColor");
          star.classList.remove("filled");
        }
      });
    }

    starButtons.forEach((btn) => {
      const rating = btn.getAttribute("data-stars");

      // Инициализируем визуальное отображение при загрузке
      if (rating !== "any") {
        updateStarsVisual(btn, parseInt(rating));
      }

      btn.addEventListener("click", () => {
        // Убираем активный класс со всех кнопок
        starButtons.forEach((b) => b.classList.remove("active"));
        // Добавляем активный класс к нажатой кнопке
        btn.classList.add("active");
        // Устанавливаем значение в скрытое поле
        if (hiddenInput) {
          hiddenInput.value = rating;
        }

        // Обновляем визуальное отображение всех кнопок при клике
        starButtons.forEach((b) => {
          const bRating = b.getAttribute("data-stars");
          if (bRating !== "any") {
            updateStarsVisual(b, parseInt(bRating));
          }
        });

        // Очистка ошибки при выборе звезд
        clearFieldError("hotel_stars");
      });
    });
  }

  // Инициализация flatpickr для интервала вылета (inline календарь)
  function initDatePicker() {
    const calendarContainer = document.getElementById("departure_range_calendar");
    const hiddenInput = document.getElementById("departure_range");
    if (calendarContainer && hiddenInput && !datePickerInstance) {
      datePickerInstance = flatpickr(calendarContainer, {
        mode: "range",
        minDate: "today",
        locale: Russian,
        dateFormat: "d.m.Y",
        inline: true,
        showMonths: 4,
        disableMobile: true,
        onChange: (selectedDates) => {
          if (selectedDates.length === 2) {
            const startDate = selectedDates[0].toISOString().split("T")[0];
            const endDate = selectedDates[1].toISOString().split("T")[0];
            hiddenInput.value = `${startDate} - ${endDate}`;
          } else if (selectedDates.length === 0) {
            hiddenInput.value = "";
          }
          // Очистка ошибки при изменении даты
          clearFieldError("departure_range");
        },
      });
    }
  }

  // Инициализация выбора продолжительности тура (day range)
  function initDayRange() {
    const dayGrid = document.querySelector(".fit-form__daypicker");
    const hiddenInput = document.getElementById("tour_duration");
    if (dayGrid && hiddenInput && !dayRangeInstance) {
      dayRangeInstance = createDayRange({
        gridEl: dayGrid,
        onChange: ({ startDay, endDay }) => {
          if (startDay !== null && endDay !== null) {
            hiddenInput.value = `${startDay}-${endDay}`;
          } else if (startDay !== null) {
            hiddenInput.value = String(startDay);
          } else {
            hiddenInput.value = "";
          }
          // Очистка ошибки при изменении продолжительности тура
          clearFieldError("tour_duration");
        },
      });
    }
  }

  // Инициализация peopleCounter
  function initPeopleCounter() {
    const peopleSelect = document.querySelector(".fit-form__people-select");
    const peopleTotal = document.querySelector(".fit-form__people-total");

    if (peopleSelect && peopleTotal && !peopleCounterInstance) {
      // Инициализируем dropdown для peopleCounter
      dropdown(".fit-form__people-select");

      // Инициализируем сам счетчик
      peopleCounterInstance = peopleCounter({
        rootEl: peopleSelect,
        outputEl: peopleTotal,
        maxAdults: 10,
        maxChildren: 5,
        onChange: ({ adults, children, ages, total }) => {
          // Можно добавить дополнительную логику при изменении
        },
      });
    }
  }

  // Валидация формы
  function validateForm() {
    const errors = {};
    const clientType = clientTypeInput ? clientTypeInput.value : "corporate";

    // Обязательные поля для всех
    const fullName = form.querySelector('[name="full_name"]');
    const email = form.querySelector('[name="email"]');
    const phone = form.querySelector('[name="phone"]');

    if (!fullName || !fullName.value.trim()) {
      errors.full_name = "Введите ФИО";
    }

    if (!email || !email.value.trim()) {
      errors.email = "Введите email";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
      errors.email = "Неверный формат email";
    }

    if (!phone || !phone.value.trim()) {
      errors.phone = "Введите телефон";
    } else {
      // Проверка что телефон заполнен (минимум 10 цифр)
      const phoneDigits = phone.value.replace(/\D/g, "");
      if (phoneDigits.length < 10) {
        errors.phone = "Введите корректный номер телефона";
      }
    }

    // Обязательные поля для корпоративных клиентов
    if (clientType === "corporate") {
      const companyName = form.querySelector('[name="company_name"]');
      const inn = form.querySelector('[name="inn"]');

      if (!companyName || !companyName.value.trim()) {
        errors.company_name = "Введите название организации";
      }

      if (!inn || !inn.value.trim()) {
        errors.inn = "Введите ИНН организации";
      } else {
        const innDigits = inn.value.replace(/\D/g, "");
        if (innDigits.length !== 10 && innDigits.length !== 12) {
          errors.inn = "ИНН должен содержать 10 или 12 цифр";
        }
      }
    }

    // Проверка обязательных полей
    const countrySelect = form.querySelector(".fit-form__country-select");
    if (countrySelect) {
      const countryValue = choicesInstances.country ? choicesInstances.country.getValue(true) : countrySelect.value;
      if (!countryValue || countryValue === "") {
        errors.country_id = "Выберите страну";
      }
    }

    const departureRange = form.querySelector('[name="departure_range"]');
    if (!departureRange || !departureRange.value.trim()) {
      errors.departure_range = "Выберите интервал вылета";
    }

    const tourDuration = form.querySelector('[name="tour_duration"]');
    if (!tourDuration || !tourDuration.value.trim()) {
      errors.tour_duration = "Выберите продолжительность тура";
    }

    const hotelStars = form.querySelector('[name="hotel_stars"]');
    if (!hotelStars || !hotelStars.value.trim()) {
      errors.hotel_stars = "Выберите звездность отеля";
    }

    const budget = form.querySelector('[name="budget"]');
    if (!budget || !budget.value.trim()) {
      errors.budget = "Укажите бюджет";
    } else {
      // Проверяем, что бюджет содержит хотя бы одну цифру
      const budgetDigits = budget.value.replace(/\D/g, "");
      if (budgetDigits.length === 0) {
        errors.budget = "Укажите корректный бюджет";
      }
    }

    return errors;
  }

  // Показать ошибки полей
  function showFieldError(fieldName, message) {
    console.log(`[showFieldError] Field: ${fieldName}, Message: ${message}`);

    const errorEl = form.querySelector(`[data-field="${fieldName}"]`);

    if (errorEl) {
      errorEl.textContent = message;
      // Убеждаемся, что элемент видим
      errorEl.style.display = "block";
      errorEl.style.visibility = "visible";
      errorEl.style.opacity = "1";
      errorEl.style.color = "#dc2626";
      console.log(`[showFieldError] Error message element updated:`, errorEl);
    } else {
      console.warn(`[showFieldError] Error element not found for field: ${fieldName}`);
    }

    // Обработка обычных input полей
    const inputEl = form.querySelector(`[name="${fieldName}"]`) || form.querySelector(`#${fieldName}`);
    if (inputEl) {
      inputEl.classList.add("error");
      console.log(`[showFieldError] Error class added to input:`, inputEl);
    } else {
      console.warn(`[showFieldError] Input element not found for field: ${fieldName}`);
    }

    // Обработка ChoicesJS select (country_id)
    if (fieldName === "country_id" && choicesInstances.country) {
      const choicesEl = choicesInstances.country.containerOuter.element;
      if (choicesEl) {
        choicesEl.classList.add("is-error");
      }
    }

    // Обработка datepicker (departure_range)
    if (fieldName === "departure_range") {
      const datepickerWrapper = form.querySelector(".fit-form__datepicker-wrapper");
      if (datepickerWrapper) {
        datepickerWrapper.classList.add("error");
      }
    }

    // Обработка day-grid (tour_duration)
    if (fieldName === "tour_duration") {
      const dayPicker = form.querySelector(".fit-form__daypicker");
      if (dayPicker) {
        dayPicker.classList.add("error");
      }
    }

    // Обработка звезд (hotel_stars)
    if (fieldName === "hotel_stars") {
      const starsWrapper = form.querySelector(".fit-form__hotel-stars-wrapper");
      if (starsWrapper) {
        starsWrapper.classList.add("error");
      }
    }
  }

  // Скролл к первому полю с ошибкой
  function scrollToFirstError() {
    // Ищем первое поле с ошибкой по наличию текста в error-message
    const errorMessages = form.querySelectorAll(".error-message[data-field]");
    let firstErrorField = null;

    for (const errorMsg of errorMessages) {
      if (errorMsg.textContent.trim() !== "") {
        firstErrorField = errorMsg;
        break;
      }
    }

    if (firstErrorField) {
      const fieldName = firstErrorField.getAttribute("data-field");
      let targetElement = null;

      // Ищем соответствующий элемент поля
      const inputEl = form.querySelector(`[name="${fieldName}"]`) || form.querySelector(`#${fieldName}`);
      if (inputEl) {
        targetElement = inputEl.closest(".input-item") || inputEl.closest(".form-group") || inputEl;
      } else if (fieldName === "country_id") {
        const selectItem = form.querySelector(".select-item");
        if (selectItem) targetElement = selectItem;
      } else if (fieldName === "departure_range") {
        targetElement = form.querySelector(".fit-form__datepicker-wrapper");
      } else if (fieldName === "tour_duration") {
        targetElement = form.querySelector(".fit-form__duration-wrapper");
      } else if (fieldName === "hotel_stars") {
        targetElement = form.querySelector(".fit-form__hotel-stars-wrapper");
      }

      if (targetElement) {
        const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset - 100;
        window.scrollTo({
          top: offsetTop,
          behavior: "smooth",
        });
      }
    }
  }

  // Очистить ошибки
  function clearErrors() {
    form.querySelectorAll(".error-message").forEach((el) => {
      el.textContent = "";
      // Сбрасываем стили, но оставляем display как есть (будет управляться CSS)
      el.style.visibility = "";
      el.style.opacity = "";
      el.style.color = "";
    });
    form.querySelectorAll(".error").forEach((el) => {
      el.classList.remove("error");
    });

    // Очистка ChoicesJS ошибок
    Object.values(choicesInstances).forEach((choice) => {
      if (choice && choice.containerOuter && choice.containerOuter.element) {
        choice.containerOuter.element.classList.remove("is-error");
      }
    });

    // Очистка специальных контейнеров
    const datepickerWrapper = form.querySelector(".fit-form__datepicker-wrapper");
    if (datepickerWrapper) {
      datepickerWrapper.classList.remove("error");
    }

    const dayPicker = form.querySelector(".fit-form__daypicker");
    if (dayPicker) {
      dayPicker.classList.remove("error");
    }

    const starsWrapper = form.querySelector(".fit-form__hotel-stars-wrapper");
    if (starsWrapper) {
      starsWrapper.classList.remove("error");
    }

    const statusEl = document.getElementById("form-status");
    if (statusEl) {
      statusEl.textContent = "";
      statusEl.className = "";
    }
  }

  // Очистить ошибку конкретного поля
  function clearFieldError(fieldName) {
    const errorEl = form.querySelector(`[data-field="${fieldName}"]`);
    if (errorEl) {
      errorEl.textContent = "";
    }

    const inputEl = form.querySelector(`[name="${fieldName}"]`) || form.querySelector(`#${fieldName}`);
    if (inputEl) {
      inputEl.classList.remove("error");
    }

    // Обработка ChoicesJS select
    if (fieldName === "country_id" && choicesInstances.country) {
      const choicesEl = choicesInstances.country.containerOuter.element;
      if (choicesEl) {
        choicesEl.classList.remove("is-error");
      }
    }

    // Обработка datepicker
    if (fieldName === "departure_range") {
      const datepickerWrapper = form.querySelector(".fit-form__datepicker-wrapper");
      if (datepickerWrapper) {
        datepickerWrapper.classList.remove("error");
      }
    }

    // Обработка day-grid
    if (fieldName === "tour_duration") {
      const dayPicker = form.querySelector(".fit-form__daypicker");
      if (dayPicker) {
        dayPicker.classList.remove("error");
      }
    }

    // Обработка звезд
    if (fieldName === "hotel_stars") {
      const starsWrapper = form.querySelector(".fit-form__hotel-stars-wrapper");
      if (starsWrapper) {
        starsWrapper.classList.remove("error");
      }
    }
  }

  // Показать статус
  function showStatus(message, type) {
    const statusEl = document.getElementById("form-status");
    if (statusEl) {
      statusEl.textContent = message;
      statusEl.className = `form-status ${type}`;
    }
  }

  // Обработчик отправки формы
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Очищаем все предыдущие ошибки
    clearErrors();

    // Валидация перед отправкой
    const errors = validateForm();
    console.log("=== Client-side Validation ===");
    console.log("Errors found:", Object.keys(errors).length);
    console.log("Errors:", errors);

    if (Object.keys(errors).length > 0) {
      // Показываем все ошибки
      Object.keys(errors).forEach((field) => {
        console.log(`Showing client-side error for field: ${field}`, errors[field]);
        showFieldError(field, errors[field]);
      });

      showStatus("Исправьте ошибки в форме", "error");

      // Скролл к первому полю с ошибкой
      setTimeout(() => {
        scrollToFirstError();
      }, 200);

      return;
    }

    showStatus("Отправка...", "loading");

    // Собираем данные формы
    const formData = new FormData(form);

    // Добавляем данные из ChoicesJS
    Object.keys(choicesInstances).forEach((key) => {
      const choice = choicesInstances[key];
      if (choice) {
        const value = choice.getValue(true);
        const fieldMap = {
          country: "country_id",
        };
        if (fieldMap[key] && value) {
          formData.set(fieldMap[key], value);
        }
      }
    });

    // Обрабатываем бюджет - убираем пробелы перед отправкой
    const budgetInput = form.querySelector('[name="budget"]');
    if (budgetInput && budgetInput.value) {
      const budgetValue = budgetInput.value.replace(/\s/g, "");
      if (budgetValue) {
        formData.set("budget", budgetValue);
      }
    }

    // Добавляем данные из dayRange (продолжительность тура)
    const tourDurationInput = form.querySelector('[name="tour_duration"]');
    if (tourDurationInput && tourDurationInput.value) {
      formData.set("tour_duration", tourDurationInput.value);
    }

    // Добавляем данные из peopleCounter
    if (peopleCounterInstance) {
      const peopleState = peopleCounterInstance.getState();
      formData.append("adults_count", peopleState.adults);
      formData.append("children_count", peopleState.children);
      if (peopleState.ages && peopleState.ages.length > 0) {
        formData.append("children_ages", JSON.stringify(peopleState.ages));
      }
    }

    // Добавляем данные из datePicker
    const departureRangeInput = form.querySelector('[name="departure_range"]');
    if (departureRangeInput && departureRangeInput.value) {
      const [startDate, endDate] = departureRangeInput.value.split(" - ");
      if (startDate && endDate) {
        formData.append("departure_start", startDate);
        formData.append("departure_end", endDate);
      }
    }

    // Добавляем action для AJAX
    formData.append("action", "fit_form");

    // Выводим данные в консоль для отладки
    console.log("=== FIT Form Data ===");
    const formDataObj = {};
    for (const [key, value] of formData.entries()) {
      formDataObj[key] = value;
    }
    console.log("Form Data:", formDataObj);

    try {
      const response = await fetch(ajax.url, {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      // Выводим ответ сервера в консоль
      console.log("=== Server Response ===");
      console.log("Success:", result.success);
      console.log("Data:", result.data);

      if (result.success) {
        showStatus("Успешно отправлено!", "success");
        form.reset();

        // Сброс ChoicesJS
        Object.values(choicesInstances).forEach((choice) => {
          if (choice) {
            choice.clearStore();
            choice.setChoiceByValue("");
          }
        });

        // Сброс datePicker
        if (datePickerInstance) {
          datePickerInstance.clear();
        }

        // Сброс dayRange
        if (dayRangeInstance) {
          dayRangeInstance.reset();
        }

        // Сброс peopleCounter
        if (peopleCounterInstance) {
          peopleCounterInstance.setState({ adults: 2, children: 0, ages: [] });
        }

        // Закрываем dropdown если открыт
        const peopleSelect = document.querySelector(".fit-form__people-select");
        if (peopleSelect && peopleSelect.classList.contains("is-open")) {
          peopleSelect.classList.remove("is-open");
        }
      } else {
        // Выводим ошибки в консоль
        console.log("=== Form Errors ===");
        console.log("Errors:", result.data?.errors);
        console.log("Message:", result.data?.message);

        // Показываем ошибки полей
        if (result.data && result.data.errors) {
          // Очищаем все предыдущие ошибки перед показом новых
          clearErrors();

          // Показываем все ошибки с сервера
          const errorFields = Object.keys(result.data.errors);
          console.log(`Total errors to show: ${errorFields.length}`, errorFields);

          errorFields.forEach((field) => {
            console.log(`Showing error for field: ${field}`, result.data.errors[field]);
            showFieldError(field, result.data.errors[field]);
          });
        }
        showStatus(result.data?.message || "Ошибка отправки", "error");

        // Скролл к первому полю с ошибкой
        setTimeout(() => {
          scrollToFirstError();
        }, 200);
      }
    } catch (error) {
      console.error("Form submission error:", error);
      showStatus("Ошибка сети", "error");
    }
  });

  // Инициализация автоматической очистки ошибок при изменении значений
  function initErrorAutoClear() {
    // Очистка ошибок для обычных input полей
    const inputFields = form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea');
    inputFields.forEach((input) => {
      input.addEventListener("input", () => {
        const fieldName = input.getAttribute("name") || input.getAttribute("id");
        if (fieldName) {
          clearFieldError(fieldName);
        }
      });
    });

    // Очистка ошибок для ChoicesJS
    Object.keys(choicesInstances).forEach((key) => {
      const choice = choicesInstances[key];
      if (choice) {
        const element = choice.passedElement.element;
        if (element) {
          element.addEventListener("change", () => {
            const fieldMap = {
              country: "country_id",
            };
            const fieldName = fieldMap[key];
            if (fieldName) {
              clearFieldError(fieldName);
            }
          });
        }
      }
    });

    // Очистка ошибок для Flatpickr
    if (datePickerInstance) {
      const calendarContainer = document.getElementById("departure_range_calendar");
      if (calendarContainer) {
        // Очистка ошибки при изменении даты через onChange callback
        // Это уже обрабатывается в initDatePicker через onChange
      }
    }

    // Очистка ошибок для dayRange и звезд уже обрабатывается в их соответствующих функциях инициализации
    // Очистка ошибок для peopleCounter (обрабатывается через callback в initPeopleCounter)
  }

  // Инициализация всех компонентов
  initClientTypeTabs();
  initChoices();
  initDatePicker();
  initDayRange();
  initBudgetFormatting();
  initHotelStars();
  initPeopleCounter();
  initErrorAutoClear();
};
