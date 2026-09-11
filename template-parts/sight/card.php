<?php
/**
 * Карточка достопримечательности для каталога страны.
 *
 * @param int $args['post_id']
 */

$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if ($post_id <= 0) {
  return;
}

$title = get_the_title($post_id);
$permalink = get_permalink($post_id);

$short = function_exists('get_field') ? trim((string) get_field('sight_short', $post_id)) : '';
if ($short === '') {
  $short = trim((string) get_the_excerpt($post_id));
}

$resort_terms = get_the_terms($post_id, 'resort');
$resort_name = (!is_wp_error($resort_terms) && !empty($resort_terms)) ? (string) $resort_terms[0]->name : '';

$region_terms = get_the_terms($post_id, 'region');
$region_name = (!is_wp_error($region_terms) && !empty($region_terms)) ? (string) $region_terms[0]->name : '';

/* Локация — общий компонент location-line. Страну не выводим (каталог и так
   внутри страны), но флаг её берём: он и опознаётся быстрее пина. */
$country_id = function_exists('bsi_get_sight_country_id') ? bsi_get_sight_country_id($post_id) : 0;
$flag_url = ($country_id > 0 && function_exists('bsi_get_country_flag_url'))
  ? (string) bsi_get_country_flag_url($country_id)
  : '';

$type_terms = get_the_terms($post_id, 'sight_type');
$type_term = (!is_wp_error($type_terms) && !empty($type_terms)) ? $type_terms[0] : null;
$type_name = $type_term ? (string) $type_term->name : '';
$thumb = get_the_post_thumbnail_url($post_id, 'medium_large');
?>

<article class="sight-card">
  <a class="sight-card-media" href="<?= esc_url($permalink); ?>">
    <?php if ($thumb): ?>
      <img class="sight-card-image" src="<?= esc_url($thumb); ?>" alt="<?= esc_attr($title); ?>" loading="lazy">
    <?php else: ?>
      <span class="sight-card-placeholder" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10 18v-7" />
          <path d="M11.12 2.198a2 2 0 0 1 1.76.006l7.866 3.847c.476.233.31.949-.22.949H3.474c-.53 0-.695-.716-.22-.949z" />
          <path d="M14 18v-7" />
          <path d="M18 18v-7" />
          <path d="M3 22h18" />
          <path d="M6 18v-7" />
        </svg>
      </span>
    <?php endif; ?>

    <?php if ($type_name !== ''): ?>
      <span class="sight-card-type"><?= esc_html($type_name); ?></span>
    <?php endif; ?>
  </a>

  <div class="sight-card-body">
    <?php
    get_template_part('template-parts/ui/location-line', null, [
      'flag_url' => $flag_url,
      'class' => 'sight-card-location',
      'parts' => [
        ['label' => $resort_name],
        ['label' => $region_name],
      ],
    ]);
    ?>

    <h3 class="sight-card-title">
      <a href="<?= esc_url($permalink); ?>"><?= esc_html($title); ?></a>
    </h3>

    <?php if ($short !== ''): ?>
      <p class="sight-card-text"><?= esc_html(wp_trim_words($short, 16, '…')); ?></p>
    <?php endif; ?>
  </div>
</article>
