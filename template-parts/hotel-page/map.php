<?php

/**
 * Карта расположения отеля. Рисуется только при известных координатах;
 * сам холст подхватывает общий модуль js/modules/maps.js по data-атрибутам.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$map = $view['map'] ?? null;

if (!$map) {
  return;
}
?>

<section class="hp-section hp-map map-section" id="hotel-map">
  <div class="container">
    <h2 class="h2 hp-section__title">На карте</h2>
    <div class="hp-map__canvas hotel-map"
         id="hotel-map-container"
         data-lat="<?= esc_attr((string) $map['lat']); ?>"
         data-lng="<?= esc_attr((string) $map['lng']); ?>"
         data-zoom="<?= esc_attr((string) ($map['zoom'] ?? 14)); ?>"></div>
  </div>
</section>
