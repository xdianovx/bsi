# Яндекс Метрика — контекст проекта BSI Group

## 1. О проекте

**Компания:** BSI Group — туроператор (bsigroup.ru)
**Стек:** WordPress + ACF (Advanced Custom Fields) + тема на Underscores
**Внешние API:** SAMO (туры/отели/бронирование), Unisender (рассылки), Яндекс.Карты v3, reCAPTCHA v3
**Тип сайта:** классический серверный multi-page WordPress с AJAX-обогащением (фильтры, формы, подгрузка цен). Не SPA, нет клиентского роутинга (только `replaceState` для фильтров).

**Номер счётчика Яндекс.Метрики:** `108341897`

---

## 2. Текущая реализация Метрики

### Где установлен счётчик

Счётчик вставлен инлайном в `header.php` сразу после `wp_head()`:

```html
<script type="text/javascript">
  (function (m, e, t, r, i, k, a) {
    m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments) };
    m[i].l = 1 * new Date();
    for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
    k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
  })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=108341897', 'ym');

  ym(108341897, 'init', {
    ssr: true,
    webvisor: true,
    clickmap: true,
    ecommerce: "dataLayer",
    referrer: document.referrer,
    url: location.href,
    accurateTrackBounce: true,
    trackLinks: true
  });
</script>
<noscript>
  <div><img src="https://mc.yandex.ru/watch/108341897" style="position:absolute; left:-9999px;" alt="" /></div>
</noscript>
```

**Текущие параметры инициализации:**
- `ssr: true` — server-side rendering hint
- `webvisor: true` — Вебвизор включён
- `clickmap: true` — карта кликов включена
- `ecommerce: "dataLayer"` — e-commerce через контейнер dataLayer
- `accurateTrackBounce: true` — точный учёт отказов
- `trackLinks: true` — учёт внешних ссылок и загрузок

### ПРОБЛЕМА: MICE-раздел без счётчика

Файл `header-mice.php` (используется страницей `page-mice.php`) **не содержит** блок Яндекс.Метрики. В нём только `wp_head()` без инлайн-счётчика. Это значит, что MICE-страницы не отслеживаются.

### Существующие dataLayer-события

В файле `js/modules/tour-prices.js` есть функция `sendGTMEvent()`, которая делает `dataLayer.push()`:

```javascript
function sendGTMEvent(eventName, data = {}) {
  if (typeof window.dataLayer === "undefined") {
    window.dataLayer = [];
  }
  window.dataLayer.push({
    event: eventName,
    tour_id: parseInt(tourId, 10),
    ...data,
  });
}
```

**Текущие события:**
| Событие | Когда срабатывает | Дополнительные данные |
|---------|-------------------|---------------------|
| `tour_card_booking_clicked` | Клик «Забронировать» в карточке отеля на странице тура | `booking_url`, `hotel_id`, `star_rating` |
| `tour_prices_viewed` | Загружены цены на страницу тура | `stars_count`, `prices_count` |
| `tour_prices_search` | Отправлен запрос поиска цен | `nights_from`, `nights_till`, и т.д. |
| `tour_star_filter_changed` | Изменён фильтр по звёздности | `star_filter` |
| `tour_booking_clicked` | Клик основной кнопки бронирования тура | `booking_url`, `star_filter` |

**ВАЖНО:** Ни одного вызова `ym(108341897, 'reachGoal', ...)` в коде нет. Все события идут только через `dataLayer.push()`.

---

## 3. Формы (главные конверсии)

Все формы отправляются через AJAX (`admin-ajax.php`) с помощью общей функции `submitFormWithRecaptcha()` из `js/modules/forms/form-ajax.js`. reCAPTCHA v3 подключается опционально при наличии ключа.

### 3.1. FIT-заявка (индивидуальный тур)
- **Страница:** `page-fit.php`
- **AJAX action:** `fit_form`
- **Бэкенд:** `inc/requests/ajax-fit.php`
- **JS:** `js/modules/forms/fit-form.js`
- **При успехе:** inline-сообщение `showStatus("Успешно отправлено!", "success")`, форма сбрасывается
- **Нет модалки успеха**, нет редиректа

### 3.2. Визовая консультация
- **Страница:** `page-visa.php`
- **AJAX action:** `visa_form`
- **Бэкенд:** `inc/requests/ajax-visa-form.php`
- **JS:** `js/modules/forms/visa-form.js`
- **При успехе:** inline-сообщение `showStatus("Успешно отправлено!", "success")`, форма сбрасывается
- **Нет модалки успеха**, нет редиректа

