import Choices from "choices.js";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  searchPlaceholderValue: "Поиск...",
};

export const visaForm = () => {
  const form = document.getElementById("visa-form");
  if (!form) return;

  let choicesInstances = {};

  // Инициализация ChoicesJS для select полей
  function initChoices() {
    const selects = {
      country: ".visa-form__country-select",
      visaType: ".visa-form__visa-type-select",
    };

    Object.keys(selects).forEach((key) => {
      const selector = selects[key];
      const element = form.querySelector(selector);
      if (element && !choicesInstances[key]) {
        choicesInstances[key] = new Choices(element, {
          ...CHOICES_RU,
          searchEnabled: key === "country",
          shouldSort: false,
        });
      }
    });
  }



  // Валидация формы
  function validateForm() {
    const errors = {};

    // Проверка страны
    const countrySelect = form.querySelector(".visa-form__country-select");
    if (countrySelect) {
      const countryValue = choicesInstances.country ? choicesInstances.country.getValue(true) : countrySelect.value;
      if (!countryValue || countryValue === "") {
        errors.country_id = "Выберите страну";
      }
    }

    // Тип визы необязательный - проверку не делаем

    // Проверка имени
    const nameInput = form.querySelector('[name="name"]');
    if (!nameInput || !nameInput.value.trim()) {
      errors.name = "Введите имя";
    }

    // Проверка гражданства
    const citizenshipInput = form.querySelector('[name="citizenship"]');
    if (!citizenshipInput || !citizenshipInput.value.trim()) {
      errors.citizenship = "Введите гражданство";
    }

    // Проверка телефона
    const phoneInput = form.querySelector('[name="phone"]');
    if (!phoneInput || !phoneInput.value.trim()) {
      errors.phone = "Введите телефон";
    } else {
      // Проверка что телефон заполнен (минимум 10 цифр)
      const phoneDigits = phoneInput.value.replace(/\D/g, "");
      if (phoneDigits.length < 10) {
        errors.phone = "Введите корректный номер телефона";
      }
    }

    // Проверка дат поездки
    const travelDatesInput = form.querySelector('[name="travel_dates"]');
    if (!travelDatesInput || !travelDatesInput.value.trim()) {
      errors.travel_dates = "Укажите даты поездки";
    }

    return errors;
  }

  // Показать ошибки полей
  function showFieldError(fieldName, message) {
    const errorEl = form.querySelector(`[data-field="${fieldName}"]`);

    if (errorEl) {
      errorEl.textContent = message;
      errorEl.style.display = "block";
      errorEl.style.visibility = "visible";
      errorEl.style.opacity = "1";
      errorEl.style.color = "#dc2626";
    }

    // Обработка обычных input полей
    const inputEl = form.querySelector(`[name="${fieldName}"]`) || form.querySelector(`#${fieldName}`);
    if (inputEl) {
      inputEl.classList.add("error");
    }

    // Обработка ChoicesJS select (country_id)
    if (fieldName === "country_id" && choicesInstances.country) {
      const choicesEl = choicesInstances.country.containerOuter.element;
      if (choicesEl) {
        choicesEl.classList.add("is-error");
      }
    }

    // Обработка ChoicesJS select (visa_type)
    if (fieldName === "visa_type" && choicesInstances.visaType) {
      const choicesEl = choicesInstances.visaType.containerOuter.element;
      if (choicesEl) {
        choicesEl.classList.add("is-error");
      }
    }

  }

  // Скролл к первому полю с ошибкой
  function scrollToFirstError() {
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
        targetElement = inputEl.closest(".input-item") || inputEl.closest(".select-item") || inputEl.closest(".form-group") || inputEl;
      } else if (fieldName === "country_id" || fieldName === "visa_type") {
        const filterFields = form.querySelectorAll(".education-programs-filter__field");
        filterFields.forEach((item) => {
          const select = item.querySelector("select");
          if (select && (select.name === fieldName || select.classList.contains(fieldName === "country_id" ? "visa-form__country-select" : "visa-form__visa-type-select"))) {
            targetElement = item;
          }
        });
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


    const statusEl = document.getElementById("visa-form-status");
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

    if (fieldName === "visa_type" && choicesInstances.visaType) {
      const choicesEl = choicesInstances.visaType.containerOuter.element;
      if (choicesEl) {
        choicesEl.classList.remove("is-error");
      }
    }

  }

  // Показать статус
  function showStatus(message, type) {
    const statusEl = document.getElementById("visa-form-status");
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

    if (Object.keys(errors).length > 0) {
      // Показываем все ошибки
      Object.keys(errors).forEach((field) => {
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
    if (choicesInstances.country) {
      const countryValue = choicesInstances.country.getValue(true);
      if (countryValue) {
        formData.set("country_id", countryValue);
      }
    }

    if (choicesInstances.visaType) {
      const visaTypeValue = choicesInstances.visaType.getValue(true);
      if (visaTypeValue) {
        formData.set("visa_type", visaTypeValue);
      }
    }

    // Даты поездки - просто текстовое поле
    const travelDatesInput = form.querySelector('[name="travel_dates"]');
    if (travelDatesInput && travelDatesInput.value) {
      formData.set("travel_dates", travelDatesInput.value);
    }

    // Добавляем action для AJAX
    formData.append("action", "visa_form");

    // reCAPTCHA v3
    if (typeof ajax !== "undefined" && ajax.recaptchaSiteKey) {
      if (typeof grecaptcha === "undefined") {
        showStatus("Подождите, загрузка проверки…", "loading");
        return;
      }
      try {
        const token = await grecaptcha.execute(ajax.recaptchaSiteKey, {
          action: "submit",
        });
        formData.append("recaptcha_token", token);
      } catch (err) {
        showStatus("Ошибка проверки. Попробуйте ещё раз.", "error");
        return;
      }
    }

    try {
      const response = await fetch(ajax.url, {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

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


      } else {
        // Показываем ошибки полей
        if (result.data && result.data.errors) {
          clearErrors();

          const errorFields = Object.keys(result.data.errors);
          errorFields.forEach((field) => {
            showFieldError(field, result.data.errors[field]);
          });
        }
        showStatus(
          result.data?.errors?.recaptcha ||
            result.data?.message ||
            "Ошибка отправки",
          "error"
        );

        setTimeout(() => {
          scrollToFirstError();
        }, 200);
      }
    } catch (error) {
      console.error("Visa form error:", error);
      showStatus("Ошибка сети", "error");
    }
  });

  // Инициализация автоматической очистки ошибок при изменении значений
  function initErrorAutoClear() {
    // Очистка ошибок для обычных input полей
    const inputFields = form.querySelectorAll('input[type="text"], input[type="tel"]');
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
              visaType: "visa_type",
            };
            const fieldName = fieldMap[key];
            if (fieldName) {
              clearFieldError(fieldName);
            }
          });
        }
      }
    });
  }

  // Инициализация всех компонентов
  initChoices();
  initErrorAutoClear();
};
