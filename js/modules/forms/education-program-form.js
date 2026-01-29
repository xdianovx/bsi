import MicroModal from "micromodal";
import IMask from "imask";

/**
 * Модуль для работы с формой бронирования образовательной программы
 */

let phoneMaskInstance = null;
let currentProgramData = null;

/**
 * Форматирование цены
 */
function formatPrice(price) {
  if (!price && price !== 0) return "";
  const num = parseInt(price, 10);
  if (isNaN(num)) return price;
  return num.toLocaleString("ru-RU") + " ₽";
}

/**
 * Парсинг цены из строки (убираем все кроме цифр)
 */
function parsePrice(priceStr) {
  if (!priceStr) return 0;
  if (typeof priceStr === "number") return priceStr;
  const cleaned = priceStr.replace(/[^\d]/g, "");
  return parseInt(cleaned, 10) || 0;
}

/**
 * Расчет итоговой цены
 */
function calculateTotal() {
  if (!currentProgramData) return 0;

  const basePrice = parsePrice(currentProgramData.price);
  let total = basePrice;

  console.log("[Modal] Base price:", basePrice, "raw:", currentProgramData.price);

  // Проверяем чекбокс визы
  const visaCheckbox = document.querySelector('.js-modal-services-list input[data-service-type="visa"]');
  if (visaCheckbox && visaCheckbox.checked) {
    const visaPrice = parsePrice(currentProgramData.visaPrice);
    total += visaPrice;
    console.log("[Modal] + Visa:", visaPrice);
  }

  // Проверяем дополнительные услуги
  const serviceCheckboxes = document.querySelectorAll('.js-modal-services-list input[data-service-type="additional"]');
  serviceCheckboxes.forEach((checkbox) => {
    if (checkbox.checked) {
      const servicePrice = parsePrice(checkbox.dataset.servicePrice);
      total += servicePrice;
      console.log("[Modal] + Service:", checkbox.dataset.serviceTitle, servicePrice);
    }
  });

  console.log("[Modal] Total:", total);
  return total;
}

/**
 * Обновление отображения итоговой цены
 */
function updateTotalDisplay() {
  const totalEl = document.querySelector(".js-modal-total");
  if (totalEl) {
    const total = calculateTotal();
    totalEl.textContent = formatPrice(total);

    // Обновляем скрытое поле формы
    const totalInput = document.querySelector(".js-form-total-price");
    if (totalInput) {
      totalInput.value = total;
    }
  }
}

/**
 * Получение списка выбранных услуг
 */
function getSelectedServices() {
  const services = [];

  const visaCheckbox = document.querySelector('.js-modal-services-list input[data-service-type="visa"]');
  if (visaCheckbox && visaCheckbox.checked) {
    services.push({
      title: "Оформление визы",
      price: parsePrice(currentProgramData.visaPrice),
    });
  }

  const serviceCheckboxes = document.querySelectorAll('.js-modal-services-list input[data-service-type="additional"]');
  serviceCheckboxes.forEach((checkbox) => {
    if (checkbox.checked) {
      services.push({
        title: checkbox.dataset.serviceTitle,
        price: parsePrice(checkbox.dataset.servicePrice),
      });
    }
  });

  return services;
}

/**
 * Рендер секции дополнительных услуг
 */
function renderServices(programData) {
  const servicesContainer = document.querySelector(".js-modal-services");
  const servicesList = document.querySelector(".js-modal-services-list");

  if (!servicesContainer || !servicesList) return;

  let html = "";
  let hasServices = false;

  // Виза
  if (programData.visaRequired && programData.visaPrice) {
    hasServices = true;
    html += `
      <label class="modal-program-booking__service-item">
        <input
          type="checkbox"
          class="modal-program-booking__service-checkbox"
          data-service-type="visa"
          data-service-price="${programData.visaPrice}"
          checked
        >
        <span class="modal-program-booking__service-checkmark"></span>
        <span class="modal-program-booking__service-text">Оформление визы</span>
        <span class="modal-program-booking__service-price">${formatPrice(programData.visaPrice)}</span>
      </label>
    `;
  }

  // Дополнительные услуги
  if (programData.services && programData.services.length > 0) {
    hasServices = true;
    programData.services.forEach((service, index) => {
      html += `
        <label class="modal-program-booking__service-item">
          <input
            type="checkbox"
            class="modal-program-booking__service-checkbox"
            data-service-type="additional"
            data-service-price="${service.price}"
            data-service-title="${service.title}"
          >
          <span class="modal-program-booking__service-checkmark"></span>
          <span class="modal-program-booking__service-text">${service.title}</span>
          <span class="modal-program-booking__service-price">${formatPrice(service.price)}</span>
        </label>
        ${service.note ? `<div class="modal-program-booking__service-note">${service.note}</div>` : ""}
      `;
    });
  }

  servicesList.innerHTML = html;
  servicesContainer.style.display = hasServices ? "block" : "none";

  // Добавляем обработчики на чекбоксы
  const checkboxes = servicesList.querySelectorAll("input[type='checkbox']");
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", () => {
      updateTotalDisplay();
      updateSelectedServicesInput();
    });
  });
}

