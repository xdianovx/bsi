<?php

add_action('wp_ajax_agency_event_registration', 'bsi_agency_event_registration');
add_action('wp_ajax_nopriv_agency_event_registration', 'bsi_agency_event_registration');

function bsi_agency_event_registration()
{
  $token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
  if (function_exists('bsi_recaptcha_verify_or_die')) {
    bsi_recaptcha_verify_or_die($token);
  }

  $errors = [];

  $name = sanitize_text_field($_POST['name'] ?? '');
  if (empty($name)) {
    $errors['name'] = true;
  }

  $company = sanitize_text_field($_POST['company'] ?? '');
  if (empty($company)) {
    $errors['company'] = true;
  }

  $city = sanitize_text_field($_POST['city'] ?? '');
  if (empty($city)) {
    $errors['city'] = true;
  }

  $inn = sanitize_text_field($_POST['inn'] ?? '');
  if (empty($inn)) {
    $errors['inn'] = true;
  }

  $email = sanitize_email($_POST['email'] ?? '');
  if (empty($email) || !is_email($email)) {
    $errors['email'] = true;
  }

  $tel = sanitize_text_field($_POST['tel'] ?? '');
  if (empty($tel)) {
    $errors['tel'] = true;
  } else {
    $phone_digits = preg_replace('/\D/', '', $tel);
    if (strlen($phone_digits) < 11) {
      $errors['tel'] = true;
    }
  }

  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Заполните обязательные поля',
      'errors' => $errors,
    ]);
  }

  $event_id = (int) ($_POST['event_id'] ?? 0);
  $event_title = sanitize_text_field($_POST['event_title'] ?? '');
  $event_kind = sanitize_text_field($_POST['event_kind'] ?? '');
  $page_url = esc_url_raw($_POST['page_url'] ?? '');

  $recipients = ['agent@bsigroup.ru'];
  if ($event_id && function_exists('get_field')) {
    $extra_email = trim((string) get_field('event_notify_email', $event_id));
    if (!empty($extra_email) && is_email($extra_email)) {
      $recipients[] = $extra_email;
    }
  }
  $recipient_email = implode(', ', array_unique($recipients));

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
    <h1>Регистрация на мероприятие</h1>

    <div class="section">
      <div class="section-title">Мероприятие</div>
      <div class="field">
        <span class="field-label">Название:</span>
        <span>' . esc_html($event_title) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Тип:</span>
        <span>' . esc_html($event_kind) . '</span>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Контактные данные</div>
      <div class="field">
        <span class="field-label">ФИО:</span>
        <span>' . esc_html($name) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Email:</span>
        <span><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></span>
      </div>
      <div class="field">
        <span class="field-label">Телефон:</span>
        <span><a href="tel:' . esc_attr($tel) . '">' . esc_html($tel) . '</a></span>
      </div>
    </div>';

  $html_message .= '
    <div class="section">
      <div class="section-title">Компания</div>
      <div class="field">
        <span class="field-label">Компания:</span>
        <span>' . esc_html($company) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Город:</span>
        <span>' . esc_html($city) . '</span>
      </div>
      <div class="field">
        <span class="field-label">ИНН:</span>
        <span>' . esc_html($inn) . '</span>
      </div>
    </div>';

  $html_message .= '
    <div class="footer">
      <p><strong>Страница заявки:</strong> <a href="' . esc_url($page_url) . '">' . esc_html($page_url) . '</a></p>
      <p><strong>Дата отправки:</strong> ' . date('d.m.Y H:i:s') . '</p>
      <p><strong>IP адрес:</strong> ' . esc_html($_SERVER['REMOTE_ADDR'] ?? 'не определен') . '</p>
    </div>
  </div>
</body>
</html>';

  $headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
  ];

  if (!empty($email)) {
    $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
  }

  $subject = 'Регистрация на мероприятие: ' . $event_title;
  $sent = wp_mail($recipient_email, $subject, $html_message, $headers);

  $is_localhost = strpos(home_url(), 'localhost') !== false;
  if (!$sent && $is_localhost) {
    wp_send_json_success(['message' => 'OK (localhost)']);
  }

  if ($sent) {
    wp_send_json_success(['message' => 'Заявка успешно отправлена!']);
  } else {
    wp_send_json_error(['message' => 'Ошибка отправки письма']);
  }
}
