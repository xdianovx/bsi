<?php

/**
 * Расположение отеля: текст о месте и карта. Одна секция — заголовок «Расположение»
 * не должен повторяться дважды. Карта рисуется только при известных координатах,
 * холст подхватывает общий модуль js/modules/maps.js по data-атрибутам.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$map = $view['map'] ?? null;
$note = trim((string) ($view['location_note'] ?? ''));

if (!$map && $note === '') {
  return;
}
?>

<section class="hp-section hp-location map-section" id="hotel-location">
  <div class="container">
    <h2 class="h2 hp-section__title">Расположение</h2>

    <?php if ($note !== ''): ?>
      <div class="editor-content hp-location__note"><?= wpautop(esc_html($note)); ?></div>
    <?php endif; ?>

    <?php if ($map): ?>
      <div class="hp-location__canvas hotel-map"
           id="hotel-map-container"
           data-lat="<?= esc_attr((string) $map['lat']); ?>"
           data-lng="<?= esc_attr((string) $map['lng']); ?>"
           data-zoom="<?= esc_attr((string) ($map['zoom'] ?? 14)); ?>"></div>
    <?php endif; ?>
  </div>
</section>
