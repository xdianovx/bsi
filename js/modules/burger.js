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
