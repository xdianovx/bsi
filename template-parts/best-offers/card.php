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
$rating = (int) ($best_offer['rating'] ?? 0);
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

  <?php if (!empty($tags) && is_array($tags)): ?>
    <div class="best-offer-card__tags">
      <?php foreach ($tags as $tag): ?>
        <?php if (!$tag)
          continue; ?>
        <div class="best-offer-card__tag"><?= esc_html($tag); ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="best-offer-card__info">

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

    <?php if ($title): ?>
      <div class="best-offer-card__title">
        <?php if ($rating > 0): ?>
          <div class="best-offer-card__rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                fill="<?= $i <= $rating ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star-icon lucide-star <?= $i <= $rating ? 'filled' : ''; ?>">
                <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
              </svg>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
        <h3><?= esc_html($title); ?></h3>
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