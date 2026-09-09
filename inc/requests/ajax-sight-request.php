<?php

declare(strict_types=1);

/**
 * AJAX: заявка «Хочу сюда» со страницы достопримечательности
 * (template-parts/sight/request-modal.php).
 */

$sight_request_email = 'doanov.js@gmail.com';

add_action('wp_ajax_sight_request', 'bsi_handle_sight_request');
add_action('wp_ajax_nopriv_sight_request', 'bsi_handle_sight_request');

function bsi_handle_sight_request(): void
{
  global $sight_request_email;

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

  $sight_title = sanitize_text_field($_POST['sight_title'] ?? '');
  $sight_country = sanitize_text_field($_POST['sight_country'] ?? '');
  $page_url = esc_url_raw($_POST['page_url'] ?? '');

  $subject = 'Хочу сюда: ' . ($sight_title !== '' ? $sight_title : 'заявка с сайта');
  if ($sight_country !== '') {
    $subject .= ' / ' . $sight_country;
  }

  $result = BSI_Mailer::send([
    'to' => $sight_request_email,
    'subject' => $subject,
    'template' => 'event-ticket-booking',
    'data' => [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'comment' => $comment,
      'event_title' => $sight_title,
      'page_url' => $page_url,
      'accommodation' => $sight_country,
      'booking_context' => 'sight',
    ],
    'reply_to' => is_email($email) ? $email : '',
  ]);

  if ($result['success']) {
    wp_send_json_success([
      'message' => $result['message'],
    ]);
  } else {
    wp_send_json_error([
      'message' => $result['message'],
    ]);
  }
}
