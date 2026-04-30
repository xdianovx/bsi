/**
 * Баннер cookie: кнопка «Принять» + лог на сервер.
 * Яндекс.Метрика подключается всегда в header.php (независимо от баннера).
 */

const STORAGE_KEY = "bsi_cookie_consent";
const CONSENT_VALUE = "accept";

function logConsent(choice) {
  const cfg = typeof window.bsiCookieConsent !== "undefined" ? window.bsiCookieConsent : null;
  if (!cfg || !cfg.ajaxUrl || !cfg.nonce) {
    return;
  }
  const fd = new FormData();
  fd.append("action", "bsi_log_cookie_consent");
  fd.append("nonce", cfg.nonce);
  fd.append("consent", choice);
  fetch(cfg.ajaxUrl, { method: "POST", body: fd, credentials: "same-origin" }).catch(() => {});
}

function hideBanner() {
  document.documentElement.classList.add("bsi-cookie-consented");
}

function applyExistingConsent() {
  try {
    return localStorage.getItem(STORAGE_KEY);
  } catch (e) {
    return null;
  }
}

function bindBanner() {
  const root = document.getElementById("bsi-cookie-consent");
  if (!root) {
    return;
  }

  const btn = root.querySelector("[data-cookie-accept]");
  if (btn) {
    btn.addEventListener("click", () => {
      try {
        localStorage.setItem(STORAGE_KEY, CONSENT_VALUE);
      } catch (e) {}
      logConsent(CONSENT_VALUE);
      hideBanner();
    });
  }
}

export function initCookieConsent() {
  applyExistingConsent();
  bindBanner();
}
