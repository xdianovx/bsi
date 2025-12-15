<?php
$best_offer = $args['best_offer'] ?? null;

if (empty($best_offer) || !is_array($best_offer)) {
  return;
}

$url = $best_offer['url'] ?? '#';
$image = $best_offer['image'] ?? '';
$type = $best_offer['type'] ?? '';
$tags = $best_offer['tags'] ?? [];
$title = $best_offer['title'] ?? '';
$flag = $best_offer['flag'] ?? '';
$location_title = $best_offer['location_title'] ?? '';

$price_raw = $best_offer['price'] ?? '';
$price = format_price_text($price_raw);

$data = $args['best_offer'] ?? [];
$post_id = (int) ($args['post_id'] ?? 0);

$flag_url = (string) ($data['flag'] ?? '');

if (!$flag_url && $post_id && get_post_type($post_id) === 'hotel') {
  $country_id = (int) get_field('hotel_country', $post_id);
  $flag = $country_id ? get_field('flag', $country_id) : null;

  if (is_array($flag) && !empty($flag['url'])) {
    $flag_url = (string) $flag['url'];
  } elseif (is_numeric($flag)) {
    $flag_url = (string) wp_get_attachment_image_url((int) $flag, 'thumbnail');
  } elseif (is_string($flag)) {
    $flag_url = $flag;
  }
}

$data['flag'] = $flag_url;
?>

<a href="<?= esc_url($url); ?>"
   class="best-offer-card">
  <?php if ($flag): ?>
    <div class="best-offer-card__location-flag">
      <img src="<?= esc_url($flag) ?>"
           alt="">
    </div>
  <?php endif; ?>
  <?php if ($image): ?>
    <img class="best-offer-card__image"
         src="<?= esc_url($image); ?>"
         alt="<?= esc_attr($title); ?>">
  <?php endif; ?>

  <?php if ($type): ?>
    <div class="best-offer-card__type"><?= esc_html($type); ?></div>
  <?php endif; ?>

  <div class="best-offer-card__info">

    <?php if (!empty($tags) && is_array($tags)): ?>
      <div class="best-offer-card__tags">
        <?php foreach ($tags as $tag): ?>
          <?php if (!$tag)
            continue; ?>
          <div class="best-offer-card__tag"><?= esc_html($tag); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($title): ?>
      <div class="best-offer-card__title"> <?php if ($flag): ?>
          <div class="best-offer-card__location-flag">
            <img src="<?= esc_url($flag); ?>"
                 alt="">
          </div>
          <h3> <?php endif; ?>   <?= esc_html($title); ?></h3>
      </div>
    <?php endif; ?>

    <?php if ($flag || $location_title): ?>
      <div class="best-offer-card__location">

        <?php if ($location_title): ?>
          <div class="best-offer-card__location-title"><?= esc_html($location_title); ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="best-offer-card__hr"></div>

    <?php if ($price): ?>
      <div class="best-offer-card__footer">
        <div class="best-offer-card__price"><?= esc_html($price); ?></div>
      </div>
    <?php endif; ?>

  </div>
</a>