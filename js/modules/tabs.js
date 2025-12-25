export const tabs = (wrap, btns, contents) => {
  const tabsContainers = document.querySelectorAll(".tabs");

  tabsContainers.forEach((container) => {
    const tabButtons = container.querySelectorAll(".tab-button");
    const tabContents = container.querySelectorAll(".tab-content__item");

    // Пропускаем контейнеры без кнопок табов
    if (tabButtons.length === 0) {
      return;
    }

    const switchTab = (index) => {
      // Убираем активный класс со всех кнопок
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      
      // Убираем активный класс со всех контентов (если они есть)
      tabContents.forEach((content) => content.classList.remove("active"));

      // Добавляем активный класс к выбранной кнопке
      if (tabButtons[index]) {
        tabButtons[index].classList.add("active");
      }

      // Добавляем активный класс к соответствующему контенту (если он есть)
      if (tabContents[index]) {
        tabContents[index].classList.add("active");
      }
    };

    tabButtons.forEach((button, index) => {
      button.addEventListener("click", () => {
        switchTab(index);
      });
    });
  });
};
