<?php

declare(strict_types=1);

/**
 * Cookie consent: логирование выбора (IP, время), nonce для AJAX.
 */

defined('ABSPATH') || exit;

/**
 * Создаёт таблицу логов при необходимости.
 */
function bsi_cookie_consent_maybe_install_table(): void
{
  global $wpdb;

  if (get_option('bsi_cookie_consent_db_ver') === '1') {
    return;
  }

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $table = $wpdb->prefix . 'bsi_cookie_consent_log';
  $charset_collate = $wpdb->get_charset_collate();

  dbDelta(
    "CREATE TABLE {$table} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      consent varchar(20) NOT NULL,
      ip varchar(45) NOT NULL DEFAULT '',
      user_agent varchar(500) NOT NULL DEFAULT '',
      created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
      PRIMARY KEY  (id),
      KEY consent (consent),
      KEY created_at (created_at)
    ) {$charset_collate};"
  );

  update_option('bsi_cookie_consent_db_ver', '1', true);
}

add_action('init', 'bsi_cookie_consent_maybe_install_table', 5);

/**
 * Ранний скрипт: не мигать баннером при повторном визите (localStorage уже есть).
 */
function bsi_cookie_consent_head_script(): void
{
  if (is_admin()) {
    return;
  }
  ?>
  <script>
    (function () {
      try {
        if (localStorage.getItem('bsi_cookie_consent')) {
          document.documentElement.classList.add('bsi-cookie-consented');
        }
      } catch (e) {}
    })();
  </script>
  <?php
}

add_action('wp_head', 'bsi_cookie_consent_head_script', 0);

/**
 * Локализация для фронта.
 */
function bsi_cookie_consent_localize_script(): void
{
  if (!function_exists('bsi_get_privacy_policy_url')) {
    return;
  }

  wp_localize_script('main', 'bsiCookieConsent', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('bsi_cookie_consent'),
    'policyUrl' => bsi_get_privacy_policy_url(),
  ]);
}

add_action('wp_enqueue_scripts', 'bsi_cookie_consent_localize_script', 30);

/**
 * AJAX: зафиксировать согласие.
 */
function bsi_ajax_log_cookie_consent(): void
{
  check_ajax_referer('bsi_cookie_consent', 'nonce');

  $choice = isset($_POST['consent']) ? sanitize_text_field(wp_unslash($_POST['consent'])) : '';
  if (!in_array($choice, ['all', 'necessary', 'accept'], true)) {
    wp_send_json_error(['message' => 'Invalid consent'], 400);
  }

  global $wpdb;
  bsi_cookie_consent_maybe_install_table();
  $table = $wpdb->prefix . 'bsi_cookie_consent_log';

  $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';
  if ($ip !== '' && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $xff = sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_X_FORWARDED_FOR']));
    $first = trim(explode(',', $xff)[0]);
    if ($first !== '') {
      $ip = $first;
    }
  }

  $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '';
  if (strlen($ua) > 500) {
    $ua = substr($ua, 0, 500);
  }

  $wpdb->insert(
    $table,
    [
      'consent' => $choice,
      'ip' => $ip,
      'user_agent' => $ua,
      'created_at' => current_time('mysql'),
    ],
    ['%s', '%s', '%s', '%s']
  );

  wp_send_json_success();
}

add_action('wp_ajax_bsi_log_cookie_consent', 'bsi_ajax_log_cookie_consent');
add_action('wp_ajax_nopriv_bsi_log_cookie_consent', 'bsi_ajax_log_cookie_consent');
