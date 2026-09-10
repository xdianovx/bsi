<?php

/**
 * Страница курорта — /country/{country}/{region}/{resort}/.
 *
 * Гид по городу: описание и факты, карта достопримечательностей, слайдеры
 * достопримечательностей и экскурсий, текст, соседние курорты. Секции без
 * данных не выводятся — курорты наполнены очень неравномерно (от 170 записей
 * в Лондоне до одиночных).
 */

/* Раздел курорта — свой шаблон с пагинацией */
if (get_query_var('resort_section') !== '') {
  get_template_part('resort-section');
  return;
}

$term = get_queried_object();

if (!($term instanceof WP_Term) || empty($term->term_id)) {
  get_header();
  get_footer();
  return;
}

$term_id = (int) $term->term_id;
$term_key = 'resort_' . $term_id;

$context = bsi_resort_context($term_id);
$country_id = (int) $context['country_id'];
$region = $context['region'];

$locative = bsi_resort_locative($term_id);
/* H1 — просто название города; вхождения «отдых в …» остаются в <title> */
$h1 = (string) $term->name;

$excerpt = function_exists('get_field') ? trim((string) get_field('resort_excerpt', $term_key)) : '';
/* (array) false даёт [false] — без фильтра пустая галерея считается заполненной */
$gallery = function_exists('get_field') ? array_filter((array) get_field('resort_gallery', $term_key)) : [];
$description = bsi_resort_description_text($term_id);

/* Контент секций */
$sight_ids = bsi_resort_posts($term_id, 'sight');
$excursion_ids = bsi_resort_posts($term_id, 'excursion');
$education_ids = bsi_resort_posts($term_id, 'education');
$hotel_count = bsi_resort_count($term_id, 'hotel');

$map = bsi_resort_map_points($sight_ids);
$siblings = bsi_resort_siblings($term_id);

/* Ссылки на разделы курорта. Пустая строка — записей меньше порога,
   отдельной страницы у раздела нет. */
$sights_url = bsi_resort_section_url($term_id, 'dostoprimechatelnosti');
$excursions_url = bsi_resort_section_url($term_id, 'ekskursii');
$education_url = bsi_resort_section_url($term_id, 'obuchenie');
$hotels_url = bsi_resort_section_url($term_id, 'oteli');

$cta_section = 'hub';

get_header(); ?>

