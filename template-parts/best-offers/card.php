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
$location_title = $best_offer['location_title'] ?? '';
$location_extra = $best_offer['location_extra'] ?? '';
$flag_url = (string) ($best_offer['flag'] ?? '');

$price_raw = $best_offer['price'] ?? '';
$price = format_price_text($price_raw);
?>

<a href="<?= esc_url($url); ?>"
   class="best-offer-card">
  <?php if ($flag_url): ?>
    <div class="best-offer-card__location-flag">
      <img src="<?= esc_url($flag_url) ?>"
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
      <div class="best-offer-card__title">
        <h3><?= esc_html($title); ?></h3>
      </div>
    <?php endif; ?>

    <?php if ($flag_url || $location_title): ?>
      <div class="best-offer-card__location">
        <?php if ($flag_url): ?>
          <div class="best-offer-card__location-flag">
            <img src="<?= esc_url($flag_url); ?>" alt="">
          </div>
        <?php endif; ?>
        <?php if ($location_title): ?>
          <div class="best-offer-card__location-title">
            <?= esc_html($location_title); ?>
            <?php if ($location_extra): ?>
              <span class="best-offer-card__location-extra"><?= esc_html($location_extra); ?></span>
            <?php endif; ?>
          </div>
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