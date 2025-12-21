<?php
$current_id = (int) get_queried_object_id();
if (!$current_id) {
  $current_id = (int) get_the_ID();
}

$parent_id = (int) wp_get_post_parent_id($current_id);

$is_hotels_page = false;
$is_promos_page = false;
$is_visas_page = false;
$is_resorts_page = false;
$is_tours_page = false;
$is_memo_page = false;
$is_entry_rules_page = false;

$country_slug = '';
$country_title = '';
$main_parent_id = 0;

if (is_singular('tour')) {
  $tour_country_id = function_exists('get_field') ? get_field('tour_country', $current_id) : 0;

  if ($tour_country_id instanceof WP_Post) {
    $tour_country_id = (int) $tour_country_id->ID;
  } elseif (is_array($tour_country_id)) {
    $tour_country_id = (int) reset($tour_country_id);
  } else {
    $tour_country_id = (int) $tour_country_id;
  }

  if ($tour_country_id) {
    $main_parent_id = $tour_country_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
    $is_tours_page = true;
  } else {
    $main_parent_id = $parent_id ?: $current_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
  }

} elseif (is_singular('tourist_memo')) {

  $memo_country_id = function_exists('get_field') ? get_field('memo_country', $current_id) : 0;

  if ($memo_country_id instanceof WP_Post) {
    $memo_country_id = (int) $memo_country_id->ID;
  } elseif (is_array($memo_country_id)) {
    $memo_country_id = (int) reset($memo_country_id);
  } else {
    $memo_country_id = (int) $memo_country_id;
  }

  if ($memo_country_id) {
    $main_parent_id = $memo_country_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
    $is_memo_page = true;
  } else {
    $main_parent_id = $parent_id ?: $current_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
  }

} elseif (is_singular('entry_rules')) {

  $rules_country_id = function_exists('get_field') ? get_field('entry_rules_country', $current_id) : 0;

  if ($rules_country_id instanceof WP_Post) {
    $rules_country_id = (int) $rules_country_id->ID;
  } elseif (is_array($rules_country_id)) {
    $rules_country_id = (int) reset($rules_country_id);
  } else {
    $rules_country_id = (int) $rules_country_id;
  }

  if ($rules_country_id) {
    $main_parent_id = $rules_country_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
    $is_entry_rules_page = true;
  } else {
    $main_parent_id = $parent_id ?: $current_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
  }

} elseif (is_singular('visa')) {

  $visa_country_id = function_exists('get_field') ? get_field('visa_country', $current_id) : 0;

  if ($visa_country_id instanceof WP_Post) {
    $visa_country_id = (int) $visa_country_id->ID;
  } elseif (is_array($visa_country_id)) {
    $visa_country_id = (int) reset($visa_country_id);
  } else {
    $visa_country_id = (int) $visa_country_id;
  }

  if ($visa_country_id) {
    $main_parent_id = $visa_country_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
    $is_visas_page = true;
  } else {
    $main_parent_id = $parent_id ?: $current_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
  }

} elseif (get_query_var('country_hotels')) {

  $country_slug = (string) get_query_var('country_hotels');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? (int) $country->ID : $current_id;
  $country_title = $country ? (string) $country->post_title : (string) get_the_title();
  $is_hotels_page = true;

} elseif (get_query_var('country_promos')) {

  $country_slug = (string) get_query_var('country_promos');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? (int) $country->ID : $current_id;
  $country_title = $country ? (string) $country->post_title : (string) get_the_title();
  $is_promos_page = true;

} elseif (get_query_var('country_resorts')) {

  $country_slug = (string) get_query_var('country_resorts');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? (int) $country->ID : $current_id;
  $country_title = $country ? (string) $country->post_title : (string) get_the_title();
  $is_resorts_page = true;

} elseif (get_query_var('country_tours')) {

  $country_slug = (string) get_query_var('country_tours');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? (int) $country->ID : $current_id;
  $country_title = $country ? (string) $country->post_title : (string) get_the_title();
  $is_tours_page = true;

} elseif (get_query_var('country_visa') || get_query_var('country_visas')) {

  $qv = get_query_var('country_visa');
  if (empty($qv))
    $qv = get_query_var('country_visas');

  $country_slug = (string) $qv;
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? (int) $country->ID : $current_id;
  $country_title = $country ? (string) $country->post_title : (string) get_the_title();
  $is_visas_page = true;

} elseif (get_query_var('country_memo')) {

  $country_slug = (string) get_query_var('country_memo');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? (int) $country->ID : $current_id;
  $country_title = $country ? (string) $country->post_title : (string) get_the_title();
  $is_memo_page = true;

} elseif (get_query_var('country_entry_rules')) {

  $country_slug = (string) get_query_var('country_entry_rules');
  $country = get_page_by_path($country_slug, OBJECT, 'country');

  $main_parent_id = $country ? (int) $country->ID : $current_id;
  $country_title = $country ? (string) $country->post_title : (string) get_the_title();
  $is_entry_rules_page = true;

} elseif (is_tax('resort')) {

  $term = get_queried_object();

  $region_id = function_exists('get_field') ? get_field('resort_region', 'term_' . $term->term_id) : 0;
  if (is_array($region_id))
    $region_id = reset($region_id);
  $region_id = (int) $region_id;

  $region_term = $region_id ? get_term($region_id, 'region') : null;

  if ($region_term && !is_wp_error($region_term)) {
    $main_parent_id = function_exists('get_field') ? (int) get_field('region_country', 'term_' . $region_term->term_id) : 0;
    $country_slug = $main_parent_id ? (string) get_post_field('post_name', $main_parent_id) : '';
    $country_title = $main_parent_id ? (string) get_the_title($main_parent_id) : '';
    $is_resorts_page = true;
  } else {
    $main_parent_id = $parent_id ?: $current_id;
    $country_slug = (string) get_post_field('post_name', $main_parent_id);
    $country_title = (string) get_the_title($main_parent_id);
  }

} else {
  $main_parent_id = $parent_id ?: $current_id;
  $country_slug = (string) get_post_field('post_name', $main_parent_id);
  $country_title = (string) get_the_title($main_parent_id);
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
    ['key' => 'hotel_country', 'value' => $main_parent_id, 'compare' => '='],
  ],
]);

