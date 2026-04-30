<?php
/**
 * AJAX: форма консультации с лендинга page-bsimice.php
 */

add_action('wp_ajax_bsimice_consultation_form', 'handle_bsimice_consultation_form');
add_action('wp_ajax_nopriv_bsimice_consultation_form', 'handle_bsimice_consultation_form');

function handle_bsimice_consultation_form(): void
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

  $email_raw = isset($_POST['email']) ? trim((string) wp_unslash($_POST['email'])) : '';
  $email = sanitize_email($email_raw);
  if ($email === '' || !is_email($email)) {
    $errors['email'] = true;
  }

  $wishes = sanitize_textarea_field($_POST['wishes'] ?? '');

  if (!isset($_POST['privacy_agreement']) || (string) $_POST['privacy_agreement'] !== 'on') {
    $errors['privacy_agreement'] = true;
  }

  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Заполните обязательные поля',
      'errors' => $errors,
    ]);
  }

  $page_title = sanitize_text_field($_POST['source_page_title'] ?? '');
  $page_url = esc_url_raw($_POST['source_page_url'] ?? '');

  $recipient_email = bsi_get_bsimice_lead_recipient_emails();
  if ($recipient_email === []) {
    wp_send_json_error([
      'message' => 'Не настроены получатели письма',
    ]);
  }

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
    .footer { margin-top: 16px; padding-top: 16px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Заявка с лендинга MICE</h1>

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
        <span class="field-label">E-mail:</span>
        <span><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></span>
      </div>
    </div>';

  if ($wishes !== '') {
    $html_message .= '
    <div class="section">
      <div class="section-title">Пожелания</div>
      <div class="field">' . nl2br(esc_html($wishes)) . '</div>
    </div>';
  }

  if ($page_title !== '' || $page_url !== '') {
    $html_message .= '
    <div class="section">
      <div class="section-title">Источник</div>';
    if ($page_title !== '') {
      $html_message .= '
      <div class="field">
        <span class="field-label">Страница:</span>
        <span>' . esc_html($page_title) . '</span>
      </div>';
    }
    if ($page_url !== '') {
      $html_message .= '
      <div class="field">
        <span class="field-label">URL:</span>
        <span><a href="' . esc_url($page_url) . '">' . esc_html($page_url) . '</a></span>
      </div>';
    }
    $html_message .= '
    </div>';
  }

  $html_message .= bsi_mail_lead_signature_html();

  $html_message .= '
    <div class="footer">
      <p><strong>Дата отправки:</strong> ' . esc_html(date('d.m.Y H:i:s')) . '</p>
      <p><strong>IP адрес:</strong> ' . esc_html($_SERVER['REMOTE_ADDR'] ?? 'не определен') . '</p>
    </div>
  </div>
</body>
</html>';

  $headers = bsi_mail_lead_headers($email);

  $subject = $page_title !== ''
    ? sprintf('Заявка MICE — %s', $page_title)
    : 'Заявка MICE';

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
