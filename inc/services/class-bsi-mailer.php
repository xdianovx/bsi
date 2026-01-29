<?php

declare(strict_types=1);

/**
 * Сервис отправки email
 * 
 * Использование:
 * BSI_Mailer::send([
 *   'to' => 'email@example.com',
 *   'subject' => 'Тема письма',
 *   'template' => 'booking', // имя файла без .php из inc/mail-templates/
 *   'data' => ['name' => 'John', ...], // данные для шаблона
 *   'reply_to' => 'user@email.com', // опционально
 * ]);
 */
class BSI_Mailer
{
  /**
   * Email по умолчанию для заявок
   */
  const DEFAULT_RECIPIENT = 'dianov.js@gmail.com'; // TODO: поменять на рабочий

  /**
   * Отправить письмо
   *
   * @param array $args {
   *   @type string $to       Email получателя (по умолчанию DEFAULT_RECIPIENT)
   *   @type string $subject  Тема письма
   *   @type string $template Имя шаблона (без .php)
   *   @type array  $data     Данные для шаблона
   *   @type string $reply_to Reply-To адрес (опционально)
   * }
   * @return array ['success' => bool, 'message' => string]
   */
  public static function send(array $args): array
  {
    $to = $args['to'] ?? self::DEFAULT_RECIPIENT;
    $subject = $args['subject'] ?? 'Новая заявка с сайта BSI';
    $template = $args['template'] ?? 'default';
    $data = $args['data'] ?? [];
    $reply_to = $args['reply_to'] ?? '';

    // Генерируем HTML из шаблона
    $html_message = self::render_template($template, $data);

    if (!$html_message) {
      error_log("BSI_Mailer: Template '{$template}' not found or empty");
      return [
        'success' => false,
        'message' => 'Ошибка формирования письма',
      ];
    }

    // Заголовки
    $site_name = get_bloginfo('name') ?: 'BSI';
    $site_host = parse_url(home_url(), PHP_URL_HOST) ?: 'bsi.ru';

    $headers = [
      'Content-Type: text/html; charset=UTF-8',
      "From: {$site_name} <noreply@{$site_host}>",
    ];

    if ($reply_to && is_email($reply_to)) {
      $headers[] = "Reply-To: {$reply_to}";
    }

    // Логируем
    error_log("BSI_Mailer: Sending to {$to}, subject: {$subject}");

    // Отправляем
    $sent = wp_mail($to, $subject, $html_message, $headers);

    if ($sent) {
      return [
        'success' => true,
        'message' => 'Письмо отправлено',
      ];
    }

    // Логируем ошибку
    global $phpmailer;
    if (isset($phpmailer) && is_object($phpmailer)) {
      error_log('BSI_Mailer error: ' . $phpmailer->ErrorInfo);
    }

    // На localhost возвращаем успех для тестирования UI
    if (self::is_localhost()) {
      error_log('BSI_Mailer: Localhost - returning success for UI testing');
      return [
        'success' => true,
        'message' => 'Заявка принята (localhost)',
      ];
    }

    return [
      'success' => false,
      'message' => 'Ошибка при отправке письма. Попробуйте позже.',
    ];
  }

  /**
   * Рендер шаблона письма
   */
  private static function render_template(string $template, array $data): string
  {
    $template_path = get_template_directory() . "/inc/mail-templates/{$template}.php";

    if (!file_exists($template_path)) {
      // Fallback на базовый шаблон
      return self::render_default_template($data);
    }

    // Извлекаем данные в переменные для шаблона
    extract($data, EXTR_SKIP);

    ob_start();
    include $template_path;
    return ob_get_clean();
  }

  /**
   * Базовый шаблон если специфичный не найден
   */
  private static function render_default_template(array $data): string
  {
    $html = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
    h2 { color: #e53935; border-bottom: 2px solid #e53935; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    td { padding: 10px; border-bottom: 1px solid #eee; }
    td:first-child { font-weight: bold; width: 40%; background: #f9f9f9; }
    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
  </style>
</head>
<body>
  <h2>Новая заявка с сайта</h2>
  <table>';

    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $value = implode(', ', $value);
      }
      $label = self::humanize_key($key);
      $html .= '<tr><td>' . esc_html($label) . '</td><td>' . esc_html($value) . '</td></tr>';
    }

    $html .= '</table>
  <div class="footer">
    <p>Дата: ' . wp_date('d.m.Y H:i:s') . '</p>
    <p>IP: ' . esc_html($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . '</p>
  </div>
</body>
</html>';

    return $html;
  }

  /**
   * Преобразование ключа в читаемый label
   */
  private static function humanize_key(string $key): string
  {
    $map = [
      'name' => 'Имя',
      'email' => 'Email',
      'phone' => 'Телефон',
      'comment' => 'Комментарий',
      'message' => 'Сообщение',
      'program_title' => 'Программа',
      'school_name' => 'Школа',
      'total_price' => 'Итого',
      'selected_services' => 'Выбранные услуги',
    ];

    return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
  }

  /**
   * Проверка localhost
   */
  private static function is_localhost(): bool
  {
    $url = home_url();
    return (
      strpos($url, 'localhost') !== false ||
      strpos($url, '127.0.0.1') !== false ||
      strpos($url, '.local') !== false
    );
  }

  /**
   * Хелпер для AJAX handlers - стандартная валидация
   */
  public static function validate_contact_fields(array $post): array
  {
    $errors = [];

    $name = sanitize_text_field($post['name'] ?? '');
    if (empty($name)) {
      $errors['name'] = 'Введите имя';
    }

    $email = sanitize_email($post['email'] ?? '');
    if (empty($email)) {
      $errors['email'] = 'Введите email';
    } elseif (!is_email($email)) {
      $errors['email'] = 'Введите корректный email';
    }

    $phone = sanitize_text_field($post['phone'] ?? '');
    if (empty($phone)) {
      $errors['phone'] = 'Введите телефон';
    } else {
      $phone_digits = preg_replace('/\D/', '', $phone);
      if (strlen($phone_digits) < 11) {
        $errors['phone'] = 'Введите полный номер телефона';
      }
    }

    return $errors;
  }
}
