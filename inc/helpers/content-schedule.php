<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Срок показа включён для фронта и admin-ajax (фильтры запросов, 404 по сроку, ACF на фронте).
 *
 * Временное отключение: BSI_CONTENT_SCHEDULE_DISABLED в functions.php (до подключения файла)
 * или в wp-config.php. Или фильтр bsi_content_schedule_enabled → false.
 */
function bsi_content_schedule_enabled(): bool
{
  if (defined('BSI_CONTENT_SCHEDULE_DISABLED') && BSI_CONTENT_SCHEDULE_DISABLED) {
    return false;
  }

  return (bool) apply_filters('bsi_content_schedule_enabled', true);
}

/*
 * --- Производительность / Query Monitor ---
 *
 * Топ-сценарии, где «срок показа» даёт доп. нагрузку в связке с тяжёлыми запросами:
 *
 * 1) Каталог событийных туров: см. page-sobytiynye-tury.php, inc/requests/event-tours-filter.php
 *    (posts_per_page -1 + meta_query расписания; явный post__in — фильтр только в PHP, без JOIN см. bsi_schedule_post__in_is_finite_nonempty).
 * 2) Admin-ajax event_tours_* / фильтры: полная выборка ID + schedule в SQL.
 * 3) Прочие листинги с bsi_query_args_append_schedule(): туры/новости/промо и т.д. — см. grep по имени функции.
 *
 * В QM смотреть: толстые SELECT с JOIN к postmeta по bsi_active_* / promo_date_*; лавину get_post_meta.
 */

/**
 * Типы записей, для которых не подключаем срок показа.
 *
 * @return string[]
 */
function bsi_content_schedule_post_types_excluded(): array
{
  return apply_filters('bsi_content_schedule_post_types_exclude', [
    'attachment',
    'revision',
    'nav_menu_item',
    'custom_css',
    'customize_changeset',
    'oembed_cache',
    'user_request',
    'wp_block',
    'wp_template',
    'wp_template_part',
    'wp_global_styles',
    'wp_navigation',
    'acf-field',
    'acf-field-group',
  ]);
}

/**
 * Все редактируемые типы записей (show_ui) со сроком показа на фронте.
 *
 * @return string[]
 */
function bsi_content_schedule_post_types(): array
{
  // Без статического кэша: первый частый вызов — init:10 до register_post_type() у CPT
  // (типы ещё не зарегистрированы), иначе навсегда выпадают event и другие поздние CPT.
  $exclude = array_flip(bsi_content_schedule_post_types_excluded());
  $types = [];

  foreach (get_post_types(['show_ui' => true], 'names') as $post_type) {
    if (!isset($exclude[$post_type])) {
      $types[] = $post_type;
    }
  }

  return apply_filters('bsi_content_schedule_post_types', $types);
}

/**
 * Тип поддерживает панель срока в блочном редакторе (REST).
 */
function bsi_content_schedule_supports_block_editor(string $post_type): bool
{
  $object = get_post_type_object($post_type);
  if (!$object instanceof WP_Post_Type) {
    return false;
  }

  if (
    empty($object->show_in_rest)
    || !function_exists('use_block_editor_for_post_type')
    || !use_block_editor_for_post_type($post_type)
  ) {
    return false;
  }

  return true;
}

/**
 * Типы с полями bsi_active_* в ACF (promo — свои promo_date_*).
 *
 * @return string[]
 */
function bsi_content_schedule_acf_post_types(): array
{
  return array_values(array_filter(
    bsi_content_schedule_post_types(),
    static fn(string $post_type): bool => $post_type !== 'promo'
  ));
}

/**
 * @return bool
 */
function bsi_content_schedule_applies_to_post_type(string $post_type): bool
{
  return in_array($post_type, bsi_content_schedule_post_types(), true);
}

/**
 * @return array{from: string, until: string}
 */
function bsi_schedule_keys_for_post_type(string $post_type): array
{
  if ($post_type === 'promo') {
    return [
      'from' => 'promo_date_from',
      'until' => 'promo_date_to',
    ];
  }

  return [
    'from' => 'bsi_active_from',
    'until' => 'bsi_active_until',
  ];
}

/**
 * Нормализует дату ACF/строку к Ymd для сравнения.
 */
function bsi_schedule_normalize_date($value): ?string
{
  if ($value === null || $value === false) {
    return null;
  }

  $value = trim((string) $value);
  if ($value === '') {
    return null;
  }

  if (preg_match('/^\d{8}$/', $value)) {
    return $value;
  }

  $ts = strtotime($value);
  return $ts ? date('Ymd', $ts) : null;
}

