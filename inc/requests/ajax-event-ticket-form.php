<?php

declare(strict_types=1);

/**
 * AJAX: заявка с формы событийного тура (модалка / блок на странице).
 */

$event_ticket_booking_email = 'dianov.js@gmail.com, a.hani@bsigroup.ru';

add_action('wp_ajax_event_ticket_booking', 'bsi_handle_event_ticket_booking');
add_action('wp_ajax_nopriv_event_ticket_booking', 'bsi_handle_event_ticket_booking');

function bsi_handle_event_ticket_booking(): void
{
  global $event_ticket_booking_email;

  $token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
  bsi_recaptcha_verify_or_die($token);

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

  $event_title = sanitize_text_field($_POST['event_title'] ?? '');
  $page_url = esc_url_raw($_POST['page_url'] ?? '');
  $accommodation = sanitize_text_field(trim($_POST['accommodation'] ?? ''));
  $booking_context = sanitize_text_field($_POST['booking_context'] ?? 'event');
  if ($booking_context !== 'promo') {
    $booking_context = 'event';
  }

  $subject_lead = ($booking_context === 'promo')
    ? 'Заявка по акции: '
    : 'Заявка по событию: ';
  $subject_suffix = $accommodation !== '' ? ' / ' . $accommodation : '';

  $result = BSI_Mailer::send([
    'to' => $event_ticket_booking_email,
    'subject' => $subject_lead . ($event_title !== '' ? $event_title : 'с сайта') . $subject_suffix,
    'template' => 'event-ticket-booking',
    'data' => [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'comment' => $comment,
      'event_title' => $event_title,
      'page_url' => $page_url,
      'accommodation' => $accommodation,
      'booking_context' => $booking_context,
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
