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
 * Типы мероприятий на вкладках и в мегаменю — в порядке вывода.
 * Список закрытый: в таксономии есть служебные термы (Выставка, Конгресс,
 * Саммит, Бизнес-завтрак), которые во вкладки не выносим — их записи
 * попадают на вкладку «Все».
 */
function bsi_agency_event_kind_order()
{
  return ['webinar', 'seminar', 'vorkshop', 'otraslevye', 'promo-tour', 'event'];
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
    'otraslevye' => 'Отраслевые',
    'promo-tour' => 'Рекламные туры',
    'event' => 'Мероприятия',
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
 * CSS-модификатор бейджа типа на карточке.
 */
function bsi_agency_event_kind_class($slug)
{
  $map = [
    'webinar' => 'is-webinar',
    'seminar' => 'is-webinar',
    'vorkshop' => 'is-webinar',
    'event' => 'is-event',
    'otraslevye' => 'is-event',
    'promo-tour' => 'is-promo',
  ];

  return isset($map[$slug]) ? $map[$slug] : 'is-default';
}

/**
 * Ссылка на вкладку страницы мероприятий.
 * $kind = '' → «Все», $kind = 'archive' → архив.
 */
function bsi_agency_events_tab_url($kind = '')
{
  $base = bsi_agency_events_page_url();

  if ($kind === 'archive') {
    return add_query_arg('archive', '1', $base);
  }

  if ($kind !== '') {
    return add_query_arg('kind', $kind, $base);
  }

  return $base;
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
