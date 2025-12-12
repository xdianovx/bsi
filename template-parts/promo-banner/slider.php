<?php
// Ожидаем аргумент section_id при вызове get_template_part(..., ..., ['section_id' => 123])
$section_id = isset($args['section_id']) ? (int) $args['section_id'] : 0;

// Если аргумента нет, а слайдер выводится внутри записи секции – берём текущий ID
if (!$section_id && is_singular('banner_section')) {
  $section_id = get_the_ID();
}

$query_args = [
  'post_type' => 'banner', // слаг твоего CPT для баннеров
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'orderby' => [
    'menu_order' => 'ASC',
    'date' => 'DESC',
  ],
];

if ($section_id) {
  $query_args['meta_query'] = [
    [
      'key' => 'banner_section', // ACF поле-связка баннера с секцией
      'value' => $section_id,
      'compare' => '=',
    ],
  ];
}

$promo_banners = new WP_Query($query_args);


?>


<?php if ($promo_banners->have_posts()): ?>
  <section class="promo-banner__section">
    <div class="container">
      <div class="promo-banner__wrap">
        <div class="swiper promo-banner-slider">
          <div class="swiper-wrapper">
            <?php while ($promo_banners->have_posts()):
              $promo_banners->the_post(); ?>
              <div class="swiper-slide">
                <?php get_template_part('template-parts/promo-banner/card'); ?>
              </div>
            <?php endwhile; ?>
          </div>
          <div class="swiper-pagination promo-banner-slider-pag"></div>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>