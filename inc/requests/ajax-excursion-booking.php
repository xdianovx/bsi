<?php

declare(strict_types=1);

/**
 * AJAX: заявка на экскурсию из модального окна (template-parts/excursion/booking-modal.php).
 */

$excursion_booking_email = 'doanov.js@gmail.com';

add_action('wp_ajax_excursion_booking', 'bsi_handle_excursion_booking');
add_action('wp_ajax_nopriv_excursion_booking', 'bsi_handle_excursion_booking');

function bsi_handle_excursion_booking(): void
{
  global $excursion_booking_email;

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

  $excursion_id = isset($_POST['excursion_id']) ? absint($_POST['excursion_id']) : 0;
  $excursion_title = sanitize_text_field($_POST['excursion_title'] ?? '');
  $excursion_date = sanitize_text_field($_POST['excursion_date'] ?? '');
  $page_url = esc_url_raw($_POST['page_url'] ?? '');

  $subject = 'Заявка на экскурсию: ' . ($excursion_title !== '' ? $excursion_title : 'с сайта');
  if ($excursion_date !== '') {
    $subject .= ' / ' . $excursion_date;
  }

  $result = BSI_Mailer::send([
    'to' => $excursion_booking_email,
    'subject' => $subject,
    'template' => 'event-ticket-booking',
    'data' => [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'comment' => $comment,
      'event_title' => $excursion_title,
      'page_url' => $page_url,
      'accommodation' => $excursion_date,
      'booking_context' => 'excursion',
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
