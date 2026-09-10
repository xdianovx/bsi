<?php

declare(strict_types=1);

/**
 * AJAX: заявка «Подобрать поездку» со страницы курорта
 * (template-parts/resort/request-modal.php).
 *
 * В письмо кладём курорт, страну, раздел и адрес страницы: по ним видно,
 * с какого экрана пришла заявка. Те же значения уходят параметрами цели
 * в Яндекс.Метрику — см. js/modules/forms/resort-request-form.js.
 */

$resort_request_email = 'doanov.js@gmail.com';

add_action('wp_ajax_resort_request', 'bsi_handle_resort_request');
add_action('wp_ajax_nopriv_resort_request', 'bsi_handle_resort_request');

function bsi_handle_resort_request(): void
{
  global $resort_request_email;

  $token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
  if (function_exists('bsi_recaptcha_verify_or_die')) {
    bsi_recaptcha_verify_or_die($token);
  }

  $errors = BSI_Mailer::validate_contact_fields($_POST, ['require_email' => false]);

  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Пожалуйста, исправьте ошибки в форме',
      'errors' => $errors,
    ]);
  }

  $name = sanitize_text_field(trim($_POST['name'] ?? ''));
  $email = sanitize_email(trim($_POST['email'] ?? ''));
  $phone = sanitize_text_field(trim($_POST['phone'] ?? ''));
  $comment = sanitize_textarea_field(trim($_POST['comment'] ?? ''));

  $resort_name = sanitize_text_field($_POST['resort_name'] ?? '');
  $resort_country = sanitize_text_field($_POST['resort_country'] ?? '');
  $resort_section = sanitize_text_field($_POST['resort_section'] ?? '');
  $page_url = esc_url_raw($_POST['page_url'] ?? '');
  $referrer = esc_url_raw($_POST['referrer'] ?? '');

  $subject = 'Подбор поездки: ' . ($resort_name !== '' ? $resort_name : 'заявка с сайта');
  if ($resort_country !== '') {
    $subject .= ' / ' . $resort_country;
  }

  /* Раздел и переход — в комментарий: отдельных полей под них в шаблоне письма нет */
  $context_lines = [];
  if ($resort_section !== '') {
    $context_lines[] = 'Раздел: ' . $resort_section;
  }
  if ($referrer !== '') {
    $context_lines[] = 'Переход с: ' . $referrer;
  }

  if (!empty($context_lines)) {
    $comment = trim($comment . "\n\n" . implode("\n", $context_lines));
  }

  $result = BSI_Mailer::send([
    'to' => $resort_request_email,
    'subject' => $subject,
    'template' => 'event-ticket-booking',
    'data' => [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'comment' => $comment,
      'event_title' => $resort_name,
      'page_url' => $page_url,
      'accommodation' => $resort_country,
      'booking_context' => 'resort',
    ],
    'reply_to' => is_email($email) ? $email : '',
  ]);

  if ($result['success']) {
    wp_send_json_success(['message' => $result['message']]);
  } else {
    wp_send_json_error(['message' => $result['message']]);
  }
}
