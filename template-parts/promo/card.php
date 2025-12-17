<?php
$promo_id = get_the_ID();

$short = get_field('promo_short', $promo_id);
$promo_link = get_field('promo_link', $promo_id) ?: get_permalink($promo_id);

$raw_from = get_field('promo_date_from', $promo_id);
$raw_to = get_field('promo_date_to', $promo_id);

$formatted_from = format_date_value($raw_from);
$formatted_to = format_date_value($raw_to);

$thumb_url = get_the_post_thumbnail_url($promo_id, 'medium');
$title = get_the_title($promo_id);
$excerpt = get_the_excerpt($promo_id);
?>

<a href="<?= esc_url($promo_link); ?>"
   class="promo-card">
  <div class="promo-card__poster">
    <?php if ($thumb_url): ?>
      <img src="<?= esc_url($thumb_url); ?>"
           alt="<?= esc_attr($title); ?>">
    <?php endif; ?>
  </div>

  <div class="promo-card__info">
    <?php if ($formatted_from || $formatted_to): ?>
      <div class="promo-card__dates">
        <?php if ($formatted_from && $formatted_to): ?>
          <span class="promo-card__date-range">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="18"
                 height="18"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.5"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-calendar-days-icon lucide-calendar-days">
              <path d="M8 2v4" />
              <path d="M16 2v4" />
              <rect width="18"
                    height="18"
                    x="3"
                    y="4"
                    rx="2" />
              <path d="M3 10h18" />
              <path d="M8 14h.01" />
              <path d="M12 14h.01" />
              <path d="M16 14h.01" />
              <path d="M8 18h.01" />
              <path d="M12 18h.01" />
              <path d="M16 18h.01" />
            </svg>
            <span><?= esc_html($formatted_from); ?> – <?= esc_html($formatted_to); ?></span>
          </span>
        <?php elseif ($formatted_from): ?>
          <span class="promo-card__date-from"><?= esc_html($formatted_from); ?></span>
        <?php elseif ($formatted_to): ?>
          <span class="promo-card__date-to"><?= esc_html($formatted_to); ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <h3 class="h3 promo-card__item-title">
      <?= esc_html($title); ?>
    </h3>

    <?php if ($excerpt): ?>
      <div class="promo-card__excerpt">
        <?= esc_html($excerpt); ?>
      </div>
    <?php endif; ?>
  </div>
</a>