import Choices from "choices.js";

export const fitForm = () => {
  const form = document.getElementById("simple-form");
  if (!form) return;

  const userPositionSelect = new Choices(".user_position_select", {
    searchEnabled: false,
    itemSelectText: "",
  });
  const counrySelect = new Choices(".fit-form__country-select", {
    searchEnabled: true,
    itemSelectText: "",
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    clearErrors();
    showStatus("Отправка...", "loading");
    const userPos = userPositionSelect.getValue(true);
    const country = counrySelect.getValue(true);
    const formData = new FormData(e.target);
    formData.append("action", "simple_contact_form");
    formData.append("user_position", userPos);
    formData.append("country_id", country);

    try {
      const response = await fetch(ajax.url, {
        method: "POST",
        body: formData,
      });

      const result = await response.json();
      console.log(result, "form");

      if (result.success) {
        showStatus("Успешно отправлено!", "success");
        e.target.reset();
      } else {
        // Показываем ошибки полей
        if (result.data.errors) {
          Object.keys(result.data.errors).forEach((field) => {
            showFieldError(field, result.data.errors[field]);
          });
        }
        showStatus(result.data.message || "Ошибка отправки", "error");
      }
    } catch (error) {
      showStatus("Ошибка сети", "error");
    }
  });

  function clearErrors() {
    document.querySelectorAll(".error-message").forEach((el) => (el.textContent = ""));
    document.getElementById("form-status").textContent = "";
  }

  function showFieldError(fieldName, message) {
    const errorEl = document.querySelector(`[data-field="${fieldName}"]`);
    if (errorEl) errorEl.textContent = message;
  }

  function showStatus(message, type) {
    const statusEl = document.getElementById("form-status");
    statusEl.textContent = message;
    statusEl.className = `form-status ${type}`;
  }

  function showFieldError(fieldName, message) {
    const errorEl = document.querySelector(`[data-field="${fieldName}"]`);
    const inputEl = document.querySelector(`[name="${fieldName}"]`);

    if (errorEl) errorEl.textContent = message;
    if (inputEl) inputEl.classList.add("error");
  }

  function clearErrors() {
    document.querySelectorAll(".error-message").forEach((el) => (el.textContent = ""));
    document.querySelectorAll("input.error").forEach((el) => el.classList.remove("error"));
    document.getElementById("form-status").textContent = "";
  }
};
