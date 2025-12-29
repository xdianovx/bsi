// modules/day-range.js
// Выбор диапазона ночей (1..30) по кликам
// Улучшения:
// - Работает в scope (можно передавать gridEl или rootEl + gridSelector)
// - Не завязан на document.querySelector напрямую
// - Методы: getState(), setState(), reset(), destroy()

export function createDayRange({
  gridSelector, // ".day-grid"
  gridEl, // можно сразу передать элемент грида
  rootEl, // или корневой элемент таба/виджета
  defaultStartDay = null,
  defaultEndDay = null,
  onChange = null,
}) {
  const grid =
    gridEl || (rootEl ? rootEl.querySelector(gridSelector) : null) || (gridSelector ? document.querySelector(gridSelector) : null);

  if (!grid) return null;

  const items = Array.from(grid.querySelectorAll(".day-item"));
  if (!items.length) return null;

  let startIndex = null;
  let endIndex = null;

  // ---- helpers ----
  function getIndexByDay(day) {
    return items.findIndex((el) => Number(el.textContent) === Number(day));
  }

  function isDisabled(el) {
    return el.classList.contains("is-disabled");
  }

  function getDays() {
    const startDay = startIndex === null ? null : Number(items[startIndex]?.textContent);
    const endDay = endIndex === null ? null : Number(items[endIndex]?.textContent);
    return { startDay, endDay };
  }

  function emitChange(reason = "change") {
    if (typeof onChange !== "function") return;
    const { startDay, endDay } = getDays();

    onChange({
      startDay,
      endDay,
      startIndex,
      endIndex,
      reason, // "init" | "click" | "setState" | "reset" | "change"
    });
  }

  function render() {
    items.forEach((item, index) => {
      if (isDisabled(item)) return;

      item.classList.remove("is-active");

      if (startIndex === null) return;

      // один выбранный день (первый клик)
      if (endIndex === null && index === startIndex) {
        item.classList.add("is-active");
      }

      // диапазон
      if (endIndex !== null && index >= startIndex && index <= endIndex) {
        item.classList.add("is-active");
      }
    });
  }

  // ---- click handler ----
  function handleItemClick(e) {
    const item = e.currentTarget;
    if (isDisabled(item)) return;

    const index = items.indexOf(item);

    // если клик на уже выбранный элемент (когда выбран только startIndex) - делаем его диапазоном (одно число)
    if (startIndex !== null && endIndex === null && index === startIndex) {
      endIndex = index;
    }
    // первый клик или перезапуск, или клик "влево" от старта
    else if (startIndex === null || (startIndex !== null && endIndex !== null) || index <= startIndex) {
      startIndex = index;
      endIndex = null;
    } else {
      endIndex = index;
    }

    render();

    // вызываем onChange когда выбран хотя бы один день
    if (startIndex !== null) {
      emitChange("click");
    }
  }

  // ---- bind ----
  items.forEach((item) => item.addEventListener("click", handleItemClick));

  // ---- init defaults ----
  if (defaultStartDay !== null) startIndex = getIndexByDay(defaultStartDay);
  if (defaultEndDay !== null) endIndex = getIndexByDay(defaultEndDay);

  if (startIndex !== null) {
    // если endIndex есть, но он левее startIndex — сбрасываем
    if (endIndex !== null && endIndex <= startIndex) endIndex = null;

    render();

    // эмитим сразу при инициализации
    emitChange("init");
  }

  // ---- API ----
  return {
    getState() {
      const { startDay, endDay } = getDays();
      return { startIndex, endIndex, startDay, endDay };
    },

    // можно задавать через дни или индексы:
    // setState({ startDay: 5, endDay: 10 })
    // setState({ startIndex: 4, endIndex: 9 })
    setState(next) {
      if (!next) return;

      if (typeof next.startIndex === "number") startIndex = next.startIndex;
      if (typeof next.endIndex === "number") endIndex = next.endIndex;

      if (typeof next.startDay === "number") startIndex = getIndexByDay(next.startDay);
      if (typeof next.endDay === "number") endIndex = getIndexByDay(next.endDay);

      // нормализация
      if (startIndex === -1) startIndex = null;
      if (endIndex === -1) endIndex = null;
      if (startIndex !== null && endIndex !== null && endIndex <= startIndex) endIndex = null;

      render();

      if (startIndex !== null && endIndex !== null) emitChange("setState");
    },

    reset() {
      startIndex = null;
      endIndex = null;
      render();
      emitChange("reset");
    },

    destroy() {
      items.forEach((item) => item.removeEventListener("click", handleItemClick));
    },
  };
}
