<?php
/**
 * Single template for agency events (CPT agency_event).
 */

get_header();

$post_id = get_the_ID();
$title = get_the_title($post_id);

$start_date = function_exists('get_field') ? trim((string) get_field('event_start_date', $post_id)) : '';
$start_time = function_exists('get_field') ? trim((string) get_field('event_start_time', $post_id)) : '';
$place = function_exists('get_field') ? trim((string) get_field('event_place', $post_id)) : '';
$registration_closed = function_exists('get_field') ? (bool) get_field('event_registration_closed', $post_id) : false;
$price = function_exists('get_field') ? trim((string) get_field('event_price', $post_id)) : '';

$start_date_label = '';
if ($start_date !== '') {
  $ts = strtotime($start_date);
  if ($ts) {
    $start_date_label = date_i18n('j F Y', $ts);
  }
}

$kind_terms = get_the_terms($post_id, 'agency_event_kind');
$kind = (!empty($kind_terms) && !is_wp_error($kind_terms)) ? $kind_terms[0] : null;
$kind_label = $kind ? $kind->name : 'Событие';
$kind_slug = $kind ? $kind->slug : '';

$kind_class = 'is-default';
if ('webinar' === $kind_slug) {
  $kind_class = 'is-webinar';
} elseif ('event' === $kind_slug) {
  $kind_class = 'is-event';
} elseif ('promo-tour' === $kind_slug) {
  $kind_class = 'is-promo';
}

$kind_plural_map = [
  'webinar' => 'Вебинары',
  'event' => 'Мероприятия',
  'promo-tour' => 'Рекламные туры',
];
$kind_breadcrumb_label = isset($kind_plural_map[$kind_slug]) ? $kind_plural_map[$kind_slug] : 'Обучение';

$education_post = get_page_by_path('obuchenie', OBJECT, 'documentation');
$education_url = $education_post ? get_permalink($education_post->ID) : home_url('/');
$kind_url = $kind_slug ? add_query_arg('kind', $kind_slug, $education_url) : $education_url;
?>

<main class="site-main agency-page">
  <div id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <p>
        <a href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
        <span class="breadcrumb-separator"></span>
        <a href="<?php echo esc_url($kind_url); ?>"><?php echo esc_html($kind_breadcrumb_label); ?></a>
        <span class="breadcrumb-separator"></span>
        <span><?php echo esc_html($title); ?></span>
      </p>
    </div>
  </div>

  <section class="agency-page__section">
    <div class="container">
      <div class="agency-page__layout">
        <aside class="agency-page__aside">
          <?php get_template_part('template-parts/pages/agency/sidebar'); ?>
        </aside>

        <div class="agency-page__content">
          <div class="agency-education-card__top">
            <span class="agency-education-card__kind <?php echo esc_attr($kind_class); ?>"><?php echo esc_html($kind_label); ?></span>
          </div>

          <h1 class="h1 agency-page__title"><?php echo esc_html($title); ?></h1>

          <div class="agency-education-card__meta">
            <?php if ($start_date_label !== ''): ?>
              <span class="agency-education-card__meta-item"><?php echo esc_html($start_date_label); ?></span>
            <?php endif; ?>
            <?php if ($start_time !== ''): ?>
              <span class="agency-education-card__meta-item"><?php echo esc_html($start_time); ?></span>
            <?php endif; ?>
            <?php if ($place !== ''): ?>
              <span class="agency-education-card__meta-item"><?php echo esc_html($place); ?></span>
            <?php endif; ?>
          </div>

          <div class="agency-education-card__bottom">
            <?php if ($price !== ''): ?>
              <div class="agency-education-card__price numfont"><?php echo esc_html($price); ?></div>
            <?php endif; ?>
            <button type="button"
                    class="btn sm <?php echo $registration_closed ? 'btn-gray' : 'btn-accent'; ?> agency-education-card__cta"
              <?php echo $registration_closed ? 'disabled' : ''; ?>>
              <?php echo $registration_closed ? 'Запись недоступна' : 'Регистрация'; ?>
            </button>
          </div>

          <?php if (trim((string) get_post_field('post_content', $post_id)) !== ''): ?>
            <div class="editor-content agency-page__editor" style="margin-top: 24px;">
              <?php the_content(); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php get_template_part('template-parts/sections/subscribe'); ?>
</main>

<?php get_footer();