$has_promos = get_posts([
  'post_type' => 'promo',
  'posts_per_page' => 1,
  'fields' => 'ids',
  'meta_query' => [
    ['key' => 'promo_countries', 'value' => '"' . $main_parent_id . '"', 'compare' => 'LIKE'],
  ],
]);

$has_visas = get_posts([
  'post_type' => 'visa',
  'posts_per_page' => 1,
  'fields' => 'ids',
  'meta_query' => [
    ['key' => 'visa_country', 'value' => $main_parent_id, 'compare' => '='],
  ],
]);

$has_tours = get_posts([
  'post_type' => 'tour',
  'posts_per_page' => 1,
  'fields' => 'ids',
  'meta_query' => [
    ['key' => 'tour_country', 'value' => $main_parent_id, 'compare' => '='],
  ],
]);

$has_memo = get_posts([
  'post_type' => 'tourist_memo',
  'posts_per_page' => 1,
  'fields' => 'ids',
  'meta_query' => [
    ['key' => 'memo_country', 'value' => $main_parent_id, 'compare' => '='],
  ],
]);

$has_entry_rules = get_posts([
  'post_type' => 'entry_rules',
  'posts_per_page' => 1,
  'fields' => 'ids',
  'meta_query' => [
    ['key' => 'entry_rules_country', 'value' => $main_parent_id, 'compare' => '='],
  ],
]);

$has_regions = get_terms([
  'taxonomy' => 'region',
  'hide_empty' => false,
  'number' => 1,
  'meta_query' => [
    ['key' => 'region_country', 'value' => $main_parent_id, 'compare' => '='],
  ],
]);