/**
 * Обновление скрытого поля с выбранными услугами
 */
function updateSelectedServicesInput() {
  const input = document.querySelector(".js-form-selected-services");
  if (input) {
    input.value = JSON.stringify(getSelectedServices());
  }
}

/**
 * Заполнение модального окна данными программы
 */
function populateModal(programData) {
  currentProgramData = programData;

  // Заголовок
  const titleEl = document.getElementById("modal-program-booking-title");
  if (titleEl) {
    titleEl.textContent = programData.title || "";
  }

  // Мета
  const dateEl = document.querySelector(".js-modal-date");
  const ageEl = document.querySelector(".js-modal-age");
  const durationEl = document.querySelector(".js-modal-duration");

  if (dateEl) {
    dateEl.textContent = programData.date || "";
    dateEl.style.display = programData.date ? "" : "none";
    // Скрываем сепаратор если нет даты
    const nextSep = dateEl.nextElementSibling;
    if (nextSep && nextSep.classList.contains("modal-program-booking__meta-separator")) {
      nextSep.style.display = programData.date ? "" : "none";
    }
  }

  if (ageEl) {
    ageEl.textContent = programData.age || "";
    ageEl.style.display = programData.age ? "" : "none";
    const nextSep = ageEl.nextElementSibling;
    if (nextSep && nextSep.classList.contains("modal-program-booking__meta-separator")) {
      nextSep.style.display = programData.age && programData.duration ? "" : "none";
    }
  }

  if (durationEl) {
    durationEl.textContent = programData.duration || "";
    durationEl.style.display = programData.duration ? "" : "none";
  }

  // Проживание/питание
  const accommodationEl = document.querySelector(".js-modal-accommodation");
  if (accommodationEl) {
    accommodationEl.textContent = programData.accommodation || "";
    accommodationEl.style.display = programData.accommodation ? "" : "none";
  }

  // Услуги
  renderServices(programData);

  // Обновляем итого
  updateTotalDisplay();

  // Заполняем скрытые поля формы
  const formTitleInput = document.querySelector(".js-form-program-title");
  const formDateInput = document.querySelector(".js-form-program-date");
  const formPriceInput = document.querySelector(".js-form-program-price");
  const formSchoolInput = document.querySelector(".js-form-school-name");
  const formPageUrlInput = document.querySelector(".js-form-page-url");

  if (formTitleInput) formTitleInput.value = programData.title || "";
  if (formDateInput) formDateInput.value = programData.date || "";
  if (formPriceInput) formPriceInput.value = parsePrice(programData.price);
  if (formSchoolInput) formSchoolInput.value = programData.schoolName || "";
  if (formPageUrlInput) formPageUrlInput.value = window.location.href;

  updateSelectedServicesInput();
}

/**
 * Сброс формы
 */
function resetForm() {
  const form = document.querySelector(".js-program-booking-form");

  if (form) {
    form.reset();
    form.style.display = "";
    // Убираем ошибки
    form.querySelectorAll(".js-field-error").forEach((el) => {
      el.textContent = "";
    });
    form.querySelectorAll(".modal-program-booking__input--error").forEach((el) => {
      el.classList.remove("modal-program-booking__input--error");
    });
  }
}

/**
 * Показать ошибку поля
 */
function showFieldError(fieldName, message) {
  const errorEl = document.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
  const input = document.querySelector(`[data-field="${fieldName}"]`);

  if (errorEl) {
    errorEl.textContent = message;
  }
  if (input) {
    input.classList.add("modal-program-booking__input--error");
  }
}

/**
 * Очистка ошибок
 */
function clearErrors() {
  document.querySelectorAll(".js-field-error").forEach((el) => {
    el.textContent = "";
  });
  document.querySelectorAll(".modal-program-booking__input--error").forEach((el) => {
    el.classList.remove("modal-program-booking__input--error");
  });
}

/**
 * Валидация формы
 */
