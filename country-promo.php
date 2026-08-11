<?php
global $country_promos_data;

$country = $country_promos_data['country'] ?? null;
$country_slug = $country_promos_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? $country->ID : 0;

$promos = get_posts(bsi_query_args_append_schedule([
  'post_type' => 'promo',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'meta_query' => [
    [
      'key' => 'promo_countries',
      'value' => '"' . $country_id . '"',
      'compare' => 'LIKE',
    ],
  ],
  'orderby' => 'date',
  'order' => 'DESC',
]));

get_header(); ?>

<main class="site-main">

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section>
    <div class="container">
      <div class="coutry-page__wrap">

        <aside class="coutry-page__aside">
          <?= get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <div class="page-country__content">
          <?php
          // H1 выводится всегда: раньше он был внутри проверки на наличие
          // акций, и страны без активных предложений отдавали страницу
          // вообще без заголовка.
          // «Акции в Италии» — предложный падеж, отвечает на «где?».
          $promo_country_acc = function_exists('bsi_country_locative_title')
            ? trim((string) bsi_country_locative_title((int) $country->ID))
            : '';
          if ($promo_country_acc === '') {
            $promo_country_acc = (string) $country->post_title;
          }
          $promo_prep = function_exists('bsi_seo_preposition_v')
            ? bsi_seo_preposition_v($promo_country_acc)
            : 'в';
          ?>
          <h1 class="h1 country-promos__title">
            Акции <?= esc_html($promo_prep . ' ' . $promo_country_acc); ?>
          </h1>

          <?php if ($promos): ?>
            <div class="country-promos__counter">

              <span>Нашли акций: <span class=""><?= count($promos); ?></span>
              </span>
            </div>

            <div class="country-promos__list promo-grid">
              <?php
              global $post;

              foreach ($promos as $promo) {
                $post = $promo;               // важно
                setup_postdata($post);        // теперь get_the_ID() и т.п. точно про promo
                get_template_part('template-parts/promo/card');
              }

              wp_reset_postdata();
              ?>
            </div>
          <?php else: ?>
            <p class="country-promos__empty">
              Сейчас активных акций нет. Загляните позже или посмотрите
              <a href="<?= esc_url(trailingslashit(home_url('/country/' . $country->post_name . '/tours'))); ?>">туры<?= '' ?></a>.
            </p>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>

</main>

<?php
get_footer();