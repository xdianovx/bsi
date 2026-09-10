import IMask from "imask";
import { submitFormWithRecaptcha, RECAPTCHA_NOT_LOADED } from "./form-ajax.js";

/**
 * Заявка «Подобрать поездку» — форма секцией на странице курорта
 * (template-parts/resort/request-form.php), без модалки.
 * Обработчик — action `resort_request` (inc/requests/ajax-resort-request.php).
 *
 * Метрика: `<goal>_start` при первом вводе (видно, кто начал заполнять
 * и бросил) и `<goal>_submitted` при успешной отправке. Имя цели берётся
 * из `data-ym-goal` формы: у раздела обучения своя воронка
 * (`education_request`), у остальных — общая `resort_request`.
 * Параметры {resort, country, section} — чтобы в отчётах было видно,
 * какой курорт и какой его экран приносят заявки.
 */

const YM_ID = 108341897;

function reachGoal(goal, params) {
  if (typeof window.ym !== "function") return;
  window.ym(YM_ID, "reachGoal", goal, params);
}

/** Имя цели задаёт шаблон формы, чтобы разделы не смешивались в одной воронке */
function goalName(form, suffix) {
  const base = form.dataset.ymGoal || "resort_request";

  return `${base}_${suffix}`;
}

/** Параметры цели — из скрытых полей формы: один источник и для письма, и для отчётов */
function goalParams(form) {
  const val = (sel) => {
    const input = form.querySelector(sel);
    return input ? input.value : "";
  };

  return {
    resort: val(".js-form-resort-name"),
    country: val(".js-form-resort-country"),
    section: val(".js-form-resort-section") || "hub",
  };
}

function showFieldError(fieldName, message, form) {
  const errorEl = form.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
  const input = form.querySelector(`[data-field="${fieldName}"]`) || form.querySelector(`[name="${fieldName}"]`);

  if (errorEl) errorEl.textContent = message;
  if (input) {
    const item = input.closest(".input-item");
    if (item) item.classList.add("err");
    else input.classList.add("error");
  }
}

function clearErrors(form) {
  form.querySelectorAll(".js-field-error").forEach((el) => (el.textContent = ""));
  form.querySelectorAll(".input-item.err").forEach((el) => el.classList.remove("err"));
  form.querySelectorAll("input.error, textarea.error").forEach((el) => el.classList.remove("error"));
}

function validateForm(form) {
  const errors = {};

  const nameEl = form.querySelector('[name="name"]');
  if (!nameEl || !nameEl.value.trim()) errors.name = "Введите имя";

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
      reachGoal(goalName(form, "submitted"), goalParams(form));

      const success = form.querySelector(".js-resort-request-success");
      if (success) success.hidden = false;

      form.reset();
      clearErrors(form);
    } else {
      if (result.data && result.data.errors) {
        Object.entries(result.data.errors).forEach(([field, message]) => showFieldError(field, message, form));
      }
      const errMsg = result.data?.errors?.recaptcha || result.data?.message || "Произошла ошибка при отправке";
      alert(errMsg);
    }
  } catch (error) {
    if (error.message === RECAPTCHA_NOT_LOADED) {
      alert("Подождите, загрузка проверки…");
    } else {
      console.error("Resort request submit error:", error);
      alert("Произошла ошибка при отправке. Попробуйте позже.");
    }
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = defaultLabel;
    }
  }
}

function bindForm(form) {
  const setVal = (sel, val) => {
    const input = form.querySelector(sel);
    if (input) input.value = val;
  };

  setVal(".js-form-page-url", window.location.href);
  setVal(".js-form-referrer", document.referrer || "");

  initPhoneMasks(form);
  form.addEventListener("submit", submitForm);

  /* Первое касание формы — отдельная цель: разрыв со «submitted»
     показывает, где люди начинают заполнять и бросают */
  let started = false;
  form.addEventListener(
    "input",
    () => {
      if (started) return;
      started = true;
      reachGoal(goalName(form, "start"), goalParams(form));
    },
    { once: false }
  );

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

export function initResortRequestForm() {
  document.querySelectorAll(".js-resort-request-form").forEach(bindForm);
}
