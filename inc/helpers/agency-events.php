<?php
/**
 * Хелперы раздела «Турагентствам» → «Мероприятия».
 *
 * Страница мероприятий — запись CPT documentation со слагом `meropriyatiya`
 * (исторически `obuchenie`). Слаг больше не сравнивается строкой по шаблонам:
 * все проверки идут через bsi_is_agency_events_page().
 */

/**
 * Слаги страницы мероприятий: актуальный и legacy.
 */
function bsi_agency_events_page_slugs()
{
  return ['meropriyatiya', 'obuchenie'];
}

/**
 * Запись-страницу «Мероприятия» ищем по актуальному слагу, затем по legacy.
 */
function bsi_agency_events_page()
{
  static $cached = false;

  if ($cached !== false) {
    return $cached;
  }

  $cached = null;
  foreach (bsi_agency_events_page_slugs() as $slug) {
    $post = get_page_by_path($slug, OBJECT, 'documentation');
    if ($post) {
      $cached = $post;
      break;
    }
  }

  return $cached;
}

function bsi_agency_events_page_id()
{
  $post = bsi_agency_events_page();

  return $post ? (int) $post->ID : 0;
}

function bsi_agency_events_page_url()
{
  $post = bsi_agency_events_page();

  return $post ? (string) get_permalink($post->ID) : home_url('/');
}

/**
 * Текущая страница — список мероприятий?
 */
function bsi_is_agency_events_page($post_id = 0)
{
  $post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
  if (!$post_id) {
    return false;
  }

  $events_page_id = bsi_agency_events_page_id();
  if ($events_page_id) {
    return $post_id === $events_page_id;
  }

  return in_array(get_post_field('post_name', $post_id), bsi_agency_events_page_slugs(), true);
}

/**
 * Архив мероприятий — дочерняя запись со слагом `arhiv`.
 */
function bsi_agency_events_archive_page()
{
  static $cached = false;

  if ($cached !== false) {
    return $cached;
  }

  $cached = null;
  $parent = bsi_agency_events_page();
  if ($parent) {
    $post = get_page_by_path($parent->post_name . '/arhiv', OBJECT, 'documentation');
    $cached = $post ?: null;
  }

  return $cached;
}

function bsi_agency_events_archive_page_url()
{
  $post = bsi_agency_events_archive_page();

  return $post ? (string) get_permalink($post->ID) : '';
}

/**
 * Текущая страница — архив мероприятий?
 */
function bsi_is_agency_events_archive_page($post_id = 0)
{
  $post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
  $archive = bsi_agency_events_archive_page();

  return ($post_id && $archive && $post_id === (int) $archive->ID);
}

/**
 * Страница списка мероприятий — ближайшие или архив.
 */
function bsi_is_agency_events_any_page($post_id = 0)
{
  return bsi_is_agency_events_page($post_id) || bsi_is_agency_events_archive_page($post_id);
}

/**
 * Порядок типов мероприятий на вкладках и в мегаменю.
 * Термы вне списка идут следом по алфавиту.
 */
function bsi_agency_event_kind_order()
{
  return ['webinar', 'seminar', 'vorkshop', 'event', 'promo-tour'];
}

/**
 * Множественное число для вкладок, мегаменю и крошек.
 * Имена термов остаются в единственном числе — они выводятся на карточках.
 */
function bsi_agency_event_kind_plural($slug, $fallback = '')
{
  $map = [
    'webinar' => 'Вебинары',
    'seminar' => 'Семинары',
    'vorkshop' => 'Воркшопы',
    // Слаг `event` исторический — тип называется «Отраслевое».
    'event' => 'Отраслевые',
    'promo-tour' => 'Рекламные туры',
  ];

  if (isset($map[$slug])) {
    return $map[$slug];
  }

  return $fallback !== '' ? $fallback : 'Мероприятия';
}

/**
 * Все термы типов мероприятий для вкладок: сначала в порядке
 * bsi_agency_event_kind_order(), остальные — следом по алфавиту.
 * hide_empty = false: вкладки должны совпадать с пунктами мегаменю,
 * даже если по типу ещё нет записей.
 */
