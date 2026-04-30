<?php
/**
 * AJAX обработчик формы консультации со страницы single-visa.php
 */

add_action('wp_ajax_single_visa_form', 'handle_single_visa_form');
add_action('wp_ajax_nopriv_single_visa_form', 'handle_single_visa_form');

function handle_single_visa_form()
{
  $token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
  bsi_recaptcha_verify_or_die($token);

  $errors = [];

  $name = sanitize_text_field($_POST['name'] ?? '');
  if ($name === '') {
    $errors['name'] = true;
  }

  $phone = sanitize_text_field($_POST['phone'] ?? '');
  if ($phone === '') {
    $errors['phone'] = true;
  } else {
    $phone_digits = preg_replace('/\D/', '', $phone);
    if (strlen($phone_digits) < 11) {
      $errors['phone'] = true;
    }
  }

  $travel_dates = sanitize_text_field($_POST['travel_dates'] ?? '');

  if (!isset($_POST['privacy_agreement']) || (string) $_POST['privacy_agreement'] !== 'on') {
    $errors['privacy_agreement'] = true;
  }

  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Заполните обязательные поля',
      'errors' => $errors,
    ]);
  }

  $visa_page_title = sanitize_text_field($_POST['visa_page_title'] ?? '');
  $visa_page_url = esc_url_raw($_POST['visa_page_url'] ?? '');
  $visa_country_title = sanitize_text_field($_POST['visa_country_title'] ?? '');
  $visa_page_slug = sanitize_text_field($_POST['visa_page_slug'] ?? '');
  $visa_type_label = sanitize_text_field($_POST['visa_type_label'] ?? '');

  $recipient_email = [
    'dianov.js@gmail.com',
    'v.ivanova@bsigroup.ru',
  ];

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
    <h1>Новая заявка с визовой страницы</h1>

    <div class="section">
      <div class="section-title">Контактные данные</div>
      <div class="field">
        <span class="field-label">Имя:</span>
        <span>' . esc_html($name) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Телефон:</span>
        <span><a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></span>
      </div>
      <div class="field">
        <span class="field-label">Даты поездки:</span>
        <span>' . esc_html($travel_dates) . '</span>
      </div>
    </div>';

  if ($visa_page_title !== '' || $visa_page_url !== '' || $visa_country_title !== '' || $visa_page_slug !== '' || $visa_type_label !== '') {
    $html_message .= '
    <div class="section">
      <div class="section-title">Источник заявки</div>';

    if ($visa_page_title !== '') {
      $html_message .= '
      <div class="field">
        <span class="field-label">Страница:</span>
        <span>' . esc_html($visa_page_title) . '</span>
      </div>';
    }

    if ($visa_page_url !== '') {
      $html_message .= '
      <div class="field">
        <span class="field-label">URL:</span>
        <span><a href="' . esc_url($visa_page_url) . '">' . esc_html($visa_page_url) . '</a></span>
      </div>';
    }

    if ($visa_country_title !== '') {
      $html_message .= '
      <div class="field">
        <span class="field-label">Страна:</span>
        <span>' . esc_html($visa_country_title) . '</span>
      </div>';
    }

    if ($visa_page_slug !== '') {
      $html_message .= '
      <div class="field">
        <span class="field-label">Метка страницы:</span>
        <span>' . esc_html($visa_page_slug) . '</span>
      </div>';
    }

    if ($visa_type_label !== '') {
      $html_message .= '
      <div class="field">
        <span class="field-label">Тип визы:</span>
        <span>' . esc_html($visa_type_label) . '</span>
      </div>';
    }

    $html_message .= '
    </div>';
  }

  $html_message .= '
    <div class="footer">
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

  $country_for_subject = $visa_country_title !== '' ? $visa_country_title : 'без страны';
  $label_for_subject = $visa_page_slug !== '' ? $visa_page_slug : 'без метки';
  $subject = sprintf(
    'Новая заявка с визовой страницы — %s [%s]',
    $country_for_subject,
    $label_for_subject
  );
  $sent = wp_mail($recipient_email, $subject, $html_message, $headers);

  if ($sent) {
    wp_send_json_success([
      'message' => 'Форма успешно отправлена!',
    ]);
  }

  wp_send_json_error([
    'message' => 'Ошибка отправки письма',
  ]);
}
