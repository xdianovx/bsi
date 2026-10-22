/**
 * ВРЕМЕННО (2026-10-22): хостинг (nginx reg.ru) отвечает 403 на любой POST multipart/form-data —
 * формы сайта падали. FormData без файлов отправляем как application/x-www-form-urlencoded:
 * PHP получает те же $_POST. Формы с файлами (отклик на вакансию) по-прежнему идут multipart.
 * Убрать, когда хостинг снимет блокировку.
 */
const nativeFetch = window.fetch.bind(window);

window.fetch = (input, init) => {
  const body = init && init.body;

  if (body instanceof FormData) {
    const params = new URLSearchParams();
    let hasFile = false;

    for (const [key, value] of body.entries()) {
      if (typeof value !== "string") {
        // Пустой <input type="file"> браузер кладёт как File размером 0 — его можно не отправлять.
        if (value.size > 0) {
          hasFile = true;
          break;
        }
        continue;
      }
      params.append(key, value);
    }

    if (!hasFile) {
      return nativeFetch(input, { ...init, body: params });
    }
  }

  return nativeFetch(input, init);
};
