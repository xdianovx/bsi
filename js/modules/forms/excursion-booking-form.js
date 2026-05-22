import MicroModal from "micromodal";
import IMask from "imask";
import { submitFormWithRecaptcha, RECAPTCHA_NOT_LOADED } from "./form-ajax.js";

/**
 * Заявка на экскурсию: модалка #modal-excursion-booking.
 * Триггер — .js-excursion-booking-btn с data-excursion-id/title/date.
 * Метрика: ym(108341897, "reachGoal", "excursion_submited").
 */

const YM_ID = 108341897;
const EXCURSION_GOAL = "excursion_submited";

function getModalRoot() {
  return document.getElementById("modal-excursion-booking");
}

function getModalForm() {
  const modal = getModalRoot();
  return modal ? modal.querySelector(".js-excursion-booking-form") : null;
}

function populateModal(data) {
  const form = getModalForm();
  if (!form) return;

  const titleEl = document.getElementById("modal-excursion-booking-title");
  if (titleEl) {
    titleEl.textContent = data.excursionTitle || "Бронирование экскурсии";
  }

  const leadEl = getModalRoot()?.querySelector(".js-excursion-booking-lead");
  if (leadEl) {
    leadEl.textContent = data.excursionDate ? `Дата: ${data.excursionDate}` : "";
  }

  const setVal = (sel, val) => {
    const input = form.querySelector(sel);
    if (input) input.value = val;
  };

  setVal(".js-form-excursion-id", data.excursionId || "");
  setVal(".js-form-excursion-title", data.excursionTitle || "");
  setVal(".js-form-excursion-date", data.excursionDate || "");
  setVal(".js-form-page-url", window.location.href);
}

function reachGoal() {
  if (typeof ym === "undefined") return;
  ym(YM_ID, "reachGoal", EXCURSION_GOAL);
}

function resetForm() {
  const form = getModalForm();
  if (!form) return;
  form.reset();
  clearErrors(form);
}

function showFieldError(fieldName, message, form) {
  if (!form) return;
  const errorEl = form.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
  const input =
    form.querySelector(`[data-field="${fieldName}"]`) || form.querySelector(`[name="${fieldName}"]`);

  if (errorEl) errorEl.textContent = message;
  if (input) {
    const item = input.closest(".input-item");
    if (item) item.classList.add("err");
    else input.classList.add("error");
  }
}

function clearErrors(form) {
  if (!form) return;
  form.querySelectorAll(".js-field-error").forEach((el) => (el.textContent = ""));
  form.querySelectorAll(".input-item.err").forEach((el) => el.classList.remove("err"));
  form.querySelectorAll("input.error, textarea.error").forEach((el) => el.classList.remove("error"));
}

function validateForm(form) {
  const errors = {};
  if (!form) return errors;

  const nameEl = form.querySelector('[name="name"]');
  const name = nameEl ? nameEl.value.trim() : "";
  if (!name) errors.name = "Введите имя";

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
  } else if (phone.replace(/\D/g, "").length < 11) {
    errors.phone = "Введите полный номер телефона";
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

function bindForm(form) {
  if (!form) return;

  form.addEventListener("submit", submitForm);

  form.querySelectorAll("input, textarea").forEach((input) => {
    input.addEventListener("input", () => {
      const fieldName = input.dataset.field || input.name;
      if (!fieldName) return;
      const errorEl = form.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
      if (errorEl) errorEl.textContent = "";
      const item = input.closest(".input-item");
      if (item) item.classList.remove("err");
      input.classList.remove("error");
    });
  });

  const privacyCb = form.querySelector('[name="privacy_agreement"]');
  if (privacyCb) {
    privacyCb.addEventListener("change", () => {
      const err = form.querySelector('.js-field-error[data-error-for="privacy_agreement"]');
      if (err) err.textContent = "";
      const item = privacyCb.closest(".input-item");
      if (item) item.classList.remove("err");
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
    Object.entries(errors).forEach(([field, message]) => showFieldError(field, message, form));
    return;
  }

  const submitBtn = form.querySelector('button[type="submit"]');
  const defaultLabel = submitBtn?.dataset.defaultLabel || submitBtn?.textContent || "Отправить";

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Отправка...";
  }

  try {
    const formData = new FormData(form);

    const result = await submitFormWithRecaptcha(formData, { debug: false });

    if (result.success) {
      reachGoal();

      const isInline = form.id === "excursion-cta-form";

      if (!isInline) {
        MicroModal.close("modal-excursion-booking");
      }

      setTimeout(() => {
        MicroModal.show("modal-excursion-booking-success", {
          awaitCloseAnimation: true,
          onClose: () => {
            if (isInline) {
              form.reset();
              clearErrors(form);
            } else {
              resetForm();
            }
          },
        });

        setTimeout(() => {
          MicroModal.close("modal-excursion-booking-success");
        }, 2000);
      }, isInline ? 0 : 300);
    } else {
      if (result.data && result.data.errors) {
        Object.entries(result.data.errors).forEach(([field, message]) =>
          showFieldError(field, message, form)
        );
      }
      const errMsg =
        result.data?.errors?.recaptcha || result.data?.message || "Произошла ошибка при отправке";
      alert(errMsg);
    }
  } catch (error) {
    if (error.message === RECAPTCHA_NOT_LOADED) {
      alert("Подождите, загрузка проверки…");
    } else {
      console.error("Excursion booking submit error:", error);
      alert("Произошла ошибка при отправке. Попробуйте позже.");
    }
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = defaultLabel;
    }
  }
}

export function openExcursionBookingModal(data) {
  resetForm();
  populateModal(data);

  MicroModal.show("modal-excursion-booking", {
    disableScroll: true,
    disableFocus: false,
    awaitOpenAnimation: true,
    awaitCloseAnimation: true,
    onClose: () => resetForm(),
  });
}

export function initExcursionBookingForm() {
  initPhoneMasks(document);

  const modal = getModalRoot();
  if (modal) {
    bindForm(getModalForm());
  }

  const ctaForm = document.getElementById("excursion-cta-form");
  if (ctaForm) {
    bindForm(ctaForm);
  }

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-excursion-booking-btn");
    if (!btn) return;
    e.preventDefault();

    openExcursionBookingModal({
      excursionId: btn.dataset.excursionId || "",
      excursionTitle: btn.dataset.excursionTitle || "",
      excursionDate: btn.dataset.excursionDate || "",
    });
  });
}
