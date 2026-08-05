<?php

declare(strict_types=1);

/**
 * AJAX: заявка на тур из модального окна (template-parts/tour/booking-modal.php).
 *
 * Модалка показывается только для туров без ссылки на Самотур (`tour_booking_url` пуст) —
 * забронировать онлайн нельзя, поэтому собираем контакты и шлём менеджеру письмом.
 */

const BSI_TOUR_BOOKING_EMAIL = 'dianov.js@gmail.com';

add_action('wp_ajax_tour_booking', 'bsi_handle_tour_booking');
add_action('wp_ajax_nopriv_tour_booking', 'bsi_handle_tour_booking');

function bsi_handle_tour_booking(): void
{
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

  $tour_id = isset($_POST['tour_id']) ? absint($_POST['tour_id']) : 0;
  $tour_title = sanitize_text_field($_POST['tour_title'] ?? '');
  $tour_price = sanitize_text_field($_POST['tour_price'] ?? '');
  $page_url = esc_url_raw($_POST['page_url'] ?? '');

  // Заголовок берём из БД, а не из POST — клиентское значение может быть подменено.
  if ($tour_id > 0 && get_post_type($tour_id) === 'tour') {
    $tour_title = get_the_title($tour_id);
    if ($page_url === '') {
      $page_url = (string) get_permalink($tour_id);
    }
  }

  $subject = 'Заявка на тур: ' . ($tour_title !== '' ? $tour_title : 'с сайта');

  $result = BSI_Mailer::send([
    'to' => BSI_TOUR_BOOKING_EMAIL,
    'subject' => $subject,
    'template' => 'event-ticket-booking',
    'data' => [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'comment' => $comment,
      'event_title' => $tour_title,
      'page_url' => $page_url,
      'price_line' => $tour_price,
      'booking_context' => 'tour',
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
