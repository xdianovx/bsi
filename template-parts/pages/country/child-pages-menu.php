<?php
$current_id = get_the_ID();
$parent_id = wp_get_post_parent_id($current_id);

$is_hotels_page = false;
$is_promos_page = false;
$is_visas_page = false;
$is_resorts_page = false;

$country_slug = '';
$country_title = '';
$main_parent_id = 0;

if (get_query_var('country_hotels')) {
  $country_slug = get_query_var('country_hotels');
  $country = get_page_by_path($country_slug, OBJECT, 'country');
  $main_parent_id = $country ? $country->ID : $current_id;
  $country_title = $country ? $country->post_title : get_the_title();
  $is_hotels_page = true;

} elseif (get_query_var('country_promos')) {
  $country_slug = get_query_var('country_promos');
  $country = get_page_by_path($country_slug, OBJECT, 'country');
  $main_parent_id = $country ? $country->ID : $current_id;
  $country_title = $country ? $country->post_title : get_the_title();
  $is_promos_page = true;

} elseif (get_query_var('country_visas')) {
  $country_slug = get_query_var('country_visas');
  $country = get_page_by_path($country_slug, OBJECT, 'country');
  $main_parent_id = $country ? $country->ID : $current_id;
  $country_title = $country ? $country->post_title : get_the_title();
  $is_visas_page = true;

} elseif (get_query_var('country_resorts')) {
  $country_slug = get_query_var('country_resorts');
  $country = get_page_by_path($country_slug, OBJECT, 'country');
  $main_parent_id = $country ? $country->ID : $current_id;
  $country_title = $country ? $country->post_title : get_the_title();
  $is_resorts_page = true;

} elseif (is_tax('region')) {
  $term = get_queried_object();
  $main_parent_id = get_field('region_country', 'term_' . $term->term_id);
  $country_slug = $main_parent_id ? get_post_field('post_name', $main_parent_id) : '';
  $country_title = $main_parent_id ? get_the_title($main_parent_id) : '';
  $is_resorts_page = true;

} elseif (is_tax('resort')) {
  $term = get_queried_object();
  $region_term = $term && $term->parent ? get_term($term->parent, 'region') : null;

  if ($region_term && !is_wp_error($region_term)) {
    $main_parent_id = get_field('region_country', 'term_' . $region_term->term_id);
    $country_slug = $main_parent_id ? get_post_field('post_name', $main_parent_id) : '';
    $country_title = $main_parent_id ? get_the_title($main_parent_id) : '';
    $is_resorts_page = true;
  }

} else {
  $main_parent_id = $parent_id ?: $current_id;
  $country_slug = get_post_field('post_name', $main_parent_id);
  $country_title = get_the_title($main_parent_id);
}

$child_pages = get_posts([
  'post_type' => 'country',
  'post_parent' => $main_parent_id,
  'numberposts' => -1,
  'orderby' => 'title',
  'order' => 'ASC',
]);

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

$has_regions = get_terms([
  'taxonomy' => 'region',
  'hide_empty' => false,
  'number' => 1,
  'meta_query' => [
    [
      'key' => 'region_country',
      'value' => $main_parent_id,
      'compare' => '=',
    ],
  ],
]);
?>