### 3.3. Страхование
- **Страница:** `page-insurance.php`
- **AJAX action:** `insurance_form`
- **Бэкенд:** `inc/requests/ajax-insurance-form.php`
- **JS:** `js/modules/forms/insurance-form.js`
- **При успехе:** открывается модалка `modal-insurance-success` (MicroModal), автозакрытие через 2 сек
- **Нет редиректа**

### 3.4. Бронирование образовательной программы
- **Страницы:** `single-education.php`, `page-education.php`
- **AJAX action:** `education_program_booking`
- **Бэкенд:** `inc/requests/ajax-education-program-form.php`
- **JS:** `js/modules/forms/education-program-form.js`
- **При успехе:** закрывается `modal-program-booking`, открывается `modal-program-booking-success`, автозакрытие 2 сек

### 3.5. Бронирование билета на событие
- **Страницы:** `single-event.php`
- **AJAX action:** `event_ticket_booking`
- **Бэкенд:** `inc/requests/ajax-event-ticket-form.php`
- **JS:** `js/modules/forms/event-ticket-form.js`
- **При успехе:** закрывается `modal-event-ticket-booking`, открывается `modal-event-booking-success`, автозакрытие 2 сек

### 3.6. Регистрация на агентское мероприятие
- **Страницы:** `single-agency_event.php`
- **AJAX action:** `agency_event_registration`
- **Бэкенд:** `inc/requests/agency-event-registration.php`
- **JS:** `js/modules/forms/agency-event-reg-form.js`
- **При успехе:** закрывается `modal-agency-event-reg`, открывается `modal-agency-reg-success`, автозакрытие 2 сек

### 3.7. Подписка на рассылку (Unisender)
- **Шаблон:** `template-parts/sections/subscribe.php` (подключается на многих страницах)
- **Отправка:** обычный POST на `https://cp.unisender.com/ru/subscribe?hash=...` — **не AJAX**, пользователя уводит на внешний сайт Unisender
- **Нет callback-а** для JS-трекинга — нужно перехватывать submit формы до отправки

---

## 4. Микроконверсии и важные взаимодействия

### 4.1. Телефонные звонки (клики по tel:)
- **Шапка:** два номера из ACF — `the_field('telefon', 'option')` и `the_field('telefon_po_rf', 'option')`
- **Подвал:** `8 (495) 785-55-35` и `8 (800) 200-55-35` (захардкожены)
- **Мобильная навигация:** те же ACF-номера
- **Карточки услуг:** на страницах визы, страхования, образования, отелей, событий
- CSS-классы: `.phone-link`, `.footer-contact__link`, `.mobile-nav-contacts__phone`

### 4.2. Email-клики (mailto:)
- **Подвал:** `the_field('email', 'option')` — CSS-класс `.footer-contact__link`

### 4.3. Внешние ссылки бронирования
- Основная система бронирования: **`online.bsigroup.ru`** (САМО-тур)
- Личный кабинет агентств: **`past.bsigroup.ru`**
- Ссылки бронирования генерируются динамически в `js/modules/tour-prices.js` (`buildBookingUrl()`, `buildBookingUrlForHotel()`)
- На страницах туров, отелей, событий — внешние ссылки на бронирование

### 4.4. Скачивание документов
- Визовые документы: `single-visa.php` — ссылки с атрибутом `download`
- В `functions.php` есть фильтр, добавляющий `download` к ссылкам на файлы определённых расширений в контенте

### 4.5. Социальные сети
- **Telegram:** ссылка из ACF `the_field('telegram', 'option')`
- **VK:** ссылка из ACF `the_field('vk', 'option')`
- **MAX (OK/Mail.ru):** `https://max.ru/id7707421824_biz` (захардкожена)
- **WhatsApp:** закомментирован в шаблоне
- CSS-классы: `.social-item.--tg`, `.social-item.--vk`, `.social-item.--max`

### 4.6. Поисковый виджет на главной
- Шаблон: `template-parts/gtm-search.php`
- JS: `js/modules/gtm-search.js`
- Вкладки: Туры, Отели, Экскурсии (ссылка на past.bsigroup.ru), Визы (ссылка на страницу виз)
- Кнопка «Найти» — отправка поиска через SAMO API (`action: bsi_samo`)
- CSS-класс секции: `.gtm-search__section`

### 4.7. Модальные окна
- `modal-currency-history` — курсы валют (в шапке)
- `modal-hotel-pdf` — бланк отеля на `single-hotel.php`
- `modal-maintenance-warning` — предупреждение о техработах
- Все через **MicroModal** (`data-micromodal-trigger`)