/**
 * Ymd → значение для input[type=date] (Y-m-d).
 */
function bsi_schedule_ymd_to_date_input($value): string
{
  $norm = bsi_schedule_normalize_date($value);
  if ($norm === null) {
    return '';
  }

  $date = DateTime::createFromFormat('Ymd', $norm);
  return $date instanceof DateTime ? $date->format('Y-m-d') : '';
}

/**
 * input[type=date] → Ymd для хранения.
 */
function bsi_schedule_date_input_to_ymd(string $value): string
{
  $value = trim($value);
  if ($value === '') {
    return '';
  }

  $norm = bsi_schedule_normalize_date($value);
  return $norm ?? '';
}

/**
 * @return array{from: string, until: string}
 */
function bsi_schedule_get_post_meta(int $post_id, ?string $post_type = null): array
{
  $post_type = $post_type ?: (string) get_post_type($post_id);
  $keys = bsi_schedule_keys_for_post_type($post_type);

  if (function_exists('get_field')) {
    $from = get_field($keys['from'], $post_id);
    $until = get_field($keys['until'], $post_id);
  } else {
    $from = get_post_meta($post_id, $keys['from'], true);
    $until = get_post_meta($post_id, $keys['until'], true);
  }

  return [
    'from' => bsi_schedule_normalize_date($from) ?? '',
    'until' => bsi_schedule_normalize_date($until) ?? '',
  ];
}

/**
 * @param string $from_ymd
 * @param string $until_ymd
 */
function bsi_schedule_save_post_meta(int $post_id, string $from_ymd, string $until_ymd, ?string $post_type = null): void
{
  $post_type = $post_type ?: (string) get_post_type($post_id);
  $keys = bsi_schedule_keys_for_post_type($post_type);
  $from_ymd = bsi_schedule_normalize_date($from_ymd) ?? '';
  $until_ymd = bsi_schedule_normalize_date($until_ymd) ?? '';

  if (function_exists('update_field')) {
    update_field($keys['from'], $from_ymd, $post_id);
    update_field($keys['until'], $until_ymd, $post_id);
    return;
  }

  update_post_meta($post_id, $keys['from'], $from_ymd);
  update_post_meta($post_id, $keys['until'], $until_ymd);
}

/**
 * @param mixed $value
 */
function bsi_schedule_sanitize_meta_value($value): string
{
  if ($value === null || $value === false || $value === '') {
    return '';
  }

  return bsi_schedule_normalize_date($value) ?? '';
}

/**
 * @return array<string, array{from: string, until: string}>
 */
function bsi_schedule_keys_map_for_js(): array
{
  $map = [];
  foreach (bsi_content_schedule_post_types() as $post_type) {
    $map[$post_type] = bsi_schedule_keys_for_post_type($post_type);
  }

  return $map;
}

/**
 * Активен ли контент в заданном диапазоне (пустое поле = без ограничения с этой стороны).
 */
function bsi_is_content_active($from, $until, ?string $now = null): bool
{
  if (!bsi_content_schedule_enabled()) {
    return true;
  }

  $now = bsi_schedule_normalize_date($now ?? date('Ymd'));
  if ($now === null) {
    return true;
  }

  $from_norm = bsi_schedule_normalize_date($from);
  $until_norm = bsi_schedule_normalize_date($until);

  if ($from_norm !== null && $now < $from_norm) {
    return false;
  }

  if ($until_norm !== null && $now > $until_norm) {
    return false;
  }

  return true;
}

/**
 * meta_query: запись активна «сегодня» (для WP_Query).
 *
 * @return array<int|string, mixed>
 */
function bsi_schedule_meta_query(string $from_key, string $until_key, ?string $now = null): array
{
  $today = bsi_schedule_normalize_date($now ?? date('Ymd')) ?: date('Ymd');

  return [
    'relation' => 'AND',
    [
      'relation' => 'OR',
      ['key' => $from_key, 'compare' => 'NOT EXISTS'],
      ['key' => $from_key, 'value' => '', 'compare' => '='],
      ['key' => $from_key, 'value' => $today, 'compare' => '<='],
    ],
    [
      'relation' => 'OR',
      ['key' => $until_key, 'compare' => 'NOT EXISTS'],
      ['key' => $until_key, 'value' => '', 'compare' => '='],
      ['key' => $until_key, 'value' => $today, 'compare' => '>='],
    ],
  ];
}

