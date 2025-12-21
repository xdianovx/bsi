// country-aside.js
export const initCountryAside = () => {
  const root = document.querySelector("[data-country-aside]");
  if (!root) return;

  const prefersReduce = window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches;
  const DURATION = prefersReduce ? 0 : 240;

  const accordions = Array.from(root.querySelectorAll("[data-accordion]"));
  if (!accordions.length) return;

  const open = (wrap, trigger, content) => {
    wrap.classList.add("is-open");
    trigger.setAttribute("aria-expanded", "true");

    content.hidden = false;
    content.style.overflow = "hidden";
    content.style.willChange = "height";
    content.style.transition = `height ${DURATION}ms ease`;

    content.style.height = "0px";
    content.offsetHeight;

    const target = content.scrollHeight;
    content.style.height = `${target}px`;

    const onEnd = (e) => {
      if (e.target !== content) return;
      content.removeEventListener("transitionend", onEnd);
      content.style.height = "auto";
      content.style.overflow = "";
      content.style.willChange = "";
      content.style.transition = "";
    };

    if (DURATION) content.addEventListener("transitionend", onEnd);
    else onEnd({ target: content });
  };

  const close = (wrap, trigger, content) => {
    wrap.classList.remove("is-open");
    trigger.setAttribute("aria-expanded", "false");

    content.style.overflow = "hidden";
    content.style.willChange = "height";
    content.style.transition = `height ${DURATION}ms ease`;

    const start = content.scrollHeight;
    content.style.height = `${start}px`;
    content.offsetHeight;

    content.style.height = "0px";

    const onEnd = (e) => {
      if (e.target !== content) return;
      content.removeEventListener("transitionend", onEnd);
      content.hidden = true;
      content.style.height = "";
      content.style.overflow = "";
      content.style.willChange = "";
      content.style.transition = "";
    };

    if (DURATION) content.addEventListener("transitionend", onEnd);
    else onEnd({ target: content });
  };

  accordions.forEach((wrap) => {
    const trigger = wrap.querySelector("[data-accordion-trigger]");
    const content = wrap.querySelector("[data-accordion-content]");
    if (!trigger || !content) return;

    // если сервером уже открыт (у тебя $is_tours_open) — приводим в порядок inline-стили
    if (!content.hidden && wrap.classList.contains("is-open")) {
      trigger.setAttribute("aria-expanded", "true");
      content.style.height = "auto";
    } else {
      trigger.setAttribute("aria-expanded", "false");
      content.hidden = true;
    }

    trigger.addEventListener("click", (e) => {
      e.preventDefault();

      const isOpen = wrap.classList.contains("is-open") && !content.hidden;
      if (isOpen) close(wrap, trigger, content);
      else open(wrap, trigger, content);
    });
  });
};
