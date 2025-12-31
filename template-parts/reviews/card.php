<?php
$review_id = get_the_ID();
$company = get_field('review_company', $review_id);
$company_logo = get_field('review_company_logo', $review_id);
$author_name = get_field('review_author_name', $review_id);
$author_position = get_field('review_author_position', $review_id);
$author_photo = get_field('review_author_photo', $review_id);
$review_date = get_field('review_date', $review_id);
$review_text = get_field('review_text', $review_id);
$thankyou_letter = get_field('review_thankyou_letter', $review_id);
$review_permalink = get_permalink($review_id);
$letter_url = !empty($thankyou_letter['url']) ? $thankyou_letter['url'] : '';


$excerpt = $review_text ?: get_the_excerpt($review_id);
?>

<div class="review-card">
  <div class="review-card__link">
    <?php if ($review_date): ?>
      <time class="review-card__date"
            datetime="<?= esc_attr($review_date); ?>">
        <?= esc_html(format_date_russian($review_date)); ?>
      </time>
    <?php endif; ?>

    <div class="review-card__body">
      <div class="review-card__head">
        <div class="review-card__company">
          <?php if (!empty($company_logo['url'])): ?>
            <div class="review-card__company-logo">
              <img src="<?= esc_url($company_logo['url']); ?>"
                   alt="<?= esc_attr($company ?: get_the_title()); ?>">
            </div>
          <?php endif; ?>

          <div class="review-card__company-info">
            <?php if ($company): ?>
              <div class="review-card__company-name">
                <?= esc_html($company); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>


      </div>
      <div class="">
        <h2 class="review-card__title">
          <?= esc_html(get_the_title()); ?>
        </h2>

        <?php if ($excerpt): ?>
          <div class="review-card__text">
            <?= $excerpt; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="review-card-hr"></div>

    <div class="review-card__footer">
      <?php if ($letter_url): ?>
        <a href="<?= esc_url($letter_url); ?>"
           data-fancybox="review-<?= esc_attr($review_id); ?>"
           class="review-card__badge">
          <svg xmlns="http://www.w3.org/2000/svg"
               width="20"
               height="20"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               stroke-width="1.5"
               stroke-linecap="round"
               stroke-linejoin="round"
               class="lucide lucide-file-icon lucide-file">
            <path
                  d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" />
            <path d="M14 2v5a1 1 0 0 0 1 1h5" />
          </svg>
          <span>Вложение</span>
        </a>
      <?php endif; ?>
      <div class="review-card__author">
        <?php if (!empty($author_photo['url'])): ?>
          <div class="review-card__author-photo">
            <img src="<?= esc_url($author_photo['url']); ?>"
                 alt="<?= esc_attr($author_name ?: 'Автор отзыва'); ?>">
          </div>
        <?php endif; ?>

        <div class="review-card__author-info">
          <?php if ($author_name): ?>
            <div class="review-card__author-name">
              <?= esc_html($author_name); ?>
            </div>
          <?php endif; ?>

          <?php if ($author_position): ?>
            <div class="review-card__author-position">
              <?= esc_html($author_position); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>