/**
 * Явный непустой post__in (не только [0]).
 * Такие запросы фильтруем по расписанию в PHP в pre_get_posts — см. bsi_schedule_filter_post__in_ids(),
 * чтобы не добавлять в SQL второй слой тех же условий (LEFT JOIN wp_postmeta ×4 + GROUP BY).
 *
 * @param int[] $post_in
 */
function bsi_schedule_post__in_is_finite_nonempty(array $post_in): bool
{
  $normalized = array_values(array_unique(array_filter(array_map('intval', $post_in))));
  return $normalized !== [] && $normalized !== [0];
}

/**
 * Активна ли запись по meta полям срока.
 */
function bsi_post_is_schedule_active(int $post_id, ?string $post_type = null): bool
{
  $post_type = $post_type ?: (string) get_post_type($post_id);
  if (!bsi_content_schedule_applies_to_post_type($post_type)) {
    return true;
  }

  $keys = bsi_schedule_keys_for_post_type($post_type);
  $from = function_exists('get_field') ? get_field($keys['from'], $post_id) : null;
  $until = function_exists('get_field') ? get_field($keys['until'], $post_id) : null;

  return bsi_is_content_active($from, $until);
}

/**
 * Одним запросом: ID записи → post_type (для батча без N раз get_post_type).
 *
 * @param int[] $ids
 * @return array<int, string>
 */
function bsi_schedule_map_post_types_for_ids(array $ids): array
{
  $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
  if ($ids === []) {
    return [];
  }

  global $wpdb;
  $out = [];
  foreach (array_chunk($ids, 500) as $chunk) {
    if ($chunk === []) {
      continue;
    }
    $placeholders = implode(',', array_fill(0, count($chunk), '%d'));
    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPlaceholder
    $sql = "SELECT ID, post_type FROM {$wpdb->posts} WHERE ID IN ($placeholders)";
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders match chunk count
    $prepared = $wpdb->prepare($sql, ...$chunk);
    $rows = $wpdb->get_results($prepared, ARRAY_A);
    if (!is_array($rows)) {
      continue;
    }
    foreach ($rows as $row) {
      $out[(int) $row['ID']] = (string) $row['post_type'];
    }
  }

  return $out;
}

/**
 * Добавляет meta_query срока к аргументам WP_Query для одного post_type.
 *
 * @param array<string, mixed> $args
 * @return array<string, mixed>
 */
function bsi_schedule_meta_query_is_present(array $meta_query, string $from_key, string $until_key): bool
{
  foreach ($meta_query as $clause) {
    if (!is_array($clause)) {
      continue;
    }
    if (isset($clause['key']) && in_array($clause['key'], [$from_key, $until_key], true)) {
      return true;
    }
    if (bsi_schedule_meta_query_is_present($clause, $from_key, $until_key)) {
      return true;
    }
  }

  return false;
}

/**
 * @param array<string, mixed> $args
 * @return array<string, mixed>
 */
function bsi_query_args_append_schedule(array $args): array
{
  if (!bsi_content_schedule_enabled()) {
    return $args;
  }

  if (!empty($args['bsi_schedule_applied'])) {
    return $args;
  }

  if (isset($args['post__in']) && is_array($args['post__in']) && bsi_schedule_post__in_is_finite_nonempty($args['post__in'])) {
    $args['bsi_schedule_applied'] = true;
    return $args;
  }

  $post_type = $args['post_type'] ?? '';
  $types = is_array($post_type) ? $post_type : ($post_type !== '' ? [$post_type] : []);

  if (count($types) !== 1) {
    return $args;
  }

  $type = (string) $types[0];
  if (!bsi_content_schedule_applies_to_post_type($type)) {
    return $args;
  }

  $keys = bsi_schedule_keys_for_post_type($type);
  $schedule_mq = bsi_schedule_meta_query($keys['from'], $keys['until']);

  $existing = $args['meta_query'] ?? [];
  if (is_array($existing) && bsi_schedule_meta_query_is_present($existing, $keys['from'], $keys['until'])) {
    $args['bsi_schedule_applied'] = true;
    return $args;
  }

  if (empty($existing)) {
    $args['meta_query'] = $schedule_mq;
  } elseif (isset($existing['relation'])) {
    $args['meta_query'] = [
      'relation' => 'AND',
      $existing,
      $schedule_mq,
    ];
  } else {
    $args['meta_query'] = array_merge(
      ['relation' => 'AND'],
      $existing,
      [$schedule_mq]
    );
  }

  $args['bsi_schedule_applied'] = true;

  return $args;
}

