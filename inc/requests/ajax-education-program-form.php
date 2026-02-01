<?php

declare(strict_types=1);

/**
 * AJAX обработчик формы бронирования образовательной программы
 */

add_action('wp_ajax_education_program_booking', 'bsi_handle_education_program_booking');
add_action('wp_ajax_nopriv_education_program_booking', 'bsi_handle_education_program_booking');

function bsi_handle_education_program_booking(): void
{
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
  $checkin_date = sanitize_text_field(trim($_POST['checkin_date'] ?? ''));
  $comment = sanitize_textarea_field(trim($_POST['comment'] ?? ''));

  // Данные программы
  $program_title = sanitize_text_field($_POST['program_title'] ?? '');
  $program_date = sanitize_text_field($_POST['program_date'] ?? '');
  $program_price = absint($_POST['program_price'] ?? 0);
  $total_price = absint($_POST['total_price'] ?? 0);
  $school_name = sanitize_text_field($_POST['school_name'] ?? '');
  $page_url = esc_url_raw($_POST['page_url'] ?? '');

  // Выбранные услуги
  $selected_services = [];
  if (!empty($_POST['selected_services'])) {
    $services_json = stripslashes($_POST['selected_services']);
    $decoded = json_decode($services_json, true);
    if (is_array($decoded)) {
      $selected_services = array_map(function ($service) {
        return [
          'title' => sanitize_text_field($service['title'] ?? ''),
          'price' => absint($service['price'] ?? 0),
        ];
      }, $decoded);
    }
  }

  // Отправка через BSI_Mailer
  $result = BSI_Mailer::send([
    'subject' => 'Заявка на бронирование программы: ' . $program_title,
    'template' => 'education-booking',
    'data' => [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'checkin_date' => $checkin_date,
      'comment' => $comment,
      'program_title' => $program_title,
      'program_date' => $program_date,
      'program_price' => $program_price,
      'total_price' => $total_price,
      'school_name' => $school_name,
      'selected_services' => $selected_services,
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
