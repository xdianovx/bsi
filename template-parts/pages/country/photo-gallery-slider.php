<?php
$gallery = get_field('galereya', get_the_ID());
?>

<?php if ($gallery): ?>



  <div class="country-page__gallery">
    <div class="swiper country-page__gallery-slider">
      <div class="swiper-wrapper">
        <?php foreach ($gallery as $image): ?>
          <div class="swiper-slide">
            <div class="country-page__gallery-slide">
              <a href="<?php echo esc_url($image['url']); ?>"
                 data-fancybox="gallery"
                 data-caption="<?php echo esc_attr($image['caption']); ?>">
                <img src="<?php echo esc_url($image['sizes']['medium']); ?>"
                     alt="<?php echo esc_attr($image['alt']); ?>" />
              </a>
            </div>
          </div>
        <?php endforeach; ?>

      </div>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
      <div class="swiper-pagination"></div>
    </div>

  </div>
<?php endif; ?>