### 4.8. Фильтры и AJAX-подгрузка
| Фильтр | Страница | AJAX action |
|--------|----------|-------------|
| Туры по стране | `country-tours.php` | `country_tours_filter` |
| Образование | `page-education.php` | `education_filter` |
| Образование по стране | `country-education.php` | `country_education_filter` |
| Событийные туры | `page-sobytiynye-tury.php` | `event_tours_filter` |
| Новости | `page-news.php` | `bsi_filter_news` |
| Акции | `page-promos.php` | `bsi_filter_promos` |
| Агентские мероприятия | `page-agentstvam.php` | `agency_events_filter` |
| Отели курорта | `taxonomy-resort.php` | `bsi_resort_hotels` |
| Популярные туры | Главная и др. | `popular_tours_by_country` |
| Популярное образование | Главная и др. | `popular_education_by_country` |
| Популярные отели | Главная и др. | `popular_hotels_by_country` |

### 4.9. Галереи (Fancybox)
- Фотогалереи на страницах стран, отелей, событий — `[data-fancybox]`
- Отзывы с фото — `template-parts/reviews/card.php`
- Награды — `template-parts/awards/card.php`

---

## 5. Структура страниц (URL для целей)

### Главная и служебные
| URL | Шаблон |
|-----|--------|
| `/` | `front-page.php` |
| `/404` | `404.php` |
| Результаты поиска | `search.php` |

### Страницы (page-*.php)
| Шаблон | Примерный URL |
|--------|---------------|
| `page-mice.php` | `/mice/` |
| `page-bonus.php` | `/bonus/` |
| `page-visa.php` | `/visa/` или `/vizy/` |
| `page-education.php` | `/education/` |
| `page-news.php` | `/news/` |
| `page-promos.php` | `/akcii/` |
| `page-promo-archive.php` | `/arhiv-akcij/` |
| `page-agentstvam.php` | `/agentstvam/` |
| `page-cruise.php` | `/cruise/` |
| `page-sobytiynye-tury.php` | `/sobytiynye-tury/` |
| `page-gde-kupit.php` | `/gde-kupit/` |
| `page-insurance.php` | `/insurance/` |
| `page-awards.php` | `/awards/` |
| `page-fit.php` | `/fit/` |

### Кастомные типы записей (CPT)
| CPT | Одиночная запись | Архив |
|-----|-----------------|-------|
| `country` | `/country/{slug}/` | `/country/` → редирект на страницу `strany` |
| `tour` | `/country/{country}/tours/{tour}/` | — |
| `hotel` | `/country/{country}/hotel/{hotel}/` | — |
| `event` | `/event-tours/{slug}/` | — |
| `education` | `/education/{slug}/` | — |
| `news` | `/news/{slug}/` | — |
| `promo` | `/akcii/{slug}/` | — |
| `visa` | `/country/{country}/visa/{type}/` | — |
| `insurance` | `/insurance/{slug}/` | — |
| `agency_event` | `/agency-events/{slug}/` | — |
| `review` | `/otzyvy/{slug}/` | `/otzyvy/` |
| `project` | `/proekty/{slug}/` | `/proekty/` |
| `documentation` | `/agentstvam/{slug}/` | `/agentstvam/` (архив) |
| `service` | `/service/{slug}/` | `/service/` |

### Вложенные маршруты стран
```
/country/{country}/tours/          — туры по стране
/country/{country}/hotel/          — отели по стране
/country/{country}/kurorty/        — курорты
/country/{country}/promo/          — акции страны
/country/{country}/visa/           — визы страны
/country/{country}/obuchenie/      — образование в стране
/country/{country}/novosti/        — новости страны
/country/{country}/pamyatka/       — памятка туристу
/country/{country}/pravila-vyezda/ — правила въезда
/country/{country}/{region}/{resort}/ — конкретный курорт
```

---

## 6. Техническая архитектура (для правильной интеграции)

### Подключение скриптов
- Стили и скрипты через `wp_enqueue_scripts` в `functions.php`
- Основной бандл: `dist/js/main.min.js` (footer)
- `wp_localize_script('main', 'ajax', { url: admin_url('admin-ajax.php'), recaptchaSiteKey: ... })`
- reCAPTCHA v3 подключается отдельным скриптом при наличии ключа