$is_country_overview = (
  is_singular('country') &&
  (int) $current_id === (int) $main_parent_id &&
  !$is_hotels_page && !$is_promos_page && !$is_visas_page &&
  !$is_resorts_page && !$is_tours_page &&
  !$is_memo_page && !$is_entry_rules_page
);

$active_tour_types = [];
if (!empty($_GET['tour_type'])) {
  $raw = $_GET['tour_type'];
  $raw = is_array($raw) ? $raw : [$raw];
  $active_tour_types = array_values(array_filter(array_map('intval', $raw)));
}

$tours_list_url = home_url("/country/{$country_slug}/tours/");
$tour_types_for_country = [];

if (!empty($has_tours)) {
  $tour_ids = get_posts([
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
      ['key' => 'tour_country', 'value' => $main_parent_id, 'compare' => '='],
    ],
  ]);

  if (!empty($tour_ids)) {
    $terms = wp_get_object_terms($tour_ids, 'tour_type', [
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!is_wp_error($terms) && !empty($terms)) {
      $tour_types_for_country = $terms;
    }
  }
}

$active_visa_types = [];
if (!empty($_GET['visa_type'])) {
  $raw = $_GET['visa_type'];
  $raw = is_array($raw) ? $raw : [$raw];
  $active_visa_types = array_values(array_filter(array_map('intval', $raw)));
}

$visas_list_url = home_url("/country/{$country_slug}/visa/");
$visa_types_for_country = [];

if (!empty($has_visas)) {
  $visa_ids = get_posts([
    'post_type' => 'visa',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
      ['key' => 'visa_country', 'value' => $main_parent_id, 'compare' => '='],
    ],
  ]);

  if (!empty($visa_ids)) {
    $terms = wp_get_object_terms($visa_ids, 'visa_type', [
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!is_wp_error($terms) && !empty($terms)) {
      $visa_types_for_country = $terms;
    }
  }
}

$is_tours_open = $is_tours_page || !empty($active_tour_types);
$acc_id = 'sidebar-tours-' . (int) $main_parent_id;

$is_visas_open = $is_visas_page || !empty($active_visa_types);
$visa_acc_id = 'sidebar-visas-' . (int) $main_parent_id;
?>

