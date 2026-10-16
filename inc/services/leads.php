<?php

/**
 * Журнал заявок с сайта (основа будущей CRM).
 *
 * Каждая отправка формы пишется строкой в {prefix}bsi_leads — независимо от того,
 * ушло ли письмо. Читать журнал можно только в админке с правом manage_options:
 * ни REST, ни admin-ajax для чтения нет.
 *
 * Контакты (name/phone/email/payload) по сроку обезличиваются кроном, если задан
 * срок в опции bsi_leads_pd_retention_days (0 — не обезличивать). Дата, форма,
 * страница и UTM остаются навсегда — на них строится статистика.
 */

defined('ABSPATH') || exit;

function bsi_leads_table_name(): string
{
  global $wpdb;

  return $wpdb->prefix . 'bsi_leads';
}

function bsi_leads_schema_version(): string
{
  return '1.0.0';
}

function bsi_leads_install_table(): void
{
  global $wpdb;

  $table_name = bsi_leads_table_name();
  $charset_collate = $wpdb->get_charset_collate();

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $sql = "CREATE TABLE {$table_name} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		created_at DATETIME NOT NULL,
		form VARCHAR(40) NOT NULL DEFAULT '',
		section VARCHAR(40) NOT NULL DEFAULT '',
		post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		title VARCHAR(255) NOT NULL DEFAULT '',
		page_url VARCHAR(500) NOT NULL DEFAULT '',
		utm_source VARCHAR(100) NOT NULL DEFAULT '',
		utm_medium VARCHAR(100) NOT NULL DEFAULT '',
		utm_campaign VARCHAR(150) NOT NULL DEFAULT '',
		name VARCHAR(200) NULL,
		phone VARCHAR(50) NULL,
		email VARCHAR(200) NULL,
		payload LONGTEXT NULL,
		mail_sent TINYINT(1) NOT NULL DEFAULT 0,
		status VARCHAR(20) NOT NULL DEFAULT 'new',
		manager_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		anonymized_at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY created_at (created_at),
		KEY form (form),
		KEY section (section),
		KEY status (status)
	) {$charset_collate};";

  dbDelta($sql);
  update_option('bsi_leads_schema_version', bsi_leads_schema_version(), false);
}

function bsi_leads_maybe_install_table(): void
{
  if (get_option('bsi_leads_schema_version') === bsi_leads_schema_version()) {
    return;
  }

  bsi_leads_install_table();
}

add_action('after_switch_theme', 'bsi_leads_install_table');
add_action('admin_init', 'bsi_leads_maybe_install_table');

/**
 * Названия форм для админки. Ключ — значение колонки form.
 */
function bsi_leads_form_labels(): array
{
  return [
    'tour' => 'Тур',
    'event' => 'Событие',
    'promo' => 'Акция',
    'hotel' => 'Отель',
    'excursion' => 'Экскурсия',
    'sight' => 'Достопримечательность',
    'resort' => 'Курорт',
    'education' => 'Образование',
    'vacancy' => 'Вакансия',
    'visa' => 'Виза',
    'visa_single' => 'Виза (страница визы)',
    'insurance' => 'Страхование',
    'fit' => 'FIT',
    'mice' => 'MICE',
    'agency_event' => 'Регистрация агентства',
    'other' => 'Другое',
  ];
}

/**
 * Записать заявку. Никогда не роняет обработчик формы: любая ошибка — только в error_log.
 *
 * @param string $form      Ключ формы из bsi_leads_form_labels().
 * @param array  $data      Поля заявки. name/phone/email/page_url/title вынимаются в колонки,
 *                          остальное уходит в payload (JSON).
 * @param bool   $mail_sent Ушло ли письмо менеджерам.
 */
