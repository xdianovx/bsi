import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "../forms/dropdown.js";
import { displayTourPrices } from "../services/priceLoader.js";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  addItemText: (value) => `Нажмите Enter, чтобы добавить «${value}»`,
  maxItemText: (maxItemCount) => `Можно выбрать максимум: ${maxItemCount}`,
  searchPlaceholderValue: "Поиск...",
};

// Форматирует число с разделителем разрядов (1000 -> "1 000")
const formatNumberInput = (value) => {
  if (!value) return '';
  const num = value.replace(/\s/g, '');
  return num.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
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
  const viewToggle = root.querySelector(".js-tours-view-toggle");
  const viewBtns = viewToggle?.querySelectorAll(".tours-page__view-btn");

  let datePickerInstance = null;
  let sortDropdown = null;
  let perPageDropdown = null;
  let currentSortValue = 'price_asc';
  let currentPerPage = 12;
  let currentPage = 1;
  let totalPages = 1;
  let currentView = 'grid'; // 'grid' или 'list'

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
    if (currentView !== 'grid') {
      params.set("view", currentView);
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
      });
    });
  };

  // Обновление опций фильтров на основе результатов
  const updateFilterOptions = (options) => {
    if (!options) return;

    // Страны не обновляем динамически, чтобы не сбивать выбранное значение.

    // Обновляем регионы
    if (regionSelect && regionChoice && options.regions) {
      const currentValue = regionSelect.value;
      const hasCurrentValue = options.regions.some(r => String(r.id) === currentValue);
      regionChoice.setChoices(
        [
          { value: '', label: 'Все регионы', selected: !hasCurrentValue || currentValue === '', placeholder: true },
          ...options.regions.map(r => ({
            value: String(r.id),
            label: r.name,
            selected: hasCurrentValue && String(r.id) === currentValue
          }))
        ],
        'value', 'label', true
      );
    }

    // Обновляем курорты
    if (resortSelect && resortChoice && options.resorts) {
      const currentValue = resortSelect.value;
      const hasCurrentValue = options.resorts.some(r => String(r.id) === currentValue);
      resortChoice.setChoices(
        [
          { value: '', label: 'Все курорты', selected: !hasCurrentValue || currentValue === '', placeholder: true },
          ...options.resorts.map(r => ({
            value: String(r.id),
            label: r.name,
            selected: hasCurrentValue && String(r.id) === currentValue
          }))
        ],
        'value', 'label', true
      );
    }

    // Обновляем типы туров
    if (tourTypeSelect && tourTypeChoice && options.tour_types) {
      const currentValue = tourTypeSelect.value;
      const hasCurrentValue = options.tour_types.some(t => String(t.id) === currentValue);
      tourTypeChoice.setChoices(
        [
          { value: '', label: 'Все типы', selected: !hasCurrentValue || currentValue === '', placeholder: true },
          ...options.tour_types.map(t => ({
            value: String(t.id),
            label: t.name,
            selected: hasCurrentValue && String(t.id) === currentValue
          }))
        ],
        'value', 'label', true
      );
    }
  };

  // Функция для получения обновленных опций фильтров
  const fetchFilterOptions = async () => {
    try {
      const body = new URLSearchParams();
      body.set("action", "tours_filter");
      body.set("view", currentView);

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
        body.set("price_min", priceMinInput.value.replace(/\s/g, ''));
      }
      if (priceMaxInput?.value) {
        body.set("price_max", priceMaxInput.value.replace(/\s/g, ''));
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
      if (json && json.success && json.data.filter_options) {
        updateFilterOptions(json.data.filter_options);
      }
    } catch (e) {
      console.error('Fetch filter options error:', e);
    }
  };

  const loadTours = async (page = 1) => {
    setLoading(true);
    updateUrl(); // Обновляем URL при загрузке туров

    try {
      const body = new URLSearchParams();
      body.set("action", "tours_filter");
      body.set("paged", String(page));
      body.set("per_page", String(currentPerPage));
      body.set("view", currentView);

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
        body.set("price_min", priceMinInput.value.replace(/\s/g, ''));
      }
      if (priceMaxInput?.value) {
        body.set("price_max", priceMaxInput.value.replace(/\s/g, ''));
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

      // Загружаем цены в только что отрендеренных карточках
      displayTourPrices(list);

      if (counter) {
        counter.textContent = `Найдено: ${json.data.total || 0}`;
      }

      totalPages = json.data.pages || 1;
      currentPage = page;

      // Обновляем опции фильтров из отфильтрованных результатов
      if (json.data.filter_options && page === 1) {
        updateFilterOptions(json.data.filter_options);
      }

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
  const dateRangeInput = form.querySelector('input[name="date_range"]');
  if (dateRangeInput && dateFromInput && dateToInput) {
    datePickerInstance = flatpickr(dateRangeInput, {
      mode: "range",
      dateFormat: "d.m.Y",
      locale: Russian,
      onChange: (selectedDates) => {
        // Обновляем скрытые поля для AJAX запроса
        if (selectedDates.length >= 1) {
          const fromDate = new Date(selectedDates[0]);
          dateFromInput.value = fromDate.toISOString().split('T')[0];
        }
        if (selectedDates.length === 2) {
          const toDate = new Date(selectedDates[1]);
          dateToInput.value = toDate.toISOString().split('T')[0];
        }

        // Обновляем доступные опции и загружаем туры при изменении дат
        currentPage = 1;
        fetchFilterOptions();
        loadTours(1);
      },
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

        // Обновляем активный класс
        sortOptions.forEach((opt) => opt.classList.remove('is-active'));
        option.classList.add('is-active');

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

          // Обновляем активный класс
          perPageOptions.forEach((opt) => opt.classList.remove('is-active'));
          option.classList.add('is-active');

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
    fetchFilterOptions(); // Обновляем доступные опции
    loadTours(1);
  };

  const onCountryChange = () => {
    // Сброс зависимых фильтров при смене страны
    if (regionChoice) regionChoice.setChoiceByValue('');
    if (resortChoice) resortChoice.setChoiceByValue('');
    if (tourTypeChoice) tourTypeChoice.setChoiceByValue('');

    onFilterChange();
  };

  if (countrySelect) {
    countrySelect.addEventListener('change', onCountryChange);
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

  // Обработчик для поиска с дебаунсом
  if (searchInput) {
    let searchTimeout = null;
    const SEARCH_DEBOUNCE_MS = 500; // Дебаунс 500мс для поиска
    const SEARCH_MIN_LENGTH = 3; // Минимум 3 символа для поиска

    // Нормализация поискового запроса для лучшего UX
    const normalizeSearchQuery = (query) => {
      if (!query) return '';
      // Обрезаем пробелы слева и справа
      let normalized = query.trim();
      // Заменяем множественные пробелы на один
      normalized = normalized.replace(/\s+/g, ' ');
      return normalized;
    };

    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);

      // Нормализуем ввод
      const normalizedValue = normalizeSearchQuery(searchInput.value);

      // Если меньше 3 символов - ничего не делаем
      if (normalizedValue.length > 0 && normalizedValue.length < SEARCH_MIN_LENGTH) {
        return;
      }

      // Показываем что идет поиск
      if (normalizedValue.length > 0) {
        setLoading(true);
      }

      searchTimeout = setTimeout(() => {
        currentPage = 1;
        fetchFilterOptions();
        loadTours(1);
      }, SEARCH_DEBOUNCE_MS);
    });
  }

  // Обработчик для цены - форматируем при потере фокуса, не при вводе
  if (priceMinInput) {
    priceMinInput.addEventListener('blur', (e) => {
      const value = e.target.value.replace(/\s/g, '');
      if (value) {
        e.target.value = formatNumberInput(value);
      }
    });
    priceMinInput.addEventListener('change', onFilterChange);
  }
  if (priceMaxInput) {
    priceMaxInput.addEventListener('blur', (e) => {
      const value = e.target.value.replace(/\s/g, '');
      if (value) {
        e.target.value = formatNumberInput(value);
      }
    });
    priceMaxInput.addEventListener('change', onFilterChange);
  }

  // Обработчик для дат больше не нужен - он в onChange callback flatpickr

  // Обработчики для кнопок смены вида
  if (viewBtns && viewBtns.length > 0) {
    viewBtns.forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const view = btn.getAttribute('data-view');
        currentView = view;

        // Обновляем активную кнопку
        viewBtns.forEach((b) => b.classList.remove('is-active'));
        btn.classList.add('is-active');

        // Очищаем список ПЕРЕД переключением, чтобы избежать смешивания разных шаблонов
        list.innerHTML = '';
        setLoading(true);

        // Обновляем класс списка и карточек
        if (view === 'list') {
          list.classList.add('is-list-view');
        } else {
          list.classList.remove('is-list-view');
        }

        // Обновляем URL с новым видом
        updateUrl();

        // Перезагружаем туры с новым шаблоном
        loadTours(1);
      });
    });
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
      if (dateRangeInput) dateRangeInput.value = '';
      if (datePickerInstance) datePickerInstance.clear();

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

      // Сбрасываем is-active классы для опций
      const sortOptions = sortContainer?.querySelectorAll('.tours-page__sort-option');
      if (sortOptions) {
        sortOptions.forEach((opt) => opt.classList.remove('is-active'));
        const defaultSortOption = sortContainer?.querySelector('[data-value="price_asc"]');
        if (defaultSortOption) defaultSortOption.classList.add('is-active');
      }

      const perPageOptions = perPageContainer?.querySelectorAll('.tours-page__per-page-option');
      if (perPageOptions) {
        perPageOptions.forEach((opt) => opt.classList.remove('is-active'));
        const defaultPerPageOption = perPageContainer?.querySelector('[data-value="12"]');
        if (defaultPerPageOption) defaultPerPageOption.classList.add('is-active');
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
    // Восстанавливаем отображение диапазона дат в видимое поле
    if (dateRangeInput && params.get('date_from') && params.get('date_to')) {
      const dateFrom = new Date(params.get('date_from'));
      const dateTo = new Date(params.get('date_to'));
      if (datePickerInstance) {
        datePickerInstance.setDate([dateFrom, dateTo]);
      }
    }

    const sort = params.get('sort');
    if (sort && sort !== 'price_asc') {
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

    // Восстанавливаем вид (grid или list)
    const view = params.get('view');
    if (view && ['grid', 'list'].includes(view)) {
      currentView = view;
      if (viewBtns && viewBtns.length > 0) {
        viewBtns.forEach((btn) => {
          if (btn.getAttribute('data-view') === view) {
            btn.classList.add('is-active');
            if (view === 'list') {
              list.classList.add('is-list-view');
              const items = list.querySelectorAll('.tours-page__item');
              items.forEach((item) => item.classList.add('is-list-view'));
            }
          } else {
            btn.classList.remove('is-active');
          }
        });
      }
    }
  };

  applyFromUrl();
  updateResetButton();

  // Инициализация is-active классов для sort и per-page опций
  const initActiveClasses = () => {
    // Sort опции
    const sortOptions = sortContainer?.querySelectorAll('.tours-page__sort-option');
    if (sortOptions) {
      sortOptions.forEach((opt) => opt.classList.remove('is-active'));
      const activeSort = sortContainer?.querySelector(`[data-value="${currentSortValue}"]`);
      if (activeSort) activeSort.classList.add('is-active');
    }

    // Per-page опции
    const perPageOptions = perPageContainer?.querySelectorAll('.tours-page__per-page-option');
    if (perPageOptions) {
      perPageOptions.forEach((opt) => opt.classList.remove('is-active'));
      const activePerPage = perPageContainer?.querySelector(`[data-value="${currentPerPage}"]`);
      if (activePerPage) activePerPage.classList.add('is-active');
    }
  };
  initActiveClasses();
};