<nav class="child-pages"
     data-country-aside>
  <div class="child-pages__list">

    <a href="<?= esc_url(get_permalink($main_parent_id)); ?>"
       class="child-page-item <?= $is_country_overview ? 'active' : ''; ?>">
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

    <?php if (!empty($has_entry_rules)): ?>
      <a href="<?= esc_url(home_url("/country/{$country_slug}/entry-rules/")); ?>"
         class="child-page-item <?= $is_entry_rules_page ? 'active' : ''; ?>">
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
               class="lucide lucide-file-exclamation-point-icon lucide-file-exclamation-point">
            <path
                  d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
          </svg>
        </span>
        <span>Правила въезда</span>
      </a>
    <?php endif; ?>

    <?php if (!empty($has_visas)): ?>
      <div class="child-page-accordion <?= $is_visas_open ? 'is-open' : ''; ?>"
           data-accordion>
        <button type="button"
                class="child-page-item <?= $is_visas_page ? 'active' : ''; ?>"
                data-accordion-trigger
                aria-expanded="<?= $is_visas_open ? 'true' : 'false'; ?>"
                aria-controls="<?= esc_attr($visa_acc_id); ?>">
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

          <div class="child-page-item__icon child-page-item__chevrone">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-chevron-down-icon lucide-chevron-down">
              <path d="m6 9 6 6 6-6" />
            </svg>
          </div>
        </button>

        <div id="<?= esc_attr($visa_acc_id); ?>"
             class="child-page-submenu"
             data-accordion-content
             <?= $is_visas_open ? '' : 'hidden'; ?>>
          <?php if (!empty($visa_types_for_country)): ?>
            <?php foreach ($visa_types_for_country as $vt): ?>
              <?php
              $vt_id = (int) $vt->term_id;
              $is_active_vt = in_array($vt_id, $active_visa_types, true);
              $url = add_query_arg(['visa_type[]' => $vt_id], $visas_list_url);
              ?>
              <a class="child-page-subitem <?= $is_active_vt ? 'active' : ''; ?>"
                 href="<?= esc_url($url); ?>"><?= esc_html($vt->name); ?></a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($has_visas)): ?>
      <a href="<?= esc_url($visas_list_url); ?>"
         class="child-page-item --mobile <?= $is_visas_page ? 'active' : ''; ?>">
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

    <?php if (!empty($has_promos)): ?>
      <a href="<?= esc_url(home_url("/country/{$country_slug}/promo/")); ?>"
         class="child-page-item <?= $is_promos_page ? 'active' : ''; ?>">
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
               class="lucide lucide-flame-icon lucide-flame">
            <path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4" />
          </svg>
        </span>
        <span>Акции</span>
      </a>
    <?php endif; ?>

    <?php if (!empty($has_tours)): ?>
      <div class="child-page-accordion <?= $is_tours_open ? 'is-open' : ''; ?>"
           data-accordion>
        <button type="button"
                class="child-page-item <?= $is_tours_page ? 'active' : ''; ?>"
                data-accordion-trigger
                aria-expanded="<?= $is_tours_open ? 'true' : 'false'; ?>"
                aria-controls="<?= esc_attr($acc_id); ?>">
          <span class="child-page-item__icon ">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.5"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-plane-icon lucide-plane">
              <path
                    d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z" />
            </svg>
          </span>
          <span>Туры</span>

          <div class="child-page-item__icon child-page-item__chevrone">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-chevron-down-icon lucide-chevron-down">
              <path d="m6 9 6 6 6-6" />
            </svg>
          </div>
        </button>

        <div id="<?= esc_attr($acc_id); ?>"
             class="child-page-submenu"
             data-accordion-content
             <?= $is_tours_open ? '' : 'hidden'; ?>>
          <a class="child-page-subitem <?= ($is_tours_page && empty($active_tour_types)) ? 'active' : ''; ?>"
             href="<?= esc_url($tours_list_url); ?>">Все туры</a>

          <?php if (!empty($tour_types_for_country)): ?>
            <?php foreach ($tour_types_for_country as $tt): ?>
              <?php
              $tt_id = (int) $tt->term_id;
              $is_active_tt = in_array($tt_id, $active_tour_types, true);
              $url = add_query_arg(['tour_type[]' => $tt_id], $tours_list_url);
              ?>
              <a class="child-page-subitem <?= $is_active_tt ? 'active' : ''; ?>"
                 href="<?= esc_url($url); ?>"><?= esc_html($tt->name); ?></a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($has_tours)): ?>
      <a href="<?= esc_url($tours_list_url); ?>"
         class="child-page-item --mobile <?= $is_tours_page ? 'active' : ''; ?>">
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
               class="lucide lucide-plane-icon lucide-plane">
            <path
                  d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z" />
          </svg>
        </span>
        <span>Туры</span>
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
          </svg>
        </span>
        <span>Курорты</span>
      </a>
    <?php endif; ?>

    <?php if (!empty($has_hotels)): ?>
      <a href="<?= esc_url(home_url("/country/{$country_slug}/hotel/")); ?>"
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

    <?php if (!empty($has_memo)): ?>
      <a href="<?= esc_url(home_url("/country/{$country_slug}/memo/")); ?>"
         class="child-page-item <?= $is_memo_page ? 'active' : ''; ?>">
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
               class="lucide lucide-bookmark-icon lucide-bookmark">
            <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
          </svg>
        </span>
        <span>Памятка туристам</span>
      </a>
    <?php endif; ?>

    <?php if (!empty($child_pages)): ?>
      <?php foreach ($child_pages as $child): ?>
        <a href="<?= esc_url(get_permalink($child->ID)); ?>"
           class="child-page-item <?= ((int) $current_id === (int) $child->ID) ? 'active' : ''; ?>">
          <span><?= esc_html($child->post_title); ?></span>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</nav>