<?php
/**
 * Single template for agency events (CPT agency_event).
 */

get_header();

$post_id = get_the_ID();
$title = get_the_title($post_id);

$start_date = function_exists('get_field') ? trim((string) get_field('event_start_date', $post_id)) : '';
$start_time = function_exists('get_field') ? trim((string) get_field('event_start_time', $post_id)) : '';
$city = function_exists('get_field') ? trim((string) get_field('event_city', $post_id)) : '';
$place = function_exists('get_field') ? trim((string) get_field('event_place', $post_id)) : '';
$place_display = implode(', ', array_filter([$city, $place]));
$registration_closed = function_exists('get_field') ? (bool) get_field('event_registration_closed', $post_id) : false;
$registration_url = function_exists('get_field') ? trim((string) get_field('event_registration_url', $post_id)) : '';
$price_raw = function_exists('get_field') ? trim((string) get_field('event_price', $post_id)) : '';
$price = $price_raw;
if ($price !== '' && function_exists('format_price_text')) {
  $price = format_price_text($price);
}
if ($price !== '' && function_exists('format_price_with_from')) {
  $price = format_price_with_from($price, false);
}

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

          <div class="agency-event-single__meta">
            <?php if ($start_date_label !== ''): ?>
              <div class="agency-event-single__meta-item agency-event-single__meta-item--date">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.66667 1.66797V5.0013M13.3333 1.66797V5.0013M2.5 8.33463H17.5M6.66667 11.668H6.675M10 11.668H10.0083M13.3333 11.668H13.3417M6.66667 15.0013H6.675M10 15.0013H10.0083M13.3333 15.0013H13.3417M4.16667 3.33464H15.8333C16.7538 3.33464 17.5 4.08083 17.5 5.0013V16.668C17.5 17.5884 16.7538 18.3346 15.8333 18.3346H4.16667C3.24619 18.3346 2.5 17.5884 2.5 16.668V5.0013C2.5 4.08083 3.24619 3.33464 4.16667 3.33464Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <div class="agency-event-single__meta-value"><?php echo esc_html($start_date_label); ?></div>
              </div>
            <?php endif; ?>
            <?php if ($start_time !== ''): ?>
              <div class="agency-event-single__meta-item agency-event-single__meta-item--time">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.99984 5.0013V10.0013L13.3332 11.668M18.3332 10.0013C18.3332 14.6037 14.6022 18.3346 9.99984 18.3346C5.39746 18.3346 1.6665 14.6037 1.6665 10.0013C1.6665 5.39893 5.39746 1.66797 9.99984 1.66797C14.6022 1.66797 18.3332 5.39893 18.3332 10.0013Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <div class="agency-event-single__meta-value"><?php echo esc_html($start_time); ?></div>
              </div>
            <?php endif; ?>
            <?php if ($place_display !== ''): ?>
              <div class="agency-event-single__meta-item agency-event-single__meta-item--place">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.6663 8.33464C16.6663 12.4955 12.0505 16.8288 10.5005 18.1671C10.3561 18.2757 10.1803 18.3344 9.99967 18.3344C9.81901 18.3344 9.64324 18.2757 9.49884 18.1671C7.94884 16.8288 3.33301 12.4955 3.33301 8.33464C3.33301 6.56653 4.03539 4.87083 5.28563 3.62059C6.53587 2.37035 8.23156 1.66797 9.99967 1.66797C11.7678 1.66797 13.4635 2.37035 14.7137 3.62059C15.964 4.87083 16.6663 6.56653 16.6663 8.33464Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.99967 10.8346C11.3804 10.8346 12.4997 9.71535 12.4997 8.33464C12.4997 6.95392 11.3804 5.83464 9.99967 5.83464C8.61896 5.83464 7.49967 6.95392 7.49967 8.33464C7.49967 9.71535 8.61896 10.8346 9.99967 10.8346Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <div class="agency-event-single__meta-value"><?php echo esc_html($place_display); ?></div>
              </div>
            <?php endif; ?>
          </div>

          <div class="agency-education-card__bottom">
            <?php if ($price !== ''): ?>
              <div class="agency-education-card__price numfont"><?php echo esc_html($price); ?></div>
            <?php endif; ?>
            <?php if ($registration_closed): ?>
              <button type="button" class="btn sm btn-gray agency-education-card__cta" disabled>Запись недоступна</button>
            <?php elseif ($registration_url !== ''): ?>
              <a href="<?php echo esc_url($registration_url); ?>" target="_blank" rel="noopener" class="btn sm btn-accent agency-education-card__cta">Регистрация</a>
            <?php else: ?>
              <button type="button"
                      class="btn sm btn-accent agency-education-card__cta js-agency-event-reg-btn"
                      data-event-id="<?php echo esc_attr($post_id); ?>"
                      data-event-title="<?php echo esc_attr($title); ?>"
                      data-event-kind="<?php echo esc_attr($kind_label); ?>">
                Регистрация
              </button>
            <?php endif; ?>
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

<?php get_template_part('template-parts/agency/event-registration-modal'); ?>
<?php get_footer();
