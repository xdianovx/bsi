<?php

/**
 * Галерея-мозаика — общая для страны, отеля, достопримечательности, образования.
 *
 * Одно крупное фото и до четырёх мелких; остальные снимки лежат скрытыми
 * ссылками и открываются в том же fancybox-альбоме. Раньше здесь был
 * Swiper с превью-полосой, но мозаика показывает больше кадров сразу
 * и не требует JS для отрисовки.
 *
 * Картинки выводятся через bsi_picture(): WordPress проставляет ширину и
 * высоту, поэтому место резервируется до загрузки и макет не дёргается
 * (CLS 0,283 → 0,004, см. wiki/docs/seo-audit-2026-09-14.md, C3).
 *
 * @package bsi
 */

declare(strict_types=1);

$gallery = $args['gallery'] ?? [];
if (empty($gallery) || !is_array($gallery)) {
  return;
}

/** Голые URL без файла отбрасываем, чтобы не рвать сетку пустыми плитками. */
$gallery = array_values(array_filter($gallery, static function ($item): bool {
  return is_array($item) && !empty($item['url']);
}));

if (!$gallery) {
  return;
}

$id = $args['id'] ?? uniqid('gallery_');
$title = $args['title'] ?? '';

/** Крупный слайд занимает две трети сетки, мелкие — по шестой части. */
$main_sizes = '(max-width: 767px) 100vw, 640px';
$thumb_sizes = '(max-width: 767px) 50vw, 320px';

$main = $gallery[0];
$thumbs = array_slice($gallery, 1, 4);
$hidden = array_slice($gallery, 5);
$rest = count($hidden);

/**
 * ID вложения из элемента ACF-галереи; 0 — если пришёл голый URL.
 */
$attachment_id = static function (array $item): int {
  return (int) ($item['ID'] ?? $item['id'] ?? 0);
};

/**
 * Плитка галереи: ссылка в fancybox с картинкой внутри.
 */
$render_item = static function (array $item, string $size, array $attrs, string $sizes, string $gallery_id, string $class) use ($attachment_id): void {
  $url = $item['url'] ?? '';
  $alt = $item['alt'] ?? '';
  $item_id = $attachment_id($item);
  $attrs['alt'] = $alt;
  ?>
  <a class="<?= esc_attr($class); ?>" href="<?= esc_url($url); ?>" data-fancybox="<?= esc_attr($gallery_id); ?>">
    <?php if ($item_id > 0 && function_exists('bsi_picture')): ?>
      <?= bsi_picture($item_id, $size, $attrs, $sizes); ?>
    <?php else: ?>
      <img src="<?= esc_url($url); ?>" alt="<?= esc_attr($alt); ?>"
        loading="<?= esc_attr($attrs['loading'] ?? 'lazy'); ?>" decoding="async">
    <?php endif; ?>
  </a>
<?php
};
?>

<div class="ui-gallery ui-gallery--<?= $thumbs ? 'mosaic' : 'single'; ?>" data-gallery-id="<?= esc_attr($id); ?>">

  <?php if (!empty($title)): ?>
    <h2 class="h2 ui-gallery__title"><?= esc_html($title); ?></h2>
  <?php endif; ?>

  <div class="ui-gallery__grid">
    <?php
    /* Первое фото видно сразу, остальные ждут прокрутки. */
    $render_item(
      $main,
      'large',
      ['decoding' => 'async', 'loading' => 'eager', 'sizes' => $main_sizes],
      $main_sizes,
      (string) $id,
      'ui-gallery__item ui-gallery__item--main'
    );
    ?>

    <?php foreach ($thumbs as $index => $item): ?>
      <div class="ui-gallery__cell">
        <?php
        $render_item(
          $item,
          'medium_large',
          ['decoding' => 'async', 'loading' => 'lazy', 'sizes' => $thumb_sizes],
          $thumb_sizes,
          (string) $id,
          'ui-gallery__item'
        );
        ?>

        <?php if ($rest > 0 && $index === count($thumbs) - 1): ?>
          <span class="ui-gallery__more">Ещё <?= esc_html((string) $rest); ?> фото</span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php foreach ($hidden as $item): ?>
      <a class="ui-gallery__hidden" href="<?= esc_url($item['url']); ?>" data-fancybox="<?= esc_attr($id); ?>"
        tabindex="-1"></a>
    <?php endforeach; ?>
  </div>

</div>
