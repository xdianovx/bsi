import MicroModal from "micromodal";
import IMask from "imask";

/**
 * Модуль для работы с формой бронирования билета на событийный тур
 */

let phoneMaskInstance = null;
let currentTicketData = null;

/**
 * Форматирование цены
 */
function formatPrice(price) {
  if (!price && price !== 0) return "";
  const num = parseInt(price, 10);
  if (isNaN(num)) return price;
  return num.toLocaleString("ru-RU") + " руб.";
}

/**
 * Парсинг цены из строки
 */
function parsePrice(priceStr) {
  if (!priceStr) return 0;
  if (typeof priceStr === "number") return priceStr;
  const cleaned = priceStr.replace(/[^\d]/g, "");
  return parseInt(cleaned, 10) || 0;
}

/**
 * Заполнение модального окна данными билета
 */
function populateModal(ticketData) {
  currentTicketData = ticketData;

  // Заголовок события
  const titleEl = document.getElementById("modal-event-ticket-booking-title");
  if (titleEl) {
    titleEl.textContent = ticketData.eventTitle || "";
  }

  // Мета информация
  const venueEl = document.querySelector(".js-modal-venue");
  const timeEl = document.querySelector(".js-modal-time");

  if (venueEl) {
    venueEl.textContent = ticketData.eventVenue || "";
    venueEl.style.display = ticketData.eventVenue ? "" : "none";
    const nextSep = venueEl.nextElementSibling;
    if (nextSep && nextSep.classList.contains("modal-program-booking__meta-separator")) {
      nextSep.style.display = ticketData.eventVenue && ticketData.eventTime ? "" : "none";
    }
  }

  if (timeEl) {
    timeEl.textContent = ticketData.eventTime || "";
    timeEl.style.display = ticketData.eventTime ? "" : "none";
  }

  // Тип билета
  const ticketTypeEl = document.querySelector(".js-modal-ticket-type");
  if (ticketTypeEl) {
    ticketTypeEl.textContent = ticketData.ticketType || "";
  }

  // Цена билета
  const ticketPriceEl = document.querySelector(".js-modal-ticket-price");
  if (ticketPriceEl) {
    ticketPriceEl.textContent = formatPrice(ticketData.ticketPrice);
  }

  // Заполняем скрытые поля формы
  const formEventTitleInput = document.querySelector(".js-form-event-title");
  const formEventVenueInput = document.querySelector(".js-form-event-venue");
  const formEventTimeInput = document.querySelector(".js-form-event-time");
  const formTicketTypeInput = document.querySelector(".js-form-ticket-type");
  const formTicketPriceInput = document.querySelector(".js-form-ticket-price");
  const formPageUrlInput = document.querySelector(".js-form-page-url");

  if (formEventTitleInput) formEventTitleInput.value = ticketData.eventTitle || "";
  if (formEventVenueInput) formEventVenueInput.value = ticketData.eventVenue || "";
  if (formEventTimeInput) formEventTimeInput.value = ticketData.eventTime || "";
  if (formTicketTypeInput) formTicketTypeInput.value = ticketData.ticketType || "";
  if (formTicketPriceInput) formTicketPriceInput.value = parsePrice(ticketData.ticketPrice);
  if (formPageUrlInput) formPageUrlInput.value = window.location.href;
}

/**
 * Сброс формы
 */
function resetForm() {
  const form = document.querySelector(".js-event-ticket-booking-form");

  if (form) {
    form.reset();
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
  const form = document.querySelector(".js-event-ticket-booking-form");
  if (!form) return errors;

  const name = form.querySelector('[name="name"]').value.trim();
  const email = form.querySelector('[name="email"]').value.trim();
  const phone = form.querySelector('[name="phone"]').value.trim();
  const quantity = form.querySelector('[name="quantity"]').value;

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

  if (!quantity || quantity < 1) {
    errors.quantity = "Укажите количество билетов";
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

  const form = document.querySelector(".js-event-ticket-booking-form");
  const submitBtn = form.querySelector(".modal-program-booking__submit");

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
      MicroModal.close("modal-event-ticket-booking");

      setTimeout(() => {
        MicroModal.show("modal-event-booking-success", {
          awaitCloseAnimation: true,
          onClose: () => {
            resetForm();
          },
        });

        setTimeout(() => {
          MicroModal.close("modal-event-booking-success");
        }, 2000);
      }, 300);
    } else {
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
 * Открытие модального окна с данными билета
 */
export function openEventTicketModal(ticketData) {
  console.log("[Event Ticket Modal] Opening with data:", ticketData);
  resetForm();
  populateModal(ticketData);

  MicroModal.show("modal-event-ticket-booking", {
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
export function initEventTicketForm() {
  const modal = document.getElementById("modal-event-ticket-booking");
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
  const form = modal.querySelector(".js-event-ticket-booking-form");
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

  // Обработчики кнопок "Купить" на билетах
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-event-ticket-booking-btn");
    if (!btn) return;

    e.preventDefault();

    const ticketData = {
      eventTitle: btn.dataset.eventTitle || "",
      eventVenue: btn.dataset.eventVenue || "",
      eventTime: btn.dataset.eventTime || "",
      ticketType: btn.dataset.ticketType || "",
      ticketPrice: btn.dataset.ticketPrice || "0",
    };

    openEventTicketModal(ticketData);
  });

  // Обработчик кнопки "Забронировать" в aside
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-event-booking-btn");
    if (!btn) return;

    e.preventDefault();

    const ticketData = {
      eventTitle: btn.dataset.eventTitle || "",
      eventVenue: btn.dataset.eventVenue || "",
      eventTime: btn.dataset.eventTime || "",
      ticketType: "Стандартный билет",
      ticketPrice: btn.dataset.minPrice || "0",
    };

    openEventTicketModal(ticketData);
  });
}
