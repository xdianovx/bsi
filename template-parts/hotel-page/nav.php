<?php

/**
 * Липкая навигация по секциям страницы. Строится только из тех секций,
 * которые реально попали на страницу.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$nav = $view ? bsi_hotel_view_nav($view) : [];

if (count($nav) < 2) {
  return;
}
?>

<nav class="hp-nav js-hotel-nav">
  <div class="container">
    <ul class="hp-nav__list">
      <?php foreach ($nav as $item): ?>
        <li>
          <a class="hp-nav__link" href="#<?= esc_attr($item['id']); ?>"><?= esc_html($item['label']); ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>