function bsi_agency_event_kind_terms()
{
  $terms = get_terms([
    'taxonomy' => 'agency_event_kind',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
  ]);

  if (is_wp_error($terms) || !is_array($terms)) {
    return [];
  }

  $order = array_flip(bsi_agency_event_kind_order());
  $tail = count($order);

  usort($terms, function ($a, $b) use ($order, $tail) {
    $a_pos = isset($order[$a->slug]) ? $order[$a->slug] : $tail;
    $b_pos = isset($order[$b->slug]) ? $order[$b->slug] : $tail;

    if ($a_pos === $b_pos) {
      return strcmp($a->name, $b->name);
    }

    return $a_pos <=> $b_pos;
  });

  return $terms;
}

/**
 * Аргументы выборки мероприятий: ближайшие или прошедшие.
 * Дата берётся из event_start_ts, для записей без метки — из ACF-поля.
 */
function bsi_agency_events_query_args($archive = false)
{
    $now_ts = (int) current_time('timestamp');
    $today = wp_date('Y-m-d');
    $compare = $archive ? '<' : '>=';

    return [
        'post_type' => 'agency_event',
        'post_status' => 'publish',
        'meta_key' => 'event_start_ts',
        'orderby' => 'meta_value_num',
        'order' => $archive ? 'DESC' : 'ASC',
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'event_start_ts',
                'value' => $now_ts,
                'compare' => $compare,
                'type' => 'NUMERIC',
            ],
            [
                'relation' => 'AND',
                [
                    'key' => 'event_start_ts',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => 'event_start_date',
                    'value' => $today,
                    'compare' => $compare,
                    'type' => 'DATE',
                ],
            ],
        ],
    ];
}

/**
 * CSS-модификатор бейджа типа на карточке.
 */
function bsi_agency_event_kind_class($slug)
{
  $map = [
    'webinar' => 'is-webinar',
    'seminar' => 'is-webinar',
    'vorkshop' => 'is-webinar',
    'event' => 'is-event',
    'promo-tour' => 'is-promo',
  ];

  return isset($map[$slug]) ? $map[$slug] : 'is-default';
}

/**
 * Базовый адрес текущего списка: архив или ближайшие мероприятия.
 */
function bsi_agency_events_base_url()
{
  if (bsi_is_agency_events_archive_page()) {
    $url = bsi_agency_events_archive_page_url();
    if ($url !== '') {
      return $url;
    }
  }

  return bsi_agency_events_page_url();
}

/**
 * Ссылка на вкладку типа в пределах текущего списка.
 * Поисковый запрос сохраняется, номер страницы сбрасывается.
 */
function bsi_agency_events_tab_url($kind = '', $base = '')
{
  $url = ($base !== '') ? $base : bsi_agency_events_base_url();

  $args = [];
  if ($kind !== '') {
    $args['kind'] = $kind;
  }
  $query = bsi_agency_events_search_query();
  if ($query !== '') {
    $args['q'] = $query;
  }

  return $args ? add_query_arg($args, $url) : $url;
}

/**
 * Открыт ли архив мероприятий (отдельная страница `arhiv`).
 */
function bsi_agency_events_is_archive_view()
{
  return bsi_is_agency_events_archive_page();
}

/**
 * Выбранный тип мероприятий ('' — все).
 */
function bsi_agency_events_current_kind()
{
  return isset($_GET['kind']) ? sanitize_key(wp_unslash($_GET['kind'])) : '';
}

/**
 * Поисковый запрос по названию мероприятия.
 */
function bsi_agency_events_search_query()
{
  $query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

  return trim($query);
}

/**
 * Номер страницы списка. На singular WordPress отдаёт /page/N/ в `page`.
 */
function bsi_agency_events_paged()
{
  $paged = (int) get_query_var('page');
  if (!$paged) {
    $paged = (int) get_query_var('paged');
  }

  return max(1, $paged);
}

/**
 * Ссылка на противоположный список: с ближайших — в архив и обратно.
 * Тип и поисковый запрос сохраняются.
 */
function bsi_agency_events_archive_toggle_url()
{
  $archive_url = bsi_agency_events_archive_page_url();
  $is_archive = bsi_is_agency_events_archive_page();

  if (!$is_archive && $archive_url === '') {
    return '';
  }

  $base = $is_archive ? bsi_agency_events_page_url() : $archive_url;

  return bsi_agency_events_tab_url(bsi_agency_events_current_kind(), $base);
}

/**
 * 301 со старого адреса /agentstvam/obuchenie/ на новый.
 * Штатный wp_old_slug_redirect для иерархического CPT не срабатывает,
 * поэтому ловим 404 по последнему сегменту пути.
 */
