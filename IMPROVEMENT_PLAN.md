# План улучшения проекта BSI

**Дата:** 2026-05-18
**Автор:** Анализ кодовой базы (PHP, фронтенд, БД/кеширование)

Проект имеет критические проблемы безопасности (пароли в коде, отсутствие CSRF), перегруженные бандлы (1.5 МБ JS, 342 КБ CSS), и N+1 запросы в ключевых фильтрах.

---

## УРОВЕНЬ 1 — КРИТИЧНО (безопасность + блокеры производительности)

### 1.1 Убрать пароли из кода
- `inc/services/class-bsi-mailer.php:42-45` — SMTP логин/пароль в исходниках, видны в git
- **Действие:** перенести в `wp-config.php` через `define()` или `.env`

### 1.2 Добавить nonce-проверки во все AJAX-обработчики
- `inc/requests/ajax-visa-form.php`, `ajax-insurance-form.php` и др. — ни один POST не проверяет `wp_verify_nonce()`
- reCAPTCHA — не замена CSRF-защите

### 1.3 Убрать логирование персональных данных
- `inc/requests/ajax-visa-form.php:15` — `print_r($_POST)` пишет ФИО, email, телефоны в error_log

### 1.4 ~~Удалить GSAP из зависимостей~~ ВЫПОЛНЕНО
- ~~`package.json` — gsap (6.2 МБ) не используется нигде в коде, но попадает в бандл~~

### 1.5 Устранить N+1 в education-filter.php
- `inc/requests/education-filter.php:583-653` — 5 отдельных `wp_get_post_terms()` на каждую запись в цикле (50 записей = 250+ SQL-запросов)
- **Действие:** заранее загрузить термины для всех ID одним запросом через `wp_get_object_terms()`

---

## УРОВЕНЬ 2 — ВЫСОКИЙ ПРИОРИТЕТ (скорость загрузки)

### 2.1 Code-splitting JS (1.5 МБ → ~300 КБ на страницу)
- `js/main.js` — 38+ модулей загружаются на каждой странице
- **Действие:** разделить на бандлы:
  - `common.min.js` — burger, sliders, cookie consent, lazy images
  - `tours.min.js` — toursFilter, tourPrices, gtmSearch
  - `education.min.js` — educationFilter
  - `forms.min.js` — visaForm, fitForm, insuranceForm
- Загружать условно через `wp_enqueue_script()` + `is_page()`

### 2.2 Code-splitting CSS (342 КБ → ~100 КБ на страницу)
- `scss/main.scss` — все 59 файлов в одном бандле
- **Действие:** выделить `critical.css` (above-the-fold) + `common.css` + page-specific CSS
- Отключить sourcemaps в production (`gulp/tasks/scss.js`)

### 2.3 Заменить тяжёлые библиотеки

| Библиотека | Сейчас | Замена | Экономия |
|---|---|---|---|
| choices.js | 2.2 МБ | Tom Select или Slim Select | ~2 МБ |
| daterangepicker | 492 КБ (+ moment.js) | Оставить только flatpickr | ~500 КБ |
| swiper 8.4.7 | 5.2 МБ | swiper 12 (tree-shakeable) | ~3 МБ |

### 2.4 Условная загрузка reCAPTCHA
- `functions.php:155-163` — скрипт Google reCAPTCHA загружается на всех страницах
- **Действие:** загружать только на страницах с формами

### 2.5 Устранить N+1 в tour/card.php
- `template-parts/tour/card.php:74-134` — `get_field()` + `wp_get_post_terms()` в каждой карточке (12 туров = 36+ ACF запросов)
- **Действие:** предзагружать мета-данные до цикла, передавать через `set_query_var()`

### 2.6 Снизить timeout Samo API
- `inc/samo/SamoClient.php:66` — timeout 25 сек блокирует AJAX
- **Действие:** снизить до 8-10 сек, добавить retry с backoff (2 попытки)

### 2.7 Лимит batch-prices
- `inc/requests/batch-prices.php` — нет лимита на количество tour_ids
- **Действие:** ограничить до 30-50 ID за запрос

---

## УРОВЕНЬ 3 — СРЕДНИЙ ПРИОРИТЕТ (качество кода)

### 3.1 Разбить functions.php (759 строк)
- Выделить:
  - `inc/filters/LinkProcessor.php` (строки 209-317)
  - `inc/routing/TourPageRouter.php` (строки 504-675)
  - `inc/admin/CountrySyncManager.php` (строки 712-752)

### 3.2 Разбить helpers.php (1105 строк)
- 3 функции форматирования дат с дублирующимися массивами месяцев
- **Действие:** создать `inc/services/DateFormatter.php`

### 3.3 Синхронизировать TTL кэшей
- `PriceLoaderService` кэширует на 3 часа, `SamoClient` — на 30 мин для тех же данных
- **Действие:** унифицировать TTL, добавить автоинвалидацию на хуке `save_post`

### 3.4 Убрать extract() в BSI_Mailer
- `inc/services/class-bsi-mailer.php:138` — `extract($data)` потенциально опасен
- **Действие:** заменить на явную передачу переменных в шаблон

### 3.5 Убрать 137 !important в CSS
- Индикатор проблем со специфичностью
- **Действие:** рефакторить постепенно, начиная с `buttons.scss`, `forms.scss`, `slider.scss`

---

## УРОВЕНЬ 4 — НИЗКИЙ ПРИОРИТЕТ (долгосрочное улучшение)

### 4.1 Добавить type hints в PHP
- Большинство функций в `helpers.php`, `requests/` без типизации
- Начать с сервисных классов и публичных API

### 4.2 Вынести бизнес-логику из шаблонов
- `single-tour.php` (445 строк), `page-visa.php` (695 строк) содержат парсинг URL, обработку GET-параметров
- **Действие:** перенести в сервисные классы

### 4.3 Оптимизировать Webpack конфигурацию
- `webpack.config.js` — `readDir()` создаёт entry point для каждого файла
- **Действие:** настроить явные entry points + tree-shaking + минификацию через esbuild

### 4.4 Оптимизировать шрифты
- 7 начертаний Inter (2 МБ) — проверить, все ли используются (ExtraLight, Black?)
- Оставить 3-4 основных

---

## Ожидаемый эффект

| Метрика | Сейчас | После уровней 1-2 |
|---|---|---|
| JS размер на страницу | 1.5 МБ | ~200-300 КБ |
| CSS размер на страницу | 342 КБ | ~80-120 КБ |
| SQL-запросов (education) | 250+ | ~10-15 |
| SQL-запросов (tours) | 36+ | ~5-8 |
| AJAX timeout | 25 сек | 8-10 сек |
