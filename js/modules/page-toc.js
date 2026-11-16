// Оглавление страницы по H2 (template-parts/ui/page-toc.php).
// Desktop: всегда раскрыто, подсвечивает текущий раздел.
// ≤1024px: свёрнуто по умолчанию, закрывается после перехода по ссылке.
export const initPageToc = () => {
  const root = document.querySelector("[data-page-toc]");
  if (!root) return;

  const mobile = window.matchMedia("(max-width: 1024px)");
  const syncOpen = () => {
    root.open = !mobile.matches;
  };
  syncOpen();
  mobile.addEventListener?.("change", syncOpen);

  const links = Array.from(root.querySelectorAll(".page-toc-link"));
  const pairs = links
    .map((link) => {
      const id = (link.getAttribute("href") || "").slice(1);
      return { link, heading: document.getElementById(id) };
    })
    .filter((p) => p.heading);

  // На мобилке блок сворачивается — текст под ним сдвигается вверх. Поэтому
  // сначала сворачиваем, потом сами прокручиваем к заголовку.
  pairs.forEach(({ link, heading }) => {
    link.addEventListener("click", (e) => {
      if (!mobile.matches) return;
      e.preventDefault();
      root.open = false;
      heading.scrollIntoView();
      history.replaceState(null, "", `#${heading.id}`);
    });
  });

  if (!pairs.length || !("IntersectionObserver" in window)) return;

  const setActive = (link) => {
    links.forEach((l) => l.classList.toggle("is-active", l === link));
  };

  // Текущий раздел — последний H2, который уже прошёл верхнюю треть экрана.
  const update = () => {
    const line = window.innerHeight * 0.3;
    let current = null;
    pairs.forEach((p) => {
      if (p.heading.getBoundingClientRect().top <= line) current = p.link;
    });
    setActive(current);
  };

  const observer = new IntersectionObserver(update, { rootMargin: "0px 0px -70% 0px" });
  pairs.forEach((p) => observer.observe(p.heading));
  update();
};
