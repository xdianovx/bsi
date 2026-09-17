import IMask from "imask";
import { submitFormWithRecaptcha } from "./form-ajax.js";

/**
 * Заявка на отель: секция #hotel-request (template-parts/hotel-page/request.php).
 *
 * Кнопки тарифов без ссылки на бронирование ведут на эту секцию якорем и
 * заодно передают выбранный номер, питание и цену — их видит клиент над формой
 * и получает менеджер в письме. Обработчик — action `hotel_request`.
 */

const clearErrors = (form) => {
  form.querySelectorAll(".js-field-error").forEach((el) => {
    el.textContent = "";
  });
  form.querySelectorAll(".input-item.err").forEach((el) => {
    el.classList.remove("err");
  });
};

const showErrors = (form, errors) => {
  Object.entries(errors || {}).forEach(([field, message]) => {
    const errorEl = form.querySelector(`.js-field-error[data-error-for="${field}"]`);
    const input = form.querySelector(`[data-field="${field}"]`);

    if (errorEl) errorEl.textContent = message;
    input?.closest(".input-item")?.classList.add("err");
  });
};

/** Запоминает выбранный тариф и показывает его над формой. */
const bindOfferButtons = (form, selected) => {
  document.addEventListener("click", (event) => {
    const link = event.target.closest('a[href="#hotel-request"]');
    if (!link) return;

    const room = link.closest(".hp-room");
    const offer = link.closest(".hp-offer");

    const roomName = room?.querySelector(".hp-room__title")?.textContent.trim() || "";
    const meal = offer?.querySelector(".hp-offer__meal")?.textContent.trim() || "";
    const price = offer?.querySelector(".hp-offer__price b")?.textContent.trim() || "";
    const nights = offer?.querySelector(".hp-offer__price span")?.textContent.trim() || "";

    const dateSelect = document.querySelector(".js-hotel-date");
    const dates = dateSelect ? dateSelect.options[dateSelect.selectedIndex]?.textContent.trim() : "";

    const setVal = (sel, value) => {
      const input = form.querySelector(sel);
      if (input) input.value = value;
    };

    setVal(".js-form-room-name", roomName);
    setVal(".js-form-offer-meal", meal);
    setVal(".js-form-offer-dates", [dates, nights].filter(Boolean).join(", "));
    setVal(".js-form-offer-price", price);

    if (!selected) return;

    const parts = [roomName, meal, [dates, nights].filter(Boolean).join(", "), price].filter(Boolean);

    if (parts.length) {
      selected.textContent = `Выбрано: ${parts.join(" · ")}`;
      selected.hidden = false;
    }
  });
};

export const initHotelRequestForm = () => {
  const form = document.querySelector(".js-hotel-request-form");
  if (!form) return;

  const status = form.querySelector(".js-hotel-request-status");

  /** Меняет только состояние статуса — служебные классы остаются на месте. */
  const setStatus = (state, text) => {
    status.classList.remove("loading", "success", "error");
    if (state) status.classList.add(state);
    status.textContent = text;
  };
  const selected = document.querySelector(".js-hotel-request-selected");
  const pageUrl = form.querySelector(".js-form-page-url");

  if (pageUrl) pageUrl.value = window.location.href;

  const phone = form.querySelector(".js-phone-mask");
  if (phone) {
    IMask(phone, { mask: "+{7} (000) 000-00-00" });
  }

  bindOfferButtons(form, selected);

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearErrors(form);

    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    setStatus("loading", "Отправляем…");

    try {
      const result = await submitFormWithRecaptcha(new FormData(form));

      if (result.success) {
        setStatus("success", result.data?.message || "Заявка отправлена, мы свяжемся с вами");
        form.reset();
        if (selected) selected.hidden = true;
      } else {
        setStatus("error", result.data?.message || "Не удалось отправить заявку");
        showErrors(form, result.data?.errors);
      }
    } catch (error) {
      setStatus("error", "Не удалось отправить заявку, попробуйте позже");
    } finally {
      button.disabled = false;
    }
  });
};
