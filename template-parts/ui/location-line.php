<?php
/**
 * Строка локации: флаг страны + «Страна, Регион, Курорт».
 *
 * Один переиспользуемый блок для всех single-страниц (экскурсия, образование,
 * отель, тур, проект) — раньше в каждом шаблоне была своя разметка и свои
 * доп. стили, из-за чего блок выглядел по-разному.
 *
 *   get_template_part('template-parts/ui/location-line', null, [
 *     'country_id' => $country_id,          // необязательно: сам достанет флаг и ссылку
 *     'parts' => [                          // необязательно: доп. уровни после страны
 *       ['label' => $region_name],
 *       ['label' => $resort_name, 'url' => $resort_link],
 *     ],
 *     'class' => 'single-excursion-location', // необязательный доп. класс
 *   ]);
 *
 * @var array $args
 */

$country_id = isset($args['country_id']) ? (int) $args['country_id'] : 0;
$extra_class = isset($args['class']) ? trim((string) $args['class']) : '';

$parts = [];

if ($country_id > 0) {
  $parts[] = [
    'label' => get_the_title($country_id),
    'url' => get_permalink($country_id),
  ];
}

foreach ((array) ($args['parts'] ?? []) as $part) {
  $label = is_array($part) ? trim((string) ($part['label'] ?? '')) : trim((string) $part);
  if ($label === '') {
    continue;
  }
  /* Регион и курорт часто совпадают (Лондон / Лондон) — не дублируем. */
  foreach ($parts as $existing) {
    if (mb_strtolower($existing['label']) === mb_strtolower($label)) {
      continue 2;
    }
  }
  $parts[] = [
    'label' => $label,
    'url' => is_array($part) ? (string) ($part['url'] ?? '') : '',
  ];
}

if (empty($parts)) {
  return;
}

$flag_url = isset($args['flag_url']) ? (string) $args['flag_url'] : '';
if ($flag_url === '' && $country_id > 0 && function_exists('bsi_get_country_flag_url')) {
  $flag_url = (string) bsi_get_country_flag_url($country_id);
}

$last_index = count($parts) - 1;
?>

<div class="location-line<?= $extra_class !== '' ? ' ' . esc_attr($extra_class) : ''; ?>">
  <?php if ($flag_url !== ''): ?>
    <span class="location-line-flag">
      <img src="<?= esc_url($flag_url); ?>" alt="" loading="lazy">
    </span>
  <?php endif; ?>

  <span class="location-line-text"><?php foreach ($parts as $index => $part): ?><?php if ($part['url'] !== ''): ?><a class="location-line-link" href="<?= esc_url($part['url']); ?>"><?= esc_html($part['label']); ?></a><?php else: ?><span class="location-line-item"><?= esc_html($part['label']); ?></span><?php endif; ?><?= $index < $last_index ? ', ' : ''; ?><?php endforeach; ?></span>
</div>
