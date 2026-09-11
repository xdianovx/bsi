<?php

/**
 * Плитки фактов о стране — общий компонент `.fact-tiles` (scss/ui/fact-tiles.scss),
 * тот же, что на странице курорта.
 *
 * Выводим только иконку и значение — расшифровка («Столица», «Валюта»)
 * остаётся в `title`, чтобы строка не разрасталась подписями.
 */

$country_id = (int) get_the_ID();

$fields = [
  ['field' => 'stolicza', 'icon' => 'landmark', 'label' => 'Столица'],
  ['field' => 'chislennost', 'icon' => 'users', 'label' => 'Население'],
  ['field' => 'yazyk', 'icon' => 'languages', 'label' => 'Язык'],
  ['field' => 'chasovoj_poyas', 'icon' => 'clock', 'label' => 'Время'],
  ['field' => 'valyuta', 'icon' => 'wallet', 'label' => 'Валюта'],
];

$facts = [];

if (function_exists('get_field')) {
  foreach ($fields as $item) {
    $value = trim((string) get_field($item['field'], $country_id));
    if ($value !== '') {
      $facts[] = ['icon' => $item['icon'], 'value' => $value, 'label' => $item['label']];
    }
  }
}

if (empty($facts)) {
  return;
}
?>

<ul class="fact-tiles country-page__top-info">
  <?php foreach ($facts as $fact): ?>
    <li class="fact-tile" title="<?= esc_attr($fact['label']); ?>">
      <span class="fact-tile-icon">
        <?= bsi_lucide_icon($fact['icon'], [
          'width' => '20',
          'height' => '20',
          'stroke' => 'currentColor',
          'stroke-width' => '1.5',
        ]); ?>
      </span>
      <span class="fact-tile-value"><?= esc_html($fact['value']); ?></span>
    </li>
  <?php endforeach; ?>
</ul>
