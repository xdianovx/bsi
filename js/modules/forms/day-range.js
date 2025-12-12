// modules/day-range.js
export function createDayRange({ gridSelector, defaultStartDay = null, defaultEndDay = null, onChange = null }) {
  const grid = document.querySelector(gridSelector);
  if (!grid) return null;

  const items = Array.from(grid.querySelectorAll(".day-item"));
  if (!items.length) return null;

  let startIndex = null;
  let endIndex = null;

  function getIndexByDay(day) {
    return items.findIndex((el) => Number(el.textContent) === Number(day));
  }

  function render() {
    items.forEach((item, index) => {
      if (item.classList.contains("is-disabled")) return;

      item.classList.remove("is-active");

      if (startIndex === null) return;

      // включаем диапазон
      if (endIndex === null && index === startIndex) {
        item.classList.add("is-active");
      }

      if (endIndex !== null && index >= startIndex && index <= endIndex) {
        item.classList.add("is-active");
      }
    });
  }

  function handleItemClick(e) {
    const item = e.currentTarget;
    if (item.classList.contains("is-disabled")) return;

    const index = items.indexOf(item);

    // первый клик или перезапуск
    if (startIndex === null || (startIndex !== null && endIndex !== null) || index <= startIndex) {
      startIndex = index;
      endIndex = null;
    } else {
      // второй клик
      endIndex = index;
    }

    render();

    if (typeof onChange === "function" && startIndex !== null && endIndex !== null) {
      const startDay = Number(items[startIndex].textContent);
      const endDay = Number(items[endIndex].textContent);

      onChange({ startDay, endDay, startIndex, endIndex });
    }
  }

  // вешаем слушатели
  items.forEach((item) => {
    item.addEventListener("click", handleItemClick);
  });

  // ---- предвыбранные дни ----
  if (defaultStartDay !== null) {
    startIndex = getIndexByDay(defaultStartDay);
  }

  if (defaultEndDay !== null) {
    endIndex = getIndexByDay(defaultEndDay);
  }

  if (startIndex !== null) {
    render();

    if (endIndex !== null && typeof onChange === "function") {
      onChange({
        startDay: Number(items[startIndex].textContent),
        endDay: Number(items[endIndex].textContent),
        startIndex,
        endIndex,
      });
    }
  }

  return {
    getRange() {
      return { startIndex, endIndex };
    },
    reset() {
      startIndex = null;
      endIndex = null;
      render();
    },
  };
}
