# Codebase Structure

**Analysis Date:** 2026-04-02

## Directory Layout

```
theme-root/
├── functions.php                         # Theme bootstrap: enqueue assets, wiring CPT/AJAX/services
├── header.php / footer.php              # Global layout
├── index.php                             # Main entry for WP front controller
├── page.php / single.php                # Generic template fallbacks
├── page-*.php / single-*.php            # Custom template files for specific pages/CPTs
├── archive.php / archive-*.php         # CPT archives
├── search.php / 404.php / comments.php
├── template-parts/                     # Reusable partials
├── inc/
│   ├── post-types/                     # CPT/taxonomy registration modules
│   ├── requests/                      # WordPress AJAX handlers (admin-ajax.php actions)
│   ├── services/                      # Domain services: cache, mailer, price loader
│   ├── samo/                          # External SAMOTOUR integration layer
│   ├── mail-templates/               # Email HTML templates used by BSI_Mailer
│   ├── helpers.php                     # Shared procedural utilities used across templates/services
│   ├── template-functions.php         # Theme-specific helpers for templates
│   └── template-tags.php              # Theme-specific template tags
├── custom-fields/                     # ACF local field group definitions
├── js/                                 # Source JS modules used by bundler
├── scss/                               # Source SCSS for styles
├── dist/                               # Build output (minified CSS/JS) enqueued by functions.php
├── gulp/                               # gulp tasks/config used for build
├── gulpfile.js / webpack.config.js / package.json
└── languages/                          # WP translations
```

## Directory Purposes

**`inc/`:**
- Purpose: isolate non-template PHP logic (post types, AJAX handlers, services, integrations, helpers).
- Contains: `inc/post-types/*`, `inc/requests/*`, `inc/services/*`, `inc/samo/*`, `inc/helpers.php`, `inc/mail-templates/*`.
- Key files: `inc/helpers.php`, `inc/services/class-bsi-mailer.php`, `inc/services/PriceLoaderService.php`, `inc/services/CacheService.php`.

**`inc/post-types/`:**
- Purpose: register and configure all custom post types and taxonomies.
- Contains: CPT/taxonomy registration per feature area.
- Key files (examples): `inc/post-types/tour.php`, `inc/post-types/custom-post-types-hotel.php`, `inc/post-types/education.php`, `inc/post-types/news.php`.

**`inc/requests/`:**
- Purpose: WordPress AJAX endpoints and form/filter handlers.
- Contains: `add_action('wp_ajax_*')` registrations + JSON response logic.
- Key files (examples):
  - `inc/requests/samo.php` (proxy router for SAMOTOUR)
  - `inc/requests/batch-prices.php` (tour price fetch + cache persistence)
  - `inc/requests/ajax-fit.php` / `inc/requests/ajax-visa-form.php` / `inc/requests/ajax-insurance-form.php`
  - `inc/requests/ajax-education-program-form.php` / `inc/requests/ajax-event-ticket-form.php`

**`inc/services/`:**
- Purpose: reusable services with explicit responsibilities.
- Contains:
  - `CacheService` (transients wrapper)
  - `PriceLoaderService` (domain logic for pricing)
  - `class-bsi-mailer.php` (template-based mail sending + SMTP init)
- Key files: `inc/services/CacheService.php`, `inc/services/PriceLoaderService.php`, `inc/services/class-bsi-mailer.php`.

**`inc/samo/`:**
- Purpose: integration with SAMOTOUR API.
- Contains: HTTP client, endpoint mapping, service singleton, and lightweight AJAX composition glue.
- Key files: `inc/samo/SamoClient.php`, `inc/samo/SamoEndpoints.php`, `inc/samo/SamoService.php`, `inc/samo/config.php`.

**`inc/mail-templates/`:**
- Purpose: HTML email templates used by `BSI_Mailer::render_template()`.
- Contains: template PHP files expected by the mailer.
- Key files: `inc/mail-templates/education-booking.php`, `inc/mail-templates/event-ticket-booking.php`.

**`custom-fields/`:**
- Purpose: define ACF field groups used by templates and AJAX handlers.
- Contains: field group PHP files (local field group definitions).
- Key files: `custom-fields/pages/contacts.php`, `custom-fields/pages/bonus.php`, `custom-fields/education-fields.php`.

