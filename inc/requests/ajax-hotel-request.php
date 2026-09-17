<?php

declare(strict_types=1);

/**
 * AJAX: заявка на отель со страницы отеля (template-parts/hotel-page/request.php).
 *
 * Онлайн-брони у нас нет: когда у тарифа нет ссылки на бронирование, клиент
 * оставляет контакты, а менеджер получает письмо с выбранным номером, датами,
 * питанием и ценой.
 */

/** Получатели заявки. Список через запятую — wp_mail шлёт всем. */
const BSI_HOTEL_REQUEST_EMAIL = 'dianov.js@gmail.com, o.ser@bsigroup.ru';

add_action('wp_ajax_hotel_request', 'bsi_handle_hotel_request');
add_action('wp_ajax_nopriv_hotel_request', 'bsi_handle_hotel_request');

function bsi_handle_hotel_request(): void
{
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

  $hotel_title = sanitize_text_field($_POST['hotel_title'] ?? '');
  $page_url = esc_url_raw($_POST['page_url'] ?? '');

  // Контекст выбранного тарифа приходит с клиента и в письмо идёт как справка.
  $offer_parts = array_filter([
    sanitize_text_field($_POST['room_name'] ?? ''),
    sanitize_text_field($_POST['offer_meal'] ?? ''),
    sanitize_text_field($_POST['offer_dates'] ?? ''),
  ]);

  $subject = 'Заявка на отель: ' . ($hotel_title !== '' ? $hotel_title : 'с сайта');

  $result = BSI_Mailer::send([
    'to' => BSI_HOTEL_REQUEST_EMAIL,
    'subject' => $subject,
    'template' => 'event-ticket-booking',
    'data' => [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'comment' => $comment,
      'event_title' => trim($hotel_title . ($offer_parts ? ' — ' . implode(', ', $offer_parts) : '')),
      'page_url' => $page_url,
      'price_line' => sanitize_text_field($_POST['offer_price'] ?? ''),
      'booking_context' => 'hotel',
    ],
    'reply_to' => is_email($email) ? $email : '',
  ]);

  if ($result['success']) {
    wp_send_json_success(['message' => $result['message']]);
  }

  wp_send_json_error(['message' => $result['message']]);
}
