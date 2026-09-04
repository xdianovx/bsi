<?php

/**
 * Удобства отеля. Длинный список сворачивается до двух рядов.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$amenities = $view['amenities'] ?? [];

if (!$amenities) {
  return;
}

$limit = 12;
$rest = max(0, count($amenities) - $limit);
?>

<section class="hp-section hp-amenities" id="hotel-amenities">
  <div class="container">
    <h2 class="h2 hp-section__title">Удобства</h2>

    <ul class="hp-amenities__list js-hotel-amenities">
      <?php foreach ($amenities as $index => $amenity): ?>
        <li class="hp-amenities__item<?= $index >= $limit ? ' is-hidden' : ''; ?>">
          <?php if (!empty($amenity['icon'])): ?>
            <img class="hp-amenities__icon" src="<?= esc_url($amenity['icon']); ?>" alt="" loading="lazy">
          <?php else: ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
          <?php endif; ?>
          <?= esc_html($amenity['name']); ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($rest): ?>
      <button class="btn-expand hp-amenities__more js-hotel-amenities-more" type="button">
        Показать все удобства (<?= $rest; ?>)
      </button>
    <?php endif; ?>
  </div>
</section>
