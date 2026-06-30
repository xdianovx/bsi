import MicroModal from "micromodal";
import IMask from "imask";
import { submitFormWithRecaptcha, RECAPTCHA_NOT_LOADED } from "./form-ajax.js";

/**
 * Заявка по событию: модалка и inline-форма (имя + телефон обязательны; почта и комментарий — нет).
 * Метрика: ym(108341897, "reachGoal", "event_tour_submited").
 */

const YM_ID = 108341897;
const EVENT_TOUR_GOAL = "event_tour_submited";

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

function buildTicketData(btn) {
  const d = btn.dataset;
  const accName = (d.accommodationName || "").trim();
  const stars = (d.accommodationStars || "").trim();
  const accommodation = accName
    ? accName + (stars ? ` ${stars}*` : "")
    : "";

  let price = "";
  const rub = parseInt(String(d.minPrice || "").replace(/\D/g, ""), 10);
  if (rub > 0) {
    price = `от ${rub.toLocaleString("ru-RU")} ₽`;
    const orig = d.accommodationPriceOriginal;
    const cur = d.accommodationPriceCurrency;
    if (orig && cur) {
      price += ` (${Number(orig).toLocaleString("ru-RU")} ${cur})`;
    }
  }

  return {
    eventTitle: (d.eventTitle || "").trim(),
    eventId: d.eventId || "",
    accName,
    accommodation,
    date: (d.eventDate || "").trim(),
    venue: (d.eventVenue || "").trim(),
    time: (d.eventTime || "").trim(),
    price,
  };
}

function populateModal(ticketData) {
  const modalRoot = getModalRoot();
  if (!modalRoot) {
    return;
  }

  const titleEl = document.getElementById("modal-event-ticket-booking-title");
  if (titleEl) {
    const accSuffix = ticketData.accName ? ` — ${ticketData.accName}` : "";
    titleEl.textContent = (ticketData.eventTitle || "") + accSuffix;
  }

  const form = getModalForm();
  if (!form) {
    return;
  }

  const title = ticketData.eventTitle || "";
  const idStr = ticketData.eventId != null && ticketData.eventId !== "" ? String(ticketData.eventId) : "";
  const accommodation = ticketData.accommodation || "";
  form.dataset.eventTitle = title;
  form.dataset.eventId = idStr;
  form.dataset.accommodation = accommodation;

  const setVal = (sel, val) => {
    const input = form.querySelector(sel);
    if (input) input.value = val || "";
  };

  // Детали брони (видимый блок + скрытые поля для письма).
  const rows = [
    ["Размещение", accommodation],
    ["Дата", ticketData.date],
    ["Цена", ticketData.price],
    ["Площадка", ticketData.venue],
    ["Время", ticketData.time],
  ].filter(([, v]) => v);

  const detailsEl = modalRoot.querySelector(".js-form-details");
  if (detailsEl) {
    if (rows.length) {
      const esc = (s) =>
        String(s).replace(
          /[&<>"']/g,
          (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c],
        );
      detailsEl.innerHTML = rows
        .map(
          ([k, v]) =>
            `<li class="modal-program-booking__details-item"><span class="modal-program-booking__details-key">${esc(k)}:</span> ${esc(v)}</li>`,
        )
        .join("");
      detailsEl.hidden = false;
    } else {
      detailsEl.innerHTML = "";
      detailsEl.hidden = true;
    }
  }

  setVal(".js-form-event-title", title);
  setVal(".js-form-page-url", window.location.href);
  setVal(".js-form-accommodation", accommodation);
  setVal(".js-form-event-date", ticketData.date);
  setVal(".js-form-event-venue", ticketData.venue);
  setVal(".js-form-event-time", ticketData.time);
  setVal(".js-form-event-price", ticketData.price);
  setVal(
    ".js-form-event-details",
    rows.map(([k, v]) => `${k}: ${v}`).join("; "),
  );
}

function reachEventTourBookingGoal(params) {
  if (typeof ym === "undefined") {
    return;
  }
  if (params && Object.keys(params).length > 0) {
    ym(YM_ID, "reachGoal", EVENT_TOUR_GOAL, params);
  } else {
    ym(YM_ID, "reachGoal", EVENT_TOUR_GOAL);
  }
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
  const input =
    form.querySelector(`[data-field="${fieldName}"]`) || form.querySelector(`[name="${fieldName}"]`);

  if (errorEl) {
    errorEl.textContent = message;
  }
  if (input) {
    const item = input.closest(".input-item");
    if (item) {
      item.classList.add("err");
    } else if (input.classList.contains("single-event__booking-cta-input")) {
      input.classList.add("single-event__booking-cta-input--error");
    } else {
      input.classList.add("error");
    }
  }
}

function clearErrors(form) {
  if (!form) return;
  form.querySelectorAll(".js-field-error").forEach((el) => {
    el.textContent = "";
  });
  form.querySelectorAll(".input-item.err").forEach((el) => {
    el.classList.remove("err");
  });
  form.querySelectorAll("input.error, textarea.error").forEach((el) => {
    el.classList.remove("error");
  });
  form.querySelectorAll(".single-event__booking-cta-input--error").forEach((el) => {
    el.classList.remove("single-event__booking-cta-input--error");
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
    if (!email) {
      errors.email = "Введите email";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
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
      const fieldName = input.dataset.field || input.name;
      if (!fieldName) return;
      const errorEl = form.querySelector(`.js-field-error[data-error-for="${fieldName}"]`);
      if (errorEl) errorEl.textContent = "";
      const item = input.closest(".input-item");
      if (item) item.classList.remove("err");
      input.classList.remove("error", "single-event__booking-cta-input--error");
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
      const accommodationParam = (form.dataset.accommodation
        || (form.querySelector('.js-form-accommodation')?.value || '')).trim();
      reachEventTourBookingGoal(accommodationParam ? { accommodation: accommodationParam } : null);

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
  const promoCtaForm = document.getElementById("promo-booking-cta-form");

  if (modal) {
    bindBookingForm(getModalForm());
  }
  if (ctaForm) {
    bindBookingForm(ctaForm);
  }
  if (promoCtaForm) {
    bindBookingForm(promoCtaForm);
  }

  if (!modal && !ctaForm && !promoCtaForm) {
    return;
  }

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-event-ticket-booking-btn");
    if (!btn) return;
    e.preventDefault();
    openEventTicketModal(buildTicketData(btn));
  });

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-event-booking-btn");
    if (!btn) return;
    e.preventDefault();
    openEventTicketModal(buildTicketData(btn));
  });
}
