import MicroModal from "micromodal";
import IMask from "imask";
import { submitFormWithRecaptcha, RECAPTCHA_NOT_LOADED } from "./form-ajax.js";

/**
 * Заявка по событию: модалка и inline-форма (имя + телефон обязательны; почта и комментарий — нет).
 */

function getModalRoot() {
  return document.getElementById("modal-event-ticket-booking");
}

function getModalForm() {
  const modal = getModalRoot();
  return modal ? modal.querySelector(".js-event-ticket-booking-form") : null;
}

function isMinimalBookingForm(form) {
  const el = form.querySelector('input[name="event_booking_minimal"]');
  return el !== null && el.value === "1";
}

function populateModal(ticketData) {
  const modalRoot = getModalRoot();
  if (!modalRoot) {
    return;
  }

  const titleEl = document.getElementById("modal-event-ticket-booking-title");
  if (titleEl) {
    titleEl.textContent = ticketData.eventTitle || "";
  }

  const form = getModalForm();
  if (!form) {
    return;
  }

  const setVal = (sel, val) => {
    const input = form.querySelector(sel);
    if (input) input.value = val;
  };

  setVal(".js-form-event-title", ticketData.eventTitle || "");
  setVal(".js-form-page-url", window.location.href);
}

function resetModalForm() {
  const form = getModalForm();
  if (!form) return;
  form.reset();
  clearErrors(form);
}

function showFieldError(fieldName, message, form) {
  if (!form) return;
  const errorEl = form.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
  const input = form.querySelector(`[data-field="${fieldName}"]`);

  if (errorEl) {
    errorEl.textContent = message;
  }
  if (input) {
    input.classList.add("modal-program-booking__input--error", "single-event__booking-cta-input--error");
  }
}

function clearErrors(form) {
  if (!form) return;
  form.querySelectorAll(".js-field-error").forEach((el) => {
    el.textContent = "";
  });
  form.querySelectorAll(".modal-program-booking__input--error, .single-event__booking-cta-input--error").forEach((el) => {
    el.classList.remove("modal-program-booking__input--error", "single-event__booking-cta-input--error");
  });
}

function validateForm(form) {
  const errors = {};
  if (!form) return errors;

  const nameEl = form.querySelector('[name="name"]');
  const name = nameEl ? nameEl.value.trim() : "";
  if (!name) {
    errors.name = "Введите имя";
  }

  const emailEl = form.querySelector('[name="email"]');
  if (emailEl) {
    const email = emailEl.value.trim();
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      errors.email = "Введите корректный email";
    }
  }

  const phoneEl = form.querySelector('[name="phone"]');
  const phone = phoneEl ? phoneEl.value.trim() : "";
  if (!phone) {
    errors.phone = "Введите телефон";
  } else {
    const phoneDigits = phone.replace(/\D/g, "");
    if (phoneDigits.length < 11) {
      errors.phone = "Введите полный номер телефона";
    }
  }

  const privacy = form.querySelector('[name="privacy_agreement"]');
  if (!privacy || !privacy.checked) {
    errors.privacy_agreement = "Необходимо согласие на обработку персональных данных";
  }

  return errors;
}

function initPhoneMasks(root) {
  root.querySelectorAll(".js-phone-mask").forEach((input) => {
    if (input.dataset.imaskReady === "1") return;
    IMask(input, {
      mask: "+{7} (000) 000-00-00",
      lazy: false,
      placeholderChar: "_",
    });
    input.dataset.imaskReady = "1";
  });
}

function bindBookingForm(form) {
  if (!form) return;

  form.addEventListener("submit", submitForm);

  form.querySelectorAll("input, textarea").forEach((input) => {
    input.addEventListener("input", () => {
      const fieldName = input.dataset.field;
      if (fieldName) {
        const errorEl = form.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
        if (errorEl) errorEl.textContent = "";
        input.classList.remove("modal-program-booking__input--error", "single-event__booking-cta-input--error");
      }
    });
  });

  const privacyCb = form.querySelector('[name="privacy_agreement"]');
  if (privacyCb) {
    privacyCb.addEventListener("change", () => {
      const err = form.querySelector('.js-field-error[data-error-for="privacy_agreement"]');
      if (err) err.textContent = "";
    });
  }
}

async function submitForm(e) {
  e.preventDefault();

  const form = e.currentTarget;
  if (!(form instanceof HTMLFormElement)) return;

  clearErrors(form);

  const errors = validateForm(form);
  if (Object.keys(errors).length > 0) {
    Object.entries(errors).forEach(([field, message]) => {
      showFieldError(field, message, form);
    });
    return;
  }

  const submitBtn = form.querySelector('button[type="submit"]');
  const defaultLabel = submitBtn?.dataset.defaultLabel || submitBtn?.textContent || "Отправить";

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Отправка...";
  }

  const isMinimal = isMinimalBookingForm(form);

  try {
    const formData = new FormData(form);

    const result = await submitFormWithRecaptcha(formData, {
      debug: false,
    });

    if (result.success) {
      if (!isMinimal) {
        MicroModal.close("modal-event-ticket-booking");
      }

      setTimeout(() => {
        MicroModal.show("modal-event-booking-success", {
          awaitCloseAnimation: true,
          onClose: () => {
            if (isMinimal) {
              form.reset();
              clearErrors(form);
            } else {
              resetModalForm();
            }
          },
        });

        setTimeout(() => {
          MicroModal.close("modal-event-booking-success");
        }, 2000);
      }, isMinimal ? 0 : 300);
    } else {
      if (result.data && result.data.errors) {
        Object.entries(result.data.errors).forEach(([field, message]) => {
          showFieldError(field, message, form);
        });
      }
      const errMsg =
        result.data?.errors?.recaptcha || result.data?.message || "Произошла ошибка при отправке";
      alert(errMsg);
    }
  } catch (error) {
    if (error.message === RECAPTCHA_NOT_LOADED) {
      alert("Подождите, загрузка проверки…");
    } else {
      console.error("Form submit error:", error);
      alert("Произошла ошибка при отправке. Попробуйте позже.");
    }
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = defaultLabel;
    }
  }
}

export function openEventTicketModal(ticketData) {
  resetModalForm();
  populateModal(ticketData);

  MicroModal.show("modal-event-ticket-booking", {
    disableScroll: true,
    disableFocus: false,
    awaitOpenAnimation: true,
    awaitCloseAnimation: true,
    onClose: () => {
      resetModalForm();
    },
  });
}

export function initEventTicketForm() {
  initPhoneMasks(document);

  const modal = getModalRoot();
  const ctaForm = document.getElementById("event-booking-cta-form");

  if (modal) {
    bindBookingForm(getModalForm());
  }
  if (ctaForm) {
    bindBookingForm(ctaForm);
  }

  if (!modal && !ctaForm) {
    return;
  }

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-event-ticket-booking-btn");
    if (!btn) return;

    e.preventDefault();

    const ticketData = {
      eventTitle: btn.dataset.eventTitle || "",
    };

    openEventTicketModal(ticketData);
  });

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-event-booking-btn");
    if (!btn) return;

    e.preventDefault();

    const ticketData = {
      eventTitle: btn.dataset.eventTitle || "",
    };

    openEventTicketModal(ticketData);
  });
}
