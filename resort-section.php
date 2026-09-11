<?php

/**
 * Раздел курорта — /country/{country}/{region}/{resort}/{section}/.
 *
 * Один шаблон на все разделы: отличаются типом записи, заголовком и partial'ом
 * карточки (`bsi_resort_sections()`). Подключается из `taxonomy-resort.php`,
 * когда выставлен query var `resort_section`.
 *
 * Записей меньше порога — раздела нет: страница на две карточки дублирует хаб
 * и в индексе только мешает.
 */

$term = get_queried_object();
$section = (string) get_query_var('resort_section');
$sections = bsi_resort_sections();

if (!($term instanceof WP_Term) || !isset($sections[$section])) {
  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  get_template_part('404');
  return;
}

$term_id = (int) $term->term_id;
$config = $sections[$section];
$post_type = (string) $config['post_type'];

$all_ids = bsi_resort_posts($term_id, $post_type);

if (count($all_ids) < bsi_resort_section_min_items()) {
  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  get_template_part('404');
  return;
}

$context = bsi_resort_context($term_id);
$country_id = (int) $context['country_id'];
$region = $context['region'];

$locative = bsi_resort_locative($term_id);
$h1 = bsi_resort_section_h1($term_id, $section);

/* Редактируемые тексты подстраницы — на терме курорта */
$intro = bsi_resort_section_field($term_id, $section, 'intro');
$bottom_text = bsi_resort_section_field($term_id, $section, 'text');

$paged = max(1, (int) get_query_var('paged'));
$per_page = 24;
$total_pages = (int) ceil(count($all_ids) / $per_page);
$page_ids = array_slice($all_ids, ($paged - 1) * $per_page, $per_page);

/* Страница за пределами выборки — 404, а не пустой список */
if (empty($page_ids)) {
  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  get_template_part('404');
  return;
}

$resort_url = bsi_resort_url($term_id);
$cta_section = $section;

get_header(); ?>

<main class="site-main resort-page resort-section-page">

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
          <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <div class="page-country__content">

          <div class="resort-hero">
            <a class="resort-back" href="<?= esc_url($resort_url); ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
              <?= esc_html($term->name); ?>
            </a>

            <?php
            /* Курорт со ссылкой: с раздела всегда есть куда вернуться.
               Регион не линкуем — его архив служебный и закрыт от индекса. */
            get_template_part('template-parts/ui/location-line', null, [
              'country_id' => $country_id,
              'class' => 'resort-hero-location',
              'parts' => [
                ['label' => $region instanceof WP_Term ? $region->name : ''],
                ['label' => $term->name, 'url' => $resort_url],
              ],
            ]);
            ?>

            <h1 class="h1 resort-hero-title"><?= esc_html($h1); ?></h1>

            <?php if ($intro !== ''): ?>
              <div class="resort-hero-intro editor-content"><?= wp_kses_post($intro); ?></div>
            <?php endif; ?>
          </div>

          <?php
          $map = ($post_type === 'sight') ? bsi_resort_map_points($all_ids) : ['points' => [], 'icons' => []];
          ?>
          <?php if (!empty($map['points'])): ?>
            <section class="resort-section resort-map-section">
              <div class="resort-map"
                   data-sights-map
                   data-icons="<?= esc_attr(wp_json_encode($map['icons'])); ?>"
                   data-points="<?= esc_attr(wp_json_encode($map['points'])); ?>"></div>
            </section>
          <?php endif; ?>

          <div class="resort-section resort-section-list">
            <div class="resort-grid">
              <?php foreach ($page_ids as $post_id): ?>
                <div class="resort-grid-item">
                  <?php
                  if ($post_type === 'education') {
                    /* Карточка образования читает данные из query_var, а не из args */
                    set_query_var('education', bsi_resort_education_item((int) $post_id));
                    get_template_part($config['template']);
                  } elseif ($post_type === 'hotel') {
                    get_template_part($config['template'], null, ['hotel_id' => (int) $post_id]);
                  } else {
                    get_template_part($config['template'], null, ['post_id' => (int) $post_id]);
                  }
                  ?>
                </div>
              <?php endforeach; ?>
            </div>

            <?php
            bsi_pagination([
              /* %_% + format: первая страница остаётся базовым URL, без /page/1/ */
              'base' => trailingslashit($resort_url) . $section . '/%_%',
              'format' => 'page/%#%/',
              'total' => $total_pages,
              'current' => $paged,
            ], ['class' => 'resort-section-pagination']);
            ?>
          </div>


          <?php
          /* Заявка — секцией на странице, без модалки. data-поля формы уезжают
             в Метрику параметрами цели: видно, какой курорт даёт конверсии. */
          get_template_part('template-parts/resort/request-form', null, [
            'resort' => $term->name,
            'resort_locative' => $locative,
            'resort_accusative' => bsi_resort_accusative($term_id),
            'country' => $context['country_title'],
            'section' => $cta_section,
          ]);
          ?>

          <?php if ($bottom_text !== ''): ?>
            <div class="editor-content page-country__editor-content resort-text">
              <?= wp_kses_post($bottom_text); ?>
            </div>
          <?php endif; ?>

        </div>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
