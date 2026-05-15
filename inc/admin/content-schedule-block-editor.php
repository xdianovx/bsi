<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

add_action('init', 'bsi_content_schedule_register_rest_meta', 20);

function bsi_content_schedule_register_rest_meta(): void
{
  if (
    !function_exists('bsi_content_schedule_enabled')
    || !bsi_content_schedule_enabled()
  ) {
    return;
  }

  foreach (bsi_content_schedule_post_types() as $post_type) {
    if (!bsi_content_schedule_supports_block_editor($post_type)) {
      continue;
    }

    $keys = bsi_schedule_keys_for_post_type($post_type);

    foreach ($keys as $meta_key) {
      register_post_meta($post_type, $meta_key, [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'default' => '',
        'auth_callback' => static function (bool $allowed, string $meta_key_arg, int $post_id): bool {
          unset($meta_key_arg);
          return current_user_can('edit_post', $post_id);
        },
        'sanitize_callback' => 'bsi_schedule_sanitize_meta_value',
      ]);
    }
  }
}

add_action('enqueue_block_editor_assets', 'bsi_content_schedule_enqueue_block_editor_assets');

function bsi_content_schedule_enqueue_block_editor_assets(): void
{
  if (
    !function_exists('bsi_content_schedule_enabled')
    || !bsi_content_schedule_enabled()
  ) {
    return;
  }

  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (
    !$screen
    || $screen->base !== 'post'
    || !bsi_content_schedule_applies_to_post_type($screen->post_type)
    || !bsi_content_schedule_supports_block_editor($screen->post_type)
  ) {
    return;
  }

  $script_path = get_template_directory() . '/js/admin/content-schedule-panel.js';
  if (!is_readable($script_path)) {
    return;
  }

  wp_enqueue_script(
    'bsi-content-schedule-panel',
    get_template_directory_uri() . '/js/admin/content-schedule-panel.js',
    ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n'],
    (string) filemtime($script_path),
    true
  );

  wp_localize_script('bsi-content-schedule-panel', 'bsiContentSchedulePanel', [
    'postTypes' => bsi_content_schedule_post_types(),
    'keysByType' => bsi_schedule_keys_map_for_js(),
    'hint' => __('На сайте скрыто вне диапазона. Статус записи не меняется.', 'bsi'),
  ]);

  wp_add_inline_style('wp-edit-blocks', '
    .bsi-content-schedule-post-status { margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd; }
    .bsi-content-schedule-post-status__heading { margin: 0 0 10px; font-size: 13px; font-weight: 600; }
    .bsi-content-schedule-post-status .components-base-control { margin-bottom: 10px; }
    .bsi-content-schedule-panel__hint { margin: 0; color: #646970; font-size: 12px; line-height: 1.4; }
  ');
}
