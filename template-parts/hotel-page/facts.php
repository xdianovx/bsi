<?php

/**
 * Две секции из одного набора фактов: «Расстояния» (до пляжа, центра, аэропорта)
 * и «Важно знать» (заезд, выезд, ограничения).
 *
 * Иконки строк ждём от хаба: как только он начнёт отдавать URL в `icon`,
 * картинка появится сама — своих подстановок здесь нет.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$facts = $view['facts'] ?? [];

if (!$facts) {
  return;
}

$sections = [
  ['id' => 'hotel-distances', 'title' => 'Расстояния', 'items' => bsi_hotel_view_facts_of($facts, 'distance')],
  ['id' => 'hotel-facts', 'title' => 'Важно знать', 'items' => bsi_hotel_view_facts_of($facts, 'note')],
];
?>

<?php foreach ($sections as $section): ?>
  <?php if (!$section['items']) {
    continue;
  } ?>

  <section class="hp-section hp-facts" id="<?= esc_attr($section['id']); ?>">
    <div class="container">
      <h2 class="h2 hp-section__title"><?= esc_html($section['title']); ?></h2>

      <ul class="hp-facts__list">
        <?php foreach ($section['items'] as $fact): ?>
          <li class="hp-facts__item">
            <span class="hp-facts__label">
              <?php if (!empty($fact['icon'])): ?>
                <img class="hp-facts__icon" src="<?= esc_url($fact['icon']); ?>" alt="" loading="lazy">
              <?php endif; ?>
              <?= esc_html($fact['label']); ?>
            </span>
            <b class="hp-facts__value"><?= esc_html($fact['value']); ?></b>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
<?php endforeach; ?>
