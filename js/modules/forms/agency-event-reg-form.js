import IMask from "imask";
import MicroModal from "micromodal";

export const initAgencyEventRegForm = () => {
  const form = document.querySelector(".js-agency-reg-form");
  if (!form) return;

  const phoneInput = form.querySelector("#agency-reg-tel");
  if (phoneInput) {
    IMask(phoneInput, {
      mask: "+{7} (000) 000-00-00",
      lazy: false,
      placeholderChar: "_",
    });
  }

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-agency-event-reg-btn");
    if (!btn) return;

    const { eventId, eventTitle, eventKind } = btn.dataset;

    form.querySelector(".js-agency-reg-event-id").value = eventId || "";
    form.querySelector(".js-agency-reg-event-title").value = eventTitle || "";
    form.querySelector(".js-agency-reg-event-kind").value = eventKind || "";

    const subtitle = document.querySelector(".js-agency-reg-event-name");
    if (subtitle) subtitle.textContent = eventTitle || "";

    MicroModal.show("modal-agency-event-reg", { awaitCloseAnimation: true });
  });

  function validateForm() {
    const errors = {};

    const name = form.querySelector('[name="name"]');
    if (!name || !name.value.trim()) errors.name = true;

    const company = form.querySelector('[name="company"]');
    if (!company || !company.value.trim()) errors.company = true;

    const city = form.querySelector('[name="city"]');
    if (!city || !city.value.trim()) errors.city = true;

    const inn = form.querySelector('[name="inn"]');
    if (!inn || !inn.value.trim()) errors.inn = true;

    const email = form.querySelector('[name="email"]');
    if (!email || !email.value.trim()) {
      errors.email = true;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
      errors.email = true;
    }

    const tel = form.querySelector('[name="tel"]');
    if (!tel || !tel.value.trim()) {
      errors.tel = true;
    } else {
      const phoneDigits = tel.value.replace(/\D/g, "");
      if (phoneDigits.length < 11) errors.tel = true;
    }

    return errors;
  }

  function showFieldError(fieldName) {
    const inputEl = form.querySelector(`[name="${fieldName}"]`);
    if (inputEl) {
      const inputItem = inputEl.closest(".input-item");
      if (inputItem) inputItem.classList.add("err");
    }
  }

  function clearErrors() {
    form.querySelectorAll(".input-item.err").forEach((el) => {
      el.classList.remove("err");
    });
    const statusEl = document.getElementById("agency-reg-form-status");
    if (statusEl) {
      statusEl.textContent = "";
      statusEl.className = "";
    }
  }

  function clearFieldError(fieldName) {
    const inputEl = form.querySelector(`[name="${fieldName}"]`);
    if (inputEl) {
      const inputItem = inputEl.closest(".input-item");
      if (inputItem) inputItem.classList.remove("err");
    }
  }

  function showStatus(message, type) {
    const statusEl = document.getElementById("agency-reg-form-status");
    if (statusEl) {
      statusEl.textContent = message;
      statusEl.className = `form-status ${type}`;
    }
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearErrors();

    const errors = validateForm();
    if (Object.keys(errors).length > 0) {
      Object.keys(errors).forEach((field) => showFieldError(field));
      showStatus("Заполните обязательные поля", "error");
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
    formData.append("action", "agency_event_registration");

    if (typeof ajax !== "undefined" && ajax.recaptchaSiteKey) {
      if (typeof grecaptcha !== "undefined") {
        const token = await grecaptcha.execute(ajax.recaptchaSiteKey, {
          action: "submit",
        });
        formData.append("recaptcha_token", token);
      }
    }

    try {
      const ajaxUrl =
        (typeof ajax !== "undefined" && ajax.url) || window.ajaxurl;
      const response = await fetch(ajaxUrl, {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        form.reset();
        clearErrors();
        MicroModal.close("modal-agency-event-reg");

        setTimeout(() => {
          MicroModal.show("modal-agency-reg-success", {
            awaitCloseAnimation: true,
          });
          setTimeout(() => {
            MicroModal.close("modal-agency-reg-success");
          }, 2000);
        }, 300);
      } else {
        if (result.data && result.data.errors) {
          clearErrors();
          Object.keys(result.data.errors).forEach((field) =>
            showFieldError(field)
          );
        }
        showStatus(result.data?.message || "Ошибка отправки", "error");
      }
    } catch (error) {
      console.error("Agency event reg form error:", error);
      showStatus("Ошибка сети", "error");
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    }
  });

  const inputFields = form.querySelectorAll(
    'input[type="text"], input[type="tel"], input[type="email"]'
  );
  inputFields.forEach((input) => {
    input.addEventListener("input", () => {
      const fieldName = input.getAttribute("name");
      if (fieldName) clearFieldError(fieldName);
    });
  });
};
