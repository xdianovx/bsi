<?php

declare(strict_types=1);

/**
 * AJAX обработчик формы бронирования образовательной программы
 */

add_action('wp_ajax_education_program_booking', 'bsi_handle_education_program_booking');
add_action('wp_ajax_nopriv_education_program_booking', 'bsi_handle_education_program_booking');

function bsi_handle_education_program_booking(): void
{
  $errors = [];

  // Валидация и санитизация
  $name = isset($_POST['name']) ? sanitize_text_field(trim($_POST['name'])) : '';
  $email = isset($_POST['email']) ? sanitize_email(trim($_POST['email'])) : '';
  $phone = isset($_POST['phone']) ? sanitize_text_field(trim($_POST['phone'])) : '';
  $comment = isset($_POST['comment']) ? sanitize_textarea_field(trim($_POST['comment'])) : '';

  // Данные программы
  $program_title = isset($_POST['program_title']) ? sanitize_text_field($_POST['program_title']) : '';
  $program_date = isset($_POST['program_date']) ? sanitize_text_field($_POST['program_date']) : '';
  $program_price = isset($_POST['program_price']) ? absint($_POST['program_price']) : 0;
  $total_price = isset($_POST['total_price']) ? absint($_POST['total_price']) : 0;
  $school_name = isset($_POST['school_name']) ? sanitize_text_field($_POST['school_name']) : '';

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

  // Валидация обязательных полей
  if (empty($name)) {
    $errors['name'] = 'Введите имя';
  }

  if (empty($email)) {
    $errors['email'] = 'Введите email';
  } elseif (!is_email($email)) {
    $errors['email'] = 'Введите корректный email';
  }

  if (empty($phone)) {
    $errors['phone'] = 'Введите телефон';
  } else {
    $phone_digits = preg_replace('/\D/', '', $phone);
    if (strlen($phone_digits) < 11) {
      $errors['phone'] = 'Введите полный номер телефона';
    }
  }

  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Пожалуйста, исправьте ошибки в форме',
      'errors' => $errors,
    ]);
  }

  // Формируем письмо
  $subject = 'Заявка на бронирование программы: ' . $program_title;

  // HTML версия
  $html_message = bsi_build_education_booking_email_html([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'comment' => $comment,
    'program_title' => $program_title,
    'program_date' => $program_date,
    'program_price' => $program_price,
    'total_price' => $total_price,
    'school_name' => $school_name,
    'selected_services' => $selected_services,
  ]);

  // Настройки письма
  $to = get_option('admin_email');
  $headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: BSI <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
  ];

  // Отправка
  $sent = wp_mail($to, $subject, $html_message, $headers);

  if ($sent) {
    wp_send_json_success([
      'message' => 'Заявка успешно отправлена!',
    ]);
  } else {
    wp_send_json_error([
      'message' => 'Ошибка при отправке письма. Попробуйте позже.',
    ]);
  }
}

/**
 * Формирование HTML письма
 */
function bsi_build_education_booking_email_html(array $data): string
{
  $services_html = '';
  if (!empty($data['selected_services'])) {
    $services_html = '<tr><td colspan="2" style="padding: 10px 0;"><strong>Выбранные услуги:</strong><ul style="margin: 5px 0; padding-left: 20px;">';
    foreach ($data['selected_services'] as $service) {
      $price_formatted = number_format($service['price'], 0, ',', ' ') . ' ₽';
      $services_html .= '<li>' . esc_html($service['title']) . ' — ' . $price_formatted . '</li>';
    }
    $services_html .= '</ul></td></tr>';
  }

  $program_price_formatted = number_format($data['program_price'], 0, ',', ' ') . ' ₽';
  $total_price_formatted = number_format($data['total_price'], 0, ',', ' ') . ' ₽';

  $html = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Заявка на бронирование программы</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
  <h2 style="color: #e53935; border-bottom: 2px solid #e53935; padding-bottom: 10px;">
    Заявка на бронирование образовательной программы
  </h2>

  <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tr style="background-color: #f5f5f5;">
      <td style="padding: 10px; font-weight: bold; width: 40%;">Программа</td>
      <td style="padding: 10px;">' . esc_html($data['program_title']) . '</td>
    </tr>
    ' . ($data['school_name'] ? '
    <tr>
      <td style="padding: 10px; font-weight: bold;">Школа</td>
      <td style="padding: 10px;">' . esc_html($data['school_name']) . '</td>
    </tr>' : '') . '
    ' . ($data['program_date'] ? '
    <tr style="background-color: #f5f5f5;">
      <td style="padding: 10px; font-weight: bold;">Дата начала</td>
      <td style="padding: 10px;">' . esc_html($data['program_date']) . '</td>
    </tr>' : '') . '
    <tr>
      <td style="padding: 10px; font-weight: bold;">Базовая стоимость</td>
      <td style="padding: 10px;">' . $program_price_formatted . '</td>
    </tr>
    ' . $services_html . '
    <tr style="background-color: #fff3e0;">
      <td style="padding: 10px; font-weight: bold; font-size: 16px;">Итого</td>
      <td style="padding: 10px; font-weight: bold; font-size: 16px; color: #e53935;">' . $total_price_formatted . '</td>
    </tr>
  </table>

  <h3 style="color: #333; margin-top: 30px;">Контактные данные</h3>
  <table style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f5f5f5;">
      <td style="padding: 10px; font-weight: bold; width: 40%;">Имя</td>
      <td style="padding: 10px;">' . esc_html($data['name']) . '</td>
    </tr>
    <tr>
      <td style="padding: 10px; font-weight: bold;">Email</td>
      <td style="padding: 10px;"><a href="mailto:' . esc_attr($data['email']) . '">' . esc_html($data['email']) . '</a></td>
    </tr>
    <tr style="background-color: #f5f5f5;">
      <td style="padding: 10px; font-weight: bold;">Телефон</td>
      <td style="padding: 10px;"><a href="tel:' . esc_attr(preg_replace('/\D/', '', $data['phone'])) . '">' . esc_html($data['phone']) . '</a></td>
    </tr>
    ' . ($data['comment'] ? '
    <tr>
      <td style="padding: 10px; font-weight: bold;">Комментарий</td>
      <td style="padding: 10px;">' . nl2br(esc_html($data['comment'])) . '</td>
    </tr>' : '') . '
  </table>

  <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
  <p style="font-size: 12px; color: #999;">
    IP: ' . esc_html($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . '<br>
    Дата: ' . esc_html(wp_date('d.m.Y H:i:s')) . '
  </p>
</body>
</html>';

  return $html;
}
