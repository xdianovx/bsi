<?php
// Проверяем, нужно ли фильтровать по MICE (передается через get_template_part)
$filter_mice = isset($args['filter_mice']) ? $args['filter_mice'] : false;

// Если на странице MICE, фильтруем по ACF полю
$meta_query = [];
if ($filter_mice) {
  $meta_query[] = [
    'key' => 'show_on_mice_page',
    'value' => '1',
    'compare' => '='
  ];
}

$news_args = [
  'post_type' => 'news',
  'posts_per_page' => 6,
  'orderby' => 'date',
  'order' => 'DESC'
];

if (!empty($meta_query)) {
  $news_args['meta_query'] = $meta_query;
}

$news_page_link = get_post_type_archive_link('news');
$news_query = new WP_Query($news_args);
?>

<section class="news-slider__section">
  <div class="container">

    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="h2 news-slider__title">Последние новости</h2>
        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev news-slider-arrow-prev">
          </div>
          <div class="slider-arrow slider-arrow-next news-slider-arrow-next">
          </div>
        </div>
      </div>

      <div class="title-wrap__buttons">
        <a href="<?= get_permalink(2223) ?>" class="title-wrap__link link-arrow">
          <span>Все новости </span>
          <div class="link-arrow__icon">

            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-arrow-up-right-icon lucide-arrow-up-right">
              <path d="M7 7h10v10" />
              <path d="M7 17 17 7" />
            </svg>

          </div>
        </a>
      </div>
    </div>


    <?php if ($news_query->have_posts()): ?>

      <div class="swiper news-slider-slider">
        <div class="swiper-wrapper">
          <?php
          while ($news_query->have_posts()):
            $news_query->the_post();
            ?>
            <div class="swiper-slide">
              <?php get_template_part('template-parts/news/card'); ?>
            </div>

          <?php endwhile; ?>

        </div>
      </div>

      <div class="slider-arrow-wrap news-slider__arrows-wrap --mob">
        <div class="slider-arrow slider-arrow-prev news-slider-arrow-prev">
        </div>
        <div class="slider-arrow slider-arrow-next news-slider-arrow-next">
        </div>
      </div>

      <div class="news-slider__buttons-mob">
        <a href="<?= get_permalink(2223) ?>" class="btn btn-accent">Все новости</a>
      </div>

      <?php wp_reset_postdata(); ?>
    <?php endif; ?>


  </div>
</section>