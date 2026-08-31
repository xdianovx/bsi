<?php
/**
 * Письмо: отклик на вакансию.
 *
 * @var string $name
 * @var string $email
 * @var string $phone
 * @var string $comment
 * @var string $vacancy_title
 * @var string $page_url
 * @var string $resume_name Исходное имя приложенного резюме ('' — файла нет)
 */

if (!defined('ABSPATH')) {
  exit;
}

$site_name = get_bloginfo('name');
$rows = [
  'Вакансия' => $vacancy_title ?? '',
  'Имя' => $name ?? '',
  'Телефон' => $phone ?? '',
  'Email' => $email ?? '',
  'Резюме' => ($resume_name ?? '') !== '' ? $resume_name . ' (во вложении)' : 'Не приложено',
];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body
  style="margin:0;padding:0;background:#f4f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#333;line-height:160%;">
  <div style="max-width:600px;margin:40px auto;background:#ffffff;border-radius:8px;overflow:hidden;">

    <div style="background:#ee3145;color:#ffffff;padding:24px 32px;">
      <h1 style="margin:0;font-size:20px;line-height:130%;">Отклик на вакансию</h1>
      <p style="margin:6px 0 0;font-size:14px;opacity:.9;"><?php echo esc_html($vacancy_title ?? ''); ?></p>
    </div>

    <div style="padding:24px 32px;">
      <table style="width:100%;border-collapse:collapse;font-size:15px;">
        <?php foreach ($rows as $label => $value) : ?>
          <?php if (trim((string) $value) === '') {
            continue;
          } ?>
          <tr>
            <td
              style="padding:10px 12px;border-bottom:1px solid #eee;background:#faf6f5;font-weight:600;width:38%;vertical-align:top;">
              <?php echo esc_html($label); ?>
            </td>
            <td style="padding:10px 12px;border-bottom:1px solid #eee;vertical-align:top;">
              <?php echo esc_html((string) $value); ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>

      <?php if (trim((string) ($comment ?? '')) !== '') : ?>
        <div style="margin-top:20px;">
          <p style="margin:0 0 6px;font-weight:600;font-size:15px;">Комментарий кандидата</p>
          <div style="padding:12px 14px;background:#f4f4f6;border-radius:6px;font-size:15px;white-space:pre-line;">
            <?php echo esc_html((string) $comment); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (trim((string) ($page_url ?? '')) !== '') : ?>
        <p style="margin:20px 0 0;font-size:14px;">
          Страница вакансии:
          <a href="<?php echo esc_url((string) $page_url); ?>" style="color:#ee3145;"><?php echo esc_html((string) $page_url); ?></a>
        </p>
      <?php endif; ?>
    </div>

    <div style="padding:16px 32px;border-top:1px solid #eee;font-size:12px;color:#999;">
      <?php echo esc_html($site_name); ?> · <?php echo esc_html(wp_date('d.m.Y H:i')); ?>
    </div>

  </div>
</body>

</html>
