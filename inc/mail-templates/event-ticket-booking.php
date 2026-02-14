<?php
/**
 * Email шаблон для бронирования билета на событийный тур
 * Доступные переменные:
 * - $name, $email, $phone
 * - $quantity, $comment
 * - $event_title, $event_venue, $event_time
 * - $ticket_type, $ticket_price, $total_price
 * - $page_url
 */

if (!defined('ABSPATH')) {
  exit;
}

$site_name = get_bloginfo('name');
$site_url = home_url();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      line-height: 1.6;
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
      font-size: 24px;
      font-weight: 700;
    }

    .email-header p {
      margin: 10px 0 0;
      font-size: 14px;
      opacity: 0.9;
    }

    .email-body {
      padding: 30px;
    }

    .section {
      margin-bottom: 30px;
    }

    .section-title {
      font-size: 16px;
      font-weight: 700;
      color: #e53935;
      margin: 0 0 15px;
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
      min-width: 150px;
    }

    .info-value {
      color: #333;
      flex: 1;
    }

    .ticket-highlight {
      background: #f4f4f6;
      border-left: 4px solid #e53935;
      padding: 20px;
      margin: 20px 0;
      border-radius: 4px;
    }

    .ticket-highlight .ticket-type {
      font-size: 18px;
      font-weight: 700;
      color: #333;
      margin: 0 0 10px;
    }

    .ticket-highlight .ticket-price {
      font-size: 24px;
      font-weight: 700;
      color: #e53935;
      margin: 10px 0 0;
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
      <h1>🎫 Заявка на билет</h1>
      <p><?php echo esc_html($event_title ?? ''); ?></p>
    </div>

    <div class="email-body">
      <!-- Информация о событии -->
      <div class="section">
        <h2 class="section-title">Информация о событии</h2>
        <?php if (!empty($event_venue)): ?>
          <div class="info-row">
            <div class="info-label">Место проведения:</div>
            <div class="info-value"><?php echo esc_html($event_venue); ?></div>
          </div>
        <?php endif; ?>
        <?php if (!empty($event_time)): ?>
          <div class="info-row">
            <div class="info-label">Время:</div>
            <div class="info-value"><?php echo esc_html($event_time); ?></div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Выбранный билет -->
      <div class="section">
        <h2 class="section-title">Выбранный билет</h2>
        <div class="ticket-highlight">
          <div class="ticket-type"><?php echo esc_html($ticket_type ?? 'Билет'); ?></div>
          <div class="info-row">
            <div class="info-label">Цена за билет:</div>
            <div class="info-value"><?php echo number_format($ticket_price ?? 0, 0, ',', ' '); ?> руб.</div>
          </div>
          <div class="info-row">
            <div class="info-label">Количество:</div>
            <div class="info-value"><?php echo esc_html($quantity ?? 1); ?> шт.</div>
          </div>
          <div class="ticket-price">
            Итого: <?php echo number_format($total_price ?? 0, 0, ',', ' '); ?> руб.
          </div>
        </div>
      </div>

      <!-- Контактные данные -->
      <div class="section">
        <h2 class="section-title">Контактные данные</h2>
        <div class="info-row">
          <div class="info-label">Имя:</div>
          <div class="info-value"><?php echo esc_html($name ?? ''); ?></div>
        </div>
        <div class="info-row">
          <div class="info-label">Телефон:</div>
          <div class="info-value"><a href="tel:<?php echo esc_attr($phone ?? ''); ?>"><?php echo esc_html($phone ?? ''); ?></a></div>
        </div>
        <div class="info-row">
          <div class="info-label">Email:</div>
          <div class="info-value"><a href="mailto:<?php echo esc_attr($email ?? ''); ?>"><?php echo esc_html($email ?? ''); ?></a></div>
        </div>
      </div>

      <!-- Комментарий -->
      <?php if (!empty($comment)): ?>
        <div class="section">
          <h2 class="section-title">Комментарий</h2>
          <div class="info-value"><?php echo nl2br(esc_html($comment)); ?></div>
        </div>
      <?php endif; ?>
    </div>

    <div class="email-footer">
      <p>
        Заявка отправлена со страницы:<br>
        <a href="<?php echo esc_url($page_url ?? $site_url); ?>"><?php echo esc_html($event_title ?? 'Страница события'); ?></a>
      </p>
      <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. Все права защищены.</p>
    </div>
  </div>
</body>

</html>
