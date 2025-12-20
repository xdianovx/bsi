export const initAccordion = (rootSelector = ".accordion") => {
  const roots = document.querySelectorAll(rootSelector);
  if (!roots.length) return;

  const ANIM_MS = 220; // приятная быстрая анимация

  const setBtnState = (btn, expanded) => {
    btn.setAttribute("aria-expanded", expanded ? "true" : "false");
  };

  const setPanelState = (panel, expanded) => {
    panel.hidden = !expanded;
    panel.setAttribute("aria-hidden", expanded ? "false" : "true");
  };

  const openItem = (item) => {
    const btn = item.querySelector(".accordion__btn");
    const panel = item.querySelector(".accordion__panel");
    if (!btn || !panel) return;

    if (item.classList.contains("is-open")) return;

    item.classList.add("is-open");
    setBtnState(btn, true);

    // Анимация: height 0 -> scrollHeight
    panel.hidden = false;
    panel.style.overflow = "hidden";
    panel.style.height = "0px";
    panel.style.transition = `height ${ANIM_MS}ms ease`;

    const target = panel.scrollHeight;

    // форсим reflow
    panel.getBoundingClientRect();

    panel.style.height = `${target}px`;

    window.setTimeout(() => {
      panel.style.height = "";
      panel.style.transition = "";
      panel.style.overflow = "";
      setPanelState(panel, true);
    }, ANIM_MS);
  };

  const closeItem = (item) => {
    const btn = item.querySelector(".accordion__btn");
    const panel = item.querySelector(".accordion__panel");
    if (!btn || !panel) return;

    if (!item.classList.contains("is-open")) return;

    item.classList.remove("is-open");
    setBtnState(btn, false);

    // Анимация: height current -> 0
    panel.style.overflow = "hidden";
    panel.style.height = `${panel.scrollHeight}px`;
    panel.style.transition = `height ${ANIM_MS}ms ease`;

    // форсим reflow
    panel.getBoundingClientRect();

    panel.style.height = "0px";

    window.setTimeout(() => {
      panel.style.transition = "";
      panel.style.overflow = "";
      panel.style.height = "";
      setPanelState(panel, false);
    }, ANIM_MS);
  };

  const syncToggleAllText = (root) => {
    const toggleAll = root.querySelector(".accordion__toggle-all");
    if (!toggleAll) return;

    const items = root.querySelectorAll(".accordion__item");
    const opened = root.querySelectorAll(".accordion__item.is-open");

    toggleAll.textContent = opened.length === items.length ? "Свернуть все" : "Раскрыть все";
    toggleAll.setAttribute("aria-expanded", opened.length === items.length ? "true" : "false");
  };

  roots.forEach((root) => {
    const items = root.querySelectorAll(".accordion__item");

    // Инициализация ARIA / скрытий
    items.forEach((item) => {
      const btn = item.querySelector(".accordion__btn");
      const panel = item.querySelector(".accordion__panel");
      if (!btn || !panel) return;

      // если хочешь дефолтно открытые — просто добавь is-open в HTML
      const expanded = item.classList.contains("is-open");

      btn.setAttribute("type", "button");
      btn.setAttribute("aria-controls", "");
      setBtnState(btn, expanded);

      panel.setAttribute("role", "region");
      setPanelState(panel, expanded);
    });

    syncToggleAllText(root);

    // Клик по пункту
    root.addEventListener("click", (e) => {
      const btn = e.target.closest(".accordion__btn");
      if (!btn || !root.contains(btn)) return;

      const item = btn.closest(".accordion__item");
      if (!item) return;

      if (item.classList.contains("is-open")) closeItem(item);
      else openItem(item);

      syncToggleAllText(root);
    });

    // Кнопка “раскрыть все”
    const toggleAll = root.querySelector(".accordion__toggle-all");
    if (toggleAll) {
      toggleAll.addEventListener("click", () => {
        const allItems = root.querySelectorAll(".accordion__item");
        const allOpen = root.querySelectorAll(".accordion__item.is-open").length === allItems.length;

        allItems.forEach((it) => (allOpen ? closeItem(it) : openItem(it)));
        syncToggleAllText(root);
      });
    }

    // Если контент внутри панелей меняет высоту после открытия (картинки догружаются)
    // можно мягко поправить: при ресайзе пересчитать высоты открытых во время анимации не надо,
    // но после открытия у нас height сбрасывается -> авто, так что обычно всё ок.
  });
};
