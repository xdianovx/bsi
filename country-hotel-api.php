<?php
/**
 * Карточка отеля из хаба BSIHOTELS.
 * Подключается из inc/hotels-api/hotel-page.php по адресу
 * /country/{country}/hotel/{slug}/, когда отеля нет в WordPress.
 */

global $bsi_hotels_api_hotel;

$hotel = $bsi_hotels_api_hotel['hotel'] ?? [];
$country = $bsi_hotels_api_hotel['country'] ?? null;

if (!$hotel || !$country instanceof WP_Post) {
  return;
}

$catalog_url = bsi_hotels_api_catalog_url($country);
$city = (string) ($hotel['city']['name'] ?? '');
$city_slug = (string) ($hotel['city']['slug'] ?? '');
$stars = (int) ($hotel['stars'] ?? 0);
$photos = is_array($hotel['photos'] ?? null) ? $hotel['photos'] : [];
$amenities = is_array($hotel['amenities'] ?? null) ? $hotel['amenities'] : [];
$rooms = is_array($hotel['room_types'] ?? null) ? $hotel['room_types'] : [];
$min_rate = bsi_hotels_api_min_rate($hotel);
$distances = is_array($hotel['distances'] ?? null) ? $hotel['distances'] : [];

$distance_labels = [
  'beach_m' => 'До пляжа',
  'center_m' => 'До центра',
  'airport_m' => 'До аэропорта',
];

get_header();
?>

<main class="site-main">

  <?php
  /* Крошки — общим механизмом темы; цепочку подставляет
     фильтр wpseo_breadcrumb_links в inc/hotels-api/seo.php. */
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>', '</p></div></div>');
  }
  ?>

  <section>
    <div class="container">
      <div class="api-hotel-page">

        <header class="api-hotel-page__head">
          <h1 class="h1">
            <?= esc_html($hotel['name']); ?>
            <?php if ($stars): ?>
              <span class="hotel-rating api-hotel-page__stars"><?= $stars; ?>*</span>
            <?php endif; ?>
          </h1>

          <p class="api-hotel-page__place">
            <?php if ($city !== ''): ?>
              <a href="<?= esc_url(add_query_arg('kurort', $city_slug, $catalog_url)); ?>"><?= esc_html($city); ?></a>,
            <?php endif; ?>
            <?= esc_html(get_the_title($country)); ?>
            <?php if (!empty($hotel['address'])): ?>
              — <?= esc_html($hotel['address']); ?>
            <?php endif; ?>
          </p>

          <?php if ($min_rate): ?>
            <p class="api-hotel-page__price">
              от <?= esc_html(bsi_hotels_api_format_price([
                'amount' => $min_rate['amount'],
                'currency' => $min_rate['currency'],
              ])); ?> за ночь
            </p>
          <?php endif; ?>
        </header>

        <?php if ($photos): ?>
          <div class="api-hotel-page__gallery">
            <?php foreach (array_slice($photos, 0, 8) as $photo): ?>
              <img src="<?= esc_url($photo['url'] ?? ''); ?>"
                   alt="<?= esc_attr($photo['caption'] ?? $hotel['name']); ?>"
                   loading="lazy"
                   decoding="async">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($hotel['description'])): ?>
          <div class="editor-content api-hotel-page__description">
            <?= wpautop(esc_html($hotel['description'])); ?>
          </div>
        <?php endif; ?>

        <?php
        $distance_rows = array_filter($distances, static fn($v) => (int) $v > 0);
        if ($distance_rows || !empty($hotel['beach_line']) || !empty($hotel['check_in_time'])): ?>
          <ul class="api-hotel-page__facts">
            <?php foreach ($distance_rows as $key => $meters): ?>
              <li>
                <span><?= esc_html($distance_labels[$key] ?? $key); ?></span>
                <b><?= esc_html(number_format((int) $meters, 0, ',', ' ')); ?> м</b>
              </li>
            <?php endforeach; ?>

            <?php if (!empty($hotel['beach_line'])): ?>
              <li><span>Линия пляжа</span><b><?= (int) $hotel['beach_line']; ?></b></li>
            <?php endif; ?>

            <?php if (!empty($hotel['check_in_time'])): ?>
              <li><span>Заезд</span><b><?= esc_html($hotel['check_in_time']); ?></b></li>
            <?php endif; ?>

            <?php if (!empty($hotel['check_out_time'])): ?>
              <li><span>Выезд</span><b><?= esc_html($hotel['check_out_time']); ?></b></li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>

        <?php if ($amenities): ?>
          <section class="api-hotel-page__section">
            <h2 class="h2">Удобства</h2>
            <ul class="api-hotel-page__amenities">
              <?php foreach ($amenities as $amenity): ?>
                <li><?= esc_html($amenity['name'] ?? ''); ?></li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <?php if ($rooms): ?>
          <section class="api-hotel-page__section">
            <h2 class="h2">Номера</h2>

            <div class="api-hotel-page__rooms">
              <?php foreach ($rooms as $room):
                $room_rate = bsi_hotels_api_min_rate(['room_types' => [$room]]); ?>
                <article class="api-room">
                  <h3 class="api-room__title"><?= esc_html($room['name'] ?? ''); ?></h3>

                  <p class="api-room__meta">
                    <?php if (!empty($room['area_m2'])): ?>
                      <?= esc_html($room['area_m2']); ?> м²,
                    <?php endif; ?>
                    до <?= (int) ($room['max_total'] ?? 0); ?> гостей
                  </p>

                  <?php if (!empty($room['description'])): ?>
                    <p class="api-room__descr"><?= esc_html($room['description']); ?></p>
                  <?php endif; ?>

                  <?php if ($room_rate): ?>
                    <p class="api-room__price">
                      от <?= esc_html(bsi_hotels_api_format_price([
                        'amount' => $room_rate['amount'],
                        'currency' => $room_rate['currency'],
                      ])); ?> за ночь
                    </p>
                  <?php else: ?>
                    <p class="api-room__price api-room__price--empty">Цену уточним по запросу</p>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <p class="api-hotel-page__back">
          <a href="<?= esc_url($catalog_url); ?>">Все отели: <?= esc_html(get_the_title($country)); ?></a>
        </p>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
