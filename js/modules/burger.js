export const burger = () => {
  const burger = document.querySelector(".burger");
  const nav = document.querySelector(".mobile-nav");

  burger.addEventListener("click", () => {
    if (burger.classList.contains("active")) {
      burger.classList.remove("active");
      nav.classList.remove("active");
      document.body.style.overflow = "";
    } else {
      burger.classList.add("active");
      nav.classList.add("active");
      document.body.style.overflow = "hidden";
    }
  });
};

export const mobileNavAccordion = () => {
  const items = document.querySelectorAll(".mobile-nav__item");

  if (!items.length) return;

  const setHeight = (item, open) => {
    const submenu = item.querySelector(".mobile-nav__submenu");
    if (!submenu) return;

    if (open) {
      submenu.style.maxHeight = submenu.scrollHeight + "px";
    } else {
      submenu.style.maxHeight = "0px";
    }
  };

  items.forEach((item) => {
    const trigger = item.querySelector(".mobile-nav__link");
    const submenu = item.querySelector(".mobile-nav__submenu");

    if (!trigger || !submenu) return;

    setHeight(item, item.classList.contains("active"));

    trigger.addEventListener("click", (e) => {
      e.preventDefault();

      const isOpen = item.classList.contains("active");

      items.forEach((i) => {
        if (i === item) return;
        i.classList.remove("active");
        const t = i.querySelector(".mobile-nav__link");
        if (t) t.setAttribute("aria-expanded", "false");
        setHeight(i, false);
      });

      item.classList.toggle("active", !isOpen);
      trigger.setAttribute("aria-expanded", String(!isOpen));
      setHeight(item, !isOpen);
    });
  });

  window.addEventListener("resize", () => {
    document.querySelectorAll(".mobile-nav__item.active").forEach((item) => {
      setHeight(item, true);
    });
  });
};
