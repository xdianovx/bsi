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
  console.log('loadBatchTourPrices: вызвана с tourIds:', tourIds, 'params:', params);
  
  if (!tourIds || !Array.isArray(tourIds) || tourIds.length === 0) {
    console.log('loadBatchTourPrices: tourIds пуст или не массив');
    return {};
  }

  const ajaxUrl = window.ajax?.url || window.ajaxurl || '/wp-admin/admin-ajax.php';
  console.log('loadBatchTourPrices: ajaxUrl =', ajaxUrl);

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

    console.log('loadBatchTourPrices: отправляем запрос, body:', body.toString());
    
    const response = await fetch(ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: body.toString(),
      credentials: 'same-origin',
    });

    console.log('loadBatchTourPrices: получен ответ, status:', response.status);
    
    if (!response.ok) {
      const text = await response.text();
      console.error('loadBatchTourPrices: HTTP error', response.status, 'response:', text);
      throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
    }

    const json = await response.json();
    
    console.log('=== ОТВЕТ ОТ PHP get_batch_tour_prices ===');
    console.log('Success:', json.success);
    console.log('Data:', json.data);
    console.log('Prices:', json.data?.prices);
    
    // Детально по каждому туру
    if (json.data?.prices) {
      Object.entries(json.data.prices).forEach(([tourId, priceData]) => {
        console.log(`Tour ${tourId}:`, priceData);
      });
    }
    console.log('==========================================');

    if (!json || !json.success) {
      console.error('loadBatchTourPrices: запрос неуспешен:', json);
      throw new Error(json?.data?.message || 'Failed to load prices');
    }

    return json.data.prices || {};

  } catch (error) {
    console.error('loadBatchTourPrices: ERROR:', error);
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
  console.log('displayTourPrices: вызвана, container:', container);
  
  if (!container) {
    console.log('displayTourPrices: контейнер не найден');
    return;
  }

  // Находим все элементы с ценами
  const priceElements = container.querySelectorAll('[data-tour-price]');
  console.log('displayTourPrices: найдено элементов с [data-tour-price]:', priceElements.length);
  
  if (priceElements.length === 0) {
    console.log('displayTourPrices: нет элементов для загрузки цен');
    return;
  }

  // Собираем ID туров
  const tourIds = [];
  const elementMap = new Map();

  priceElements.forEach(el => {
    const tourId = parseInt(el.dataset.tourId);
    console.log('displayTourPrices: элемент', el, 'tour ID:', tourId);
    if (tourId && !tourIds.includes(tourId)) {
      tourIds.push(tourId);
      elementMap.set(tourId, el);
    }
  });

  console.log('displayTourPrices: собрано уникальных tour IDs:', tourIds);

  if (tourIds.length === 0) {
    console.log('displayTourPrices: нет валидных tour IDs');
    return;
  }

  // Показываем индикатор загрузки
  priceElements.forEach(el => {
    el.classList.add('is-loading');
  });

  // Загружаем цены
  console.log('displayTourPrices: отправляем запрос на загрузку цен для:', tourIds);
  const prices = await loadBatchTourPrices(tourIds, params);
  console.log('displayTourPrices: получены цены:', prices);

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