### Библиотеки (JS)
- **MicroModal** — все модальные окна
- **Fancybox** (`@fancyapps/ui`) — галереи фото
- **Swiper** — слайдеры
- **Flatpickr** — выбор дат в поисковом виджете и модалке валют
- **Choices.js** — кастомные селекты в формах
- **IMask** — маска телефона

### Кэширование
- WordPress transients через `CacheService` (`inc/services/CacheService.php`)
- Цены туров: `PriceLoaderService` + батч-запросы
- Курсы ЦБ: `bsi_cbr_rates` (1 час)
- Даты образования: `bsi_education_available_dates_*` (12 часов)

### Cookie/GDPR
- В подвале есть текст про cookies с ссылкой на политику (страница ID 47), но **нет** полноценного consent-баннера
- Скрипты (Метрика, Карты) **не блокируются** до получения согласия

---

## 7. Рекомендуемые цели Метрики

### Первичные конверсии (формы)
| Цель | Идентификатор | Тип | Как отслеживать |
|------|---------------|-----|-----------------|
| FIT-заявка отправлена | `fit_form_submitted` | JavaScript-событие | `reachGoal` после `result.success` в `fit-form.js` |
| Визовая заявка отправлена | `visa_form_submitted` | JavaScript-событие | `reachGoal` после `result.success` в `visa-form.js` |
| Страховая заявка отправлена | `insurance_form_submitted` | JavaScript-событие | `reachGoal` после `result.success` в `insurance-form.js` |
| Бронь образовательной программы | `education_booking_submitted` | JavaScript-событие | `reachGoal` после `result.success` в `education-program-form.js` |
| Бронь билета на событие | `event_ticket_submitted` | JavaScript-событие | `reachGoal` после `result.success` в `event-ticket-form.js` |
| Регистрация на агентское мероприятие | `agency_event_registered` | JavaScript-событие | `reachGoal` после `result.success` в `agency-event-reg-form.js` |
| Подписка на рассылку | `newsletter_subscribed` | JavaScript-событие | Перехват `submit` формы `.subscribe-section__form` до POST на Unisender |

### Вторичные конверсии
| Цель | Идентификатор | Как отслеживать |
|------|---------------|-----------------|
| Клик по телефону | `phone_click` | Делегированный обработчик на `a[href^="tel:"]` |
| Клик по email | `email_click` | Делегированный обработчик на `a[href^="mailto:"]` |
| Переход на бронирование (САМО) | `booking_external_click` | Клик по ссылкам с `href` содержащим `online.bsigroup.ru` |
| Скачивание документа | `document_download` | Клик по ссылкам с атрибутом `download` или расширениями `.pdf`, `.doc`, `.docx` |
| Клик по соцсети | `social_click` | Клик по `.social-item` |
| Вход в личный кабинет агента | `agent_login_click` | Клик по ссылке на `past.bsigroup.ru` |

### Engagement-цели
| Цель | Идентификатор | Как отслеживать |
|------|---------------|-----------------|
| Использование поиска туров | `tour_search_used` | Привязать к submit поисковой формы в `gtm-search.js` |
| Просмотр цен тура | `tour_prices_viewed` | Уже есть в dataLayer, добавить `reachGoal` |
| Клик «Забронировать» в карточке | `tour_booking_clicked` | Уже есть в dataLayer, добавить `reachGoal` |
| Открытие модалки бронирования | `booking_modal_opened` | `MicroModal.show` callback |
| Использование фильтров | `filter_used` | На AJAX-запросы фильтрации |
| Просмотр фотогалереи | `gallery_viewed` | Fancybox open callback |

---

## 8. Что нужно сделать (известные проблемы)

### Критичные
1. **Добавить счётчик Метрики в `header-mice.php`** — MICE-раздел не отслеживается вообще
2. **Добавить `reachGoal()` вызовы** — сейчас ни одного `ym(108341897, 'reachGoal', ...)` в коде нет; все события только через `dataLayer.push()`, который Метрика читает только как e-commerce

### Важные
3. **Добавить трекинг отправки каждой формы** — в каждом form-модуле после `result.success` вызвать `reachGoal`
4. **Перехватить подписку Unisender** — форма делает обычный POST на внешний сайт, нужно добавить JS-обработчик submit до отправки
5. **Трекинг кликов по tel:/mailto:/booking-ссылкам** — добавить делегированные обработчики с `reachGoal`
6. **Трекинг скачивания документов** — перехват кликов по ссылкам с `download`

