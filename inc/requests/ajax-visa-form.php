<?php
/**
 * AJAX обработчик для формы визы
 */

add_action('wp_ajax_visa_form', 'handle_visa_form');
add_action('wp_ajax_nopriv_visa_form', 'handle_visa_form');

function handle_visa_form()
{
  // Логируем входящие данные
  error_log('Visa Form Data: ' . print_r($_POST, true));
  
  $errors = [];

  // Валидация страны
  $country_id = intval($_POST['country_id'] ?? 0);
  if (empty($country_id)) {
    $errors['country_id'] = 'Выберите страну';
  }

  // Тип визы необязательный
  $visa_type = sanitize_text_field($_POST['visa_type'] ?? '');

  // Валидация имени
  $name = sanitize_text_field($_POST['name'] ?? '');
  if (empty($name)) {
    $errors['name'] = 'Введите имя';
  }

  // Валидация гражданства
  $citizenship = sanitize_text_field($_POST['citizenship'] ?? '');
  if (empty($citizenship)) {
    $errors['citizenship'] = 'Введите гражданство';
  }

  // Валидация телефона
  $phone = sanitize_text_field($_POST['phone'] ?? '');
  if (empty($phone)) {
    $errors['phone'] = 'Введите телефон';
  } else {
    // Проверка что телефон содержит достаточно цифр
    $phone_digits = preg_replace('/\D/', '', $phone);
    if (strlen($phone_digits) < 10) {
      $errors['phone'] = 'Введите корректный номер телефона';
    }
  }

  // Валидация дат поездки (текстовое поле)
  $travel_dates = sanitize_text_field($_POST['travel_dates'] ?? '');
  if (empty($travel_dates)) {
    $errors['travel_dates'] = 'Укажите даты поездки';
  }

  // Если есть ошибки - возвращаем их
  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Исправьте ошибки в форме',
      'errors' => $errors
    ]);
  }

  // Получаем название страны
  $country_name = '';
  if ($country_id) {
    $country_post = get_post($country_id);
    if ($country_post) {
      $country_name = $country_post->post_title;
    }
  }

  // Получаем название типа визы
  $visa_type_name = '';
  if ($visa_type) {
    $visa_type_names = [
      'tourist' => 'Туристическая',
      'educational' => 'Образовательная',
    ];
    $visa_type_name = $visa_type_names[$visa_type] ?? $visa_type;
  }

  // Формируем HTML сообщение для email
  $recipient_email = 'dianov.js@gmail.com';

  // Формируем HTML письмо
  $html_message = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    h1 { color: #dc2626; border-bottom: 2px solid #dc2626; padding-bottom: 10px; }
    .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #dc2626; }
    .section-title { font-weight: bold; font-size: 16px; margin-bottom: 10px; color: #dc2626; }
    .field { margin: 8px 0; }
    .field-label { font-weight: bold; display: inline-block; min-width: 180px; }
    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Новая заявка с формы визы</h1>
    
    <div class="section">
      <div class="section-title">Контактные данные</div>
      <div class="field">
        <span class="field-label">Имя:</span>
        <span>' . esc_html($name) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Гражданство:</span>
        <span>' . esc_html($citizenship) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Телефон:</span>
        <span><a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></span>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Информация о визе</div>
      <div class="field">
        <span class="field-label">Страна:</span>
        <span>' . esc_html($country_name) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Тип визы:</span>
        <span>' . esc_html($visa_type_name ?: 'Не указан') . '</span>
      </div>';

  if ($travel_dates) {
    $html_message .= '
      <div class="field">
        <span class="field-label">Даты поездки:</span>
        <span>' . esc_html($travel_dates) . '</span>
      </div>';
  }

  $html_message .= '
    </div>

    <div class="footer">
      <p><strong>Дата отправки:</strong> ' . date('d.m.Y H:i:s') . '</p>
      <p><strong>IP адрес:</strong> ' . esc_html($_SERVER['REMOTE_ADDR'] ?? 'не определен') . '</p>
    </div>
  </div>
</body>
</html>';

  // Формируем plain text версию
  $text_message = "Новая заявка с формы визы\n\n";
  $text_message .= "Контактные данные:\n";
  $text_message .= "Имя: $name\n";
  $text_message .= "Гражданство: $citizenship\n";
  $text_message .= "Телефон: $phone\n";
  $text_message .= "\nИнформация о визе:\n";
  $text_message .= "Страна: $country_name\n";
  $text_message .= "Тип визы: " . ($visa_type_name ?: 'Не указан') . "\n";
  if ($travel_dates) {
    $text_message .= "Даты поездки: $travel_dates\n";
  }
  $text_message .= "\n---\n";
  $text_message .= "Дата отправки: " . date('d.m.Y H:i:s') . "\n";
  $text_message .= "IP адрес: " . ($_SERVER['REMOTE_ADDR'] ?? 'не определен') . "\n";

  // Настройка заголовков для HTML письма
  $headers = array(
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
  );

  // Отправка письма
  $subject = 'Новая заявка на визу';
  error_log('Visa Form: Sending email to ' . $recipient_email);
  $sent = wp_mail($recipient_email, $subject, $html_message, $headers);
  error_log('Visa Form: wp_mail result = ' . ($sent ? 'true' : 'false'));

  if ($sent) {
    wp_send_json_success([
      'message' => 'Форма успешно отправлена!'
    ]);
  } else {
    wp_send_json_error([
      'message' => 'Ошибка отправки письма'
    ]);
  }
}
