<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

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
  static $types = null;

  if ($types !== null) {
    return $types;
  }

  $exclude = array_flip(bsi_content_schedule_post_types_excluded());
  $types = [];

  foreach (get_post_types(['show_ui' => true], 'names') as $post_type) {
    if (!isset($exclude[$post_type])) {
      $types[] = $post_type;
    }
  }

  $types = apply_filters('bsi_content_schedule_post_types', $types);

  return $types;
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

  if (!empty($object->show_in_rest)) {
    return true;
  }

  return function_exists('use_block_editor_for_post_type')
    && use_block_editor_for_post_type($post_type);
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
  if (!empty($args['bsi_schedule_applied'])) {
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

add_action('pre_get_posts', 'bsi_content_schedule_pre_get_posts', 20);

function bsi_content_schedule_pre_get_posts(WP_Query $query): void
{
  if (is_admin() || $query->get('bsi_skip_schedule')) {
    return;
  }

  if ($query->get('bsi_schedule_applied')) {
    return;
  }

  $post_type = $query->get('post_type');
  if ($post_type === '' || $post_type === null) {
    if ($query->is_home() && !$query->is_front_page()) {
      $post_type = 'post';
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

  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  nocache_headers();
}

add_action('init', 'bsi_content_schedule_register_admin_columns');

function bsi_content_schedule_register_admin_columns(): void
{
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
