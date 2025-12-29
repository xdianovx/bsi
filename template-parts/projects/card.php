<?php
$project = get_query_var('project');


$project_id = 0;

if (is_array($project) && !empty($project['id'])) {
  $project_id = (int) $project['id'];
} else {
  $project_id = (int) get_the_ID();
}

if (!$project_id) {
  return;
}

$url = is_array($project) && !empty($project['url']) ? (string) $project['url'] : (string) get_permalink($project_id);
$title = is_array($project) && !empty($project['title']) ? (string) $project['title'] : (string) get_the_title($project_id);


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

$country_title = $country_id ? (string) get_the_title($country_id) : '';
$flag_url = '';

if ($country_id && function_exists('get_field')) {
  $flag_field = get_field('flag', $country_id);
  if ($flag_field) {
    if (is_array($flag_field) && !empty($flag_field['url'])) {
      $flag_url = (string) $flag_field['url'];
    } elseif (is_string($flag_field)) {
      $flag_url = (string) $flag_field;
    }
  }
}

$image_url = '';
$thumb = get_the_post_thumbnail_url($project_id, 'large');
if ($thumb) {
  $image_url = (string) $thumb;
} else {
  $gallery = function_exists('get_field') ? get_field('project_gallery', $project_id) : [];
  $gallery = is_array($gallery) ? $gallery : [];

  $first_id = 0;
  if (!empty($gallery[0])) {
    if (is_array($gallery[0]) && !empty($gallery[0]['ID'])) {
      $first_id = (int) $gallery[0]['ID'];
    } elseif (is_numeric($gallery[0])) {
      $first_id = (int) $gallery[0];
    }
  }

  if ($first_id) {
    $img = wp_get_attachment_image_url($first_id, 'large');
    if ($img)
      $image_url = (string) $img;
  }
}

$excerpt = '';
if (is_array($project) && !empty($project['excerpt'])) {
  $excerpt = (string) $project['excerpt'];
} else {
  $raw = get_the_excerpt($project_id);
  $excerpt = $raw ? (string) $raw : '';
}
?>

<a href="<?php echo esc_url($url); ?>" class="project-card">
  <?php if ($image_url): ?>
    <div class="project-card__media">
      <img class="project-card__image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
    </div>
  <?php endif; ?>

  <div class="project-card__body">
    <div class="project-card__head">
      <?php if ($flag_url || $country_title): ?>
        <div class="project-card__country">
          <?php if ($flag_url): ?>
            <span class="project-card__flag">
              <img src="<?php echo esc_url($flag_url); ?>" alt="">
            </span>
          <?php endif; ?>
          <?php if ($country_title): ?>
            <span class="project-card__country-title"><?php echo esc_html($country_title); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <h3 class="project-card__title"><?php echo esc_html($title); ?></h3>

    <?php if (!empty($excerpt)): ?>
      <div class="project-card__excerpt">
        <?php echo wp_kses_post($excerpt); ?>
      </div>
    <?php endif; ?>
  </div>
</a>