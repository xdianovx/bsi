<?php
$hotel = get_query_var('hotel');

$hotel_id = 0;
$hotel_url = '';
$hotel_image = '';
$hotel_title = '';
$hotel_flag = '';
$price_value = '';
$price_text = '';

if ($hotel && is_array($hotel)) {
  if (!empty($hotel['id'])) {
    $hotel_id = (int) $hotel['id'];
  } elseif (!empty($hotel['url'])) {
    $hotel_id = (int) url_to_postid($hotel['url']);
  }
  $hotel_url = !empty($hotel['url']) ? (string) $hotel['url'] : '';
  $hotel_image = !empty($hotel['image']) ? (string) $hotel['image'] : '';
  $hotel_title = !empty($hotel['title']) ? (string) $hotel['title'] : '';
  $hotel_flag = !empty($hotel['flag']) ? (string) $hotel['flag'] : '';
  $price_value = !empty($hotel['price']) ? (string) $hotel['price'] : '';
  $price_text = !empty($hotel['price_text']) ? (string) $hotel['price_text'] : '';
} else {
  $hotel_id = get_the_ID();
  if (!$hotel_id) {
    return;
  }
  $hotel_url = get_permalink($hotel_id);
  $hotel_image = get_the_post_thumbnail_url($hotel_id, 'large') ?: '';
  $hotel_title = get_the_title($hotel_id);
}

if (!$hotel_id) {
  return;
}

$amenities = [];
$terms = wp_get_post_terms($hotel_id, 'amenity', ['orderby' => 'name', 'order' => 'ASC']);
if (!is_wp_error($terms) && !empty($terms)) {
  $amenities = array_slice($terms, 0, 4);
}

$extra_tags = [];
if ($hotel && is_array($hotel) && !empty($hotel['tags']) && is_array($hotel['tags'])) {
  $extra_tags = array_values(array_filter(array_map('strval', $hotel['tags'])));
}

$country_title = '';
$resort_title = '';
$rating = 0;

if ($hotel && is_array($hotel) && !empty($hotel['country_title'])) {
  $country_title = (string) $hotel['country_title'];
} else {
  $country_id = function_exists('get_field') ? get_field('hotel_country', $hotel_id) : 0;
  if ($country_id instanceof WP_Post) {
    $country_id = (int) $country_id->ID;
  } elseif (is_array($country_id)) {
    $country_id = (int) reset($country_id);
  } else {
    $country_id = (int) $country_id;
  }
  if ($country_id) {
    $country_title = get_the_title($country_id);
  }
}

if ($hotel && is_array($hotel) && !empty($hotel['resort_title'])) {
  $resort_title = (string) $hotel['resort_title'];
} else {
  $resort_terms = wp_get_post_terms($hotel_id, 'resort', ['orderby' => 'name', 'order' => 'ASC']);
  if (!is_wp_error($resort_terms) && !empty($resort_terms)) {
    $resort_title = (string) $resort_terms[0]->name;
  }
}

if (function_exists('get_field')) {
  $rating_val = get_field('rating', $hotel_id);
  if (is_numeric($rating_val)) {
    $rating = (int) $rating_val;
  }

  if (!$price_value) {
    $price_val = get_field('price', $hotel_id);
    if ($price_val) {
      if (is_numeric($price_val)) {
        $price_value = number_format((float) $price_val, 0, '.', ' ') . ' ₽';
      } elseif (is_string($price_val)) {
        $price_value = $price_val;
      }
    }
  }

  if (!$price_text) {
    $price_text_val = get_field('price_text', $hotel_id);
    if ($price_text_val) {
      $price_text = (string) $price_text_val;
    }
  }

  if (!$hotel_flag) {
    $country_id = function_exists('get_field') ? get_field('hotel_country', $hotel_id) : 0;
    if ($country_id instanceof WP_Post) {
      $country_id = (int) $country_id->ID;
    } elseif (is_array($country_id)) {
      $country_id = (int) reset($country_id);
    } else {
      $country_id = (int) $country_id;
    }
    if ($country_id && function_exists('get_field')) {
      $flag = get_field('flag', $country_id);
      if (is_array($flag) && !empty($flag['url'])) {
        $hotel_flag = (string) $flag['url'];
      } elseif (is_string($flag)) {
        $hotel_flag = (string) $flag;
      }
    }
  }
}
?>
<a href="<?php echo esc_url($hotel_url); ?>" class="hotel-card">

  <div class="hotel-card__media">
    <img src="<?php echo esc_url($hotel_image); ?>" alt="<?php echo esc_attr($hotel_title); ?>"
      class="hotel-card__image">
  </div>

  <div class="hotel-card__body">


    <div class="hotel-card__title-wrap">
      <?php if ($rating > 0): ?>
        <div class="hotel-card__rating">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
              fill="<?php echo $i <= $rating ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-star-icon lucide-star <?php echo $i <= $rating ? 'filled' : ''; ?>">
              <path
                d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
              </path>
            </svg>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
      <h3 class="hotel-card__title"><?php echo esc_html($hotel_title); ?></h3>

    </div>

    <div class="hotel-card__location">
      <?php if ($hotel_flag): ?>
        <div class="hotel-card__flag">
          <img src="<?php echo esc_url($hotel_flag); ?>" alt="">
        </div>
      <?php endif; ?>
      <div class="hotel-card__location-text">
        <?php if ($country_title): ?>
          <span class="hotel-card__country"><?php echo esc_html($country_title); ?></span>
        <?php endif; ?>
        <?php if ($resort_title): ?>
          <?php if ($country_title): ?>, <?php endif; ?>
          <span class="hotel-card__resort"><?php echo esc_html($resort_title); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="hotel-card__anemeties">
      <?php if (!empty($amenities)): ?>
        <?php foreach ($amenities as $term): ?>
          <?php
          $icon_url = '';

          if (function_exists('get_field')) {
            $icon = get_field('amenity_icon', 'term_' . $term->term_id);
            if (is_array($icon) && !empty($icon['url'])) {
              $icon_url = (string) $icon['url'];
            } elseif (is_string($icon) && $icon !== '') {
              $icon_url = (string) $icon;
            } elseif (is_numeric($icon)) {
              $tmp = wp_get_attachment_image_url((int) $icon, 'thumbnail');
              if ($tmp) {
                $icon_url = (string) $tmp;
              }
            }
          }

          if (!$icon_url) {
            $meta = get_term_meta($term->term_id, 'amenity_icon', true);
            if (is_array($meta) && !empty($meta['url'])) {
              $icon_url = (string) $meta['url'];
            } elseif (is_string($meta) && $meta !== '') {
              $icon_url = (string) $meta;
            } elseif (is_numeric($meta)) {
              $tmp = wp_get_attachment_image_url((int) $meta, 'thumbnail');
              if ($tmp) {
                $icon_url = (string) $tmp;
              }
            }
          }
          ?>

          <?php if ($icon_url): ?>
            <span class="hotel-card__anemetie" title="<?php echo esc_attr($term->name); ?>">
              <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
            </span>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if (!empty($extra_tags)): ?>
      <div class="hotel-card__tags">
        <?php foreach ($extra_tags as $t): ?>
          <span class="hotel-card__tag"><?php echo esc_html($t); ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>



    <div class="hotel-card__meta">
      <?php if ($price_value): ?>
        <div class="hotel-card__price">
          от <?php echo esc_html($price_value); ?> ₽
          <?php if ($price_text): ?>
            <span class="hotel-card__price-text">/ <?php echo esc_html($price_text); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</a>