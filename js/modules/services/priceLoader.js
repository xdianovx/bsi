/**
 * Price Loader Service
 * 
 * Клиентский сервис для загрузки цен туров из Samotour через AJAX
 * с поддержкой пакетной загрузки и обработки ошибок.
 * 
 * @module priceLoader
 */

/**
 * Загрузить цены для туров пакетно
 * 
 * @param {number[]} tourIds - Массив ID туров
 * @param {Object} params - Дополнительные параметры
 * @param {string} params.dateFrom - Дата начала (YYYY-MM-DD)
 * @param {string} params.dateTo - Дата окончания (YYYY-MM-DD)
 * @param {number} params.adults - Количество взрослых
 * @param {number} params.children - Количество детей
 * @returns {Promise<Object>} Объект с ценами { tour_id: price_data }
 */
export const loadBatchTourPrices = async (tourIds, params = {}) => {
  if (!tourIds || !Array.isArray(tourIds) || tourIds.length === 0) {
    return {};
  }

  const ajaxUrl = window.ajax?.url || window.ajaxurl || '/wp-admin/admin-ajax.php';

  try {
    const body = new URLSearchParams();
    body.set('action', 'get_batch_tour_prices');
    
    // Добавляем ID туров
    tourIds.forEach(id => {
      body.append('tour_ids[]', id);
    });

    // Добавляем дополнительные параметры
    if (params.dateFrom) {
      body.set('date_from', params.dateFrom);
    }
    if (params.dateTo) {
      body.set('date_to', params.dateTo);
    }
    if (params.adults) {
      body.set('adults', params.adults);
    }
    if (params.children) {
      body.set('children', params.children);
    }

    const response = await fetch(ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: body.toString(),
      credentials: 'same-origin',
    });

    const json = await response.json();

    if (!json || !json.success) {
      throw new Error(json?.data?.message || 'Failed to load prices');
    }

    return json.data.prices || {};

  } catch (error) {
    console.error('Error loading batch tour prices:', error);
    return {};
  }
};

/**
 * Загрузить цену одного тура
 * 
 * @param {number} tourId - ID тура
 * @param {Object} params - Дополнительные параметры
 * @returns {Promise<Object|null>} Данные о цене или null
 */
export const loadTourPrice = async (tourId, params = {}) => {
  if (!tourId) {
    return null;
  }

  const ajaxUrl = window.ajax?.url || window.ajaxurl || '/wp-admin/admin-ajax.php';

  try {
    const body = new URLSearchParams();
    body.set('action', 'get_tour_price');
    body.set('tour_id', tourId);

    if (params.dateFrom) {
      body.set('date_from', params.dateFrom);
    }
    if (params.dateTo) {
      body.set('date_to', params.dateTo);
    }
    if (params.adults) {
      body.set('adults', params.adults);
    }
    if (params.children) {
      body.set('children', params.children);
    }

    const response = await fetch(ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: body.toString(),
      credentials: 'same-origin',
    });

    const json = await response.json();

    if (!json || !json.success) {
      return null;
    }

    return json.data;

  } catch (error) {
    console.error('Error loading tour price:', error);
    return null;
  }
};

/**
 * Отобразить цены в карточках туров
 * 
 * @param {HTMLElement} container - Контейнер с карточками
 * @param {Object} params - Параметры для загрузки цен
 */
export const displayTourPrices = async (container, params = {}) => {
  if (!container) {
    return;
  }

  // Находим все элементы с ценами
  const priceElements = container.querySelectorAll('[data-tour-price]');
  if (priceElements.length === 0) {
    return;
  }

  // Собираем ID туров
  const tourIds = [];
  const elementMap = new Map();

  priceElements.forEach(el => {
    const tourId = parseInt(el.dataset.tourId);
    if (tourId && !tourIds.includes(tourId)) {
      tourIds.push(tourId);
      elementMap.set(tourId, el);
    }
  });

  if (tourIds.length === 0) {
    return;
  }

  // Показываем индикатор загрузки
  priceElements.forEach(el => {
    el.classList.add('is-loading');
  });

  // Загружаем цены
  const prices = await loadBatchTourPrices(tourIds, params);

  // Обновляем элементы с ценами
  tourIds.forEach(tourId => {
    const el = elementMap.get(tourId);
    
    if (!el) return;

    el.classList.remove('is-loading');

    const priceData = prices[tourId];
    
    if (!priceData) {
      // Если цену не удалось загрузить, показываем "Забронировать"
      el.classList.add('price-unavailable');
      el.textContent = 'Забронировать';
      return;
    }

    // Формируем текст цены: всегда "от [цена] ₽"
    const priceText = `от ${priceData.price_formatted} ₽`;

    // Обновляем содержимое
    el.textContent = priceText;
    el.classList.add('price-loaded');
  });
};

/**
 * Форматировать цену для отображения
 * 
 * @param {Object} priceData - Данные о цене
 * @returns {string} Отформатированная строка с ценой
 */
export const formatPrice = (priceData) => {
  if (!priceData) {
    return '';
  }

  const prefix = priceData.show_from ? 'от ' : '';
  return `${prefix}${priceData.price_formatted} ${priceData.currency}`;
};