/**
 * Фильтрует строки repeater/массива ACF по сроку показа.
 *
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function bsi_filter_schedule_rows(array $rows, string $from_key = 'bsi_active_from', string $until_key = 'bsi_active_until'): array
{
  return array_values(array_filter($rows, static function ($row) use ($from_key, $until_key) {
    if (!is_array($row)) {
      return false;
    }

    return bsi_is_content_active($row[$from_key] ?? null, $row[$until_key] ?? null);
  }));
}

/**
 * Подполя date_picker для repeater (срок показа секции).
 *
 * @return array<int, array<string, mixed>>
 */
function bsi_schedule_repeater_sub_fields(string $prefix): array
{
  return [
    [
      'key' => 'field_' . $prefix . '_bsi_active_from',
      'label' => 'Показывать с',
      'name' => 'bsi_active_from',
      'type' => 'date_picker',
      'display_format' => 'd.m.Y',
      'return_format' => 'Ymd',
      'first_day' => 1,
      'required' => 0,
      'wrapper' => ['width' => '50'],
    ],
    [
      'key' => 'field_' . $prefix . '_bsi_active_until',
      'label' => 'Показывать до',
      'name' => 'bsi_active_until',
      'type' => 'date_picker',
      'display_format' => 'd.m.Y',
      'return_format' => 'Ymd',
      'first_day' => 1,
      'required' => 0,
      'wrapper' => ['width' => '50'],
    ],
  ];
}

add_filter('query_vars', 'bsi_content_schedule_query_vars');

/**
 * @param string[] $vars
 * @return string[]
 */
function bsi_content_schedule_query_vars(array $vars): array
{
  $vars[] = 'bsi_skip_schedule';
  $vars[] = 'bsi_schedule_applied';

  return $vars;
}

add_action('pre_get_posts', 'bsi_content_schedule_pre_get_posts', 18);

/**
 * Оставляет в post__in только записи, активные по сроку показа (порядок сохраняется).
 *
 * @param int[] $post_in
 * @return int[]
 */
function bsi_schedule_filter_post__in_ids(array $post_in): array
{
  if (!bsi_content_schedule_enabled()) {
    $out = [];
    foreach ($post_in as $raw) {
      $id = (int) $raw;
      if ($id > 0) {
        $out[] = $id;
      }
    }

    return $out;
  }

  $normalized = [];
  foreach ($post_in as $raw) {
    $id = (int) $raw;
    if ($id > 0) {
      $normalized[] = $id;
    }
  }
  $normalized = array_values(array_unique($normalized));
  if ($normalized === []) {
    return [];
  }

  $types_by_id = bsi_schedule_map_post_types_for_ids($normalized);

  /** @var int[] $needs_meta_prime */
  $needs_meta_prime = [];
  foreach ($normalized as $id) {
    $type = $types_by_id[$id] ?? '';
    if ($type !== '' && bsi_content_schedule_applies_to_post_type($type)) {
      $needs_meta_prime[] = $id;
    }
  }

  if ($needs_meta_prime !== []) {
    update_postmeta_cache($needs_meta_prime);
  }

  $out = [];
  foreach ($post_in as $raw) {
    $id = (int) $raw;
    if ($id <= 0) {
      continue;
    }

    $type = $types_by_id[$id] ?? '';
    if ($type === '') {
      continue;
    }

    if (!bsi_content_schedule_applies_to_post_type($type)) {
      $out[] = $id;
      continue;
    }

    $keys = bsi_schedule_keys_for_post_type($type);
    $from_raw = get_post_meta($id, $keys['from'], true);
    $until_raw = get_post_meta($id, $keys['until'], true);

    if (bsi_is_content_active($from_raw, $until_raw)) {
      $out[] = $id;
    }
  }

  return $out;
}

/**
 * Один тип записи по списку ID (для meta_query), или null при смеси / пусто.
 *
 * @param int[] $ids
 */
function bsi_content_schedule_infer_single_type_from_ids(array $ids): ?string
{
  $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
  if ($ids === []) {
    return null;
  }

  $map = bsi_schedule_map_post_types_for_ids($ids);
  $types = [];
  foreach ($map as $t) {
    if ($t !== '') {
      $types[$t] = true;
    }
  }

  if (count($types) !== 1) {
    return null;
  }

  return array_key_first($types);
}

