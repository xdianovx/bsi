import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";

const initEducationProgramAccordion = () => {
  const accordions = document.querySelectorAll(".js-education-program-accordion");
  const ANIM_MS = 300;
  
  accordions.forEach((accordion) => {
    const toggle = accordion.querySelector(".js-education-program-toggle");
    const content = accordion.querySelector(".js-education-program-content");
    
    if (!toggle || !content) return;
    
    const open = () => {
      accordion.classList.add("is-open");
      toggle.setAttribute("aria-expanded", "true");
      
      content.hidden = false;
      content.style.overflow = "hidden";
      content.style.willChange = "height";
      content.style.transition = `height ${ANIM_MS}ms ease`;
      
      const start = 0;
      const target = content.scrollHeight;
      
      content.style.height = `${start}px`;
      content.offsetHeight;
      
      content.style.height = `${target}px`;
      
      const onEnd = (e) => {
        if (e.target !== content) return;
        content.removeEventListener("transitionend", onEnd);
        content.style.height = "";
        content.style.overflow = "";
        content.style.willChange = "";
        content.style.transition = "";
      };
      
      content.addEventListener("transitionend", onEnd);
    };
    
    const close = () => {
      accordion.classList.remove("is-open");
      toggle.setAttribute("aria-expanded", "false");
      
      const start = content.scrollHeight;
      content.style.overflow = "hidden";
      content.style.willChange = "height";
      content.style.transition = `height ${ANIM_MS}ms ease`;
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
      
      content.addEventListener("transitionend", onEnd);
    };
    
    toggle.addEventListener("click", (e) => {
      e.preventDefault();
      const isExpanded = toggle.getAttribute("aria-expanded") === "true";
      
      if (isExpanded) {
        close();
      } else {
        open();
      }
    });
  });
};

export const initSingleEducationPrograms = () => {
  const root = document.querySelector(".js-education-programs");
  if (!root) return;

  const filters = root.querySelector(".js-education-programs-filters");
  const list = root.querySelector(".js-education-programs-list");
  if (!filters || !list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  // Получаем education_id из data-атрибута блока программ
  const educationId = parseInt(root.getAttribute("data-education-id") || "0", 10);
  
  if (!educationId) {
    return;
  }

  const ageMinInput = filters.querySelector('input[name="program_age_min"]');
  const ageMaxInput = filters.querySelector('input[name="program_age_max"]');
  const durationInput = filters.querySelector('input[name="program_duration"]');
  const dateInput = filters.querySelector('input[name="program_date"]');

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  // Получаем доступные даты и ближайшую дату из data-атрибутов
  const availableDatesStr = root.getAttribute("data-available-dates");
  const nearestDate = root.getAttribute("data-nearest-date") || "";
  let availableDates = [];
  
  if (availableDatesStr) {
    try {
      availableDates = JSON.parse(availableDatesStr);
    } catch (e) {
      availableDates = [];
    }
  }

  let datePickerInstance = null;

  const loadPrograms = async () => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "education_programs_by_school");
      body.set("education_id", String(educationId));

      if (ageMinInput && ageMinInput.value) {
        body.set("program_age_min", ageMinInput.value);
      }
      if (ageMaxInput && ageMaxInput.value) {
        body.set("program_age_max", ageMaxInput.value);
      }

      if (durationInput && durationInput.value) {
        body.set("program_duration", durationInput.value);
      }

      if (datePickerInstance && datePickerInstance.selectedDates.length > 0) {
        const selectedDate = datePickerInstance.selectedDates[0];
        body.set("program_date", selectedDate.toISOString().split("T")[0]);
      } else if (dateInput && dateInput.value) {
        body.set("program_date", dateInput.value);
      }

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      list.innerHTML = json.data.html || "";
      initEducationProgramAccordion();
    } catch (e) {
      // Error handling without console output
    } finally {
      setLoading(false);
    }
  };

  if (ageMinInput) {
    ageMinInput.addEventListener("change", loadPrograms);
  }
  if (ageMaxInput) {
    ageMaxInput.addEventListener("change", loadPrograms);
  }

  if (durationInput) {
    durationInput.addEventListener("change", loadPrograms);
  }

  // Инициализация flatpickr для даты заселения
  if (dateInput) {
    const flatpickrOptions = {
      locale: Russian,
      dateFormat: "Y-m-d",
      minDate: "today",
      disableMobile: true,
      onChange: (selectedDates) => {
        if (selectedDates.length > 0) {
          loadPrograms();
        }
      },
    };

    if (availableDates.length > 0) {
      // Преобразуем даты в формат для flatpickr (enable только доступные даты)
      const enableDates = availableDates
        .filter(date => date && date.trim())
        .map(date => {
          // Убеждаемся, что дата в правильном формате
          const dateStr = date.trim();
          const d = new Date(dateStr + 'T00:00:00');
          // Проверяем, что дата валидна
          if (isNaN(d.getTime())) {
            return null;
          }
          return d;
        })
        .filter(d => d !== null);

      if (enableDates.length > 0) {
        flatpickrOptions.enable = enableDates;
      }
    }

    datePickerInstance = flatpickr(dateInput, flatpickrOptions);

    // Устанавливаем ближайшую дату по умолчанию
    if (nearestDate && nearestDate.trim()) {
      try {
        datePickerInstance.setDate(nearestDate.trim(), false);
        // Запускаем фильтрацию с ближайшей датой
        setTimeout(() => {
          loadPrograms();
        }, 100);
      } catch (e) {
        // Если не удалось установить дату, просто продолжаем
      }
    }
  }
  
  initEducationProgramAccordion();
};

