# Architecture

**Analysis Date:** 2026-04-02

## Pattern Overview

**Overall:** WordPress theme with include-based modularity + hook-centric wiring.

**Key Characteristics:**
- `functions.php` is the central bootstrap/wiring point: it configures theme setup/enqueues, registers hooks/filters, and `require_once` loads all CPTs + AJAX handlers + service classes.
- AJAX endpoints are implemented as self-contained modules under `inc/requests/` and are registered via `add_action('wp_ajax_*', ...)` / `add_action('wp_ajax_nopriv_*', ...)`.
- External integration (SAMOTOUR) is encapsulated in `inc/samo/*` (HTTP client + endpoint map + service singleton), while request routing lives in `inc/requests/samo.php`.
- Email sending is centralized into `inc/services/class-bsi-mailer.php`, with direct `wp_mail()` usage still present in some older/standalone AJAX handlers.

## Layers

**Theme bootstrap & runtime wiring (`functions.php`):**
- Purpose: set up theme support, enqueue assets, register CPT modules and request handlers.
- Location: `functions.php`
- Contains: `add_action`/`add_filter` registrations, `wp_localize_script()` for AJAX URL + reCAPTCHA key, `require_once` wiring for `inc/post-types/*`, `inc/requests/*`, `inc/services/*`, and `inc/samo/*`.
- Depends on: WordPress hook system, ACF (for `acf/init`), Jetpack (optional), and `inc/recaptcha.php` for runtime security checks.
- Used by: WordPress during initialization (`after_setup_theme`, `wp_enqueue_scripts`, `init`, `template_redirect`, etc.) and at request time.

**Template & rendering layer (theme templates):**
- Purpose: render HTML for pages/CPTs and push data into templates/tags.
- Location: root templates (e.g. `index.php`, `page.php`, `single.php`, `search.php`, `404.php`, `header.php`, `footer.php`) + `template-parts/`.
- Contains: template markup + calls to helpers from `inc/template-tags.php` and utilities from `inc/template-functions.php`.
- Depends on: WordPress template hierarchy + ACF fields in CPT/page templates.
- Used by: frontend page loads.

**Custom Post Types & Taxonomies (`inc/post-types/*` + ACF field groups):**
- Purpose: register all CPTs/taxonomies used by templates and by AJAX queries.
- Location: `inc/post-types/`
- Contains: `register_post_type()` / `register_taxonomy()` definitions per CPT module.
- Depends on: WordPress registration APIs; ACF field groups under `custom-fields/` enrich content (e.g. `custom-fields/pages/contacts.php`).
- Used by: template queries (`WP_Query`, `get_posts`, etc.) and request handlers that look up posts (e.g. country title by ID).

**AJAX request layer (`inc/requests/*`):**
- Purpose: handle form submissions and data fetches from the frontend through `admin-ajax.php`.
- Location: `inc/requests/`
- Contains:
  - booking/contact form handlers (FIT, visa, insurance, event ticket, education program)
  - filter endpoints (education/event/news/hotels/tours, etc.)
  - SAMO proxy endpoint router (`inc/requests/samo.php`)
  - caching endpoints (tour prices)
  - external currency endpoint (CBR rates)
- Depends on: `inc/recaptcha.php` (`bsi_recaptcha_verify_or_die()`), `inc/services/class-bsi-mailer.php` (for some handlers), `inc/samo/*` (for SAMO proxy), and `inc/services/*` (cache/price loader).
- Used by: JS modules via `admin-ajax.php` action names.

**Email / SMTP layer (`inc/services/class-bsi-mailer.php` + `inc/mail-templates/*`):**
- Purpose: send booking/contact emails from AJAX handlers with HTML templates.
- Location: `inc/services/class-bsi-mailer.php` and `inc/mail-templates/`
- Contains: `BSI_Mailer::send()`, `BSI_Mailer::render_template()`, `BSI_Mailer::configure_smtp()` (PHPMailer hook), and `BSI_Mailer::validate_contact_fields()`.
- Depends on:
  - `wp_mail()` to dispatch mail
  - PHPMailer instance via the `phpmailer_init` hook
  - SMTP constants expected in config: `BSI_SMTP_HOST`, `BSI_SMTP_PORT`, `BSI_SMTP_SECURE`, `BSI_SMTP_USER`, `BSI_SMTP_PASS` (read in `BSI_Mailer::configure_smtp()`).
- Used by:
  - `inc/requests/ajax-education-program-form.php` (template `education-booking`)
  - `inc/requests/ajax-event-ticket-form.php` (template `event-ticket-booking`)
  - indirectly by other flows via `validate_contact_fields()`

