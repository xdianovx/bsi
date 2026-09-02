import { submitFormWithRecaptcha } from "./form-ajax.js";

/**
 * Проверка статуса заявки: форма .js-claim-check-form (page-check-claim.php).
 * Поле — type="text" с inputmode="numeric": стрелок как у number нет,
 * нецифры отрезаем на input/paste. Результат рисуем из JSON admin-ajax
 * (inc/requests/ajax-check-claim.php), HTML Само на страницу не попадает.
 */

const escapeHtml = (str) =>
  String(str).replace(/[&<>"']/g, (ch) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));

const nl2br = (str) => escapeHtml(str).replace(/\n/g, "<br>");

function digitsOnly(input) {
  const clean = input.value.replace(/\D+/g, "");
  if (clean !== input.value) input.value = clean;
}

function setError(form, message) {
  const errorEl = form.querySelector('.js-field-error[data-error-for="claim"]');
  const item = form.querySelector(".input-item");
  if (errorEl) errorEl.textContent = message || "";
  if (item) item.classList.toggle("err", Boolean(message));
}

function renderResult(box, data) {
  const rows = (data.rows || [])
    .map(
      (row) => `
        <div class="claim-check__item">
          <div class="claim-check__label">${escapeHtml(row.label)}</div>
          <div class="claim-check__value${row.tone ? ` claim-check__value--${escapeHtml(row.tone)}` : ""}">${nl2br(row.value)}</div>
        </div>`
    )
    .join("");

  box.innerHTML = `
    <div class="claim-check__card">
      <div class="claim-check__head">Заявка № ${escapeHtml(data.claim)}</div>
      <div class="claim-check__list">${rows}</div>
      ${data.note ? `<p class="claim-check__note">${escapeHtml(data.note)}</p>` : ""}
      ${
        data.details_url
          ? `<a class="claim-check__details" href="${escapeHtml(
              data.details_url
            )}" target="_blank" rel="noopener">Подробная информация в личном кабинете</a>`
          : ""
      }
    </div>`;
  box.hidden = false;
}

function renderMessage(box, message, tone) {
  box.innerHTML = `<div class="claim-check__card claim-check__card--${tone}">${escapeHtml(message)}</div>`;
  box.hidden = false;
}

async function submit(form, box) {
  const input = form.querySelector(".js-claim-check-input");
  const value = input ? input.value.trim() : "";

  setError(form, "");

  if (!value) {
    setError(form, "Введите номер заявки");
    if (input) input.focus();
    return;
  }

  const btn = form.querySelector('button[type="submit"]');
  const label = btn?.dataset.defaultLabel || "Проверить";
  if (btn) {
    btn.disabled = true;
    btn.textContent = "Проверяем…";
  }
  box.hidden = true;

  try {
    const result = await submitFormWithRecaptcha(new FormData(form));

    if (result.success) {
      renderResult(box, result.data);
    } else if (result.data?.errors?.claim) {
      setError(form, result.data.errors.claim);
    } else {
      renderMessage(box, result.data?.message || "Не удалось проверить заявку", result.data?.not_found ? "empty" : "error");
    }
  } catch (err) {
    console.error("Claim check error:", err);
    renderMessage(box, "Сервис проверки временно недоступен. Попробуйте позже.", "error");
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = label;
    }
  }
}

export function initClaimCheckForm() {
  const form = document.querySelector(".js-claim-check-form");
  if (!form) return;

  const box = document.querySelector(".js-claim-check-result");
  const input = form.querySelector(".js-claim-check-input");
  if (!box || !input) return;

  input.addEventListener("input", () => {
    digitsOnly(input);
    setError(form, "");
  });
  input.addEventListener("paste", () => setTimeout(() => digitsOnly(input), 0));

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    submit(form, box);
  });

  input.focus();
}
