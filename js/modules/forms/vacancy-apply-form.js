import IMask from "imask";
import { submitFormWithRecaptcha, RECAPTCHA_NOT_LOADED } from "./form-ajax.js";

/**
 * Отклик на вакансию: форма .js-vacancy-apply-form (template-parts/vacancy/apply-form.php).
 * AJAX action `vacancy_apply` — inc/requests/ajax-vacancy-apply.php.
 *
 * Отличие от остальных форм темы: отправляется файл резюме, поэтому валидируем
 * размер и расширение ещё на клиенте, чтобы не гонять 20 МБ впустую.
 */

const MAX_RESUME_MB = 5;
const ALLOWED_EXT = ["pdf", "doc", "docx", "rtf", "odt"];

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

function setStatus(form, text, state) {
  const status = form.querySelector(".js-vacancy-apply-status");
  if (!status) return;
  status.textContent = text || "";
  status.classList.remove("loading", "success", "error");
  if (state) status.classList.add(state);
}

function validateResume(input) {
  if (!input || !input.files || input.files.length === 0) return "";

  const file = input.files[0];
  const ext = (file.name.split(".").pop() || "").toLowerCase();

  if (!ALLOWED_EXT.includes(ext)) {
    return "Допустимы только PDF, DOC, DOCX, RTF и ODT.";
  }
  if (file.size > MAX_RESUME_MB * 1024 * 1024) {
    return `Файл слишком большой. Максимум ${MAX_RESUME_MB} МБ.`;
  }
  return "";
}

function validateForm(form) {
  const errors = {};

  const name = form.querySelector('[name="name"]')?.value.trim() || "";
  if (!name) errors.name = "Введите имя";

  const email = form.querySelector('[name="email"]')?.value.trim() || "";
  if (!email) {
    errors.email = "Введите email";
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errors.email = "Введите корректный email";
  }

  const phone = form.querySelector('[name="phone"]')?.value.trim() || "";
  if (!phone) {
    errors.phone = "Введите телефон";
  } else if (phone.replace(/\D/g, "").length < 11) {
    errors.phone = "Введите полный номер телефона";
  }

  const resumeError = validateResume(form.querySelector(".js-vacancy-resume"));
  if (resumeError) errors.resume = resumeError;

  const privacy = form.querySelector('[name="privacy_agreement"]');
  if (!privacy || !privacy.checked) {
    errors.privacy_agreement = "Необходимо согласие на обработку персональных данных";
  }

  return errors;
}

function bindResumeInput(form) {
  const input = form.querySelector(".js-vacancy-resume");
  const nameEl = form.querySelector(".js-vacancy-resume-name");
  if (!input || !nameEl) return;

  input.addEventListener("change", () => {
    const file = input.files && input.files[0];
    nameEl.textContent = file ? file.name : "Файл не выбран";

    const error = validateResume(input);
    const errorEl = form.querySelector('.js-field-error[data-error-for="resume"]');
    if (errorEl) errorEl.textContent = error;
  });
}

function bindPhoneMask(form) {
  form.querySelectorAll(".js-phone-mask").forEach((input) => {
    if (input.dataset.imaskReady === "1") return;
    IMask(input, { mask: "+{7} (000) 000-00-00", lazy: false, placeholderChar: "_" });
    input.dataset.imaskReady = "1";
  });
}

async function submitForm(e) {
  e.preventDefault();
  const form = e.currentTarget;
  if (!(form instanceof HTMLFormElement)) return;

  clearErrors(form);
  setStatus(form, "");

  const errors = validateForm(form);
  if (Object.keys(errors).length > 0) {
    Object.entries(errors).forEach(([field, message]) => showFieldError(field, message, form));
    const firstField = Object.keys(errors)[0];
    const firstEl = form.querySelector(`[data-field="${firstField}"]`) || form.querySelector(`[name="${firstField}"]`);
    if (firstEl) firstEl.focus({ preventScroll: false });
    return;
  }

  const submitBtn = form.querySelector('button[type="submit"]');
  const defaultLabel = submitBtn?.dataset.defaultLabel || submitBtn?.textContent || "Отправить отклик";

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Отправка…";
  }
  setStatus(form, "Отправляем отклик…", "loading");

  try {
    const formData = new FormData(form);
    const result = await submitFormWithRecaptcha(formData, { debug: false });

    if (result.success) {
      form.reset();
      const nameEl = form.querySelector(".js-vacancy-resume-name");
      if (nameEl) nameEl.textContent = "Файл не выбран";
      setStatus(form, result.data?.message || "Спасибо! Отклик отправлен.", "success");
    } else {
      if (result.data?.errors) {
        Object.entries(result.data.errors).forEach(([field, message]) => showFieldError(field, message, form));
      }
      setStatus(form, result.data?.message || "Произошла ошибка при отправке", "error");
    }
  } catch (error) {
    if (error.message === RECAPTCHA_NOT_LOADED) {
      setStatus(form, "Подождите, загрузка проверки…", "error");
    } else {
      console.error("Vacancy apply submit error:", error);
      setStatus(form, "Не удалось отправить отклик. Попробуйте позже или напишите нам на почту.", "error");
    }
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = defaultLabel;
    }
  }
}

export function initVacancyApplyForm() {
  const form = document.querySelector(".js-vacancy-apply-form");
  if (!form) return;

  bindPhoneMask(form);
  bindResumeInput(form);
  form.addEventListener("submit", submitForm);

  form.querySelectorAll("input, textarea").forEach((input) => {
    input.addEventListener("input", () => {
      const fieldName = input.dataset.field || input.name;
      if (!fieldName) return;
      const errorEl = form.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
      if (errorEl) errorEl.textContent = "";
      input.closest(".input-item")?.classList.remove("err");
      input.classList.remove("error");
    });
  });

  const privacy = form.querySelector('[name="privacy_agreement"]');
  if (privacy) {
    privacy.addEventListener("change", () => {
      const err = form.querySelector('.js-field-error[data-error-for="privacy_agreement"]');
      if (err) err.textContent = "";
      privacy.closest(".input-item")?.classList.remove("err");
    });
  }
}
