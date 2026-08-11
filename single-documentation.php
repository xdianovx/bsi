<?php
/**
 * Single template for the "Агентствам" section CPT.
 */

get_header();

$is_education_page = bsi_is_agency_events_any_page();
$is_events_archive = bsi_is_agency_events_archive_page();
$events_title = $is_events_archive ? 'Архив мероприятий' : 'Мероприятия для турагентств';
$events_toggle_url = bsi_agency_events_archive_toggle_url();
$events_toggle_label = $is_events_archive ? 'Ближайшие мероприятия' : 'Архив';
?>

<main class="site-main agency-page">
  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section class="agency-page__section">
    <div class="container">
      <div class="agency-page__layout <?php echo $is_education_page ? 'agency-page__layout--full' : ''; ?>">
        <?php if (!$is_education_page): ?>
          <aside class="agency-page__aside">
            <?php get_template_part('template-parts/pages/agency/sidebar'); ?>
          </aside>
        <?php endif; ?>

        <div class="agency-page__content">
          <?php if ($is_education_page): ?>
            <div class="agency-page__title-row agency-page__title-row--events">
              <h1 class="h1 agency-page__title"><?php echo esc_html($events_title); ?></h1>
              <?php if ($events_toggle_url !== ''): ?>
                <a href="<?php echo esc_url($events_toggle_url); ?>" class="agency-page__archive-link">
                  <?php echo esc_html($events_toggle_label); ?>
                </a>
              <?php endif; ?>
            </div>
            <?php get_template_part('template-parts/agency/education-events'); ?>
          <?php else: ?>
            <?php while (have_posts()): ?>
              <?php the_post(); ?>
              <h1 class="h1 agency-page__title"><?php the_title(); ?></h1>
              <div class="editor-content agency-page__editor">
                <?php the_content(); ?>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php get_template_part('template-parts/sections/subscribe'); ?>
</main>

<?php if ($is_education_page): ?>
  <?php get_template_part('template-parts/agency/event-registration-modal'); ?>
<?php endif; ?>
<?php get_footer();
