<?php

declare(strict_types=1);

/**
 * AJAX: заявка с формы событийного тура (модалка / блок на странице).
 */

$event_ticket_booking_email = 'dianov.js@gmail.com, o.ser@bsigroup.ru';

add_action('wp_ajax_event_ticket_booking', 'bsi_handle_event_ticket_booking');
add_action('wp_ajax_nopriv_event_ticket_booking', 'bsi_handle_event_ticket_booking');

function bsi_handle_event_ticket_booking(): void
{
  global $event_ticket_booking_email;

  $token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
  bsi_recaptcha_verify_or_die($token);

  // Минимальные формы (CTA под каталогом и на странице события) поля email не содержат.
  $is_minimal = ($_POST['event_booking_minimal'] ?? '') === '1';

  $errors = BSI_Mailer::validate_contact_fields($_POST, ['require_email' => !$is_minimal]);

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
  $event_date = sanitize_text_field(trim($_POST['event_date'] ?? ''));
  $event_venue = sanitize_text_field(trim($_POST['event_venue'] ?? ''));
  $event_time = sanitize_text_field(trim($_POST['event_time'] ?? ''));
  $event_price = sanitize_text_field(trim($_POST['event_price'] ?? ''));
  $event_details = sanitize_text_field(trim($_POST['event_details'] ?? ''));
  // Формат заявки со страницы события: билет / тур с другим сектором / «сообщить о новых датах».
  $booking_kind = sanitize_key($_POST['booking_kind'] ?? '');
  if (!in_array($booking_kind, ['ticket', 'tour_sector', 'notify', 'consult'], true)) {
    $booking_kind = '';
  }
  $sector = sanitize_text_field(trim($_POST['sector'] ?? ''));
  $quantity = max(0, min(50, (int) ($_POST['quantity'] ?? 0)));
  if (!in_array($booking_kind, ['ticket', 'tour_sector'], true)) {
    // Поля сектора/количества в модалке есть всегда (скрыты) — в обычной заявке их не учитываем.
    $sector = '';
    $quantity = 0;
  }
  $booking_context = sanitize_text_field($_POST['booking_context'] ?? 'event');
  if ($booking_context !== 'promo') {
    $booking_context = 'event';
  }

  $kind_leads = [
    'ticket' => 'Билеты: ',
    'tour_sector' => 'Тур с другим сектором: ',
    'notify' => 'Сообщить о новых датах: ',
    'consult' => 'Консультация по событию: ',
  ];
  $subject_lead = ($booking_context === 'promo')
    ? 'Заявка по акции: '
    : ($kind_leads[$booking_kind] ?? 'Заявка по событию: ');
  $subject_suffix = $accommodation !== '' ? ' / ' . $accommodation : '';
  if ($booking_kind !== '') {
    $subject_suffix = implode(' · ', array_filter([
      $event_date,
      $sector !== '' ? $sector . ($quantity > 0 ? ' × ' . $quantity : '') : '',
    ]));
    $subject_suffix = $subject_suffix !== '' ? ' · ' . $subject_suffix : '';
  }

  // Доп. получатель из поля события (event_notify_email) — дублируем письмо, основные адреса не трогаем.
  $recipient_email = $event_ticket_booking_email;
  $event_id = (int) ($_POST['event_id'] ?? 0);
  if ($event_id && get_post_type($event_id) === 'event' && function_exists('get_field')) {
    // Одна копия на событие для всех видов заявок (14.10: билеты и тур ведёт один человек).
    // event_tickets_email убран из админки; если успели заполнить — тоже копией, не вместо общих адресов.
    foreach (['event_notify_email', 'event_tickets_email'] as $key) {
      $extra_email = sanitize_email(trim((string) get_field($key, $event_id)));
      if (is_email($extra_email) && stripos($recipient_email, $extra_email) === false) {
        $recipient_email .= ', ' . $extra_email;
      }
    }
  }

  $result = BSI_Mailer::send([
    'to' => $recipient_email,
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
      'event_date' => $event_date,
      'event_venue' => $event_venue,
      'event_time' => $event_time,
      'event_price' => $event_price,
      'event_details' => $event_details,
      'booking_context' => $booking_context,
      'booking_kind' => $booking_kind,
      'sector' => $sector,
      'quantity' => $quantity,
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
