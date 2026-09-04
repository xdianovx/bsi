<?php
get_header();

$term = get_queried_object();
$excerpt = function_exists('get_field') ? get_field('term_excerpt', 'term_' . $term->term_id) : '';


if (!$term || empty($term->term_id)) {
  get_footer();
  return;
}

/** ACF поля терма  */
$term_meta_key = 'term_' . $term->term_id;
$resort_desc = function_exists('get_field') ? get_field('resort_desc', $term_meta_key) : '';
$resort_content = function_exists('get_field') ? get_field('resort_content', $term_meta_key) : '';
$resort_gallery = function_exists('get_field') ? get_field('resort_gallery', $term_meta_key) : [];

$children = get_terms([
  'taxonomy' => 'resort',
  'hide_empty' => false,
  'parent' => (int) $term->term_id,
]);

?>
<main>
  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section>
    <div class="container">
      <div class="coutry-page__wrap">

        <!-- Aside меню страны -->
        <aside class="coutry-page__aside">
          <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <!-- Контент страницы -->
        <div class="page-country__content">
          <!-- Заголовок + описание -->
          <div class="page-country__about page-country__resort-about">
            <div class="page-country__title page-country__resort-title">
              <h1 class="h1 country-promos__title"><?= esc_html($term->name); ?></h1>
            </div>

            <?php
            $term = get_queried_object();
            $resort_excerpt = function_exists('get_field')
              ? get_field('resort_excerpt', 'term_' . $term->term_id)
              : '';

            $resort_excerpt = trim((string) $resort_excerpt);
            ?>

            <?php if ($resort_excerpt): ?>
              <p class="page-country__descr">
                <?= nl2br(esc_html($resort_excerpt)); ?>
              </p>
            <?php endif; ?>


          </div>

          <?php if (!empty($resort_gallery) && is_array($resort_gallery)): ?>
            <div class="country-page__gallery">
              <?php
              get_template_part('template-parts/sections/gallery', null, [
                'gallery' => $resort_gallery,
                'id' => 'resort_' . $term->term_id,
              ]);
              ?>
            </div>
          <?php endif; ?>

          <!-- Подробный контент -->
          <?php if (!empty($term->description)): ?>
            <div class="editor-content page-country__editor-content">
              <?= term_description($term); ?>
            </div>
          <?php endif; ?>


          <!-- Отели -->
          <?php
          /* Курорт связан с хабом BSIHOTELS — отели берём оттуда.
             Связь задаётся полем «Курорт в хабе отелей» у термина. */
          $resort_api_hotels = function_exists('bsi_hotels_api_resort_hotels')
            ? bsi_hotels_api_resort_hotels((int) $term->term_id)
            : null;
          ?>

          <?php if ($resort_api_hotels && $resort_api_hotels['items']): ?>
            <section class="resort-hotels resort-hotels--api">
              <h2 class="h2">Отели: <?= esc_html($term->name); ?></h2>

              <div class="country-hotels__counter">
                Нашли отелей: <?= (int) $resort_api_hotels['total']; ?>
              </div>

              <div class="country-hotels__grid">
                <?php foreach ($resort_api_hotels['items'] as $api_hotel): ?>
                  <?php get_template_part('template-parts/hotels/api-card', null, [
                    'hotel' => $api_hotel,
                    'country_url' => $resort_api_hotels['catalog_url'],
                  ]); ?>
                <?php endforeach; ?>
              </div>

              <?php if ($resort_api_hotels['total'] > count($resort_api_hotels['items'])): ?>
                <p class="resort-hotels__all">
                  <a href="<?= esc_url($resort_api_hotels['resort_url']); ?>">
                    Все отели курорта: <?= (int) $resort_api_hotels['total']; ?>
                  </a>
                </p>
              <?php endif; ?>
            </section>

          <?php else: ?>
            <section class="resort-hotels"
                     data-term-id="<?= (int) $term->term_id; ?>">
              <h2 class="h2">Все отели </h2>
              <div class="resort-hotels__list"></div>

              <button class="resort-hotels__more"
                      type="button">
                Показать еще
              </button>
            </section>
          <?php endif; ?>
        </div>


      </div>
    </div>
  </section>



</main>

<?php get_footer(); ?>