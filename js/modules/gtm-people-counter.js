// modules/people.js
// Счетчик людей + возраста детей
// Улучшения:
// - Работает в scope (можно передавать root/output как элемент, а не только селектор)
// - Не завязан на document.querySelector (удобно для табов)
// - Методы: getState(), setState(), destroy()

export function peopleCounter({
  rootSelector,
  outputSelector,
  rootEl,
  outputEl,
  maxAdults = 4,
  maxChildren = 3,
  onChange,
  initial = null, // { adults, children, ages[] }
}) {
  // 1) Находим корень и output
  const root = rootEl || (rootSelector ? document.querySelector(rootSelector) : null);
  const output = outputEl || (outputSelector ? document.querySelector(outputSelector) : null);
  if (!root || !output) return null;

  // 2) Ищем элементы управления внутри root
  const adultsMinus = root.querySelector(".adults-minus");
  const adultsPlus = root.querySelector(".adults-plus");
  const adultsValueEl = root.querySelector(".adults-value");

  const childrenMinus = root.querySelector(".children-minus");
  const childrenPlus = root.querySelector(".children-plus");
  const childrenValueEl = root.querySelector(".children-value");

  const agesContainer = root.querySelector(".children-ages");

  if (!adultsMinus || !adultsPlus || !adultsValueEl || !childrenMinus || !childrenPlus || !childrenValueEl || !agesContainer) {
    return null;
  }

  // 3) State
  const ADULTS_MIN = 1;
  const CHILDREN_MIN = 0;

  let adults = Number(adultsValueEl.textContent) || 2;
  let children = Number(childrenValueEl.textContent) || 0;
  let ages = [];

  // если дали initial — применяем
  if (initial) {
    if (typeof initial.adults === "number") adults = initial.adults;
    if (typeof initial.children === "number") children = initial.children;
    if (Array.isArray(initial.ages)) ages = initial.ages.map((n) => Number(n) || 0);
  }

  // 4) Helpers
  function peopleWord(n) {
    const last2 = n % 100;
    if (last2 >= 11 && last2 <= 14) return "человек";
    const last = n % 10;
    if (last === 1) return "человек";
    if (last >= 2 && last <= 4) return "человека";
    return "человек";
  }

  function clampState() {
    adults = Math.max(ADULTS_MIN, Math.min(maxAdults, adults));
    children = Math.max(CHILDREN_MIN, Math.min(maxChildren, children));
    ages = Array.isArray(ages) ? ages : [];
    ages = ages.map((n) => Number(n) || 0);
    ages = ages.slice(0, children);
    while (ages.length < children) ages.push(0);
  }

  function buildAgeSelect(index, value = 0) {
    const wrap = document.createElement("div");
    wrap.className = "child-age";

    const label = document.createElement("span");
    label.className = "child-age__label";
    label.textContent = `Ребёнок ${index + 1}. Возраст`;

    const select = document.createElement("select");
    select.className = "child-age__select";

    const opts = [
      { value: 0, label: "До 2 лет" },
      ...Array.from({ length: 17 }, (_, i) => ({
        value: i + 1,
        label: `${i + 1} лет`,
      })),
    ];

    opts.forEach((opt) => {
      const o = document.createElement("option");
      o.value = String(opt.value);
      o.textContent = opt.label;
      if (String(opt.value) === String(value)) o.selected = true;
      select.appendChild(o);
    });

    select.addEventListener("change", () => {
      ages[index] = Number(select.value) || 0;
      emitChange();
    });

    wrap.appendChild(label);
    wrap.appendChild(select);
    return wrap;
  }

  function syncAgesFields() {
    // обрезали/дополнили ages под children
    clampState();

    // DOM под children
    while (agesContainer.children.length > children) {
      agesContainer.removeChild(agesContainer.lastElementChild);
    }

    while (agesContainer.children.length < children) {
      const idx = agesContainer.children.length;
      const value = ages[idx] ?? 0;
      const field = buildAgeSelect(idx, value);
      agesContainer.appendChild(field);
    }

    // синхронизируем выбранные значения (если state меняли извне)
    Array.from(agesContainer.querySelectorAll(".child-age__select")).forEach((sel, idx) => {
      const v = ages[idx] ?? 0;
      if (String(sel.value) !== String(v)) sel.value = String(v);
    });
  }

  function render() {
    clampState();

    adultsValueEl.textContent = String(adults);
    childrenValueEl.textContent = String(children);

    const total = adults + children;
    output.textContent = `${total} ${peopleWord(total)}`;

    syncAgesFields();
  }

  function emitChange() {
    if (typeof onChange === "function") {
      onChange({ adults, children, ages: [...ages], total: adults + children });
    }
  }

  // 5) Handlers
  const onAdultsMinus = () => {
    if (adults > ADULTS_MIN) {
      adults--;
      render();
      emitChange();
    }
  };

  const onAdultsPlus = () => {
    if (adults < maxAdults) {
      adults++;
      render();
      emitChange();
    }
  };

  const onChildrenMinus = () => {
    if (children > CHILDREN_MIN) {
      children--;
      render();
      emitChange();
    }
  };

  const onChildrenPlus = () => {
    if (children < maxChildren) {
      children++;
      render();
      emitChange();
    }
  };

  adultsMinus.addEventListener("click", onAdultsMinus);
  adultsPlus.addEventListener("click", onAdultsPlus);
  childrenMinus.addEventListener("click", onChildrenMinus);
  childrenPlus.addEventListener("click", onChildrenPlus);

  // 6) Init
  render();
  emitChange();

  // 7) Public API
  return {
    getState() {
      return { adults, children, ages: [...ages], total: adults + children };
    },
    setState(next) {
      if (!next) return;
      if (typeof next.adults === "number") adults = next.adults;
      if (typeof next.children === "number") children = next.children;
      if (Array.isArray(next.ages)) ages = next.ages.map((n) => Number(n) || 0);
      render();
      emitChange();
    },
    destroy() {
      adultsMinus.removeEventListener("click", onAdultsMinus);
      adultsPlus.removeEventListener("click", onAdultsPlus);
      childrenMinus.removeEventListener("click", onChildrenMinus);
      childrenPlus.removeEventListener("click", onChildrenPlus);
    },
  };
}
