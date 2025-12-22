<?php
get_header();

$project_id = get_the_ID();
$country_id = function_exists('get_field') ? (int) get_field('project_country', $project_id) : 0;
$gallery = function_exists('get_field') ? (array) get_field('project_gallery', $project_id) : [];

echo '<div class="container" style="padding:40px 0;">';
echo '<h1>' . esc_html(get_the_title()) . '</h1>';

if ($country_id) {
  echo '<p><b>Страна:</b> ' . esc_html(get_the_title($country_id)) . ' (ID: ' . (int) $country_id . ')</p>';
} else {
  echo '<p><b>Страна:</b> не выбрана</p>';
}

if (!empty($gallery)) {
  echo '<p><b>Галерея:</b> ' . count($gallery) . ' фото</p>';

  echo '<div class="country-page__gallery">';
  get_template_part('template-parts/sections/gallery', null, [
    'gallery' => $gallery,
    'id' => 'project_' . $project_id,
  ]);
  echo '</div>';

} else {
  echo '<p><b>Галерея:</b> пусто</p>';
}

echo '<div style="margin-top:20px;">';
the_content();
echo '</div>';

echo '</div>';

get_footer();