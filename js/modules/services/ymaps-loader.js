/**
 * Ожидание API Яндекс.Карт v3.
 *
 * Скрипт API подключён отдельным тегом в footer.php после wp_footer, поэтому на
 * первой (некешированной) загрузке `ymaps3` может ещё не появиться к моменту
 * инициализации модулей — карта тогда молча пропадала. Здесь ждём глобал
 * поллингом и только по таймауту признаём, что API недоступен.
 */

const POLL_INTERVAL = 100;
const DEFAULT_TIMEOUT = 15000;

export const waitYmaps3 = async (timeout = DEFAULT_TIMEOUT) => {
  const deadline = Date.now() + timeout;

  while (typeof window.ymaps3 === "undefined") {
    if (Date.now() > deadline) {
      console.warn("Yandex Maps API v3 is not loaded");
      return null;
    }

    await new Promise((resolve) => setTimeout(resolve, POLL_INTERVAL));
  }

  try {
    await window.ymaps3.ready;
  } catch (e) {
    console.warn("Yandex Maps API failed to load", e);
    return null;
  }

  return window.ymaps3;
};
