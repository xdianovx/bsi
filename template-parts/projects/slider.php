<?php
$projects = get_posts([
  'post_type' => 'project',
  'post_status' => 'publish',
  'posts_per_page' => 12,
  'orderby' => 'date',
  'order' => 'DESC',
]);

if (empty($projects)) {
  return;
}

$archive_url = get_post_type_archive_link('project');
if (!$archive_url) {
  $archive_url = home_url('/projects/');
}
?>

<section class="projects-slider-section" id="projects">
  <div class="container">
    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="h2 news-slider__title">Проекты</h2>

        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev projects-section-arrow-prev" tabindex="0" role="button"
            aria-label="Previous slide">
          </div>
          <div class="slider-arrow slider-arrow-next projects-section-arrow-next" tabindex="0" role="button"
            aria-label="Next slide">
          </div>
        </div>
      </div>

      <div class="title-wrap__buttons">
        <a href="<?php echo esc_url($archive_url); ?>" class="title-wrap__link link-arrow">
          <span>Смотреть все</span>
          <div class="link-arrow__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-arrow-up-right-icon lucide-arrow-up-right">
              <path d="M7 7h10v10"></path>
              <path d="M7 17 17 7"></path>
            </svg>
          </div>
        </a>
      </div>
    </div>

    <div class="projects-slider-section__content">
      <div class="swiper projects-section-slider">
        <div class="swiper-wrapper">
          <?php foreach ($projects as $p): ?>
            <?php
            $project_id = (int) $p->ID;

            set_query_var('project', [
              'id' => $project_id,
              'url' => get_permalink($project_id),
              'title' => get_the_title($project_id),
              'excerpt' => get_the_excerpt($project_id),
            ]);
            ?>
            <div class="swiper-slide">
              <?php get_template_part('template-parts/projects/card'); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</section>