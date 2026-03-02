<?php

declare(strict_types=1);

/**
 * AJAX обработчик формы бронирования билета на событийный тур
 */

// Почта для заявок на билеты — сменить при необходимости
$event_ticket_booking_email = 'dianov.js@gmail.com';

add_action('wp_ajax_event_ticket_booking', 'bsi_handle_event_ticket_booking');
add_action('wp_ajax_nopriv_event_ticket_booking', 'bsi_handle_event_ticket_booking');

function bsi_handle_event_ticket_booking(): void
{
  global $event_ticket_booking_email;

  $token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
  bsi_recaptcha_verify_or_die($token);

  // Валидация контактных данных через BSI_Mailer
  $errors = BSI_Mailer::validate_contact_fields($_POST);

  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Пожалуйста, исправьте ошибки в форме',
      'errors' => $errors,
    ]);
  }

  // Санитизация данных
  $name = sanitize_text_field(trim($_POST['name'] ?? ''));
  $email = sanitize_email(trim($_POST['email'] ?? ''));
  $phone = sanitize_text_field(trim($_POST['phone'] ?? ''));
  $quantity = absint($_POST['quantity'] ?? 1);
  $comment = sanitize_textarea_field(trim($_POST['comment'] ?? ''));

  // Данные события и билета
  $event_title = sanitize_text_field($_POST['event_title'] ?? '');
  $event_venue = sanitize_text_field($_POST['event_venue'] ?? '');
  $event_time = sanitize_text_field($_POST['event_time'] ?? '');
  $ticket_type = sanitize_text_field($_POST['ticket_type'] ?? '');
  $ticket_price = absint($_POST['ticket_price'] ?? 0);
  $page_url = esc_url_raw($_POST['page_url'] ?? '');

  $total_price = $ticket_price * $quantity;

  // Отправка через BSI_Mailer
  $result = BSI_Mailer::send([
    'to' => $event_ticket_booking_email,
    'subject' => 'Заявка на билет: ' . $event_title,
    'template' => 'event-ticket-booking',
    'data' => [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'quantity' => $quantity,
      'comment' => $comment,
      'event_title' => $event_title,
      'event_venue' => $event_venue,
      'event_time' => $event_time,
      'ticket_type' => $ticket_type,
      'ticket_price' => $ticket_price,
      'total_price' => $total_price,
      'page_url' => $page_url,
    ],
    'reply_to' => $email,
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
