<?php
/**
 * Письмо: заявка по событию (упрощённо).
 *
 * @var string $name
 * @var string $email
 * @var string $phone
 * @var string $comment
 * @var string $event_title
 * @var string $page_url
 * @var string $booking_context 'event' | 'promo'
 */

if (!defined('ABSPATH')) {
  exit;
}

$site_name = get_bloginfo('name');
$site_url = home_url();
$is_promo = (($booking_context ?? '') === 'promo');
$mail_heading = $is_promo ? 'Заявка по акции' : 'Заявка по событию';
$mail_section_title = $is_promo ? 'Акция' : 'Событие';
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      line-height: 160%;
      color: #333;
      background-color: #f4f4f6;
      margin: 0;
      padding: 0;
    }

    .email-container {
      max-width: 600px;
      margin: 40px auto;
      background: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .email-header {
      background: linear-gradient(135deg, #e53935 0%, #d32f2f 100%);
      color: #ffffff;
      padding: 30px;
      text-align: center;
    }

    .email-header h1 {
      margin: 0;
      font-size: 22px;
      font-weight: 700;
    }

    .email-body {
      padding: 30px;
    }

    .section-title {
      font-size: 16px;
      font-weight: 700;
      color: #e53935;
      margin: 0 0 16px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f4f4f6;
    }

    .info-row {
      display: flex;
      padding: 12px 0;
      border-bottom: 1px solid #f4f4f6;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-label {
      font-weight: 600;
      color: #666;
      min-width: 140px;
    }

    .info-value {
      color: #333;
      flex: 1;
    }

    .email-footer {
      background: #f9f9f9;
      padding: 20px 30px;
      text-align: center;
      font-size: 12px;
      color: #999;
      border-top: 1px solid #eee;
    }

    .email-footer a {
      color: #e53935;
      text-decoration: none;
    }

    @media only screen and (max-width: 600px) {
      .email-container {
        margin: 0;
        border-radius: 0;
      }

      .info-row {
        flex-direction: column;
      }

      .info-label {
        min-width: auto;
        margin-bottom: 5px;
      }
    }
  </style>
</head>

<body>
  <div class="email-container">
    <div class="email-header">
      <h1><?php echo esc_html($mail_heading); ?></h1>
    </div>

    <div class="email-body">
      <h2 class="section-title"><?php echo esc_html($mail_section_title); ?></h2>
      <div class="info-row">
        <div class="info-label">Название</div>
        <div class="info-value"><?php echo esc_html($event_title ?? ''); ?></div>
      </div>
      <?php if (!empty($page_url)): ?>
        <div class="info-row">
          <div class="info-label">Страница</div>
          <div class="info-value"><a href="<?php echo esc_url($page_url); ?>"><?php echo esc_html($page_url); ?></a></div>
        </div>
      <?php endif; ?>

      <h2 class="section-title" style="margin-top: 28px;">Контакты</h2>
      <div class="info-row">
        <div class="info-label">Имя</div>
        <div class="info-value"><?php echo esc_html($name ?? ''); ?></div>
      </div>
      <div class="info-row">
        <div class="info-label">Телефон</div>
        <div class="info-value"><a href="tel:<?php echo esc_attr(preg_replace('/\D/', '', (string) ($phone ?? ''))); ?>"><?php echo esc_html($phone ?? ''); ?></a></div>
      </div>
      <?php if (!empty($email) && is_email($email)): ?>
        <div class="info-row">
          <div class="info-label">Почта</div>
          <div class="info-value"><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($comment)): ?>
        <h2 class="section-title" style="margin-top: 28px;">Комментарий</h2>
        <div class="info-value" style="padding: 0 0 12px;"><?php echo nl2br(esc_html($comment)); ?></div>
      <?php endif; ?>
    </div>

    <div class="email-footer">
      <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?></p>
      <p><a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_url); ?></a></p>
    </div>
  </div>
</body>

</html>