function bsi_lead_log(string $form, array $data, bool $mail_sent): void
{
  try {
    global $wpdb;

    if (get_option('bsi_leads_schema_version') !== bsi_leads_schema_version()) {
      bsi_leads_install_table();
    }

    $take = static function (array $keys) use (&$data): string {
      foreach ($keys as $key) {
        if (isset($data[$key]) && is_scalar($data[$key]) && trim((string) $data[$key]) !== '') {
          $value = trim((string) $data[$key]);
          unset($data[$key]);
          return $value;
        }
      }
      return '';
    };

    $name = $take(['name', 'fio', 'full_name']);
    $phone = $take(['phone', 'tel']);
    $email = $take(['email']);
    $page_url = $take(['page_url', 'url']);
    $title = $take(['event_title', 'program_title', 'vacancy_title', 'title', 'page_title']);

    if ($page_url === '') {
      $page_url = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash((string) $_SERVER['HTTP_REFERER'])) : '';
    }

    $post_id = $page_url !== '' ? (int) url_to_postid($page_url) : 0;
    $section = $post_id ? (string) get_post_type($post_id) : '';
    if ($title === '' && $post_id) {
      $title = get_the_title($post_id);
    }

    $utm = [];
    $query = (string) wp_parse_url($page_url, PHP_URL_QUERY);
    if ($query !== '') {
      parse_str($query, $utm);
    }

    $payload = array_filter($data, static function ($value) {
      return $value !== '' && $value !== null && $value !== [];
    });

    $wpdb->insert(
      bsi_leads_table_name(),
      [
        'created_at' => current_time('mysql'),
        'form' => substr(sanitize_key($form), 0, 40),
        'section' => substr($section, 0, 40),
        'post_id' => $post_id,
        'title' => mb_substr(wp_strip_all_tags($title), 0, 255),
        'page_url' => substr($page_url, 0, 500),
        'utm_source' => mb_substr(sanitize_text_field((string) ($utm['utm_source'] ?? '')), 0, 100),
        'utm_medium' => mb_substr(sanitize_text_field((string) ($utm['utm_medium'] ?? '')), 0, 100),
        'utm_campaign' => mb_substr(sanitize_text_field((string) ($utm['utm_campaign'] ?? '')), 0, 150),
        'name' => $name !== '' ? mb_substr($name, 0, 200) : null,
        'phone' => $phone !== '' ? mb_substr($phone, 0, 50) : null,
        'email' => $email !== '' ? mb_substr($email, 0, 200) : null,
        'payload' => $payload ? wp_json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        'mail_sent' => $mail_sent ? 1 : 0,
      ]
    );
  } catch (\Throwable $e) {
    error_log('bsi_lead_log: ' . $e->getMessage());
  }
}

/**
 * Для форм, которые шлют письмо через wp_mail напрямую: берём поля из $_POST,
 * без служебных (action, nonce, токены капчи, согласие, honeypot).
 */
function bsi_lead_log_from_post(string $form, bool $mail_sent, array $extra = []): void
{
  $skip = ['action', 'nonce', '_wpnonce', '_wp_http_referer', 'privacy_agreement', 'recaptcha_token', 'g-recaptcha-response', 'token', 'website', 'hp', 'honeypot'];

  $data = [];
  foreach (wp_unslash($_POST) as $key => $value) {
    $key = (string) $key;
    if (in_array($key, $skip, true) || str_contains($key, 'nonce') || str_contains($key, 'recaptcha')) {
      continue;
    }
    $data[$key] = is_array($value)
      ? array_map('sanitize_text_field', array_filter($value, 'is_scalar'))
      : sanitize_textarea_field((string) $value);
  }

  bsi_lead_log($form, array_merge($data, $extra), $mail_sent);
}

/* Обезличивание по сроку: контакты и payload стираются, статистика остаётся. */
add_action('bsi_leads_anonymize', function () {
  global $wpdb;

  $days = (int) get_option('bsi_leads_pd_retention_days', 0);
  if ($days <= 0) {
    return;
  }

  $wpdb->query($wpdb->prepare(
    'UPDATE ' . bsi_leads_table_name() . ' SET name = NULL, phone = NULL, email = NULL, payload = NULL, anonymized_at = %s WHERE anonymized_at IS NULL AND created_at < %s',
    current_time('mysql'),
    wp_date('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS)
  ));
});

add_action('init', function () {
  if (!wp_next_scheduled('bsi_leads_anonymize')) {
    wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'bsi_leads_anonymize');
  }
});
