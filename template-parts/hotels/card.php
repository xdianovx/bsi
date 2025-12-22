<?php
$hotel = get_query_var('hotel');
if (!$hotel || !is_array($hotel)) {
  return;
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
      <div class="hotel-card__location"><?php echo esc_html($hotel['location_title']); ?>
      </div>
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
    <?php if (!empty($hotel['tags'])): ?>
      <div class="hotel-card__tags">
        <?php foreach ($hotel['tags'] as $t): ?>
          <span class="hotel-card__tag"><?php echo esc_html($t); ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>




    <div class="hotel-card__meta">

      <?php if ($hotel['price']): ?>
        <div class="hotel-card__price numfont"><?php echo esc_html($hotel['price']); ?></div>
      <?php endif; ?>
    </div>
  </div>
</a>