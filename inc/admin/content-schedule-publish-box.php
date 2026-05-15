<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

add_action('post_submitbox_misc_actions', 'bsi_content_schedule_render_publish_box');

function bsi_content_schedule_render_publish_box(): void
{
  global $post;

  if (
    !function_exists('bsi_content_schedule_enabled')
    || !bsi_content_schedule_enabled()
  ) {
    return;
  }

  if (!($post instanceof WP_Post) || !bsi_content_schedule_applies_to_post_type($post->post_type)) {
    return;
  }

  bsi_content_schedule_render_publish_fields($post);
}

/** Поля только в блоке «Опубликовать» (classic). Для блок-редактора — см. content-schedule-panel.js. */
function bsi_content_schedule_render_publish_fields(WP_Post $post): void
{
  $keys = bsi_schedule_keys_for_post_type($post->post_type);
  $meta = bsi_schedule_get_post_meta((int) $post->ID, $post->post_type);
  $from_input = bsi_schedule_ymd_to_date_input($meta['from']);
  $until_input = bsi_schedule_ymd_to_date_input($meta['until']);

  wp_nonce_field('bsi_content_schedule_save', 'bsi_content_schedule_nonce');
  ?>
  <div class="misc-pub-section bsi-content-schedule-pub" id="bsi-content-schedule-fields">
    <div class="bsi-content-schedule-pub__row">
      <label class="bsi-content-schedule-pub__label" for="bsi-schedule-from-publish">
        <?php esc_html_e('Показывать с:', 'bsi'); ?>
      </label>
      <input
        type="date"
        name="<?php echo esc_attr($keys['from']); ?>"
        id="bsi-schedule-from-publish"
        value="<?php echo esc_attr($from_input); ?>"
        class="bsi-content-schedule-pub__input"
      />
    </div>
    <div class="bsi-content-schedule-pub__row">
      <label class="bsi-content-schedule-pub__label" for="bsi-schedule-until-publish">
        <?php esc_html_e('Показывать до:', 'bsi'); ?>
      </label>
      <input
        type="date"
        name="<?php echo esc_attr($keys['until']); ?>"
        id="bsi-schedule-until-publish"
        value="<?php echo esc_attr($until_input); ?>"
        class="bsi-content-schedule-pub__input"
      />
    </div>
    <p class="bsi-content-schedule-pub__hint">
      <?php esc_html_e('На сайте скрыто вне диапазона. Статус записи не меняется.', 'bsi'); ?>
    </p>
  </div>
  <?php
}

add_action('save_post', 'bsi_content_schedule_save_publish_box', 10, 2);

function bsi_content_schedule_save_publish_box(int $post_id, WP_Post $post): void
{
  if (
    !function_exists('bsi_content_schedule_enabled')
    || !bsi_content_schedule_enabled()
  ) {
    return;
  }

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
    return;
  }

  if (!bsi_content_schedule_applies_to_post_type($post->post_type)) {
    return;
  }

  if (
    !isset($_POST['bsi_content_schedule_nonce'])
    || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bsi_content_schedule_nonce'])), 'bsi_content_schedule_save')
  ) {
    return;
  }

  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  $keys = bsi_schedule_keys_for_post_type($post->post_type);
  $from_raw = isset($_POST[$keys['from']]) ? sanitize_text_field(wp_unslash($_POST[$keys['from']])) : '';
  $until_raw = isset($_POST[$keys['until']]) ? sanitize_text_field(wp_unslash($_POST[$keys['until']])) : '';

  bsi_schedule_save_post_meta(
    $post_id,
    bsi_schedule_date_input_to_ymd($from_raw),
    bsi_schedule_date_input_to_ymd($until_raw),
    $post->post_type
  );
}

add_action('admin_enqueue_scripts', 'bsi_content_schedule_publish_box_assets');

function bsi_content_schedule_publish_box_assets(string $hook): void
{
  if (
    !function_exists('bsi_content_schedule_enabled')
    || !bsi_content_schedule_enabled()
  ) {
    return;
  }

  if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
    return;
  }

  $screen = get_current_screen();
  if (!$screen || !bsi_content_schedule_applies_to_post_type($screen->post_type)) {
    return;
  }

  wp_add_inline_style('wp-admin', '
    .bsi-content-schedule-pub { border-top: 1px solid #dcdcde; padding-top: 8px; margin-top: 8px; }
    .bsi-content-schedule-pub__row { display: flex; align-items: center; gap: 8px; margin: 0 0 6px; }
    .bsi-content-schedule-pub__label { flex: 0 0 auto; font-weight: 600; }
    .bsi-content-schedule-pub__input { flex: 1 1 auto; max-width: 100%; }
    .bsi-content-schedule-pub__hint { margin: 4px 0 0; color: #646970; font-size: 12px; line-height: 1.4; }
  ');
}
