<?php

/**
 * «Важно знать»: расстояния, время заезда и выезда, линия пляжа.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$facts = $view['facts'] ?? [];

if (!$facts) {
  return;
}
?>

<section class="hp-section hp-facts" id="hotel-facts">
  <div class="container">
    <h2 class="h2 hp-section__title">Важно знать</h2>

    <ul class="hp-facts__list">
      <?php foreach ($facts as $fact): ?>
        <li class="hp-facts__item">
          <span class="hp-facts__label"><?= esc_html($fact['label']); ?></span>
          <b class="hp-facts__value"><?= esc_html($fact['value']); ?></b>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