**External integration layer: SAMOTOUR (`inc/samo/*` + proxy router):**
- Purpose: retrieve tour/hotel/excursion data from SAMOTOUR and normalize response shape for frontend.
- Location: `inc/samo/`
- Contains:
  - `SamoClient`: HTTP GET requester + transient caching + XML/JSON parsing (`inc/samo/SamoClient.php`)
  - `SamoEndpoints`: maps SAMO “actions” to client calls (`inc/samo/SamoEndpoints.php`)
  - `SamoService`: provides singleton access to client/endpoints + reads constants (`inc/samo/SamoService.php`)
  - `inc/samo/ajax/routes.php`: lightweight composition/integration glue (client + endpoints)
  - `inc/requests/samo.php`: AJAX-level “method” router + optional additional caching via `inc/services/CacheService.php`
- Depends on: configured SAMO constants (read in `inc/samo/config.php` and `inc/samo/SamoService.php`), WordPress HTTP API (`wp_remote_get()`), and transients for caching (`get_transient()` / `set_transient()`).
- Used by:
  - `inc/requests/samo.php` (proxy)
  - `inc/services/PriceLoaderService.php` (price lookup via `SamoService::endpoints()`)

**Caching layer (`inc/services/CacheService.php`, transients, and price caches):**
- Purpose: provide grouped transient caching for API responses and pricing.
- Location: `inc/services/CacheService.php`
- Contains: `CacheService::get/set/remember/forget/flush()` built on WordPress transients.
- Used by:
  - `inc/services/PriceLoaderService.php` for tour prices
  - `inc/requests/samo.php` for additional caching groups (e.g. `samotour`)
  - `inc/requests/batch-prices.php` for persisting computed minimal tour price (`CacheService::set()`).

**Front-end asset layer (`js/*`, `scss/*`, and `dist/*`):**
- Purpose: deliver compiled JS/CSS to visitors and call AJAX endpoints.
- Source:
  - JS source: `js/` (modular files + AJAX helpers)
  - SCSS source: `scss/main.scss` and other SCSS trees
- Build output:
  - CSS: `dist/css/main.min.css`
  - JS: `dist/js/main.min.js` (plus `dist/js/navigation.min.js` et al.)
- Enqueue wiring:
  - `functions.php` uses `wp_enqueue_style()` / `wp_enqueue_script()` to include `dist/*`.
- Used by:
  - form submission helpers (`js/modules/forms/form-ajax.js`)
  - SAMO browsing widgets (`js/modules/gtm-search.js`, `js/modules/tour-prices.js`)
  - price cache persistence (`save_tour_min_price` from `js/modules/tour-prices.js`)

**Build/tooling layer (gulp + webpack + esbuild-loader):**
- Purpose: compile SCSS and bundle JS modules into `dist/`.
- Location:
  - `gulpfile.js` + `gulp/tasks/*`
  - `webpack.config.js`
  - `package.json`
- Depends on npm toolchain and build tasks invoked via `npm run build`.

## Data Flow

### 1) Front-end form -> `admin-ajax.php` -> validation -> email

1. `functions.php` enqueues `dist/js/main.min.js` and localizes AJAX config:
   - `wp_localize_script('main', 'ajax', ['url' => admin_url('admin-ajax.php'), 'recaptchaSiteKey' => ...])`
2. JS uses `submitFormWithRecaptcha()` in `js/modules/forms/form-ajax.js`:
   - adds `recaptcha_token`
   - sends `fetch(ajax.url)` with `FormData` including `action` (WordPress AJAX action name)
3. Server resolves handler by WordPress action:
   - e.g. `inc/requests/ajax-fit.php` registers `wp_ajax_fit_form` / `wp_ajax_nopriv_fit_form`
   - e.g. `inc/requests/ajax-visa-form.php` registers `wp_ajax_visa_form` / `wp_ajax_nopriv_visa_form`
4. Each handler validates reCAPTCHA via `bsi_recaptcha_verify_or_die()` from `inc/recaptcha.php`.
5. Email dispatch:
   - Education program + event ticket booking use `BSI_Mailer::send()`:
     - `inc/requests/ajax-education-program-form.php` => template `education-booking`
     - `inc/requests/ajax-event-ticket-form.php` => template `event-ticket-booking`
   - FIT/visa/insurance handlers use direct `wp_mail()` with inline HTML message (still present in `inc/requests/ajax-fit.php`, `inc/requests/ajax-visa-form.php`, `inc/requests/ajax-insurance-form.php`).
6. Handler returns JSON:
   - `wp_send_json_success()` / `wp_send_json_error()`; the HTTP status code may be implied or explicit (some SAMO errors use a 400/500 path).

