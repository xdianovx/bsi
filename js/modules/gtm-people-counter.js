// modules/people.js
export function peopleCounter({ rootSelector, outputSelector, maxAdults = 4, maxChildren = 3, onChange }) {
  const root = document.querySelector(rootSelector);
  const output = document.querySelector(outputSelector);
  if (!root || !output) return null;

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

  let adults = Number(adultsValueEl.textContent) || 2;
  let children = Number(childrenValueEl.textContent) || 0;
  let ages = [];

  const ADULTS_MIN = 1;
  const CHILDREN_MIN = 0;

  function peopleWord(n) {
    const last2 = n % 100;
    if (last2 >= 11 && last2 <= 14) return "человек";
    const last = n % 10;
    if (last === 1) return "человек";
    if (last >= 2 && last <= 4) return "человека";
    return "человек";
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
      o.value = opt.value;
      o.textContent = opt.label;
      if (String(opt.value) === String(value)) o.selected = true;
      select.appendChild(o);
    });

    select.addEventListener("change", () => {
      ages[index] = Number(select.value);
      emitChange();
    });

    wrap.appendChild(label);
    wrap.appendChild(select);
    return wrap;
  }

  function syncAgesFields() {
    ages = ages.slice(0, children);

    while (agesContainer.children.length > children) {
      agesContainer.removeChild(agesContainer.lastElementChild);
    }

    while (agesContainer.children.length < children) {
      const idx = agesContainer.children.length;
      const value = ages[idx] ?? 0;
      const field = buildAgeSelect(idx, value);
      agesContainer.appendChild(field);
      if (ages[idx] == null) ages[idx] = value;
    }
  }

  function render() {
    adultsValueEl.textContent = adults;
    childrenValueEl.textContent = children;

    const total = adults + children;
    output.textContent = `${total} ${peopleWord(total)}`;

    syncAgesFields();
  }

  function emitChange() {
    if (typeof onChange === "function") {
      onChange({ adults, children, ages: [...ages], total: adults + children });
    }
  }

  adultsMinus.addEventListener("click", () => {
    if (adults > ADULTS_MIN) {
      adults--;
      render();
      emitChange();
    }
  });

  adultsPlus.addEventListener("click", () => {
    if (adults < maxAdults) {
      adults++;
      render();
      emitChange();
    }
  });

  childrenMinus.addEventListener("click", () => {
    if (children > CHILDREN_MIN) {
      children--;
      render();
      emitChange();
    }
  });

  childrenPlus.addEventListener("click", () => {
    if (children < maxChildren) {
      children++;
      render();
      emitChange();
    }
  });

  render();
  emitChange();

  return {
    getState() {
      return { adults, children, ages: [...ages], total: adults + children };
    },
  };
}
