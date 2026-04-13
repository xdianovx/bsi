import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "../forms/dropdown.js";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  addItemText: (value) => `Нажмите Enter, чтобы добавить «${value}»`,
  maxItemText: (maxItemCount) => `Можно выбрать максимум: ${maxItemCount}`,
  searchPlaceholderValue: "Поиск...",
};

export const initToursFilter = () => {
  const root = document.querySelector(".js-tours-page");
  if (!root) return;

  const form = root.querySelector(".js-tours-filter");
  const list = root.querySelector(".js-tours-list");
  const counter = root.querySelector(".js-tours-counter");
  const pagination = root.querySelector(".js-tours-pagination");
  if (!form || !list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  // Элементы фильтров
  const countrySelect = form.querySelector('select[name="country"]');
  const regionSelect = form.querySelector('select[name="region"]');
  const resortSelect = form.querySelector('select[name="resort"]');
  const tourTypeSelect = form.querySelector('select[name="tour_type"]');
  const searchInput = form.querySelector('input[name="search"]');
  const priceMinInput = form.querySelector('input[name="price_min"]');
  const priceMaxInput = form.querySelector('input[name="price_max"]');
  const dateFromInput = form.querySelector('input[name="date_from"]');
  const dateToInput = form.querySelector('input[name="date_to"]');
  const sortContainer = root.querySelector(".tours-page__sort");
  const perPageContainer = root.querySelector(".tours-page__per-page");
  const resetBtn = root.querySelector(".js-tours-reset");

  let datePickerInstance = null;
  let sortDropdown = null;
  let perPageDropdown = null;
  let currentSortValue = 'price_asc';
  let currentPerPage = 12;
  let currentPage = 1;
  let totalPages = 1;

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  const countActiveFilters = () => {
    let count = 0;

    if (countrySelect?.value) count++;
    if (regionSelect?.value) count++;
    if (resortSelect?.value) count++;
    if (tourTypeSelect?.value) count++;
    if (searchInput?.value) count++;
    if (priceMinInput?.value) count++;
    if (priceMaxInput?.value) count++;
    if (dateFromInput?.value) count++;
    if (dateToInput?.value) count++;

    return count;
  };

  const updateResetButton = () => {
    const count = countActiveFilters();
    if (resetBtn) {
      resetBtn.style.display = count > 0 ? "block" : "none";
    }
  };

  // Функция для обновления URL с параметрами фильтров
  const updateUrl = () => {
    const params = new URLSearchParams();

    if (countrySelect?.value) {
      params.set("country", countrySelect.value);
    }
    if (regionSelect?.value) {
      params.set("region", regionSelect.value);
    }
    if (resortSelect?.value) {
      params.set("resort", resortSelect.value);
    }
    if (tourTypeSelect?.value) {
      params.set("tour_type", tourTypeSelect.value);
    }
    if (searchInput?.value) {
      params.set("search", searchInput.value);
    }
    if (priceMinInput?.value) {
      params.set("price_min", priceMinInput.value);
    }
    if (priceMaxInput?.value) {
      params.set("price_max", priceMaxInput.value);
    }
    if (dateFromInput?.value) {
      params.set("date_from", dateFromInput.value);
    }
    if (dateToInput?.value) {
      params.set("date_to", dateToInput.value);
    }
    if (currentSortValue && currentSortValue !== 'price_asc') {
      params.set("sort", currentSortValue);
    }
    if (currentPerPage !== 12) {
      params.set("per_page", String(currentPerPage));
    }
    if (currentPage > 1) {
      params.set("page", String(currentPage));
    }

    const newUrl = params.toString()
      ? `${window.location.pathname}?${params.toString()}`
      : window.location.pathname;

    window.history.replaceState({}, '', newUrl);
  };

  const renderPagination = () => {
    if (!pagination) return;

    if (totalPages <= 1) {
      pagination.innerHTML = '';
      return;
    }

    const range = 2;
    const startPage = Math.max(1, currentPage - range);
    const endPage = Math.min(totalPages, currentPage + range);

    let html = '';

    if (currentPage > 1) {
      html += `<a href="#" class="page-numbers prev" data-page="${currentPage - 1}">&larr; Назад</a>`;
    }

    if (startPage > 1) {
      html += `<a href="#" class="page-numbers" data-page="1">1</a>`;
      if (startPage > 2) html += `<span class="page-numbers dots">&hellip;</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
      if (i === currentPage) {
        html += `<span class="page-numbers current">${i}</span>`;
      } else {
        html += `<a href="#" class="page-numbers" data-page="${i}">${i}</a>`;
      }
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) html += `<span class="page-numbers dots">&hellip;</span>`;
      html += `<a href="#" class="page-numbers" data-page="${totalPages}">${totalPages}</a>`;
    }

    if (currentPage < totalPages) {
      html += `<a href="#" class="page-numbers next" data-page="${currentPage + 1}">Вперед &rarr;</a>`;
    }

    pagination.innerHTML = html;

    pagination.querySelectorAll('a[data-page]').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const page = parseInt(link.getAttribute('data-page'), 10);
        loadTours(page);
        window.scrollTo({ top: root.offsetTop - 100, behavior: 'smooth' });
      });
    });
  };

  // Обновление опций фильтров на основе результатов
  const updateFilterOptions = (options) => {
    if (!options) return;

    // Обновляем страны
    if (countrySelect && options.countries) {
      const currentValue = countrySelect.value;
      const currentText = countrySelect.options[countrySelect.selectedIndex]?.text || '';

      countrySelect.innerHTML = '<option value="">Все страны</option>';
      options.countries.forEach((country) => {
        const opt = document.createElement('option');
        opt.value = country.id;
        opt.textContent = country.name;
        countrySelect.appendChild(opt);
      });

      if (currentValue && options.countries.some(c => c.id == currentValue)) {
        countrySelect.value = currentValue;
      }

      if (countryChoice) {
        countryChoice.destroy();
      }
      countryChoice = new Choices(countrySelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Все страны",
      });
    }

    // Аналогично для других селектов
  };

  const loadTours = async (page = 1) => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "tours_filter");
      body.set("paged", String(page));
      body.set("per_page", String(currentPerPage));

      if (currentSortValue) {
        body.set("sort", currentSortValue);
      }

      if (countrySelect?.value) {
        body.set("country", countrySelect.value);
      }
      if (regionSelect?.value) {
        body.set("region", regionSelect.value);
      }
      if (resortSelect?.value) {
        body.set("resort", resortSelect.value);
      }
      if (tourTypeSelect?.value) {
        body.set("tour_type", tourTypeSelect.value);
      }
      if (searchInput?.value) {
        body.set("search", searchInput.value);
      }
      if (priceMinInput?.value) {
        body.set("price_min", priceMinInput.value);
      }
      if (priceMaxInput?.value) {
        body.set("price_max", priceMaxInput.value);
      }
      if (dateFromInput?.value) {
        body.set("date_from", dateFromInput.value);
      }
      if (dateToInput?.value) {
        body.set("date_to", dateToInput.value);
      }

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      if (page === 1) {
        list.innerHTML = json.data.html || "";
      } else {
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = json.data.html || "";
        while (tempDiv.firstChild) {
          list.appendChild(tempDiv.firstChild);
        }
      }

      if (counter) {
        counter.textContent = `Найдено: ${json.data.total || 0}`;
      }

      totalPages = json.data.pages || 1;
      currentPage = page;

      // Обновляем опции фильтров из отфильтрованных результатов
      if (json.data.filter_options && page === 1) {
        updateFilterOptions(json.data.filter_options);
      }

      updateUrl();
      renderPagination();
      updateResetButton();
    } catch (e) {
      console.error('Tours filter error:', e);
    } finally {
      setLoading(false);
    }
  };

  // Инициализация Choices для селектов
  let countryChoice = countrySelect
    ? new Choices(countrySelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Все страны",
      })
    : null;

  let regionChoice = regionSelect
    ? new Choices(regionSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Все регионы",
      })
    : null;

  let resortChoice = resortSelect
    ? new Choices(resortSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Все курорты",
      })
    : null;

  let tourTypeChoice = tourTypeSelect
    ? new Choices(tourTypeSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Все типы",
      })
    : null;

  // Инициализация datepicker для дат
  if (dateFromInput && dateToInput) {
    datePickerInstance = flatpickr([dateFromInput, dateToInput], {
      mode: "range",
      dateFormat: "Y-m-d",
      locale: Russian,
    });
  }

  // Инициализация dropdown для сортировки
  if (sortContainer) {
    sortDropdown = dropdown(sortContainer);

    const sortOptions = sortContainer.querySelectorAll('.tours-page__sort-option');
    sortOptions.forEach((option) => {
      option.addEventListener('click', (e) => {
        e.preventDefault();
        const value = option.getAttribute('data-value');
        currentSortValue = value;

        // Обновляем текст триггера
        const trigger = sortContainer.querySelector('.tours-page__sort-trigger');
        if (trigger) {
          trigger.querySelector('.tours-page__sort-text').textContent = option.textContent;
        }

        // Закрываем dropdown
        if (sortDropdown) {
          sortDropdown.close();
        }

        loadTours(1);
      });
    });
  }

  // Инициализация dropdown для per_page
  if (perPageContainer) {
    perPageDropdown = dropdown(perPageContainer);

    const perPageOptions = perPageContainer.querySelectorAll('.tours-page__per-page-option');
    perPageOptions.forEach((option) => {
      option.addEventListener('click', (e) => {
        e.preventDefault();
        const value = parseInt(option.getAttribute('data-value'), 10);
        if ([12, 24, 48].includes(value)) {
          currentPerPage = value;

          // Обновляем текст триггера
          const trigger = perPageContainer.querySelector('.tours-page__per-page-trigger');
          if (trigger) {
            trigger.querySelector('.tours-page__per-page-text').textContent = `Показать: ${value}`;
          }

          // Закрываем dropdown
          if (perPageDropdown) {
            perPageDropdown.close();
          }

          loadTours(1);
        }
      });
    });
  }

  // Обработчики событий для фильтров
  const onFilterChange = () => {
    currentPage = 1; // Сбрасываем на первую страницу при изменении фильтра
    loadTours(1);
  };

  if (countrySelect) {
    countrySelect.addEventListener('change', onFilterChange);
  }
  if (regionSelect) {
    regionSelect.addEventListener('change', onFilterChange);
  }
  if (resortSelect) {
    resortSelect.addEventListener('change', onFilterChange);
  }
  if (tourTypeSelect) {
    tourTypeSelect.addEventListener('change', onFilterChange);
  }

  // Обработчик для поиска с задержкой
  if (searchInput) {
    let searchTimeout = null;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadTours(1);
      }, 300); // Ждём 300мс после остановки печати
    });
  }

  // Обработчик для цены
  if (priceMinInput) {
    priceMinInput.addEventListener('change', onFilterChange);
  }
  if (priceMaxInput) {
    priceMaxInput.addEventListener('change', onFilterChange);
  }

  // Обработчик для дат
  if (dateFromInput && dateToInput) {
    const onDateChange = () => {
      currentPage = 1;
      loadTours(1);
    };
    dateFromInput.addEventListener('change', onDateChange);
    dateToInput.addEventListener('change', onDateChange);
  }

  // Обработчик для кнопки сброса фильтров
  if (resetBtn) {
    resetBtn.addEventListener('click', (e) => {
      e.preventDefault();

      // Сбрасываем все фильтры
      if (countrySelect) countrySelect.value = '';
      if (regionSelect) regionSelect.value = '';
      if (resortSelect) resortSelect.value = '';
      if (tourTypeSelect) tourTypeSelect.value = '';
      if (searchInput) searchInput.value = '';
      if (priceMinInput) priceMinInput.value = '';
      if (priceMaxInput) priceMaxInput.value = '';
      if (dateFromInput) dateFromInput.value = '';
      if (dateToInput) dateToInput.value = '';

      // Обновляем Choices
      if (countryChoice) countryChoice.setChoiceByValue('');
      if (regionChoice) regionChoice.setChoiceByValue('');
      if (resortChoice) resortChoice.setChoiceByValue('');
      if (tourTypeChoice) tourTypeChoice.setChoiceByValue('');

      currentSortValue = 'price_asc';
      currentPerPage = 12;
      currentPage = 1;

      // Обновляем текст сортировки и per_page
      const sortTrigger = sortContainer?.querySelector('.tours-page__sort-trigger');
      if (sortTrigger) {
        sortTrigger.querySelector('.tours-page__sort-text').textContent = 'По цене (возрастание)';
      }

      const perPageTrigger = perPageContainer?.querySelector('.tours-page__per-page-trigger');
      if (perPageTrigger) {
        perPageTrigger.querySelector('.tours-page__per-page-text').textContent = 'Показать: 12';
      }

      loadTours(1);
    });
  }

  // Восстанавливаем состояние из URL если существует
  const applyFromUrl = () => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('country') && countrySelect) {
      countrySelect.value = params.get('country');
      if (countryChoice) {
        countryChoice.setChoiceByValue(params.get('country'));
      }
    }
    if (params.get('region') && regionSelect) {
      regionSelect.value = params.get('region');
      if (regionChoice) {
        regionChoice.setChoiceByValue(params.get('region'));
      }
    }
    if (params.get('resort') && resortSelect) {
      resortSelect.value = params.get('resort');
      if (resortChoice) {
        resortChoice.setChoiceByValue(params.get('resort'));
      }
    }
    if (params.get('tour_type') && tourTypeSelect) {
      tourTypeSelect.value = params.get('tour_type');
      if (tourTypeChoice) {
        tourTypeChoice.setChoiceByValue(params.get('tour_type'));
      }
    }
    if (params.get('search') && searchInput) {
      searchInput.value = params.get('search');
    }
    if (params.get('price_min') && priceMinInput) {
      priceMinInput.value = params.get('price_min');
    }
    if (params.get('price_max') && priceMaxInput) {
      priceMaxInput.value = params.get('price_max');
    }
    if (params.get('date_from') && dateFromInput) {
      dateFromInput.value = params.get('date_from');
    }
    if (params.get('date_to') && dateToInput) {
      dateToInput.value = params.get('date_to');
    }

    const sort = params.get('sort');
    if (sort) {
      currentSortValue = sort;
      const sortTrigger = sortContainer?.querySelector('.tours-page__sort-trigger');
      if (sortTrigger) {
        const sortOption = sortContainer?.querySelector(`[data-value="${sort}"]`);
        if (sortOption) {
          sortTrigger.querySelector('.tours-page__sort-text').textContent = sortOption.textContent;
        }
      }
    }

    const perPage = params.get('per_page');
    if (perPage && [12, 24, 48].includes(parseInt(perPage, 10))) {
      currentPerPage = parseInt(perPage, 10);
      const perPageTrigger = perPageContainer?.querySelector('.tours-page__per-page-trigger');
      if (perPageTrigger) {
        perPageTrigger.querySelector('.tours-page__per-page-text').textContent = `Показать: ${currentPerPage}`;
      }
    }

    const page = parseInt(params.get('page') || '1', 10);
    if (page > 1) {
      currentPage = page;
      loadTours(page);
    } else if (countActiveFilters() > 0) {
      loadTours(1);
    }
  };

  applyFromUrl();
  updateResetButton();
};
