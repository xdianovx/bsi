export const tabs = (wrap, btns, contents) => {
  const tabsContainers = document.querySelectorAll(".tabs");

  tabsContainers.forEach((container) => {
    const tabButtons = container.querySelectorAll(".tab-button");
    const tabContents = container.querySelectorAll(".tab-content__item");

    const switchTab = (index) => {
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      tabContents.forEach((content) => content.classList.remove("active"));

      tabButtons[index].classList.add("active");
      tabContents[index].classList.add("active");
    };

    tabButtons.forEach((button, index) => {
      button.addEventListener("click", () => {
        switchTab(index);
      });
    });
  });
};
