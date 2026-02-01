<?php
/**
 * Шаблон письма: Бронирование образовательной программы
 * 
 * Доступные переменные:
 * $name, $email, $phone, $comment
 * $program_title, $program_date, $program_price, $total_price
 * $school_name, $selected_services (array)
 */

$program_price_formatted = number_format((int) ($program_price ?? 0), 0, ',', ' ') . ' ₽';
$total_price_formatted = number_format((int) ($total_price ?? 0), 0, ',', ' ') . ' ₽';
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Заявка на бронирование программы</title>
</head>

<body
  style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
  <h2 style="color: #e53935; border-bottom: 2px solid #e53935; padding-bottom: 10px;">
    Заявка на бронирование образовательной программы
  </h2>

  <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tr style="background-color: #f5f5f5;">
      <td style="padding: 10px; font-weight: bold; width: 40%;">Программа</td>
      <td style="padding: 10px;"><?php echo esc_html($program_title ?? ''); ?></td>
    </tr>
    <?php if (!empty($school_name)): ?>
      <tr>
        <td style="padding: 10px; font-weight: bold;">Школа</td>
        <td style="padding: 10px;"><?php echo esc_html($school_name); ?></td>
      </tr>
    <?php endif; ?>
    <?php if (!empty($program_date)): ?>
      <tr style="background-color: #f5f5f5;">
        <td style="padding: 10px; font-weight: bold;">Дата начала</td>
        <td style="padding: 10px;"><?php echo esc_html($program_date); ?></td>
      </tr>
    <?php endif; ?>
    <tr>
      <td style="padding: 10px; font-weight: bold;">Базовая стоимость</td>
      <td style="padding: 10px;"><?php echo $program_price_formatted; ?></td>
    </tr>
    <?php if (!empty($selected_services) && is_array($selected_services)): ?>
      <tr>
        <td colspan="2" style="padding: 10px 0;">
          <strong>Выбранные услуги:</strong>
          <ul style="margin: 5px 0; padding-left: 20px;">
            <?php foreach ($selected_services as $service): ?>
              <li>
                <?php echo esc_html($service['title'] ?? ''); ?> —
                <?php echo number_format((int) ($service['price'] ?? 0), 0, ',', ' '); ?> ₽
              </li>
            <?php endforeach; ?>
          </ul>
        </td>
      </tr>
    <?php endif; ?>
    <tr style="background-color: #fff3e0;">
      <td style="padding: 10px; font-weight: bold; font-size: 16px;">Итого</td>
      <td style="padding: 10px; font-weight: bold; font-size: 16px; color: #e53935;">
        <?php echo $total_price_formatted; ?>
      </td>
    </tr>
  </table>

  <h3 style="color: #333; margin-top: 30px;">Контактные данные</h3>
  <table style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f5f5f5;">
      <td style="padding: 10px; font-weight: bold; width: 40%;">Имя</td>
      <td style="padding: 10px;"><?php echo esc_html($name ?? ''); ?></td>
    </tr>
    <tr>
      <td style="padding: 10px; font-weight: bold;">Email</td>
      <td style="padding: 10px;">
        <a href="mailto:<?php echo esc_attr($email ?? ''); ?>"><?php echo esc_html($email ?? ''); ?></a>
      </td>
    </tr>
    <tr style="background-color: #f5f5f5;">
      <td style="padding: 10px; font-weight: bold;">Телефон</td>
      <td style="padding: 10px;">
        <a
          href="tel:<?php echo esc_attr(preg_replace('/\D/', '', $phone ?? '')); ?>"><?php echo esc_html($phone ?? ''); ?></a>
      </td>
    </tr>
    <?php if (!empty($checkin_date)): ?>
      <tr>
        <td style="padding: 10px; font-weight: bold;">Дата заезда</td>
        <td style="padding: 10px;"><?php echo esc_html($checkin_date); ?></td>
      </tr>
    <?php endif; ?>
    <?php if (!empty($comment)): ?>
      <tr>
        <td style="padding: 10px; font-weight: bold;">Комментарий</td>
        <td style="padding: 10px;"><?php echo nl2br(esc_html($comment)); ?></td>
      </tr>
    <?php endif; ?>
  </table>

  <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
  <p style="font-size: 12px; color: #999;">
    IP: <?php echo esc_html($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?><br>
    Дата: <?php echo esc_html(wp_date('d.m.Y H:i:s')); ?>
    <?php if (!empty($page_url)): ?>
      <br>
      Страница: <a href="<?php echo esc_url($page_url); ?>"
        style="color: #e53935; text-decoration: none;"><?php echo esc_html($page_url); ?></a>
    <?php endif; ?>
  </p>
</body>

</html>