### 2) SAMOTOUR proxy: JS -> `bsi_samo` -> SAMO client -> JSON

1. JS sends request to `admin-ajax.php` with:
   - `action=bsi_samo`
   - `method=<SAMO router case name>`
   - additional params (e.g. `TOWNFROMINC`, `STATEINC`, `TOURS`, date fields)
   - Example: `samoAjax(method, params)` in `js/modules/gtm-search.js` / `js/modules/tour-prices.js`
2. WordPress routes to handler registered in `inc/requests/samo.php`:
   - `add_action('wp_ajax_bsi_samo', 'samo_ajax')` and `wp_ajax_nopriv_bsi_samo`.
3. `samo_ajax()` switch-dispatches by `$method` (e.g. `townfroms`, `states`, `excursion_prices`, etc.).
4. It calls SAMO endpoints through `SamoService::endpoints()`:
   - the endpoints are mapped in `inc/samo/SamoEndpoints.php`
   - the underlying HTTP request + transient caching is in `inc/samo/SamoClient.php`
5. `inc/requests/samo.php` may add an extra caching layer via `CacheService::remember()` using group `samotour` (and a cache key derived from params).
6. JSON is returned to JS with `wp_send_json_success()` or `wp_send_json_error()` on validation or upstream errors.

Note on request payload shape:
- `js/modules/ajax/samo-ajax.js` posts `endpoint` keys (`{ action: "bsi_samo", endpoint: "townfroms", ... }`), while `inc/requests/samo.php` reads `$_POST['method']`.
- Current codebase uses `method` in `js/modules/gtm-search.js` and `js/modules/tour-prices.js`; treat `samo-ajax.js` as “possibly legacy/unused” unless confirmed in templates.

### 3) Tour price loader: SAMO -> PriceLoaderService -> transient cache -> UI

1. Request handlers for pricing cache live in `inc/requests/batch-prices.php`:
   - `get_batch_tour_prices`
   - `get_tour_price`
   - `clear_tour_prices_cache`
   - `save_tour_min_price` (persists precomputed min price into transients)
2. `PriceLoaderService::getTourPrice()`:
   - checks cache via `CacheService::get()`
   - if missing, loads excursion params from `inc/helpers.php` (`get_tour_excursion_params($tour_id)`)
   - calls SAMO via `SamoService::endpoints()->searchExcursionPrices($api_params)`
   - normalizes response by finding minimum available price and dividing by 2 (per-person convention)
3. Front-end uses `js/modules/tour-prices.js` to:
   - display price data
   - persist `min_price` into the server cache by calling `save_tour_min_price` (AJAX action).

## State Management

- Caching primitives:
  - `CacheService` uses WordPress transients with grouped keys (`inc/services/CacheService.php`).
  - SAMO HTTP requests cache at the client level using transients (`inc/samo/SamoClient.php`).
- Tour prices state:
  - cached in `tour_prices` group (`PriceLoaderService::CACHE_GROUP_TOURS`)
  - optionally overridden by `save_tour_min_price` in `inc/requests/batch-prices.php` using `CacheService::set()`.
- Admin/operator cache purge:
  - `functions.php` includes an `init` hook that can purge tour transients by query param `clear_price_cache` (guarded by `current_user_can('manage_options')`).

## Key Abstractions

**`BSI_Mailer` (`inc/services/class-bsi-mailer.php`):**
- Purpose: template-based HTML email sender with optional SMTP configuration.
- Examples: `inc/requests/ajax-education-program-form.php`, `inc/requests/ajax-event-ticket-form.php`
- Pattern: static facade + WordPress hooks (`phpmailer_init`) + template render fallback.

**`SamoClient` (`inc/samo/SamoClient.php`):**
- Purpose: build SAMO query params, perform `wp_remote_get()`, parse JSON/XML, and cache results.
- Examples: called by `SamoEndpoints` and indirectly by `PriceLoaderService`.
- Pattern: transient-cache HTTP client.

**`SamoEndpoints` (`inc/samo/SamoEndpoints.php`):**
- Purpose: stable method names that correspond to SAMO API actions (tour search, states, hotel lists, excursion prices, tickets methods).
- Pattern: thin mapping layer over `SamoClient::request()`.

**`SamoService` (`inc/samo/SamoService.php`):**
- Purpose: singleton access to `SamoClient` and `SamoEndpoints` and constants binding.
- Pattern: static singleton holders.

**`CacheService` (`inc/services/CacheService.php`):**
- Purpose: unified transient caching with groups and cache flushing.
- Pattern: grouped transient wrapper.