/**
 * Где применять фильтрацию: публичный фронт и admin-ajax (каталоги), не списки в wp-admin.
 */
function bsi_content_schedule_allow_query_filters(): bool
{
  if (defined('WP_CLI') && WP_CLI) {
    return false;
  }

  if (!is_admin()) {
    return true;
  }

  return function_exists('wp_doing_ajax') && wp_doing_ajax();
}

function bsi_content_schedule_pre_get_posts(WP_Query $query): void
{
  if (!bsi_content_schedule_enabled()) {
    return;
  }

  if (!$query instanceof WP_Query || !bsi_content_schedule_allow_query_filters() || $query->get('bsi_skip_schedule')) {
    return;
  }

  $post_in = $query->get('post__in');
  if (is_array($post_in) && $post_in !== []) {
    $ids_for_check = array_values(array_unique(array_filter(array_map('intval', $post_in))));
    if ($ids_for_check !== [0]) {
      $filtered_in = bsi_schedule_filter_post__in_ids($post_in);
      if ($filtered_in === []) {
        $query->set('post__in', [0]);
      } elseif (count($filtered_in) !== count($post_in) || $filtered_in !== array_map('intval', $post_in)) {
        $query->set('post__in', $filtered_in);
      }
      $query->set('bsi_schedule_applied', true);

      return;
    }
  }

  if ($query->get('bsi_schedule_applied')) {
    return;
  }

  $post_type = $query->get('post_type');
  $post_in_current = $query->get('post__in');

  if ($post_type === '' || $post_type === null) {
    if ($query->is_home() && !$query->is_front_page()) {
      $post_type = 'post';
    } elseif (is_array($post_in_current) && $post_in_current !== []) {
      $inferred = bsi_content_schedule_infer_single_type_from_ids(
        array_map('intval', $post_in_current)
      );
      if ($inferred !== null) {
        $post_type = $inferred;
      } else {
        return;
      }
    } else {
      return;
    }
  }

  $types = is_array($post_type) ? $post_type : [$post_type];
  $types = array_values(array_intersect(array_map('strval', $types), bsi_content_schedule_post_types()));

  if (count($types) !== 1) {
    return;
  }

  $merged = bsi_query_args_append_schedule([
    'post_type' => $types[0],
    'meta_query' => $query->get('meta_query') ?: [],
  ]);

  if (!empty($merged['meta_query'])) {
    $query->set('meta_query', $merged['meta_query']);
  }

  if (!empty($merged['bsi_schedule_applied'])) {
    $query->set('bsi_schedule_applied', true);
  }
}

add_action('template_redirect', 'bsi_content_schedule_template_redirect', 5);

function bsi_content_schedule_template_redirect(): void
{
  if (!bsi_content_schedule_enabled()) {
    return;
  }

  if (is_admin() || !is_singular()) {
    return;
  }

  $post = get_queried_object();
  if (!($post instanceof WP_Post) || $post->post_status !== 'publish') {
    return;
  }

  if (!bsi_content_schedule_applies_to_post_type($post->post_type)) {
    return;
  }

  if (bsi_post_is_schedule_active((int) $post->ID, $post->post_type)) {
    return;
  }

  if ($post->post_type === 'promo') {
    return;
  }

  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  nocache_headers();
}

add_action('init', 'bsi_content_schedule_register_admin_columns', 50);

function bsi_content_schedule_register_admin_columns(): void
{
  if (!bsi_content_schedule_enabled()) {
    return;
  }

  $admin_types = array_values(array_intersect(
    bsi_content_schedule_post_types(),
    get_post_types(['show_ui' => true], 'names')
  ));

  foreach ($admin_types as $post_type) {
    add_filter("manage_{$post_type}_posts_columns", 'bsi_schedule_admin_add_column');
    add_action("manage_{$post_type}_posts_custom_column", 'bsi_schedule_admin_render_column', 10, 2);
  }
}

/**
 * @param array<string, string> $columns
 * @return array<string, string>
 */
function bsi_schedule_admin_add_column(array $columns): array
{
  $columns['bsi_schedule'] = 'Срок показа';
  return $columns;
}

