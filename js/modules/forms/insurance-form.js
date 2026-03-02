import IMask from "imask";
import MicroModal from "micromodal";

/**
 * Модуль для работы с формой консультации по страхованию
 */
export const initInsuranceForm = () => {
  const form = document.querySelector('.visa-consultation-form');
  if (!form) return;

  let phoneMaskInstance = null;

  // Инициализация маски телефона
  function initPhoneMask() {
    const phoneInput = form.querySelector('#insurance_phone');
    if (phoneInput && !phoneMaskInstance) {
      phoneMaskInstance = IMask(phoneInput, {
        mask: "+{7} (000) 000-00-00",
        lazy: false,
        placeholderChar: "_",
      });
    }
  }

  // Валидация формы
  function validateForm() {
    const errors = {};

    // Проверка имени (обязательно)
    const name = form.querySelector('[name="name"]');
    if (!name || !name.value.trim()) {
      errors.name = true;
    }

    // Проверка телефона (обязательно)
    const tel = form.querySelector('[name="tel"]');
    if (!tel || !tel.value.trim()) {
      errors.tel = true;
    } else {
      const phoneDigits = tel.value.replace(/\D/g, "");
      if (phoneDigits.length < 11) {
        errors.tel = true;
      }
    }

    return errors;
  }

  // Показать ошибку поля
  function showFieldError(fieldName) {
    const inputEl = form.querySelector(`[name="${fieldName}"]`);
    if (inputEl) {
      const inputItem = inputEl.closest('.input-item');
      if (inputItem) {
        inputItem.classList.add("err");
      }
    }
  }

  // Очистить все ошибки
  function clearErrors() {
    form.querySelectorAll(".input-item.err").forEach((el) => {
      el.classList.remove("err");
    });

    const statusEl = document.getElementById("form-status");
    if (statusEl) {
      statusEl.textContent = "";
      statusEl.className = "";
    }
  }

  // Очистить ошибку конкретного поля
  function clearFieldError(fieldName) {
    const inputEl = form.querySelector(`[name="${fieldName}"]`);
    if (inputEl) {
      const inputItem = inputEl.closest('.input-item');
      if (inputItem) {
        inputItem.classList.remove("err");
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

  // Скролл к первому полю с ошибкой
  function scrollToFirstError() {
    const firstErrorField = form.querySelector(".input-item.err");
    
    if (firstErrorField) {
      const offsetTop = firstErrorField.getBoundingClientRect().top + window.pageYOffset - 100;
      window.scrollTo({
        top: offsetTop,
        behavior: "smooth",
      });
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
        showFieldError(field);
      });

      showStatus("Заполните обязательные поля", "error");

      // Скролл к первому полю с ошибкой
      setTimeout(() => {
        scrollToFirstError();
      }, 200);

      return;
    }

    showStatus("Отправка...", "loading");

    // Блокируем кнопку
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.textContent : "";
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = "Отправка...";
    }

    // Собираем данные формы
    const formData = new FormData(form);

    // Добавляем action для AJAX
    formData.append("action", "insurance_form");

    // reCAPTCHA v3
    if (typeof ajax !== "undefined" && ajax.recaptchaSiteKey) {
      if (typeof grecaptcha === "undefined") {
        showStatus("Подождите, загрузка проверки…", "loading");
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalBtnText;
        }
        return;
      }
      try {
        const token = await grecaptcha.execute(ajax.recaptchaSiteKey, {
          action: "submit",
        });
        formData.append("recaptcha_token", token);
      } catch (err) {
        showStatus("Ошибка проверки. Попробуйте ещё раз.", "error");
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalBtnText;
        }
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
        // Очищаем форму
        form.reset();
        clearErrors();

        // Открываем модалку успеха с небольшой задержкой
        setTimeout(() => {
          MicroModal.show("modal-insurance-success", {
            awaitCloseAnimation: true,
          });

          // Авто-закрытие через 2 секунды
          setTimeout(() => {
            MicroModal.close("modal-insurance-success");
          }, 2000);
        }, 300);
      } else {
        // Показываем ошибки полей
        if (result.data && result.data.errors) {
          clearErrors();

          const errorFields = Object.keys(result.data.errors);
          errorFields.forEach((field) => {
            showFieldError(field);
          });
        }
        showStatus(
          result.data?.errors?.recaptcha ||
            result.data?.message ||
            "Ошибка отправки",
          "error"
        );

        // Скролл к первому полю с ошибкой
        setTimeout(() => {
          scrollToFirstError();
        }, 200);
      }
    } catch (error) {
      console.error("Insurance form error:", error);
      showStatus("Ошибка сети", "error");
    } finally {
      // Разблокируем кнопку
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    }
  });

  // Инициализация автоматической очистки ошибок при изменении значений
  function initErrorAutoClear() {
    const inputFields = form.querySelectorAll('input[type="text"], input[type="tel"]');
    inputFields.forEach((input) => {
      input.addEventListener("input", () => {
        const fieldName = input.getAttribute("name") || input.getAttribute("id");
        if (fieldName) {
          clearFieldError(fieldName);
        }
      });
    });
  }

  // Инициализация всех компонентов
  initPhoneMask();
  initErrorAutoClear();
};
