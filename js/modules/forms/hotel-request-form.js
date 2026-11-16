import MicroModal from "micromodal";
import IMask from "imask";
import { submitFormWithRecaptcha } from "./form-ajax.js";

/**
 * Заявки на отель, action `hotel_request`:
 * - общая форма внизу страницы — template-parts/hotel-page/request.php;
 * - модалка заявки на номер — template-parts/hotel-page/request-modal.php.
 *   Открывается кнопкой `.js-hotel-room-request` у номера без ссылки на
 *   бронирование; номер, питание, даты и цена уходят менеджеру в письме.
 */

const MODAL_ID = "modal-hotel-request";

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

const text = (root, selector) => root?.querySelector(selector)?.textContent.trim() || "";

/** Номер, питание, даты и цена по кнопке номера. Питание и цену берём, только если тариф один. */
const readRoom = (button) => {
  const room = button.closest(".hp-room");
  const offers = room ? [...room.querySelectorAll(".hp-offer")] : [];
  const offer = offers.length === 1 ? offers[0] : null;

  const dateSelect = document.querySelector(".js-hotel-date");
  const date = dateSelect ? dateSelect.options[dateSelect.selectedIndex]?.textContent.trim() : "";
  /* Подпись у цены — либо «за ночь» (номера из админки), либо «7 ночей» (тарифы хаба). */
  const per = text(offer, ".hp-offer__price span");
  const perNight = per.startsWith("за ");
  const price = text(offer, ".hp-offer__price b");

  /* «Размещение в номере» — заглушка тарифа без питания, в заявке она ничего не говорит. */
  const meal = text(offer, ".hp-offer__meal");

  return {
    room: text(room, ".hp-room__title"),
    meal: meal === "Размещение в номере" ? "" : meal,
    dates: [date, perNight ? "" : per].filter(Boolean).join(", "),
    price: price && perNight ? `${price} ${per}` : price,
  };
};

const fillModal = (modal, data) => {
  const form = modal.querySelector(".js-hotel-request-form");
  const setVal = (selector, value) => {
    const input = form.querySelector(selector);
    if (input) input.value = value;
  };

  setVal(".js-form-room-name", data.room);
  setVal(".js-form-offer-meal", data.meal);
  setVal(".js-form-offer-dates", data.dates);
  setVal(".js-form-offer-price", data.price);

  const list = modal.querySelector(".js-hotel-request-details");
  if (!list) return;

  const items = [
    ["Номер", data.room],
    ["Питание", data.meal],
    ["Даты", data.dates],
    ["Цена", data.price],
  ].filter(([, value]) => value);

  list.replaceChildren(
    ...items.map(([key, value]) => {
      const li = document.createElement("li");
      li.className = "modal-program-booking__details-item";

      const k = document.createElement("span");
      k.className = "modal-program-booking__details-key";
      k.textContent = key;

      const v = document.createElement("span");
      v.className = "modal-program-booking__details-value";
      v.textContent = value;

      li.append(k, v);
      return li;
    })
  );
  list.hidden = items.length === 0;
};

const initForm = (form) => {
  const status = form.querySelector(".js-hotel-request-status");

  /** Меняет только состояние статуса — служебные классы остаются на месте. */
  const setStatus = (state, message) => {
    if (!status) return;
    status.classList.remove("loading", "success", "error");
    if (state) status.classList.add(state);
    status.textContent = message;
  };

  const pageUrl = form.querySelector(".js-form-page-url");
  if (pageUrl) pageUrl.value = window.location.href;

  const phone = form.querySelector(".js-phone-mask");
  if (phone) IMask(phone, { mask: "+{7} (000) 000-00-00" });

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
        if (pageUrl) pageUrl.value = window.location.href;
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

  return { setStatus };
};

export const initHotelRequestForm = () => {
  const forms = document.querySelectorAll(".js-hotel-request-form");
  if (!forms.length) return;

  const controls = new Map();
  forms.forEach((form) => controls.set(form, initForm(form)));

  const modal = document.getElementById(MODAL_ID);
  if (!modal) return;

  document.addEventListener("click", (event) => {
    const button = event.target.closest(".js-hotel-room-request");
    if (!button) return;

    const form = modal.querySelector(".js-hotel-request-form");
    clearErrors(form);
    controls.get(form)?.setStatus("", "");
    fillModal(modal, readRoom(button));

    MicroModal.show(MODAL_ID, { disableScroll: true, awaitCloseAnimation: true });
  });
};
