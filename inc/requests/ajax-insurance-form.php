<?php
/**
 * AJAX обработчик для формы консультации по страхованию
 */

add_action('wp_ajax_insurance_form', 'handle_insurance_form');
add_action('wp_ajax_nopriv_insurance_form', 'handle_insurance_form');

function handle_insurance_form()
{
  // Логируем входящие данные
  error_log('Insurance Form Data: ' . print_r($_POST, true));

  $errors = [];

  // Валидация имени (обязательно)
  $name = sanitize_text_field($_POST['name'] ?? '');
  if (empty($name)) {
    $errors['name'] = true;
  }

  // Валидация телефона (обязательно)
  $tel = sanitize_text_field($_POST['tel'] ?? '');
  if (empty($tel)) {
    $errors['tel'] = true;
  } else {
    // Проверка что телефон содержит достаточно цифр
    $phone_digits = preg_replace('/\D/', '', $tel);
    if (strlen($phone_digits) < 10) {
      $errors['tel'] = true;
    }
  }

  // Если есть ошибки - возвращаем их
  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Заполните обязательные поля',
      'errors' => $errors
    ]);
  }

  // Необязательные поля
  $insurance_type = sanitize_text_field($_POST['insurance_type'] ?? '');
  $date = sanitize_text_field($_POST['date'] ?? '');

  // Формируем HTML сообщение для email
  $recipient_email = 'v.ivanova@bsigroup.ru';

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
    <h1>Новая заявка на консультацию по страхованию</h1>
    
    <div class="section">
      <div class="section-title">Контактные данные</div>
      <div class="field">
        <span class="field-label">Имя:</span>
        <span>' . esc_html($name) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Телефон:</span>
        <span><a href="tel:' . esc_attr($tel) . '">' . esc_html($tel) . '</a></span>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Информация о страховании</div>';

  if (!empty($insurance_type)) {
    $html_message .= '
      <div class="field">
        <span class="field-label">Тип страхования:</span>
        <span>' . esc_html($insurance_type) . '</span>
      </div>';
  }

  if (!empty($date)) {
    $html_message .= '
      <div class="field">
        <span class="field-label">Дата поездки:</span>
        <span>' . esc_html($date) . '</span>
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
  $text_message = "Новая заявка на консультацию по страхованию\n\n";
  $text_message .= "Контактные данные:\n";
  $text_message .= "Имя: $name\n";
  $text_message .= "Телефон: $tel\n";
  $text_message .= "\nИнформация о страховании:\n";
  if (!empty($insurance_type)) {
    $text_message .= "Тип страхования: $insurance_type\n";
  }
  if (!empty($date)) {
    $text_message .= "Дата поездки: $date\n";
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
  $subject = 'Новая заявка на консультацию по страхованию';
  error_log('Insurance Form: Sending email to ' . $recipient_email);
  $sent = wp_mail($recipient_email, $subject, $html_message, $headers);
  error_log('Insurance Form: wp_mail result = ' . ($sent ? 'true' : 'false'));

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