function bsi_schedule_admin_render_column(string $column, int $post_id): void
{
  if ($column !== 'bsi_schedule') {
    return;
  }

  $post_type = (string) get_post_type($post_id);
  $keys = bsi_schedule_keys_for_post_type($post_type);
  $from = function_exists('get_field') ? get_field($keys['from'], $post_id) : '';
  $until = function_exists('get_field') ? get_field($keys['until'], $post_id) : '';

  if (!bsi_is_content_active($from, $until)) {
    echo '<span style="color:#b32d2e;">Скрыт (срок)</span>';
    return;
  }

  $parts = [];
  $from_fmt = function_exists('format_date_russian') ? format_date_russian($from) : $from;
  $until_fmt = function_exists('format_date_russian') ? format_date_russian($until) : $until;

  if ($from_fmt !== '') {
    $parts[] = 'с ' . esc_html($from_fmt);
  }
  if ($until_fmt !== '') {
    $parts[] = 'до ' . esc_html($until_fmt);
  }

  if ($parts === []) {
    echo '—';
    return;
  }

  echo esc_html(implode(' ', $parts));
}

add_action('acf/init', static function (): void {
  add_filter('acf/format_value/type=repeater', 'bsi_acf_format_repeater_hide_inactive_schedule', 20, 3);
  add_filter('acf/format_value/type=post_object', 'bsi_acf_format_post_object_hide_inactive_schedule', 20, 3);
  add_filter('acf/format_value/type=relationship', 'bsi_acf_format_relationship_hide_inactive_schedule', 20, 3);
});

function bsi_acf_is_schedule_format_context(): bool
{
  if (!is_admin()) {
    return true;
  }

  return false;
}

/**
 * @param mixed $value
 * @param int|string|false $post_id
 * @param array<string, mixed> $field
 */
function bsi_acf_format_repeater_hide_inactive_schedule($value, $post_id, array $field)
{
  unset($post_id, $field);
  if (!bsi_acf_is_schedule_format_context()) {
    return $value;
  }
  if (!is_array($value) || $value === []) {
    return $value;
  }

  $out = [];
  foreach ($value as $row) {
    if (!is_array($row)) {
      $out[] = $row;
      continue;
    }
    $has_keys = array_key_exists('bsi_active_from', $row) || array_key_exists('bsi_active_until', $row);
    if (!$has_keys) {
      $out[] = $row;
      continue;
    }
    if (!bsi_is_content_active($row['bsi_active_from'] ?? null, $row['bsi_active_until'] ?? null)) {
      continue;
    }
    $out[] = $row;
  }

  return array_values($out);
}

/**
 * Удалённый объект → null или false как ожидает ACF.
 *
 * @param mixed $post
 * @return mixed
 */
function bsi_acf_filter_single_post_reference($post)
{
  if ($post === null || $post === '' || $post === false) {
    return $post;
  }
  $id = null;
  $ptype = '';
  if ($post instanceof WP_Post) {
    $id = $post->ID;
    $ptype = $post->post_type;
  } elseif (is_numeric($post)) {
    $id = (int) $post;
    $ptype = (string) get_post_type($id);
  }

  if ($id === null || $id <= 0 || $ptype === '') {
    return $post;
  }

  if (!bsi_content_schedule_applies_to_post_type($ptype)) {
    return $post;
  }

  return bsi_post_is_schedule_active($id, $ptype) ? $post : null;
}

/**
 * @param mixed $value
 */
function bsi_acf_format_post_object_hide_inactive_schedule($value, $post_id, array $field)
{
  unset($post_id);

  if (!bsi_acf_is_schedule_format_context()) {
    return $value;
  }

  $multi = !empty($field['multiple']);

  if ($multi) {
    if (!is_array($value) || $value === []) {
      return $value;
    }
    $kept = [];
    foreach ($value as $item) {
      $f = bsi_acf_filter_single_post_reference($item);
      if ($f !== null && $f !== '') {
        $kept[] = $f;
      }
    }
    return array_values($kept);
  }

  $filtered = bsi_acf_filter_single_post_reference($value);

  return $filtered === null ? false : $filtered;
}

/**
 * @param mixed $value
 */
function bsi_acf_format_relationship_hide_inactive_schedule($value, $post_id, array $field)
{
  unset($post_id, $field);

  if (!bsi_acf_is_schedule_format_context()) {
    return $value;
  }
  if (!is_array($value) || $value === []) {
    return $value;
  }

  $kept = [];
  foreach ($value as $item) {
    $f = bsi_acf_filter_single_post_reference($item);
    if ($f !== null && $f !== '') {
      $kept[] = $f;
    }
  }

  return array_values($kept);
}
