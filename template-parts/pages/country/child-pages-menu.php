<?php
// Определяем текущую страницу и родителя
$current_id = get_the_ID();
$parent_id = wp_get_post_parent_id($current_id);

// Флаги для спец-страниц
$is_hotels_page = false;
$is_promos_page = false;
$is_visas_page = false;

// Страница отелей
if (get_query_var('country_hotels')) {
  $country_slug = get_query_var('country_hotels');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? $country->ID : $current_id;
  $country_title = $country ? $country->post_title : get_the_title();
  $is_hotels_page = true;

  // Страница акций
} elseif (get_query_var('country_promos')) {
  $country_slug = get_query_var('country_promos');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? $country->ID : $current_id;
  $country_title = $country ? $country->post_title : get_the_title();
  $is_promos_page = true;

  // Страница виз
} elseif (get_query_var('country_visas')) {
  $country_slug = get_query_var('country_visas');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? $country->ID : $current_id;
  $country_title = $country ? $country->post_title : get_the_title();
  $is_visas_page = true;

  // Обычная страница страны
} else {
  $main_parent_id = $parent_id ?: $current_id;
  $country_slug = get_post_field('post_name', $main_parent_id);
  $country_title = get_the_title($main_parent_id);
}

// Дочерние страницы страны
$child_pages = get_posts([
  'post_type' => 'country',
  'post_parent' => $main_parent_id,
  'numberposts' => -1,
  'orderby' => 'title',
  'order' => 'ASC',
]);

// Есть ли отели
$has_hotels = get_posts([
  'post_type' => 'hotel',
  'posts_per_page' => 1,
  'fields' => 'ids',
  'meta_query' => [
    [
      'key' => 'hotel_country',
      'value' => $main_parent_id,
      'compare' => '=',
    ],
  ],
]);

// Есть ли акции
$has_promos = get_posts([
  'post_type' => 'promo',
  'posts_per_page' => 1,
  'fields' => 'ids',
  'meta_query' => [
    [
      'key' => 'promo_countries',
      'value' => '"' . $main_parent_id . '"',
      'compare' => 'LIKE',
    ],
  ],
]);

// Есть ли визы
$has_visas = get_posts([
  'post_type' => 'visa',
  'posts_per_page' => 1,
  'fields' => 'ids',
  'meta_query' => [
    [
      'key' => 'visa_country',
      'value' => $main_parent_id,
      'compare' => '=',
    ],
  ],
]);
?>

<nav class="child-pages">
  <div class="child-pages__list">

    <a href="<?= get_permalink($main_parent_id); ?>"
       class="child-page-item <?= ($current_id == $main_parent_id && !$is_hotels_page && !$is_promos_page && !$is_visas_page) ? 'active' : ''; ?>">
      <span>Обзор</span>
    </a>

    <?php foreach ($child_pages as $child): ?>
      <a href="<?= get_permalink($child->ID); ?>"
         class="child-page-item <?= ($current_id == $child->ID) ? 'active' : ''; ?>">
        <span><?= esc_html($child->post_title); ?></span>
      </a>
    <?php endforeach; ?>

    <?php if (!empty($has_hotels)): ?>
      <a href="<?= home_url("/country/{$country_slug}/hotel/"); ?>"
         class="child-page-item <?= $is_hotels_page ? 'active' : ''; ?>">
        <span>Отели</span>
      </a>
    <?php endif; ?>

    <?php if (!empty($has_promos)): ?>
      <a href="<?= home_url("/country/{$country_slug}/promo/"); ?>"
         class="child-page-item <?= $is_promos_page ? 'active' : ''; ?>">
        <span>Акции</span>
      </a>
    <?php endif; ?>

    <?php if (!empty($has_visas)): ?>
      <a href="<?= home_url("/country/{$country_slug}/visa/"); ?>"
         class="child-page-item <?= $is_visas_page ? 'active' : ''; ?>">
        <span>Виза</span>
      </a>
    <?php endif; ?>

  </div>
</nav>