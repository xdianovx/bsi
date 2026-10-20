/**
 * Страница наград (page-awards.php): навигация по годам.
 * Подсвечивает год, который сейчас на экране, плавно переходит к году по клику,
 * отдаёт отступ под шапкой (--awards-sticky-top) и высоту навигации (--awards-nav-height) — по ним прилипают навигация и заголовок года.
 */
export function initAwardsPage() {
  const nav = document.querySelector("[data-awards-nav]");
  if (!nav) return;

  const links = [...nav.querySelectorAll("[data-awards-nav-link]")];
  const sections = [...document.querySelectorAll("[data-awards-year]")];
  if (!links.length || !sections.length) return;

  // Мобилка: шапка сайта прилипает (header.scss) — навигация встаёт под неё; десктоп — под админ-бар, если он есть
  const stickyTop = () => {
    const header = document.querySelector(".header");
    if (header && getComputedStyle(header).position === "sticky") {
      return (parseFloat(getComputedStyle(header).top) || 0) + header.offsetHeight;
    }
    const bar = document.getElementById("wpadminbar");
    return bar && getComputedStyle(bar).position === "fixed" ? bar.offsetHeight : 0;
  };

  const setNavHeight = () => {
    const root = document.documentElement.style;
    root.setProperty("--awards-sticky-top", `${stickyTop()}px`);
    root.setProperty("--awards-nav-height", `${nav.offsetHeight}px`);
  };
  setNavHeight();
  window.addEventListener("resize", setNavHeight);

  let current = "";
  const setActive = (year) => {
    if (year === current) return;
    current = year;
    links.forEach((link) => {
      const active = link.dataset.awardsNavLink === year;
      link.classList.toggle("awards-nav__link--active", active);
      // Мобилка: чипы в одну строку с прокруткой — активный держим в видимой части
      if (active && link.parentElement.scrollWidth > link.parentElement.clientWidth) {
        const list = link.parentElement;
        list.scrollTo({ left: link.offsetLeft - (list.clientWidth - link.offsetWidth) / 2, behavior: "smooth" });
      }
    });
  };

  // Активный — последний год, чей верх уже прошёл под навигацию
  const update = () => {
    const line = nav.getBoundingClientRect().bottom + 40;
    let year = sections[0].dataset.awardsYear;
    for (const section of sections) {
      if (section.getBoundingClientRect().top <= line) year = section.dataset.awardsYear;
    }
    setActive(year);
  };

  let ticking = false;
  window.addEventListener(
    "scroll",
    () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        update();
        ticking = false;
      });
    },
    { passive: true }
  );
  update();

  links.forEach((link) => {
    link.addEventListener("click", (e) => {
      const target = document.getElementById(`year-${link.dataset.awardsNavLink}`);
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
      history.replaceState(null, "", `#year-${link.dataset.awardsNavLink}`);
    });
  });
}
