<?php
/*
Template Name: Награды
*/

/**
 * Страница наград по макету 2026-10-20 (Фигма qhOwVa1YNd0tg4TAH85JEb, страница «Награды» 2079:6564).
 * Серый фон, белые карточки. Все награды одним списком, без пагинации, по годам — от новых к старым.
 * Награды без года — последней группой без заголовка (год не подставляется).
 */

get_header();

$awards_query = new WP_Query([
  'post_type' => 'award',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'no_found_rows' => true,
  // OR с NOT EXISTS: награда без строки award_year в базе тоже попадает в список (в конец).
  'meta_query' => [
    'relation' => 'OR',
    'award_year' => ['key' => 'award_year', 'compare' => 'EXISTS', 'type' => 'NUMERIC'],
    ['key' => 'award_year', 'compare' => 'NOT EXISTS'],
  ],
  'orderby' => ['award_year' => 'DESC', 'date' => 'DESC'],
]);

// Группы по годам; ключ '' — награды без года.
$awards_groups = [];
$awards_issuers = [];
foreach ($awards_query->posts as $award_post) {
  $year = trim((string) get_field('award_year', $award_post->ID));
  $awards_groups[$year][] = $award_post;

  $issuer = trim((string) get_field('award_issuer', $award_post->ID));
  if ($issuer !== '') {
    $awards_issuers[mb_strtolower($issuer)] = true;
  }
}

if (isset($awards_groups[''])) {
  $no_year = $awards_groups[''];
  unset($awards_groups['']);
  $awards_groups[''] = $no_year;
}

$awards_years = array_values(array_filter(array_keys($awards_groups), static fn($y) => $y !== ''));
$awards_total = count($awards_query->posts);
$awards_issuers_total = count($awards_issuers);
$awards_year_min = $awards_years ? min($awards_years) : '';
$awards_year_max = $awards_years ? max($awards_years) : '';
$awards_span = $awards_years ? (int) $awards_year_max - (int) $awards_year_min : 0;
?>

<main class="site-main awards-page">
  <div class="awards-page__container">

    <section class="awards-hero">
      <?php if (function_exists('yoast_breadcrumb')) {
        yoast_breadcrumb('<div class="awards-hero__breadcrumbs breadcrumbs" id="breadcrumbs"><p>', '</p></div>');
      } ?>

      <div class="awards-hero__head">
        <div class="awards-hero__text">
          <h1 class="awards-hero__title"><?php the_title(); ?></h1>

          <?php $awards_lead = get_the_excerpt(); ?>
          <?php if ($awards_lead): ?>
            <p class="awards-hero__lead"><?php echo esc_html($awards_lead); ?></p>
          <?php endif; ?>
        </div>

        <?php if ($awards_total): ?>
          <ul class="awards-hero__stats">
            <li class="awards-stat">
              <span class="awards-stat__value"><?php echo (int) $awards_total; ?></span>
              <span class="awards-stat__label awards-stat__label--desktop"><?php echo esc_html(bsi_plural_ru($awards_total, 'награда и благодарность', 'награды и благодарности', 'наград и благодарностей')); ?></span>
              <span class="awards-stat__label awards-stat__label--mobile"><?php echo esc_html(bsi_plural_ru($awards_total, 'награда', 'награды', 'наград')); ?></span>
            </li>

            <?php if ($awards_span > 0): ?>
              <li class="awards-stat">
                <span class="awards-stat__value awards-stat__value--desktop"><?php echo esc_html($awards_year_min . '–' . $awards_year_max); ?></span>
                <span class="awards-stat__value awards-stat__value--mobile"><?php echo esc_html($awards_span . ' ' . bsi_plural_ru($awards_span, 'год', 'года', 'лет')); ?></span>
                <span class="awards-stat__label awards-stat__label--desktop"><?php echo esc_html($awards_span . ' ' . bsi_plural_ru($awards_span, 'год', 'года', 'лет') . ' признания'); ?></span>
                <span class="awards-stat__label awards-stat__label--mobile"><?php echo esc_html('с ' . $awards_year_min . ' года'); ?></span>
              </li>
            <?php endif; ?>

            <?php if ($awards_issuers_total): ?>
              <li class="awards-stat">
                <span class="awards-stat__value"><?php echo (int) $awards_issuers_total; ?></span>
                <span class="awards-stat__label awards-stat__label--desktop"><?php echo esc_html(bsi_plural_ru($awards_issuers_total, 'организация вручила', 'организации вручили', 'организаций вручили') . ' награды'); ?></span>
                <span class="awards-stat__label awards-stat__label--mobile"><?php echo esc_html(bsi_plural_ru($awards_issuers_total, 'организация', 'организации', 'организаций')); ?></span>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <?php if (count($awards_years) > 1): ?>
      <nav class="awards-nav" data-awards-nav>
        <div class="awards-nav__list">
          <?php foreach ($awards_years as $i => $year): ?>
            <a href="#year-<?php echo esc_attr($year); ?>"
              class="awards-nav__link<?php echo $i === 0 ? ' awards-nav__link--active' : ''; ?>"
              data-awards-nav-link="<?php echo esc_attr($year); ?>"><?php echo esc_html($year); ?></a>
          <?php endforeach; ?>
        </div>
      </nav>
    <?php endif; ?>

    <?php if ($awards_groups): ?>
      <?php foreach ($awards_groups as $year => $group): ?>
        <section class="awards-year" <?php echo $year !== '' ? 'id="year-' . esc_attr($year) . '" data-awards-year="' . esc_attr($year) . '"' : ''; ?>>
          <div class="awards-year__aside">
            <?php if ($year !== ''): ?>
              <h2 class="awards-year__title"><?php echo esc_html($year); ?></h2>
            <?php endif; ?>
          </div>

          <div class="awards-year__list">
            <?php foreach ($group as $award_post):
              $GLOBALS['post'] = $award_post;
              setup_postdata($award_post);
              get_template_part('template-parts/awards/card', null, ['variant' => 'grid']);
            endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
      <?php wp_reset_postdata(); ?>
    <?php else: ?>
      <div class="awards-page__empty">Наград пока нет.</div>
    <?php endif; ?>

  </div>
</main>

<?php
get_footer();
