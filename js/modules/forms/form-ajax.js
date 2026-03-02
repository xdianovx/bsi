/**
 * Общий обработчик отправки форм через AJAX с поддержкой reCAPTCHA v3.
 * Используется во всех формах заявок (виза, FIT, страхование, билеты, образование).
 */

export const RECAPTCHA_NOT_LOADED = "RECAPTCHA_NOT_LOADED";

/**
 * Добавляет токен reCAPTCHA в FormData, если ключ задан. Иначе не меняет formData.
 * @throws {Error} RECAPTCHA_NOT_LOADED — если ключ есть, но grecaptcha ещё не загружен
 */
export async function addRecaptchaToken(formData) {
  if (typeof ajax === "undefined" || !ajax.recaptchaSiteKey) {
    return;
  }
  if (typeof grecaptcha === "undefined") {
    throw new Error(RECAPTCHA_NOT_LOADED);
  }
  const token = await grecaptcha.execute(ajax.recaptchaSiteKey, {
    action: "submit",
  });
  formData.append("recaptcha_token", token);
}

/**
 * Отправляет FormData через admin-ajax.php (reCAPTCHA добавляется автоматически).
 * @param {FormData} formData — данные формы, должен содержать поле action
 * @param {{ debug?: boolean }} options — debug: true для вывода отправки и ответа в console
 * @returns {Promise<{ success: boolean, data?: object }>}
 * @throws {Error} RECAPTCHA_NOT_LOADED — если включена reCAPTCHA, но скрипт не загружен
 */
export async function submitFormWithRecaptcha(formData, options = {}) {
  const { debug = false } = options;

  await addRecaptchaToken(formData);

  if (debug) {
    const action = formData.get("action") || "unknown";
    const payload = Object.fromEntries(formData.entries());
    console.log(`[Form AJAX] ${action} — отправка:`, payload);
  }

  if (typeof ajax === "undefined") {
    throw new Error("ajax not defined");
  }

  const response = await fetch(ajax.url, {
    method: "POST",
    body: formData,
  });

  const result = await response.json();

  if (debug) {
    const action = formData.get("action") || "unknown";
    console.log(`[Form AJAX] ${action} — ответ:`, result);
  }

  return result;
}
