<?php
$views = (int) get_post_meta(get_the_ID(), 'news_views', true);
?>

<div id="post-<?php the_ID(); ?>"
     <?php post_class('news-card'); ?>>
  <a href="<?php the_permalink(); ?>"
     class="news-card__link">
    <?php if (has_post_thumbnail()): ?>
      <div class="news-card__image">
        <?php the_post_thumbnail('medium_large'); ?>
      </div>
    <?php endif; ?>

    <div class="news-card__content">
      <div class="news-card__meta">
        <div class="news-card__views">
          <svg xmlns="http://www.w3.org/2000/svg"
               width="18"
               height="18"
               viewBox="
               0
               0
               24
               24"
               fill="none"
               stroke="currentColor"
               stroke-width="1.5"
               stroke-linecap="round"
               stroke-linejoin="round"
               class="lucide lucide-eye-icon lucide-eye">
            <path
                  d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
            <circle cx="12"
                    cy="12"
                    r="3" />
          </svg>

          <p><?= format_number($views) ?></p>
        </div>
        <time class="news-card__date"
              datetime="<?php echo get_the_date('c'); ?>">
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
          <span>

            <?php echo get_the_date('j F Y'); ?>
          </span>
        </time>
      </div>
      <h2 class="news-card__title"><?php the_title(); ?></h2>



      <?php if (get_the_excerpt()): ?>
        <div class="news-card__excerpt">
          <?= get_the_excerpt(); ?>
        </div>
      <?php endif; ?>

    </div>
  </a>
</div>