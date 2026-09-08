<?php

/**
 * Шапка страницы отеля: название, звёзды, место, цена и переход к номерам.
 *
 * @var array $args ['view' => view-модель, см. inc/hotel-page/view.php]
 */

$view = $args['view'] ?? [];
if (!$view) {
  return;
}

$stars = (int) $view['stars'];
$amenities = bsi_hotel_view_popular_amenities($view['amenities'], 8);
?>

<section class="hp-head">
  <div class="container">
    <div class="hp-head__top">
      <div class="hp-head__main">
        <?php if ($stars): ?>
          <?php /* Звёздность всегда пятью иконками — как в карточке каталога. */ ?>
          <div class="hp-head__stars" title="<?= (int) $stars; ?> из 5">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <?= sprintf('<svg class="hp-head__star%s" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>', $i <= $stars ? ' is-on' : ''); ?>
            <?php endfor; ?>
          </div>
        <?php endif; ?>

        <h1 class="h1 hp-head__title"><?= esc_html($view['name']); ?></h1>

        <?php if ($view['place'] || $view['address']): ?>
          <p class="hp-head__place">
            <?php /* Флаг страны вместо значка метки: он и так есть у страны
                     в админке, и место читается быстрее. Пина держим как запас,
                     когда флаг не залит. */ ?>
            <?php if ($view['flag']): ?>
              <img class="hp-head__flag" src="<?= esc_url($view['flag']); ?>" alt="" loading="lazy" decoding="async">
            <?php else: ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            <?php endif; ?>
            <?php
            $chunks = [];
            foreach ($view['place'] as $place) {
              $chunks[] = $place['url']
                ? '<a href="' . esc_url($place['url']) . '">' . esc_html($place['label']) . '</a>'
                : '<span>' . esc_html($place['label']) . '</span>';
            }
            if ($view['address'] !== '') {
              $chunks[] = '<span>' . esc_html($view['address']) . '</span>';
            }
            echo implode('<span class="hp-head__sep">·</span>', $chunks);
            ?>
          </p>
        <?php endif; ?>

        <?php if (trim((string) $view['excerpt']) !== ''): ?>
          <div class="hp-head__excerpt editor-content"><?= wp_kses_post($view['excerpt']); ?></div>
        <?php endif; ?>

        <?php if ($amenities): ?>
          <ul class="hp-head__tags">
            <?php foreach ($amenities as $amenity): ?>
              <li class="hp-tag">
                <?php if (!empty($amenity['icon'])): ?>
                  <img class="hp-tag__icon" src="<?= esc_url($amenity['icon']); ?>" alt="" loading="lazy">
                <?php endif; ?>
                <?= esc_html($amenity['name']); ?>
              </li>
            <?php endforeach; ?>
            <?php if (count($view['amenities']) > count($amenities)): ?>
              <li class="hp-tag hp-tag--more">
                <a href="#hotel-amenities">Ещё <?= count($view['amenities']) - count($amenities); ?></a>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="hp-head__aside">
        <?php if ($view['price_from']): ?>
          <p class="hp-head__price-label">Цена за ночь от</p>
          <p class="hp-head__price"><?= esc_html(bsi_hotel_view_price($view['price_from'])); ?></p>
        <?php endif; ?>

        <?php if ($view['booking']): ?>
          <?php foreach ($view['booking'] as $booking): ?>
            <?php /* Подпись у кнопки не нужна: «Бронирование отеля» повторяет
                     то, что и так написано на самой кнопке. */ ?>
            <a class="btn btn-accent hp-head__cta"
               href="<?= esc_url($booking['url']); ?>"
               target="_blank"
               rel="nofollow noopener">Забронировать</a>
          <?php endforeach; ?>
        <?php elseif ($view['rooms']): ?>
          <a class="btn btn-accent hp-head__cta" href="#hotel-rooms">Выбрать номер</a>
        <?php else: ?>
          <a class="btn btn-accent hp-head__cta" href="#hotel-request">Уточнить цену</a>
        <?php endif; ?>

        <?php if ($view['pdf_modal']): ?>
          <button class="print-btn hp-head__print" data-micromodal-trigger="<?= esc_attr($view['pdf_modal']); ?>" type="button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
            <span>Скачать PDF</span>
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
