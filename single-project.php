<?php
get_header();

$project_id = (int) get_the_ID();

// country (ACF: project_country)
$country_id = 0;
if (function_exists('get_field')) {
  $c = get_field('project_country', $project_id);
  if ($c instanceof WP_Post) {
    $country_id = (int) $c->ID;
  } elseif (is_array($c)) {
    $country_id = (int) reset($c);
  } else {
    $country_id = (int) $c;
  }
}

// gallery (ACF: project_gallery)
$gallery = function_exists('get_field') ? (array) get_field('project_gallery', $project_id) : [];

// country meta
$country_title = '';
$country_url = '';
$flag_url = '';

if ($country_id) {
  $country_title = (string) get_the_title($country_id);
  $country_url = (string) get_permalink($country_id);

  if (function_exists('get_field')) {
    $flag_field = get_field('flag', $country_id);
    if ($flag_field) {
      if (is_array($flag_field) && !empty($flag_field['url'])) {
        $flag_url = (string) $flag_field['url'];
      } elseif (is_string($flag_field)) {
        $flag_url = (string) $flag_field;
      }
    }
  }
}

// project date: ACF project_date -> fallback WP date
$project_date_raw = function_exists('get_field') ? (string) get_field('project_date', $project_id) : '';
$project_date_ts = $project_date_raw ? strtotime($project_date_raw) : 0;

$project_date_human = $project_date_ts
  ? format_date_russian($project_date_raw)
  : format_date_russian(get_the_date('Y-m-d', $project_id));

$project_date_attr = $project_date_ts
  ? date('Y-m-d', $project_date_ts)
  : get_the_date('Y-m-d', $project_id);
?>

<?php if (function_exists('yoast_breadcrumb')): ?>
  <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
<?php endif; ?>

<section>
  <div class="container">
    <div class="coutry-page__ ">

      <?php /* Контент страницы проекта */ ?>
      <div class="page-country__content">

        <div class="page-project__top">
          <h1 class="h1 page-project__title"><?php the_title(); ?></h1>


          <div class="page-country__title-left">

            <?php if ($country_id): ?>
              <div class="single-hotel__address single-project-country">
                <?php if ($flag_url): ?>
                  <img class="page-country__subtitle-flag"
                       src="<?php echo esc_url($flag_url); ?>"
                       alt="<?php echo esc_attr($country_title); ?>">
                <?php endif; ?>


                <?php if ($country_url): ?>
                  <a class="page-country__link"
                     href="<?php echo esc_url($country_url); ?>">
                    <?php echo esc_html($country_title); ?>
                  </a>
                <?php else: ?>
                  <?php echo esc_html($country_title); ?>
                <?php endif; ?>
              </div>
            <?php endif; ?>


            <div class="single-project-div"></div>


            <?php if ($project_date_human): ?>
              <time class="project-date numfont"
                    datetime="<?php echo esc_attr($project_date_attr); ?>">
                <?php echo esc_html($project_date_human); ?>
              </time>
            <?php endif; ?>

          </div>

        </div>


        <?php if (!empty($gallery)): ?>
          <div class="country-page__gallery">
            <?php
            get_template_part('template-parts/sections/gallery', null, [
              'gallery' => $gallery,
              'id' => 'project_' . $project_id,
            ]);
            ?>
          </div>
        <?php endif; ?>

        <div class="editor-content post-content-section">
          <?php the_content(); ?>
        </div>

      </div>
    </div>
  </div>
</section>

<?php
get_footer();