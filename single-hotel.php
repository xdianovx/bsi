<?php
$post_id = get_the_ID();
$gallery = function_exists('get_field') ? get_field('gallery', $post_id) : [];
$rating = function_exists('get_field') ? get_field('rating', $post_id) : '';

$country_id = function_exists('get_field') ? get_field('hotel_country', $post_id) : 0;
$country_id = is_array($country_id) ? (int) reset($country_id) : (int) $country_id;

$country_title = '';
$country_permalink = '';
$country_visa = '';
$country_flag = '';

if ($country_id) {
  $country_title = get_the_title($country_id);
  $country_permalink = get_permalink($country_id);
  $country_visa = function_exists('get_field') ? get_field('is_visa', $country_id) : '';
  $country_flag = function_exists('get_field') ? get_field('flag', $country_id) : '';
}

$region_terms = get_the_terms($post_id, 'region');
$region_term = (!empty($region_terms) && !is_wp_error($region_terms)) ? $region_terms[0] : null;

$resort_terms = get_the_terms($post_id, 'resort');
$resort_term = (!empty($resort_terms) && !is_wp_error($resort_terms)) ? $resort_terms[0] : null;

$region_name = $region_term ? $region_term->name : '';
$resort_name = $resort_term ? $resort_term->name : '';

$region_link = $region_term ? get_term_link($region_term) : '';
$resort_link = $resort_term ? get_term_link($resort_term) : '';

$city = trim((string) (function_exists('get_field') ? get_field('hotel_city', $post_id) : ''));
$hotel_opened_at_raw = trim((string) (function_exists('get_field') ? get_field('hotel_opened_at', $post_id) : ''));
$hotel_renovated_at_raw = trim((string) (function_exists('get_field') ? get_field('hotel_renovated_at', $post_id) : ''));

$format_hotel_month_year = static function (string $value): string {
  if ($value === '') {
    return '';
  }

  $date = DateTime::createFromFormat('Y-m', $value);
  if ($date instanceof DateTime) {
    return $date->format('m/Y');
  }

  if (preg_match('/^\d{4}-\d{2}$/', $value)) {
    return substr($value, 5, 2) . '/' . substr($value, 0, 4);
  }

  return $value;
};

$hotel_opened_at = $format_hotel_month_year($hotel_opened_at_raw);
$hotel_renovated_at = $format_hotel_month_year($hotel_renovated_at_raw);

$parts = array_filter([$country_title, $region_name, $resort_name, $city]);
$address_line = implode(', ', $parts);
$phone = trim((string) get_field('phone', get_the_ID()));
$address = trim((string) get_field('address', get_the_ID()));
$website = trim((string) get_field('website', get_the_ID()));
$excerpt = get_the_excerpt($post_id);

$map_coords = function_exists('get_field') ? bsi_parse_map_coordinates(get_field('map_coordinates', $post_id)) : null;
if ($map_coords) {
  $map_lat = (string) $map_coords['lat'];
  $map_lng = (string) $map_coords['lng'];
} else {
  $map_lat = function_exists('get_field') ? get_field('map_lat', $post_id) : '';
  $map_lng = function_exists('get_field') ? get_field('map_lng', $post_id) : '';
}
$map_zoom = function_exists('get_field') ? get_field('map_zoom', $post_id) : 14;

get_header();
?>