add_action('template_redirect', 'bsi_redirect_legacy_agency_events_url');
function bsi_redirect_legacy_agency_events_url()
{
  if (!is_404()) {
    return;
  }

  $request_path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
  if (untrailingslashit(basename($request_path)) !== 'obuchenie') {
    return;
  }

  // /country/{country}/obuchenie/ — чужой маршрут, его не трогаем.
  $documentation = get_post_type_object('documentation');
  $section_base = ($documentation && !empty($documentation->rewrite['slug']))
    ? trim((string) $documentation->rewrite['slug'], '/')
    : 'agentstvam';
  if (strpos($request_path, '/' . $section_base . '/') === false) {
    return;
  }

  $target = bsi_agency_events_page();
  if (!$target || $target->post_name === 'obuchenie') {
    return;
  }

  $url = get_permalink($target->ID);
  $query = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
  if ($query !== '') {
    $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
  }

  wp_safe_redirect($url, 301);
  exit;
}

/**
 * Разовое переименование записи «Обучение» → «Мероприятия».
 * _wp_old_slug оставляем, чтобы WordPress сам отдавал 301 со старого адреса.
 */
add_action('init', 'bsi_migrate_agency_events_page_slug', 30);
function bsi_migrate_agency_events_page_slug()
{
  if (get_option('bsi_agency_events_page_renamed')) {
    return;
  }

  $post = get_page_by_path('obuchenie', OBJECT, 'documentation');
  if (!$post) {
    update_option('bsi_agency_events_page_renamed', 1, false);
    return;
  }

  wp_update_post([
    'ID' => $post->ID,
    'post_name' => 'meropriyatiya',
    'post_title' => 'Мероприятия',
  ]);

  // wp_update_post обычно проставляет _wp_old_slug сам — дублировать не надо.
  $old_slugs = get_post_meta($post->ID, '_wp_old_slug', false);
  if (!in_array('obuchenie', (array) $old_slugs, true)) {
    add_post_meta($post->ID, '_wp_old_slug', 'obuchenie');
  }

  update_option('bsi_agency_events_page_renamed', 1, false);
}

/**
 * Разовое приведение типов мероприятий к согласованному списку:
 * терм `event` переименовывается в «Отраслевое» (слаг не трогаем — он в URL),
 * пустые дубли `otraslevye` и `workshop` удаляются.
 */
add_action('init', 'bsi_migrate_agency_event_kind_terms', 32);
function bsi_migrate_agency_event_kind_terms()
{
  if (get_option('bsi_agency_event_kinds_normalized')) {
    return;
  }

  if (!taxonomy_exists('agency_event_kind')) {
    return;
  }

  $event = get_term_by('slug', 'event', 'agency_event_kind');
  if ($event && $event->name !== 'Отраслевое') {
    wp_update_term($event->term_id, 'agency_event_kind', ['name' => 'Отраслевое']);
  }

  // `otraslevye` дублировал `event`, `workshop` — существующий `vorkshop`.
  foreach (['otraslevye', 'workshop'] as $duplicate_slug) {
    $duplicate = get_term_by('slug', $duplicate_slug, 'agency_event_kind');
    if ($duplicate && (int) $duplicate->count === 0) {
      wp_delete_term($duplicate->term_id, 'agency_event_kind');
    }
  }

  update_option('bsi_agency_event_kinds_normalized', 1, false);
}

/**
 * Разовое создание дочерней записи «Архив мероприятий» (/…/meropriyatiya/arhiv/).
 * Сайдбар раздела берёт только записи верхнего уровня, поэтому в нём не появится.
 */
add_action('init', 'bsi_migrate_agency_events_archive_page', 31);
function bsi_migrate_agency_events_archive_page()
{
  if (get_option('bsi_agency_events_archive_page_created')) {
    return;
  }

  $parent = bsi_agency_events_page();
  if (!$parent) {
    return;
  }

  if (!bsi_agency_events_archive_page()) {
    wp_insert_post([
      'post_type' => 'documentation',
      'post_status' => 'publish',
      'post_title' => 'Архив мероприятий',
      'post_name' => 'arhiv',
      'post_parent' => $parent->ID,
      'menu_order' => 100,
    ]);
  }

  update_option('bsi_agency_events_archive_page_created', 1, false);
}
