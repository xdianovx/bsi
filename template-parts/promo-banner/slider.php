<?php

$section_id = isset($args['section_id']) ? (int) $args['section_id'] : 0;
$post_type = isset($args['post_type']) ? $args['post_type'] : null;

// Поддержка передачи section_id и post_type через query var
if (!$section_id) {
  $section_id = (int) get_query_var('promo_banner_section_id', 0);
}
if (!$post_type) {
  $post_type = get_query_var('promo_banner_post_type', 'banner');
}

if (!$section_id && is_singular('banner_section')) {
  $section_id = get_the_ID();
}

$query_args = [
  'post_type' => $post_type,
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
      'key' => 'banner_section',
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