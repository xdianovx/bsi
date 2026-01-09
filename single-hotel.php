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

$parts = array_filter([$country_title, $region_name, $resort_name, $city]);
$address_line = implode(', ', $parts);
$phone = trim((string) get_field('phone', get_the_ID()));
$address = trim((string) get_field('address', get_the_ID()));
$website = trim((string) get_field('website', get_the_ID()));
$excerpt = get_the_excerpt($post_id);

$map_lat = function_exists('get_field') ? get_field('map_lat', $post_id) : '';
$map_lng = function_exists('get_field') ? get_field('map_lng', $post_id) : '';
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
          <h1 class="h1 single-hotel__title"><?php the_title() ?></h1>

        </div>

        <button class="print-btn single-hotel__print-btn" data-micromodal-trigger="modal-hotel-pdf">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-printer-icon lucide-printer">
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6" />
            <rect x="6" y="14" width="12" height="8" rx="1" />
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
              <img src="<?= esc_url($country_flag); ?>" alt="">
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
        </div>

        <aside class="hotel-aside">

          <div class="hotel-widget">
            <div class="hotel-widget__address"> <?php if ($address_line): ?>
                <div class="single-hotel__address">
                  <?php if ($country_flag): ?>
                    <img src="<?= esc_url($country_flag); ?>" alt="">
                  <?php endif; ?>

                  <div><?= esc_html($address_line); ?></div>
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
            $price_text = trim((string) get_field('price_text', get_the_ID()));
            ?>


            <div class="hotel-widget__btns_wrap">

              <?php if ($booking_url_tour): ?>
                <div class="hotel-widget__btns">
                  <p class="hotel-widget__booking-label">Тур - отель с перелетом</p>
                  <a href="<?= esc_url($booking_url_tour); ?>" class="btn btn-accent hotel-widget__btn-book sm"
                    target="_blank" rel="noopener nofollow">Забронировать</a>
                </div>
              <?php endif; ?>

              <?php if ($booking_url_hotel): ?>
                <div class="hotel-widget__btns">
                  <p class="hotel-widget__booking-label">Отель без перелета</p>
                  <a href="<?= esc_url($booking_url_hotel); ?>" class="btn btn-accent hotel-widget__btn-book sm"
                    target="_blank" rel="noopener nofollow">Забронировать</a>
                </div>
              <?php endif; ?>

              <?php if ($price): ?>
                <div class="hotel-widget__booking-price numfont">
                  <?= format_price_text($price); ?> ₽<?php if ($price_text): ?> / <span><?= esc_html($price_text); ?></span><?php endif; ?>
                </div>
              <?php endif; ?>

            </div>
          </div>

          <?php if ($phone || $address || $website): ?>
            <div class="hotel-widget">
              <p class="hotel-widget__title">Контакты отеля</p>
              <div class="hotel-widget__contacts">
                <?php if ($phone): ?>
                  <div class="hotel-widget__phone hotel-widget__contacts-item">
                    <a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $phone)); ?>">
                      <img src="<?= get_template_directory_uri() ?>/img/icons/hotel/call.svg" alt="">
                      <span><?= esc_html($phone); ?></span>
                    </a>
                  </div>
                <?php endif; ?>

                <?php if ($address): ?>
                  <div class="hotel-widget__address hotel-widget__contacts-item">
                    <img src="<?= get_template_directory_uri() ?>/img/icons/hotel/home.svg" alt="">
                    <span><?= esc_html($address); ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($website): ?>
                  <div class="hotel-widget__site hotel-widget__contacts-item">
                    <a href="<?= esc_url($website); ?>" target="_blank" rel="nofollow noopener">
                      <img src="<?= get_template_directory_uri() ?>/img/icons/hotel/url.svg" alt="">
                      <span>Сайт отеля</span>
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($map_lat && $map_lng): ?>
            <div class="hotel-widget">
              <a href="#hotel-map" class="btn btn-black sm hotel-widget__btn-map">Смотреть на карте</a>
            </div>
          <?php endif; ?>
        </aside>

      </div>
    </div>
  </section>

  <?php if ($map_lat && $map_lng): ?>
    <section class="single-hotel__map-section" id="hotel-map">
      <div class="container">
        <div class="hotel-map" id="hotel-map-container"></div>
      </div>
    </section>
    <script>
      function initHotelMap() {
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
          setTimeout(initHotelMap, 100);
          return;
        }
        var location = { lat: <?php echo esc_js($map_lat); ?>, lng: <?php echo esc_js($map_lng); ?> };
        var map = new google.maps.Map(document.getElementById('hotel-map-container'), {
          zoom: <?php echo esc_js($map_zoom); ?>,
          center: location
        });
        var marker = new google.maps.Marker({
          position: location,
          map: map
        });
      }
      window.addEventListener('load', function () {
        initHotelMap();
      });
    </script>
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

        <p class="callout__text-warn">
          Нашли неточную информацию? Сообщите нам, и мы исправим ее! <a href="#">Написать сообщение</a>


        </p>
      </div>
    </div>
  </section>




</main>

<?php
// get_sidebar();
get_footer();

?>