**`PriceLoaderService` (`inc/services/PriceLoaderService.php`):**
- Purpose: translate stored CPT/ACF metadata into SAMO request params and normalize pricing into frontend-ready fields.
- Pattern: domain service using cache + external endpoint + response normalization.

**Utility helpers (`inc/helpers.php`):**
- Purpose: date/price formatting and CPT metadata helpers used by services (e.g. excursion param builder used by `PriceLoaderService`).
- Pattern: procedural helper functions used across templates/services.

## Entry Points

**Theme bootstrap:**
- `functions.php`: runtime setup, enqueue dist assets, wiring for CPTs + request handlers + email + SAMO services.

**Template files (front-end routing by WP template hierarchy):**
- `index.php`, `page.php`, `single.php`, `single-*.php`, `page-*.php`, `archive.php`, `archive-*.php`, `search.php`, `404.php`, `header.php`, `footer.php`, `comments.php`, `sidebar.php`.

**CPT registration modules:**
- `inc/post-types/*.php` (e.g. `inc/post-types/tour.php`, `inc/post-types/custom-post-types-hotel.php`, `inc/post-types/news.php`, `inc/post-types/education.php`, etc.).

**AJAX entry points (WordPress “action” names):**
- `inc/requests/*.php` modules register handlers:
  - `fit_form` (`inc/requests/ajax-fit.php`)
  - `visa_form` (`inc/requests/ajax-visa-form.php`)
  - `insurance_form` (`inc/requests/ajax-insurance-form.php`)
  - `education_program_booking` (`inc/requests/ajax-education-program-form.php`)
  - `event_ticket_booking` (`inc/requests/ajax-event-ticket-form.php`)
  - `agency_event_registration` (`inc/requests/agency-event-registration.php`)
  - `simple_contact_form` (`inc/requests/ajax.php`)
  - `bsi_samo` (`inc/requests/samo.php`) with `$method` dispatch
  - `bsi_cbr_rates` (`inc/requests/ajax-cbr-rates.php`)
  - pricing actions like `get_batch_tour_prices`, `get_tour_price`, `save_tour_min_price` (`inc/requests/batch-prices.php`)

**Operational hooks:**
- `functions.php` `init` hook: purge tour transients via `?clear_price_cache=1` (requires `manage_options`).
- `functions.php` `template_redirect` hook: custom redirect flow for `redirect-bsistudy.php`.
- `functions.php` `template_redirect` hook: custom handling for education filter parameters and dynamic template selection (`page-education.php`).

## Error Handling

**AJAX errors:**
- Most handlers validate inputs, build `$errors`, and return via `wp_send_json_error()` with structured payload.
- reCAPTCHA failure is handled centrally by `bsi_recaptcha_verify_or_die()` (`inc/recaptcha.php`), which short-circuits with JSON error if tokens are invalid/score too low.

**External API errors:**
- SAMO client: `SamoClient::request()` returns `['ok'=>false, 'error'=>..., 'url'=>...]` instead of throwing on HTTP parsing/transport issues.
- SAMO router: `inc/requests/samo.php` wraps responses so that `ok=false` becomes a `wp_send_json_error()` response.
- Currency (CBR): `inc/requests/ajax-cbr-rates.php` falls back to previous transient values if upstream fails or response shape is invalid.

**Email errors:**
- `BSI_Mailer::send()` returns `['success'=>false,...]` if the template is missing/empty or if `wp_mail()` fails.
- SMTP configuration happens via `phpmailer_init` hook in `functions.php`:
  - `add_action('phpmailer_init', ['BSI_Mailer', 'configure_smtp']);`

## Cross-Cutting Concerns

**Logging:**
- `error_log()` used in `BSI_Mailer::send()` and SAMO/email handlers for diagnostic output.

**Validation & Sanitization:**
- Form AJAX handlers use `sanitize_text_field`, `sanitize_textarea_field`, `sanitize_email`, `absint`, `sanitize_text_field` on POST input.
- Input validation errors returned as structured `errors` objects in JSON responses.

**Authorization:**
- Cache purge endpoints are restricted by `current_user_can('manage_options')` in `functions.php` and `inc/requests/batch-prices.php`.

**Security:**
- reCAPTCHA verification is applied to booking/contact actions in `inc/requests/*`.

## Top Files To Review

1. `functions.php`
2. `inc/requests/samo.php`
3. `inc/samo/SamoClient.php`
4. `inc/samo/SamoEndpoints.php`
5. `inc/samo/SamoService.php`
6. `inc/services/class-bsi-mailer.php`
7. `inc/recaptcha.php`
8. `inc/requests/batch-prices.php`
9. `inc/services/PriceLoaderService.php`
10. `inc/services/CacheService.php`

*Architecture analysis: 2026-04-02*