**`js/` (source):**
- Purpose: maintain modular frontend code; bundle into `dist/`.
- Contains: ES modules for UI widgets and AJAX calls.
- Key files: `js/modules/forms/form-ajax.js`, `js/modules/gtm-search.js`, `js/modules/tour-prices.js`.

**`dist/` (build output):**
- Purpose: production assets that WordPress enqueues.
- Contains: minified `dist/css/main.min.css` and `dist/js/main.min.js` (plus minified navigation).
- Key enqueued files (from `functions.php`): `dist/css/main.min.css`, `dist/js/main.min.js`.

**`gulp/` + `gulpfile.js` + `webpack.config.js`:**
- Purpose: build pipeline for SCSS and JS.
- Contains: task definitions and bundler configuration.
- Key files: `gulpfile.js`, `webpack.config.js`, `gulp/tasks/*`.

## Key File Locations

**Entry Points:**
- `functions.php`: theme boot, wiring, script enqueues, AJAX handler module requires.
- `index.php`, `header.php`, `footer.php`: base WordPress template files.
- Template files:
  - `page-education.php` (special filter handling via `template_redirect` in `functions.php`)
  - `single-*.php` and `archive-*.php` (per CPT UI)

**Configuration:**
- `inc/recaptcha.php`: server-side reCAPTCHA v3 verification (expects constants in WP config).
- `inc/samo/config.php`: binds SAMO constants into `samo_config()`.
- `inc/services/class-bsi-mailer.php`: reads `BSI_SMTP_*` constants and configures PHPMailer via `phpmailer_init`.

**Core Logic:**
- `inc/helpers.php`: shared formatting and CPT metadata helper functions.
- `inc/services/CacheService.php`: transient caching wrapper.
- `inc/services/PriceLoaderService.php`: pricing domain logic.
- `inc/services/class-bsi-mailer.php`: email + SMTP configuration.
- `inc/requests/samo.php`: SAMO AJAX router.

**Testing:**
- Not detected. No PHPUnit/Jest/Vitest config in this theme repo.

## Naming Conventions

**Files:**
- AJAX handlers: `inc/requests/ajax-*.php` and modules named by feature.
- SAMO proxy: `inc/requests/samo.php` (action `bsi_samo`).
- Mail templates: `inc/mail-templates/<template-name>.php`.
- Template files: `page-*.php`, `single-*.php`, `archive-*.php`.

**Directories:**
- Feature modules: `inc/post-types/*`, `inc/requests/*`, `inc/services/*`, `inc/samo/*`.
- Frontend bundles:
  - source: `js/` and `scss/`
  - output: `dist/`

## Where to Add New Code

**New AJAX handler:**
- Primary code: `inc/requests/<feature>.php`
- Wiring: add a `require get_template_directory() . '/inc/requests/<feature>.php';` line in `functions.php` (the same pattern as existing requests modules).

**New CPT / taxonomy:**
- Primary code: `inc/post-types/<cpt>.php`
- Wiring: add a `require get_template_directory() . '/inc/post-types/<cpt>.php';` line in `functions.php`.

**New email template:**
- Primary code: `inc/mail-templates/<template>.php`
- Caller: call `BSI_Mailer::send(['template' => '<template>', ...])` from the corresponding handler in `inc/requests/*`.

**New SAMO endpoint / integration method:**
- Primary code:
  - endpoint mapping: `inc/samo/SamoEndpoints.php` (add method that calls `SamoClient::request('<SAMO_ACTION_NAME>', ...)`)
  - router dispatch: `inc/requests/samo.php` (add a new `case '<method>'`)
- Front-end:
  - JS widget should call `action=bsi_samo` with `method=<method>` (as used in `js/modules/gtm-search.js` / `js/modules/tour-prices.js`).

**Utilities / formatting:**
- Shared helpers: `inc/helpers.php` (for formatting + data normalization used across templates/services).

## Special Directories

**`dist/`:**
- Purpose: committed build output consumed by WordPress enqueues.
- Generated: Yes
- Committed: Yes (minified CSS/JS exist in repo)

**`node_modules/`:**
- Purpose: dev-time dependency storage
- Generated: Yes
- Committed: No (present locally, but not intended for source control)

**`gulp/`:**
- Purpose: build tasks
- Generated: No
- Committed: Yes

---

*Structure analysis: 2026-04-02*

