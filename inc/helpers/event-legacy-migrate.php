<?php
/**
 * Разовый перенос скрытых старых полей события в новые (#135, #138).
 *
 * Старые поля скрыты в админке (bsi_event_hidden_field_keys()), но копируются вместе с постом
 * и подставлялись на сайте, когда новое поле пустое — удалить такой текст было нельзя.
 * Перенос пишет только в ПУСТЫЕ новые поля; старые поля не удаляются (остаются резервом).
 * После переноса подстановки выключаются (bsi_event_legacy_migrated()).
 *
 *   tour_included        (WYSIWYG) → tour_included_items
 *   tour_not_included    (WYSIWYG) → tour_extra_items
 *   venue_scheme_legend  (легенда) → event_sectors
 */

if (!defined('ABSPATH')) {
  exit;
}

const BSI_EVENT_LEGACY_MIGRATED_OPTION = 'bsi_event_legacy_migrated_v1';

function bsi_event_legacy_migrated(): bool
{
  return (bool) get_option(BSI_EVENT_LEGACY_MIGRATED_OPTION);
}

/** Есть ли в списке пункт с текстом — так же считает сайт (bsi_event_page_data). */
function bsi_event_legacy_has_items(int $post_id, string $name): bool
{
  foreach ((array) get_field($name, $post_id) as $row) {
    if (trim((string) ($row['text'] ?? '')) !== '') {
      return true;
    }
  }

  return false;
}

/**
 * Переносит старые поля одного события в пустые новые.
 *
 * @return string[] что перенесено: included / extra / sectors
 */
function bsi_event_migrate_legacy_fields(int $post_id): array
{
  $done = [];

  if (!bsi_event_legacy_has_items($post_id, 'tour_included_items')) {
    $rows = [];
    foreach (bsi_event_items_from_html((string) get_field('tour_included', $post_id)) as $it) {
      $rows[] = ['field_event_included_item_text' => $it['text'], 'field_event_included_item_note' => $it['note']];
    }
    if ($rows && update_field('field_event_included_items', $rows, $post_id)) {
      $done[] = 'included';
    }
  }

  if (!bsi_event_legacy_has_items($post_id, 'tour_extra_items')) {
    $rows = [];
    foreach (bsi_event_items_from_html((string) get_field('tour_not_included', $post_id)) as $it) {
      $rows[] = [
        'field_event_extra_item_text' => $it['text'],
        'field_event_extra_item_note' => $it['note'],
        'field_event_extra_item_price_mode' => 'none',
      ];
    }
    if ($rows && update_field('field_event_extra_items', $rows, $post_id)) {
      $done[] = 'extra';
    }
  }

  if ((int) get_post_meta($post_id, 'event_sectors', true) === 0) {
    $rows = bsi_event_sectors_from_legend($post_id);
    if ($rows && update_field('field_event_sectors', $rows, $post_id)) {
      $done[] = 'sectors';
    }
  }

  return $done;
}

/** Переносит все события (черновики и корзину тоже — их могут восстановить) и выключает подстановки. Повторный запуск ничего не перезапишет. */
function bsi_event_migrate_legacy_all(): array
{
  $ids = get_posts([
    'post_type' => 'event',
    'post_status' => array_keys(get_post_stati()),
    'posts_per_page' => -1,
    'fields' => 'ids',
    'suppress_filters' => true,
  ]);
  $report = [];
  foreach ($ids as $id) {
    $done = bsi_event_migrate_legacy_fields((int) $id);
    if ($done) {
      $report[(int) $id] = $done;
    }
  }
  update_option(BSI_EVENT_LEGACY_MIGRATED_OPTION, ['time' => time(), 'events' => count($ids), 'report' => $report], false);

  return $report;
}

// Однократно при первом заходе администратора в админку после выкладки.
add_action('admin_init', static function () {
  if (!current_user_can('manage_options') || bsi_event_legacy_migrated()) {
    return;
  }
  bsi_event_migrate_legacy_all();
}, 30);
