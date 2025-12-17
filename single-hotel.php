<?php
$gallery = get_field('gallery', get_the_ID());
$country_id = get_field('field_hotel_country', get_the_ID());
$rating = get_field('rating', get_the_ID());
if ($country_id) {
  $country_title = get_the_title($country_id);
  $country_permalink = get_permalink($country_id);

  $country_visa = get_field('is_visa', $country_id); // поле "Требуется виза"
  $country_flag = get_field('flag', $country_id); // поле "Флаг"
  $region_id = get_field('hotel_region', $post_id);
  if (is_array($region_id))
    $region_id = reset($region_id);
  $region_id = (int) $region_id;

  $region_name = '';
  if ($region_id) {
    $region_term = get_term($region_id, 'region');
    if ($region_term && !is_wp_error($region_term))
      $region_name = $region_term->name;
  }

  $resort_id = get_field('hotel_resort', $post_id);
  if (is_array($resort_id))
    $resort_id = reset($resort_id);
  $resort_id = (int) $resort_id;

  $resort_name = '';
  if ($resort_id) {
    $resort_term = get_term($resort_id, 'resort');
    if ($resort_term && !is_wp_error($resort_term))
      $resort_name = $resort_term->name;
  }

  // город
  $city = trim((string) get_field('field_hotel_city', $post_id));

  // собираем строку адреса
  $parts = array_filter([$country_title, $region_name, $resort_name, $city]);
  $address_line = implode(', ', $parts);
}

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
        <h1 class="h1 single-hotel__title">Отель <?php the_title() ?></h1>
        <!-- <button class="print-btn single-hotel__print-btn"
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
        </button> -->
      </div>

      <div class="single-hotel__top-line">
        <?php if ($address_line): ?>
          <div class="single-hotel__address">
            <?php if ($country_flag): ?>
              <img src="<?= esc_url($country_flag); ?>"
                   alt="">
            <?php endif; ?>
            <div><?= esc_html($address_line); ?></div>
          </div>
        <?php endif; ?>

        <div class="div"></div>

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



      <!-- <div class="single-hotel__main-opts">
        тут основные опции отеля
      </div> -->
    </div>
  </section>

  <section class="single-hotel__gallery-section">
    <div class="container">


      <div class="hotel-gallery__wrap">
        <div class="swiper hotel-gallery-main-slider">
          <div class="swiper-wrapper">
            <?php foreach ($gallery as $item): ?>
              <div class="swiper-slide">
                <div class="hotel-gallery-main-slide">
                  <img src="<?= $item['url'] ?>"
                       alt="<?= $item['alt'] ?>">
                </div>
              </div>
            <?php endforeach ?>
          </div>

          <div class="slider-arrow  xl slider-arrow-prev hotel-gallery-main-arrow-prev">
          </div>
          <div class="slider-arrow  xl  slider-arrow-next hotel-gallery-main-arrow-next"></div>


        </div>
      </div>


      <div class="swiper hotel-gallery-main-slider-thumb">
        <div class="swiper-wrapper">
          <?php foreach ($gallery as $item): ?>
            <div class="swiper-slide">
              <div class="hotel-gallery-thumb-slide">

                <img src="<?= $item['url'] ?>"
                     alt="<?= $item['alt'] ?>">
              </div>
            </div>
          <?php endforeach ?>
        </div>
      </div>


    </div>
    </div>
  </section>




  <section class="single-hotel__content">
    <div class="container">

      <div class="hotel-content editor-content">
        <?php the_content() ?>
      </div>
    </div>
  </section>


  <section>
    <div class="container">
      <?php
      $lat = trim((string) get_field('hotel_lat', get_the_ID()));
      $lng = trim((string) get_field('hotel_lng', get_the_ID()));
      ?>

      <?php if ($lat && $lng): ?>
        <div class="hotel-map is-loading"
             data-lat="<?= esc_attr($lat); ?>"
             data-lng="<?= esc_attr($lng); ?>"
             data-zoom="14"></div>
      <?php endif; ?>
    </div>
  </section>

  <div class="hotel-map is-loading"
       data-lat="-8.337197"
       data-lng="115.659987"
       data-zoom="14"></div>

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