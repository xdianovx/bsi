<?php
/*
Template Name: Акции
*/

get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section class="page-head archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 page-award__title archive-page__title ">
          <?php the_title(); ?>
        </h1>

        <?php if (trim((string) get_the_content()) !== ''): ?>
          <div class="editor-content archive-page__content">
            <?php the_content(); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="promo-filter__section">
    <div class="container">
      <?php
      $promo_countries = bsi_get_promo_countries_merged();
      $total_active = array_sum(array_column($promo_countries, 'count_active'));
      $total_archived = array_sum(array_column($promo_countries, 'count_archived'));
      ?>

      <div class="promo-page__show-archived">
        <label class="promo-page__show-archived-label">
          <input type="checkbox"
                 name="promo_show_archived"
                 value="1"
                 class="promo-page__show-archived-input js-promo-archived-toggle">
          <span class="promo-page__show-archived-text">Показать архивные</span>
        </label>
      </div>

      <div class="promo-filter promo-filter--page"
           data-total-active="<?= (int) $total_active ?>"
           data-total-archived="<?= (int) $total_archived ?>">
        <!-- Кнопка "Все направления" -->
        <button class="promo-filter__btn --all active js-promo-filter-btn"
                type="button"
                data-country=""
                data-count-active="<?= (int) $total_active ?>"
                data-count-archived="<?= (int) $total_archived ?>">
          Все (<?= (int) $total_active ?>)
        </button>

        <?php if (!empty($promo_countries)): ?>
          <?php foreach ($promo_countries as $country): ?>

            <button class="promo-filter__btn  js-promo-filter-btn"
                    type="button"
                    data-country="<?php echo esc_attr((string) $country['id']); ?>"
                    data-count-active="<?= (int) $country['count_active'] ?>"
                    data-count-archived="<?= (int) $country['count_archived'] ?>">
              <?php if (!empty($country['flag'])): ?>
                <span class="promo-filter__flag-wrap">
                  <img src="<?php echo esc_url((string) $country['flag']); ?>"
                       alt="<?php echo esc_attr($country['title']); ?>"
                       class="promo-filter__flag">
                </span>
              <?php endif; ?>

              <span class="promo-filter__title">
                <?php echo esc_html((string) $country['title']); ?>
              </span>

              <?php if (!empty($country['count_active'])): ?>
                <span class="promo-filter__count">
                  <?php echo (int) $country['count_active']; ?>
                </span>
              <?php endif; ?>
            </button>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="archive-page__content-section promo-page__content-section">
    <div class="container">
      <div class="promo-page__list">

        <?php
        $promo_query = new WP_Query(bsi_promo_list_query_args(false));
        ?>

        <?php if ($promo_query->have_posts()): ?>
          <div class="promo-grid js-promo-list">
            <?php while ($promo_query->have_posts()):
              $promo_query->the_post(); ?>

              <?php get_template_part('template-parts/promo/card'); ?>

            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="promo-archive__empty">
            <p>Акций пока нет.</p>
          </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>



      </div>
    </div>
  </section>




</main>

<?php
get_footer();