<main>

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>


  <section class="">
    <div class="container">
      <div class="single-hotel__title-wrap">
        <div class="title-rating__wrap">
          <div class="single-hotel__rating">
            <?php if ($rating): ?>
              <div class="single-hotel__rating-stars rating-stars">
                <div class="stars-rating">
                  <?php
                  for ($i = 1; $i <= 5; $i++):
                    if ($i <= $rating) {
                      echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star-icon lucide-star filled"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>';
                    } else {
                      echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star-icon lucide-star"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>';
                    }
                  endfor;
                  ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <h1 class="h1 single-hotel__title">Отель <?php the_title() ?></h1>

        </div>

        <button class="print-btn single-hotel__print-btn"
                data-micromodal-trigger="modal-hotel-pdf">
          <svg xmlns="http://www.w3.org/2000/svg"
               width="24"
               height="24"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               stroke-width="2"
               stroke-linecap="round"
               stroke-linejoin="round"
               class="lucide lucide-printer-icon lucide-printer">
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6" />
            <rect x="6"
                  y="14"
                  width="12"
                  height="8"
                  rx="1" />
          </svg>
        </button>
      </div>

      <div class="single-hotel__top-line">
        <?php if ($country_title || $region_term || $resort_term || $city): ?>
          <?php
          $items = [];

          if ($country_title) {
            $items[] = $country_permalink
              ? '<a class="single-hotel__address-link" href="' . esc_url($country_permalink) . '">' . esc_html($country_title) . '</a>'
              : '<span>' . esc_html($country_title) . '</span>';
          }

          if ($region_term) {
            $region_link = get_term_link($region_term);
            $items[] = !is_wp_error($region_link)
              ? '<a class="single-hotel__address-link" href="' . esc_url($region_link) . '">' . esc_html($region_term->name) . '</a>'
              : '<span>' . esc_html($region_term->name) . '</span>';
          }

          if ($resort_term) {
            $resort_link = get_term_link($resort_term);
            $items[] = !is_wp_error($resort_link)
              ? '<a class="single-hotel__address-link" href="' . esc_url($resort_link) . '">' . esc_html($resort_term->name) . '</a>'
              : '<span>' . esc_html($resort_term->name) . '</span>';
          }

          if ($city) {
            $items[] = '<span>' . esc_html($city) . '</span>';
          }
          ?>

          <div class="single-hotel__address">
            <?php if (!empty($country_flag)): ?>
              <img src="<?= esc_url($country_flag); ?>"
                   alt="">
            <?php endif; ?>

            <div class="single-hotel__address-text">
              <?= implode(', ', $items); ?>
            </div>
          </div>
        <?php endif; ?>


      </div>

      <div class="single-hotel__amenities">

        <?php
        $amenities = get_the_terms(get_the_ID(), 'amenity');

        if (!empty($amenities) && !is_wp_error($amenities)) {
          foreach ($amenities as $t) {
            $icon = function_exists('get_field') ? get_field('amenity_icon', 'term_' . $t->term_id) : null;
            $icon_url = is_array($icon) && !empty($icon['url']) ? $icon['url'] : '';

            echo '<span class="hotel-tag">';
            if ($icon_url) {
              echo '<img class="hotel-tag__icon" src="' . esc_url($icon_url) . '" alt="" loading="lazy">';
            }
            echo '<span class="hotel-tag__text">' . esc_html($t->name) . '</span>';
            echo '</span>';
          }
        }
        ?>
      </div>

      <div class="page-country__descr">
        <?= $excerpt ?>

      </div>

    </div>
  </section>

  <section class="single-hotel__gallery-section">
    <div class="container">
      <div class="country-page__gallery">
        <?php
        get_template_part('template-parts/sections/gallery', null, [
          'gallery' => $gallery,
          'id' => 'hotel_' . get_the_ID(),
        ]);
        ?>
      </div>



    </div>
  </section>




  <section class="single-hotel__content">
    <div class="container">

      <div class="single-hotel__content__wrap">

        <div class="hotel-content editor-content">
          <?php the_content() ?>

          <?php
          // Иконки Lucide (inline SVG) для заголовков секций. Ключ — имя из lucide.dev.
          $section_icon_paths = [
            'building-2' => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
            'utensils'   => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
            'wine'       => '<path d="M8 22h8"/><path d="M7 10h10"/><path d="M12 15v7"/><path d="M12 15a5 5 0 0 0 5-5c0-2-.5-4-2-8H9c-1.5 4-2 6-2 8a5 5 0 0 0 5 5Z"/>',
            'sparkles'   => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>',
            'dumbbell'   => '<path d="M14.4 14.4 9.6 9.6"/><path d="M18.657 21.485a2 2 0 1 1-2.829-2.828l-1.767 1.768a2 2 0 1 1-2.829-2.829l6.364-6.364a2 2 0 1 1 2.829 2.829l-1.768 1.767a2 2 0 1 1 2.828 2.829z"/><path d="m21.5 21.5-1.4-1.4"/><path d="M3.9 3.9 2.5 2.5"/><path d="M6.404 12.768a2 2 0 1 1-2.829-2.829l1.768-1.767a2 2 0 1 1-2.828-2.829l2.828-2.828a2 2 0 1 1 2.829 2.828l1.767-1.768a2 2 0 1 1 2.829 2.829z"/>',
            'baby'       => '<path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M19 6.3a9 9 0 0 1 1.8 3.9 2 2 0 0 1 0 3.6 9 9 0 0 1-17.6 0 2 2 0 0 1 0-3.6A9 9 0 0 1 12 3c2 0 3.5 1.1 3.5 2.5s-.9 2.5-2 2.5c-.8 0-1.5-.4-1.5-1"/>',
            'briefcase'  => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
            'umbrella'   => '<path d="M22 12a10.06 10.06 1 0 0-20 0Z"/><path d="M12 12v8a2 2 0 0 0 4 0"/><path d="M12 2v1"/>',
            'bed-double' => '<path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><path d="M12 4v6"/><path d="M2 18h20"/>',
          ];

          $hotel_sections = [
            'sec_infrastructure' => ['title' => 'Инфраструктура',     'icon' => 'building-2'],
            'sec_meals'          => ['title' => 'Питание',            'icon' => 'utensils'],
            'sec_restaurants'    => ['title' => 'Рестораны и бары',   'icon' => 'wine'],
            'sec_spa'            => ['title' => 'Spa и оздоровление', 'icon' => 'sparkles'],
            'sec_sport'          => ['title' => 'Спорт и развлечения', 'icon' => 'dumbbell'],
            'sec_kids'           => ['title' => 'Для детей',          'icon' => 'baby'],
            'sec_mice'           => ['title' => 'MICE',               'icon' => 'briefcase'],
            'sec_beach'          => ['title' => 'Пляж',               'icon' => 'umbrella'],
            'sec_rooms'          => ['title' => 'Номера',             'icon' => 'bed-double'],
          ];

          foreach ($hotel_sections as $sec_name => $sec):
            $sec_content = function_exists('get_field') ? get_field($sec_name) : '';
            if (empty(trim((string) $sec_content))) {
              continue;
            }
            $sec_id       = 'hotel-' . str_replace('sec_', '', $sec_name);
            $sec_title    = $sec['title'];
            $sec_icon_svg = $section_icon_paths[$sec['icon']] ?? '';
          ?>
            <section class="hotel-section" id="<?= esc_attr($sec_id); ?>">
              <h2 class="hotel-section__title">
                <?php // Иконки временно скрыты
                if (false && $sec_icon_svg): ?>
                  <svg class="hotel-section__icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $sec_icon_svg ?></svg>
                <?php endif; ?>
                <span><?= esc_html($sec_title); ?></span>
              </h2>
              <div class="hotel-section__body"><?= $sec_content; ?></div>
            </section>
          <?php endforeach; ?>
        </div>

        <aside class="hotel-aside">

          <div class="hotel-widget">
            <div class="hotel-widget__address"> <?php if ($address_line): ?>
                <div class="single-hotel__address single-hotel__address--widget">
                  <?php if ($country_flag): ?>
                    <img class="single-hotel__address-flag" src="<?= esc_url($country_flag); ?>"
                         alt="">
                  <?php endif; ?>

                  <div class="single-hotel__address-text"><?= esc_html($address_line); ?></div>
                </div>
              <?php endif; ?>
            </div>

            <div class="hotel-widget__title-wrap">

              <p class="hotel-widget__title"><?php the_title() ?></p>
            </div>


            <?php
            $check_in_time = function_exists('get_field') ? get_field('check_in_time', get_the_ID()) : '';
            $check_out_time = function_exists('get_field') ? get_field('check_out_time', get_the_ID()) : '';
            ?>

            <?php if ($check_in_time || $check_out_time): ?>
              <div class="hotel-widget__checkin-time">
                <?php if ($check_in_time): ?>
                  <div class="hotel-widget__checkin-item">
                    <span class="hotel-widget__checkin-label">Заезд:</span>
                    <span class="hotel-widget__checkin-value numfont">
                      <?= esc_html($check_in_time); ?>
                    </span>
                  </div>
                <?php endif; ?>
                <?php if ($check_out_time): ?>
                  <div class="hotel-widget__checkin-item">
                    <span class="hotel-widget__checkin-label">Выезд:</span>
                    <span class="hotel-widget__checkin-value numfont">
                      <?= esc_html($check_out_time); ?>
                    </span>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>



            <?php
            $booking_url_tour = trim((string) get_field('booking_url', get_the_ID()));
            $booking_url_hotel = trim((string) get_field('booking_url_hotel_only', get_the_ID()));
            $price = trim((string) get_field('price', get_the_ID()));
            ?>


            <div class="hotel-widget__btns_wrap">

              <?php if ($booking_url_tour): ?>
                <div class="hotel-widget__btns">
                  <p class="hotel-widget__booking-label">Тур - отель с перелетом</p>
                  <a href="<?= esc_url($booking_url_tour); ?>"
                     class="btn btn-accent hotel-widget__btn-book sm"
                     target="_blank"
                     rel="noopener nofollow">Забронировать</a>
                </div>
              <?php endif; ?>

              <?php if ($booking_url_hotel): ?>
                <div class="hotel-widget__btns">
                  <p class="hotel-widget__booking-label">Отель без перелета</p>
                  <a href="<?= esc_url($booking_url_hotel); ?>"
                     class="btn btn-accent hotel-widget__btn-book sm"
                     target="_blank"
                     rel="noopener nofollow">Забронировать</a>
                </div>
              <?php endif; ?>

              <?php if ($price): ?>
                <div class="hotel-widget__booking-price numfont">
                  <?= format_price_text($price); ?> ₽
                </div>
              <?php endif; ?>

            </div>
          </div>


          <?php if ($hotel_opened_at || $hotel_renovated_at): ?>
            <div class="hotel-widget">
              <p class="hotel-widget__title">История отеля</p>
              <div class="hotel-widget__distances">
                <?php if ($hotel_opened_at): ?>
                  <div class="hotel-widget__distance-item">
                    <span class="hotel-widget__distance-key">Построен:</span>
                    <span class="hotel-widget__distance-value numfont">
                      <?= esc_html($hotel_opened_at); ?>
                    </span>
                  </div>
                <?php endif; ?>

                <?php if ($hotel_renovated_at): ?>
                  <div class="hotel-widget__distance-item">
                    <span class="hotel-widget__distance-key">Реновация:</span>
                    <span class="hotel-widget__distance-value numfont">
                      <?= esc_html($hotel_renovated_at); ?>
                    </span>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (function_exists('have_rows') && have_rows('hotel_distances', $post_id)): ?>
            <div class="hotel-widget">
              <p class="hotel-widget__title">Расстояния</p>
              <div class="hotel-widget__distances">
                <?php while (have_rows('hotel_distances', $post_id)):
                  the_row(); ?>
                  <?php
                  $key = trim((string) get_sub_field('key'));
                  $value = trim((string) get_sub_field('value'));
                  ?>
                  <?php if ($key || $value): ?>
                    <div class="hotel-widget__distance-item">
                      <?php if ($key): ?>
                        <span class="hotel-widget__distance-key">
                          <?= esc_html($key); ?>
                        </span>
                      <?php endif; ?>
                      <?php if ($value): ?>
                        <span class="hotel-widget__distance-value">
                          <?= esc_html($value); ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                <?php endwhile; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($phone || $address || $website): ?>
            <div class="hotel-widget">
              <p class="hotel-widget__title">Контакты отеля</p>
              <div class="hotel-widget__contacts">
                <?php if ($phone): ?>
                  <div class="hotel-widget__phone hotel-widget__contacts-item">
                    <a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $phone)); ?>">
                      <img src="<?= get_template_directory_uri() ?>/img/icons/hotel/call.svg"
                           alt="">
                      <span><?= esc_html($phone); ?></span>
                    </a>
                  </div>
                <?php endif; ?>

                <?php if ($address): ?>
                  <div class="hotel-widget__address hotel-widget__contacts-item">
                    <img src="<?= get_template_directory_uri() ?>/img/icons/hotel/home.svg"
                         alt="">
                    <span><?= esc_html($address); ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($website): ?>
                  <div class="hotel-widget__site hotel-widget__contacts-item">
                    <a href="<?= esc_url($website); ?>"
                       target="_blank"
                       rel="nofollow noopener">
                      <img src="<?= get_template_directory_uri() ?>/img/icons/hotel/url.svg"
                           alt="">
                      <span>Сайт отеля</span>
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>



          <?php if ($map_lat && $map_lng): ?>

            <div class="hotel-widget">
              <a href="#hotel-map"
                 class="btn btn-black sm hotel-widget__btn-map">Смотреть на карте</a>
            </div>
          <?php endif; ?>
        </aside>

      </div>
    </div>
  </section>

  <?php if ($map_lat && $map_lng): ?>
    <?php
    $map_zoom_safe = max(1, min(17, (int) $map_zoom));
    $yandex_map_url = 'https://yandex.ru/maps/?ll=' . rawurlencode((string) $map_lng) . '%2C' . rawurlencode((string) $map_lat) . '&z=' . $map_zoom_safe . '&pt=' . rawurlencode((string) $map_lng) . ',' . rawurlencode((string) $map_lat);
    ?>
    <section class="single-hotel__map-section map-section"
             id="hotel-map">
      <div class="container">
        <h2 class="h2 map-section__title">Расположение</h2>
        <?php $marker_icon_url = get_template_directory_uri() . '/img/icons/hotel/home-map.svg'; ?>
        <div class="hotel-map map-wrap"
             id="hotel-map-container"
             data-lat="<?php echo esc_attr($map_lat); ?>"
             data-lng="<?php echo esc_attr($map_lng); ?>"
             data-zoom="<?php echo esc_attr($map_zoom); ?>"
             data-marker-icon="<?php echo esc_url($marker_icon_url); ?>"
             style="width: 100%; height: 400px;"></div>

      </div>
    </section>
  <?php endif; ?>

  <section>
    <div class="container">
      <div class="callout callout-neutral single-hotel__warn">
        <h3 class="callout__title">
          Информация об отеле носит ознакомительный характер и подвержена периодическим изменениям.
        </h3>

        <p>
          Перед бронированием необходимо обязательно уточнить актуальную информацию об оказываемых отелем услугах и его
          номерном фонде у менеджеров туроператора или на официальном сайте отеля.
        </p>


      </div>
    </div>
  </section>




</main>

<?php
// get_sidebar();
get_footer();

?>