### Желательные
7. **Scroll-depth трекинг** — отслеживание глубины прокрутки (25%, 50%, 75%, 100%)
8. **Отслеживание использования поиска** — привязка к поисковому виджету `gtm-search`
9. **Отслеживание фильтров** — фиксация факта использования фильтров на страницах туров, образования и т.д.
10. **Трекинг открытия/закрытия галерей** — Fancybox events

### Неизвестно (нужно уточнить)
11. **Чат-виджет** — в коде темы нет JivoChat/Tawk/Carrot quest и т.п., но может быть подключен через плагин или `wp_head` из другого места
12. **Другие плагины** — возможны формы CF7 или WPForms, подключённые через плагины вне темы

---

## 9. Файлы для модификации (при настройке целей)

| Файл | Что менять |
|------|-----------|
| `header.php` | Текущий код Метрики (уже есть) |
| `header-mice.php` | Добавить код Метрики (отсутствует!) |
| `js/modules/forms/fit-form.js` | Добавить `reachGoal` после `result.success` |
| `js/modules/forms/visa-form.js` | Добавить `reachGoal` после `result.success` |
| `js/modules/forms/insurance-form.js` | Добавить `reachGoal` после `result.success` |
| `js/modules/forms/education-program-form.js` | Добавить `reachGoal` после `result.success` |
| `js/modules/forms/event-ticket-form.js` | Добавить `reachGoal` после `result.success` |
| `js/modules/forms/agency-event-reg-form.js` | Добавить `reachGoal` после `result.success` |
| `js/modules/tour-prices.js` | Добавить `reachGoal` параллельно с `dataLayer.push` |
| `js/main.js` | Добавить глобальные обработчики (tel, mailto, download, social clicks) |
| `template-parts/sections/subscribe.php` или JS | Перехват submit подписки Unisender |

---

## 10. Паттерн интеграции reachGoal

Единообразный вызов для всего проекта:

```javascript
// Вспомогательная функция (добавить в общий модуль или main.js)
function ymGoal(goalName, params) {
  if (typeof ym !== 'undefined') {
    ym(108341897, 'reachGoal', goalName, params || {});
  }
}
```

Пример для формы:

```javascript
if (result.success) {
  ymGoal('fit_form_submitted', { page: window.location.pathname });
  showStatus("Успешно отправлено!", "success");
  // ... остальной код
}
```

Пример для глобальных кликов (в main.js):

```javascript
document.addEventListener('click', function(e) {
  const link = e.target.closest('a');
  if (!link) return;

  const href = link.getAttribute('href') || '';

  if (href.startsWith('tel:')) {
    ymGoal('phone_click', { phone: href.replace('tel:', '') });
  } else if (href.startsWith('mailto:')) {
    ymGoal('email_click', { email: href.replace('mailto:', '') });
  } else if (href.includes('online.bsigroup.ru')) {
    ymGoal('booking_external_click', { url: href });
  } else if (link.hasAttribute('download') || /\.(pdf|docx?|xlsx?)(\?|$)/i.test(href)) {
    ymGoal('document_download', { file: href });
  }
});

document.querySelectorAll('.social-item').forEach(function(el) {
  el.addEventListener('click', function() {
    var classes = this.className;
    var network = classes.includes('--tg') ? 'telegram' : classes.includes('--vk') ? 'vk' : 'max';
    ymGoal('social_click', { network: network });
  });
});
```

Пример перехвата подписки Unisender:

```javascript
var subscribeForm = document.querySelector('.subscribe-section__form');
if (subscribeForm) {
  subscribeForm.addEventListener('submit', function() {
    ymGoal('newsletter_subscribed', {
      email: this.querySelector('input[name="email"]').value
    });
  });
}
```

---

## 11. E-commerce через dataLayer

Метрика уже инициализирована с `ecommerce: "dataLayer"`. Текущие `dataLayer.push()` из `tour-prices.js` отправляют события, но они **не в формате Яндекс e-commerce** (purchase, add, detail и т.д.). Если нужна полноценная e-commerce аналитика в Метрике, нужно дополнительно пушить объекты в формате:

```javascript
dataLayer.push({
  ecommerce: {
    detail: {
      products: [{
        id: tourId,
        name: tourName,
        price: tourPrice,
        category: countryName
      }]
    }
  }
});
```

Это отдельная задача, не обязательная на первом этапе.

---

## 12. Сборка и деплой

- Исходные JS-файлы: `js/` (ES-модули)
- Собранный бандл: `dist/js/main.min.js`
- SCSS: `scss/` → `dist/css/main.min.css`
- После изменений в JS необходимо пересобрать бандл
- Сборщик: проверить `package.json` (вероятно Webpack/Vite/Rollup)