<main class="site-main resort-page">

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

          <?php /* Шапка: локация, H1, лид, факты */ ?>
          <div class="resort-hero">
            <?php
            get_template_part('template-parts/ui/location-line', null, [
              'country_id' => $country_id,
              'class' => 'resort-hero-location',
              'parts' => [
                ['label' => $region instanceof WP_Term ? $region->name : ''],
              ],
            ]);
            ?>

            <h1 class="h1 resort-hero-title"><?= esc_html($h1); ?></h1>

            <?php if ($excerpt !== ''): ?>
              <p class="resort-hero-text"><?= nl2br(esc_html($excerpt)); ?></p>
            <?php endif; ?>

          </div>

          <?php if (!empty($gallery)): ?>
            <div class="country-page__gallery resort-gallery">
              <?php
              get_template_part('template-parts/sections/gallery', null, [
                'gallery' => $gallery,
                'id' => 'resort_' . $term_id,
              ]);
              ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($map['points'])): ?>
            <section class="resort-section resort-map-section">
              <div class="resort-section-head">
                <h2 class="h2 resort-section-title">
                  <?php $genitive = bsi_resort_genitive($term_id); ?>
                  <?= esc_html($genitive !== '' ? 'Карта ' . $genitive : 'Карта места'); ?>
                </h2>
              </div>

              <div class="resort-map"
                   data-sights-map
                   data-icons="<?= esc_attr(wp_json_encode($map['icons'])); ?>"
                   data-points="<?= esc_attr(wp_json_encode($map['points'])); ?>"></div>
            </section>
          <?php endif; ?>

          <?php if (!empty($sight_ids)): ?>
            <section class="resort-section" id="sights">
              <div class="resort-section-head">
                <h2 class="h2 resort-section-title">
                  <?= esc_html($locative !== '' ? 'Что посмотреть в ' . $locative : 'Достопримечательности'); ?>
                </h2>

                <div class="resort-section-tools">
                  <?php if ($sights_url !== ''): ?>
                    <a class="resort-section-all" href="<?= esc_url($sights_url); ?>">
                      Смотреть все
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                    </a>
                  <?php endif; ?>

                  <div class="resort-slider-nav">
                    <button class="resort-slider-btn resort-slider-prev" type="button" aria-label="Назад">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button class="resort-slider-btn resort-slider-next" type="button" aria-label="Вперёд">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                  </div>
                </div>
              </div>

              <div class="swiper resort-slider resort-sights-slider">
                <div class="swiper-wrapper">
                  <?php foreach (array_slice($sight_ids, 0, 12) as $sight_id): ?>
                    <div class="swiper-slide">
                      <?php get_template_part('template-parts/sight/card', null, ['post_id' => $sight_id]); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

            </section>
          <?php endif; ?>

          <?php if (!empty($excursion_ids)): ?>
            <section class="resort-section" id="excursions">
              <div class="resort-section-head">
                <h2 class="h2 resort-section-title">
                  <?= esc_html($locative !== '' ? 'Экскурсии в ' . $locative : 'Экскурсии'); ?>
                </h2>

                <div class="resort-section-tools">
                  <?php if ($excursions_url !== ''): ?>
                    <a class="resort-section-all" href="<?= esc_url($excursions_url); ?>">
                      Смотреть все
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                    </a>
                  <?php endif; ?>

                  <div class="resort-slider-nav">
                    <button class="resort-slider-btn resort-slider-prev" type="button" aria-label="Назад">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button class="resort-slider-btn resort-slider-next" type="button" aria-label="Вперёд">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                  </div>
                </div>
              </div>

              <div class="swiper resort-slider resort-excursions-slider">
                <div class="swiper-wrapper">
                  <?php foreach (array_slice($excursion_ids, 0, 12) as $excursion_id): ?>
                    <div class="swiper-slide">
                      <?php get_template_part('template-parts/excursion/card-row', null, ['post_id' => $excursion_id]); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

            </section>
          <?php endif; ?>

          <?php if (!empty($education_ids)): ?>
            <section class="resort-section" id="education">
              <div class="resort-section-head">
                <h2 class="h2 resort-section-title">
                  <?= esc_html($locative !== '' ? 'Обучение в ' . $locative : 'Обучение'); ?>
                </h2>

                <div class="resort-section-tools">
                  <?php if ($education_url !== ''): ?>
                    <a class="resort-section-all" href="<?= esc_url($education_url); ?>">
                      Смотреть все
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                    </a>
                  <?php endif; ?>

                  <div class="resort-slider-nav">
                    <button class="resort-slider-btn resort-slider-prev" type="button" aria-label="Назад">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button class="resort-slider-btn resort-slider-next" type="button" aria-label="Вперёд">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                  </div>
                </div>
              </div>

              <div class="swiper resort-slider resort-education-slider">
                <div class="swiper-wrapper">
                  <?php foreach (array_slice($education_ids, 0, 12) as $education_id): ?>
                    <div class="swiper-slide">
                      <?php
                      /* Карточка образования читает данные из query_var, а не из args */
                      set_query_var('education', bsi_resort_education_item((int) $education_id));
                      get_template_part('template-parts/education/card');
                      ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

            </section>
          <?php endif; ?>

          <?php /* Отели: список приходит AJAX-ом (inc/requests/resort-hotels.php),
                    поэтому секция рисуется всегда — на проде отелей тысячи. */ ?>
          <section class="resort-section resort-hotels" id="hotels" data-term-id="<?= (int) $term_id; ?>">
            <div class="resort-section-head">
              <h2 class="h2 resort-section-title">
                <?= esc_html($locative !== '' ? 'Отели в ' . $locative : 'Отели'); ?>
              </h2>

              <?php if ($hotels_url !== ''): ?>
                <div class="resort-section-tools">
                  <a class="resort-section-all" href="<?= esc_url($hotels_url); ?>">
                    Смотреть все
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                      aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                  </a>
                </div>
              <?php endif; ?>
            </div>

            <div class="resort-hotels__list"></div>

            <button class="btn btn-gray resort-hotels__more" type="button">Показать ещё</button>

          </section>


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

          <?php if ($description !== ''): ?>
            <div class="editor-content page-country__editor-content resort-text">
              <?= wp_kses_post(wpautop($description)); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($siblings)): ?>
            <section class="resort-section resort-siblings">
              <div class="resort-section-head">
                <h2 class="h2 resort-section-title">
                  <?php
                  /* Регион часто называется как сам курорт (Лондон / Лондон) —
                     в заголовке это выглядит ошибкой */
                  $siblings_title = ($region instanceof WP_Term
                    && mb_strtolower($region->name) !== mb_strtolower((string) $term->name))
                    ? 'Другие курорты региона ' . $region->name
                    : 'Куда ещё съездить рядом';
                  ?>
                  <?= esc_html($siblings_title); ?>
                </h2>
              </div>

              <ul class="resort-siblings-list">
                <?php foreach ($siblings as $sibling): ?>
                  <li>
                    <a class="resort-siblings-link" href="<?= esc_url(get_term_link($sibling)); ?>">
                      <?= esc_html($sibling->name); ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endif; ?>

        </div>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
