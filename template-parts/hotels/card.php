<?php
$hotel = get_query_var('hotel');
if (!$hotel || !is_array($hotel)) {
  return;
}

$hotel_id = 0;
if (!empty($hotel['id'])) {
  $hotel_id = (int) $hotel['id'];
} elseif (!empty($hotel['url'])) {
  $hotel_id = (int) url_to_postid($hotel['url']);
}

$amenities = [];
if ($hotel_id) {
  $terms = wp_get_post_terms($hotel_id, 'amenity', ['orderby' => 'name', 'order' => 'ASC']);
  if (!is_wp_error($terms) && !empty($terms)) {
    $amenities = array_slice($terms, 0, 4);
  }
}

$extra_tags = [];
if (!empty($hotel['tags']) && is_array($hotel['tags'])) {
  $extra_tags = array_values(array_filter(array_map('strval', $hotel['tags'])));
}
?>
<a href="<?php echo esc_url($hotel['url']); ?>"
   class="hotel-card">

  <div class="hotel-card__media">
    <img src="<?php echo esc_url($hotel['image']); ?>"
         alt="<?php echo esc_attr($hotel['title']); ?>"
         class="hotel-card__image">
  </div>

  <div class="hotel-card__body">
    <div class="hotel-card__title-wrap">
      <h3 class="hotel-card__title"><?php echo esc_html($hotel['title']); ?></h3>
    </div>



    <div class="hotel-card__location">
      <div class="hotel-card__flag">
        <img src="<?php echo esc_url($hotel['flag']); ?>"
             alt="">
      </div>
      <div class="hotel-card__location"><?php echo esc_html($hotel['location_title']); ?></div>
      <div class="hotel-card__stars">
        <p>3</p>
        <svg xmlns="http://www.w3.org/2000/svg"
             width="16"
             height="16"
             viewBox="0 0 24 24"
             fill="currentColor"
             stroke="currentColor"
             stroke-width="2"
             stroke-linecap="round"
             stroke-linejoin="round"
             class="lucide lucide-star-icon lucide-star filled">
          <path
                d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
          </path>
        </svg>
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
            <span class="hotel-card__anemetie"
                  title="<?php echo esc_attr($term->name); ?>">
              <img src="<?php echo esc_url($icon_url); ?>"
                   alt="<?php echo esc_attr($term->name); ?>">
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
      <?php if (!empty($hotel['price'])): ?>
        <div class="hotel-card__price numfont"><?php echo esc_html($hotel['price']); ?></div>
      <?php endif; ?>
    </div>
  </div>
</a>