function validateForm() {
  const errors = {};
  const form = document.querySelector(".js-program-booking-form");
  if (!form) return errors;

  const name = form.querySelector('[name="name"]').value.trim();
  const email = form.querySelector('[name="email"]').value.trim();
  const phone = form.querySelector('[name="phone"]').value.trim();

  if (!name) {
    errors.name = "Введите имя";
  }

  if (!email) {
    errors.email = "Введите email";
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errors.email = "Введите корректный email";
  }

  if (!phone) {
    errors.phone = "Введите телефон";
  } else {
    const phoneDigits = phone.replace(/\D/g, "");
    if (phoneDigits.length < 11) {
      errors.phone = "Введите полный номер телефона";
    }
  }

  return errors;
}

/**
 * Отправка формы
 */
async function submitForm(e) {
  e.preventDefault();

  clearErrors();

  const errors = validateForm();
  if (Object.keys(errors).length > 0) {
    Object.entries(errors).forEach(([field, message]) => {
      showFieldError(field, message);
    });
    return;
  }

  const form = document.querySelector(".js-program-booking-form");
  const submitBtn = form.querySelector(".modal-program-booking__submit");

  // Блокируем кнопку
  submitBtn.disabled = true;
  submitBtn.textContent = "Отправка...";

  try {
    const formData = new FormData(form);

    const response = await fetch(ajax.url, {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      // Закрываем модалку бронирования
      MicroModal.close("modal-program-booking");

      // Открываем модалку успеха с небольшой задержкой для плавности
      setTimeout(() => {
        MicroModal.show("modal-program-booking-success", {
          awaitCloseAnimation: true,
          onClose: () => {
            resetForm();
          },
        });

        // Авто-закрытие через 2 секунды
        setTimeout(() => {
          MicroModal.close("modal-program-booking-success");
        }, 2000);
      }, 300);
    } else {
      // Показываем ошибки с сервера
      if (result.data && result.data.errors) {
        Object.entries(result.data.errors).forEach(([field, message]) => {
          showFieldError(field, message);
        });
      } else {
        alert(result.data?.message || "Произошла ошибка при отправке");
      }
    }
  } catch (error) {
    console.error("Form submit error:", error);
    alert("Произошла ошибка при отправке. Попробуйте позже.");
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = "Отправить заявку";
  }
}

/**
 * Открытие модального окна с данными программы
 */
export function openProgramModal(programData) {
  console.log("[Modal] Opening with data:", programData);
  resetForm();
  populateModal(programData);

  MicroModal.show("modal-program-booking", {
    disableScroll: true,
    disableFocus: false,
    awaitOpenAnimation: true,
    awaitCloseAnimation: true,
    onClose: () => {
      resetForm();
    },
  });
}

/**
 * Инициализация модуля
 */
export function initEducationProgramForm() {
  const modal = document.getElementById("modal-program-booking");
  if (!modal) return;

  // Инициализируем маску телефона
  const phoneInput = modal.querySelector(".js-phone-mask");
  if (phoneInput && !phoneMaskInstance) {
    phoneMaskInstance = IMask(phoneInput, {
      mask: "+{7} (000) 000-00-00",
      lazy: false,
      placeholderChar: "_",
    });
  }

  // Обработчик отправки формы
  const form = modal.querySelector(".js-program-booking-form");
  if (form) {
    form.addEventListener("submit", submitForm);

    // Очистка ошибок при вводе
    form.querySelectorAll("input, textarea").forEach((input) => {
      input.addEventListener("input", () => {
        const fieldName = input.dataset.field;
        if (fieldName) {
          const errorEl = document.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
          if (errorEl) errorEl.textContent = "";
          input.classList.remove("modal-program-booking__input--error");
        }
      });
    });
  }

  // Обработчики кнопок бронирования
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-program-booking-btn");
    if (!btn) return;

    e.preventDefault();

    // Собираем данные из data-атрибутов
    const programData = {
      title: btn.dataset.programTitle || "",
      date: btn.dataset.programDate || "",
      age: btn.dataset.programAge || "",
      duration: btn.dataset.programDuration || "",
      accommodation: btn.dataset.programAccommodation || "",
      price: btn.dataset.programPrice || "0",
      visaRequired: btn.dataset.programVisaRequired === "1",
      visaPrice: btn.dataset.programVisaPrice || "0",
      services: [],
      schoolName: btn.dataset.schoolName || "",
    };

    // Парсим услуги из JSON
    if (btn.dataset.programServices) {
      try {
        programData.services = JSON.parse(btn.dataset.programServices);
      } catch (err) {
        console.error("Error parsing services:", err);
      }
    }

    openProgramModal(programData);
  });
}
