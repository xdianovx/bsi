import IMask from "imask";
import MicroModal from "micromodal";
import { submitFormWithRecaptcha, RECAPTCHA_NOT_LOADED } from "./form-ajax.js";

export const initBsimiceConsultationForm = () => {
  const form = document.getElementById("bsimice-consultation-form");
  if (!form) return;

  let phoneMaskInstance = null;

  function initPhoneMask() {
    const phoneInput = form.querySelector("#bsimice-phone");
    if (phoneInput && !phoneMaskInstance) {
      phoneMaskInstance = IMask(phoneInput, {
        mask: "+{7} (000) 000-00-00",
        lazy: false,
        placeholderChar: "_",
      });
    }
  }

  function validateForm() {
    const errors = {};

    const name = form.querySelector('[name="name"]');
    if (!name || !name.value.trim()) {
      errors.name = true;
    }

    const phone = form.querySelector('[name="phone"]');
    if (!phone || !phone.value.trim()) {
      errors.phone = true;
    } else {
      const phoneDigits = phone.value.replace(/\D/g, "");
      if (phoneDigits.length < 11) {
        errors.phone = true;
      }
    }

    const email = form.querySelector('[name="email"]');
    const emailVal = email && email.value ? email.value.trim() : "";
    if (!emailVal) {
      errors.email = true;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
      errors.email = true;
    }

    const privacy = form.querySelector('[name="privacy_agreement"]');
    if (!privacy || !privacy.checked) {
      errors.privacy_agreement = true;
    }

    return errors;
  }

  function showFieldError(fieldName) {
    const inputEl = form.querySelector(`[name="${fieldName}"]`);
    if (!inputEl) return;

    const inputItem = inputEl.closest(".input-item");
    if (inputItem) {
      inputItem.classList.add("err");
    }
  }

  function clearErrors() {
    form.querySelectorAll(".input-item.err").forEach((el) => {
      el.classList.remove("err");
    });

    const statusEl = document.getElementById("bsimice-consultation-form-status");
    if (statusEl) {
      statusEl.textContent = "";
      statusEl.className = "form-status";
    }
  }

  function clearFieldError(fieldName) {
    const inputEl = form.querySelector(`[name="${fieldName}"]`);
    if (!inputEl) return;

    const inputItem = inputEl.closest(".input-item");
    if (inputItem) {
      inputItem.classList.remove("err");
    }
  }

  function showStatus(message, type) {
    const statusEl = document.getElementById("bsimice-consultation-form-status");
    if (!statusEl) return;

    statusEl.textContent = message;
    statusEl.className = `form-status ${type}`;
  }

  function scrollToFirstError() {
    const firstErrorField = form.querySelector(".input-item.err");
    if (!firstErrorField) return;

    const offsetTop = firstErrorField.getBoundingClientRect().top + window.pageYOffset - 100;
    window.scrollTo({
      top: offsetTop,
      behavior: "smooth",
    });
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearErrors();

    const errors = validateForm();
    if (Object.keys(errors).length > 0) {
      Object.keys(errors).forEach((field) => {
        showFieldError(field);
      });

      showStatus("Заполните обязательные поля", "error");
      setTimeout(() => {
        scrollToFirstError();
      }, 200);
      return;
    }

    showStatus("Отправка...", "loading");

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.textContent : "";
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = "Отправка...";
    }

    const formData = new FormData(form);
    formData.append("action", "bsimice_consultation_form");

    try {
      const result = await submitFormWithRecaptcha(formData, { debug: false });

      if (result.success) {
        form.reset();
        clearErrors();

        setTimeout(() => {
          MicroModal.show("modal-bsimice-consultation-success", {
            awaitCloseAnimation: true,
          });

          setTimeout(() => {
            MicroModal.close("modal-bsimice-consultation-success");
          }, 2000);
        }, 300);
      } else {
        if (result.data && result.data.errors) {
          Object.keys(result.data.errors).forEach((field) => {
            showFieldError(field);
          });
        }

        showStatus(result.data?.errors?.recaptcha || result.data?.message || "Ошибка отправки", "error");

        setTimeout(() => {
          scrollToFirstError();
        }, 200);
      }
    } catch (error) {
      if (error.message === RECAPTCHA_NOT_LOADED) {
        showStatus("Подождите, загрузка проверки…", "loading");
        return;
      }
      console.error("BSI MICE form error:", error);
      showStatus("Ошибка сети", "error");
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    }
  });

  function initErrorAutoClear() {
    const inputFields = form.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"], textarea');
    inputFields.forEach((input) => {
      input.addEventListener("input", () => {
        const fieldName = input.getAttribute("name");
        if (fieldName) {
          clearFieldError(fieldName);
        }
      });
    });

    const privacyAgree = form.querySelector('[name="privacy_agreement"]');
    if (privacyAgree) {
      privacyAgree.addEventListener("change", () => clearFieldError("privacy_agreement"));
    }
  }

  initPhoneMask();
  initErrorAutoClear();
};
