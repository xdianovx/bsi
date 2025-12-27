<?php
$tour = get_query_var('tour');
if (!$tour || !is_array($tour)) {
  return;
}

$tour_id = 0;
if (!empty($tour['id'])) {
  $tour_id = (int) $tour['id'];
} elseif (!empty($tour['url'])) {
  $tour_id = (int) url_to_postid($tour['url']);
}

$tour_types = [];
if ($tour_id) {
  $terms = wp_get_post_terms($tour_id, 'tour_type', ['orderby' => 'name', 'order' => 'ASC']);
  if (!is_wp_error($terms) && !empty($terms)) {
    $tour_types = array_slice($terms, 0, 4);
  }
}

$extra_tags = [];
if (!empty($tour['tags']) && is_array($tour['tags'])) {
  $extra_tags = array_values(array_filter(array_map('strval', $tour['tags'])));
}
?>
<a href="<?php echo esc_url($tour['url']); ?>" class="hotel-card tour-card">

  <div class="hotel-card__media">
    <img src="<?php echo esc_url($tour['image']); ?>" alt="<?php echo esc_attr($tour['title']); ?>"
      class="hotel-card__image">
  </div>

  <div class="hotel-card__body">
    <div class="hotel-card__title-wrap">
      <h3 class="hotel-card__title"><?php echo esc_html($tour['title']); ?></h3>
    </div>

    <div class="hotel-card__location">
      <div class="hotel-card__flag">
        <img src="<?php echo esc_url($tour['flag']); ?>" alt="">
      </div>
      <div class="hotel-card__location"><?php echo esc_html($tour['location_title']); ?></div>

    </div>

    <?php if (!empty($tour['duration'])): ?>
      <div class="hotel-card__duration">
        <?php echo esc_html($tour['duration']); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($tour['excerpt'])): ?>
      <div class="hotel-card__excerpt">
        <?php echo esc_html($tour['excerpt']); ?>
      </div>
    <?php endif; ?>




    <?php if (!empty($extra_tags)): ?>
      <div class="hotel-card__tags">
        <?php foreach ($extra_tags as $t): ?>
          <span class="hotel-card__tag"><?php echo esc_html($t); ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="hotel-card__meta">
      <?php if (!empty($tour['price'])): ?>
        <div class="hotel-card__price numfont"><?php echo esc_html($tour['price']); ?></div>
      <?php endif; ?>
    </div>
  </div>
</a>