<?php

/**
 * Галерея-мозаика: одно большое фото и до четырёх мелких.
 * Остальные снимки открываются во всплывающем просмотрщике.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$photos = $view['photos'] ?? [];

if (!$photos) {
  return;
}

$gallery_id = 'hotel-gallery-' . wp_rand(1000, 9999);
$main = $photos[0];
$thumbs = array_slice($photos, 1, 4);
$rest = max(0, count($photos) - 5);
$name = $view['name'] ?? '';
?>

<section class="hp-gallery hp-gallery--<?= count($thumbs) ? 'mosaic' : 'single'; ?>">
  <div class="container">
    <div class="hp-gallery__grid">
      <a class="hp-gallery__item hp-gallery__item--main"
         href="<?= esc_url($main['url']); ?>"
         data-fancybox="<?= esc_attr($gallery_id); ?>"
         data-caption="<?= esc_attr($main['caption'] ?: $name); ?>">
        <img src="<?= esc_url($main['url']); ?>"
             alt="<?= esc_attr($main['caption'] ?: $name); ?>"
             loading="eager"
             decoding="async">
      </a>

      <?php foreach ($thumbs as $index => $photo): ?>
        <a class="hp-gallery__item"
           href="<?= esc_url($photo['url']); ?>"
           data-fancybox="<?= esc_attr($gallery_id); ?>"
           data-caption="<?= esc_attr($photo['caption'] ?: $name); ?>">
          <img src="<?= esc_url($photo['url']); ?>"
               alt="<?= esc_attr($photo['caption'] ?: $name); ?>"
               loading="lazy"
               decoding="async">

          <?php if ($rest && $index === count($thumbs) - 1): ?>
            <span class="hp-gallery__more">Ещё <?= $rest; ?> фото</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>

      <?php foreach (array_slice($photos, 5) as $photo): ?>
        <a class="hp-gallery__hidden"
           href="<?= esc_url($photo['url']); ?>"
           data-fancybox="<?= esc_attr($gallery_id); ?>"
           data-caption="<?= esc_attr($photo['caption'] ?: $name); ?>"
           aria-hidden="true"
           tabindex="-1"></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