<nav class="child-pages">
  <div class="child-pages__list">

    <a href="<?= get_permalink($main_parent_id); ?>"
       class="child-page-item <?= ($current_id == $main_parent_id && !$is_hotels_page && !$is_promos_page && !$is_visas_page && !$is_resorts_page) ? 'active' : ''; ?>">
      <span class="child-page-item__icon">
        <svg xmlns="http://www.w3.org/2000/svg"
             width="24"
             height="24"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="1.5"
             stroke-linecap="round"
             stroke-linejoin="round"
             class="lucide lucide-house-icon lucide-house">
          <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
          <path
                d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
        </svg>
      </span>
      <span>Обзор</span>
    </a>



    <?php if (!empty($has_hotels)): ?>
      <a href="<?= home_url("/country/{$country_slug}/hotel/"); ?>"
         class="child-page-item <?= $is_hotels_page ? 'active' : ''; ?>">
        <span class="child-page-item__icon">
          <svg xmlns="http://www.w3.org/2000/svg"
               width="24"
               height="24"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               stroke-width="1.5"
               stroke-linecap="round"
               stroke-linejoin="round"
               class="lucide lucide-bed-double-icon lucide-bed-double">
            <path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8" />
            <path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4" />
            <path d="M12 4v6" />
            <path d="M2 18h20" />
          </svg>
        </span>
        <span>Отели</span>
      </a>
    <?php endif; ?>


    <?php if (!empty($has_regions) && !is_wp_error($has_regions)): ?>
      <a href="<?= esc_url(home_url("/country/{$country_slug}/kurorty/")); ?>"
         class="child-page-item <?= $is_resorts_page ? 'active' : ''; ?>">

        <span class="child-page-item__icon">
          <svg xmlns="http://www.w3.org/2000/svg"
               width="24"
               height="24"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               stroke-width="1.5"
               stroke-linecap="round"
               stroke-linejoin="round"
               class="lucide lucide-tree-palm-icon lucide-tree-palm">
            <path d="M13 8c0-2.76-2.46-5-5.5-5S2 5.24 2 8h2l1-1 1 1h4"></path>
            <path d="M13 7.14A5.82 5.82 0 0 1 16.5 6c3.04 0 5.5 2.24 5.5 5h-3l-1-1-1 1h-3"></path>
            <path d="M5.89 9.71c-2.15 2.15-2.3 5.47-.35 7.43l4.24-4.25.7-.7.71-.71 2.12-2.12c-1.95-1.96-5.27-1.8-7.42.35">
            </path>
            <path d="M11 15.5c.5 2.5-.17 4.5-1 6.5h4c2-5.5-.5-12-1-14"></path>
          </svg></span>
        <span>Курорты</span>
      </a>
    <?php endif; ?>

    <?php if (!empty($has_promos)): ?>
      <a href="<?= home_url("/country/{$country_slug}/promo/"); ?>"
         class="child-page-item <?= $is_promos_page ? 'active' : ''; ?>">
        <span class="child-page-item__icon"><svg xmlns="http://www.w3.org/2000/svg"
               width="24"
               height="24"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               stroke-width="1.5"
               stroke-linecap="round"
               stroke-linejoin="round"
               class="lucide lucide-flame-icon lucide-flame">
            <path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4" />
          </svg>
        </span>

        <span>Акции</span>

      </a>
    <?php endif; ?>

    <?php if (!empty($has_visas)): ?>
      <a href="<?= home_url("/country/{$country_slug}/visa/"); ?>"
         class="child-page-item <?= $is_visas_page ? 'active' : ''; ?>">
        <span class="child-page-item__icon">

          <svg width="24"
               height="24"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               xmlns="http://www.w3.org/2000/svg">
            <path d="M15 7C15 7 15.5 7.5 16 8.5C16 8.5 17.5882 6 19 5.5"
                  stroke-width="1.5"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
            <path d="M10.0144 2.00578C7.51591 1.9 5.58565 2.18782 5.58565 2.18782C4.3668 2.27496 2.03099 2.95829 2.03101 6.94898C2.03103 10.9058 2.00517 15.7837 2.03101 17.7284C2.03101 18.9164 2.76663 21.6877 5.31279 21.8363C8.40763 22.0168 13.9822 22.0552 16.54 21.8363C17.2247 21.7976 19.5042 21.2602 19.7927 18.7801C20.0915 16.2107 20.032 14.4251 20.032 14.0001"
                  stroke-width="1.5"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
            <path d="M22.0194 7C22.0194 9.76142 19.7786 12 17.0146 12C14.2505 12 12.0098 9.76142 12.0098 7C12.0098 4.23858 14.2505 2 17.0146 2C19.7786 2 22.0194 4.23858 22.0194 7Z"
                  stroke-width="1.5"
                  stroke-linecap="round" />
            <path d="M7 13H11"
                  stroke-width="1.5"
                  stroke-linecap="round" />
            <path d="M7 17H15"
                  stroke-width="1.5"
                  stroke-linecap="round" />
          </svg>

        </span>
        <span>Виза</span>
      </a>
    <?php endif; ?>



    <?php foreach ($child_pages as $child): ?>
      <a href="<?= get_permalink($child->ID); ?>"
         class="child-page-item <?= ($current_id == $child->ID) ? 'active' : ''; ?>">
        <span><?= esc_html($child->post_title); ?></span>
      </a>
    <?php endforeach; ?>

  </div>
</nav>