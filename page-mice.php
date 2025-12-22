<?php
/**
 * Template Name: MICE
 */
get_header('mice');
?>

<main class="mice-page">
  <section class="mice-hero-section">
    <div class="container">
      <div class="mice-hero__wrap">
        <div class="mice-hero-item">
          <h3 class="mice-hero-item__title">Бизнес тревел</h3>

          <div class="mice-hero-item__descr">Тут короткое описание с преимущствами! В админку не вижу смысла выводить,
            так как 1 раз напишете этот текст и забудете</div>


          <a href="http://localhost:8888/bsinew/novosti/"
             class="mice-hero-item__link link-arrow">
            <span>Подробнее</span>
            <div class="link-arrow__icon">

              <svg xmlns="http://www.w3.org/2000/svg"
                   width="24"
                   height="24"
                   viewBox="0 0 24 24"
                   fill="none"
                   stroke="currentColor"
                   stroke-width="1.5"
                   stroke-linecap="round"
                   stroke-linejoin="round"
                   class="lucide lucide-arrow-up-right-icon lucide-arrow-up-right">
                <path d="M7 7h10v10"></path>
                <path d="M7 17 17 7"></path>
              </svg>

            </div>
          </a>
        </div>

        <div class="mice-hero-item">
          <h3 class="mice-hero-item__title">MICE</h3>
          <div class="mice-hero-item__descr">Тут короткое описание с преимущствами! В админку не вижу смысла выводить,
            так как 1 раз напишете этот текст и забудете</div>


          <a href="http://localhost:8888/bsinew/novosti/"
             class="mice-hero-item__link link-arrow">
            <span>Подробнее</span>
            <div class="link-arrow__icon">

              <svg xmlns="http://www.w3.org/2000/svg"
                   width="24"
                   height="24"
                   viewBox="0 0 24 24"
                   fill="none"
                   stroke="currentColor"
                   stroke-width="1.5"
                   stroke-linecap="round"
                   stroke-linejoin="round"
                   class="lucide lucide-arrow-up-right-icon lucide-arrow-up-right">
                <path d="M7 7h10v10"></path>
                <path d="M7 17 17 7"></path>
              </svg>

            </div>
          </a>
        </div>

      </div>
    </div>
  </section>

  <?= get_template_part('template-parts/partners/partners-slider') ?>
  <?= get_template_part('template-parts/news/news-slider') ?>
  <?= get_template_part('template-parts/awards/slider') ?>
  <?= get_template_part('template-parts/reviews/slider') ?>


  <?php
  $rows = function_exists('get_field') ? get_field('mice_benefits') : [];
  if (!empty($rows) && is_array($rows)): ?>
    <section class="mice-benefits-section">
      <div class="container">
        <h2 class="h2 mice-benefits-section__title">Преимущества</h2>
        <div class="mice-benefit__wrap">

          <?php foreach ($rows as $row):
            $icon_url = !empty($row['icon']['url']) ? $row['icon']['url'] : '';
            $title = !empty($row['title']) ? $row['title'] : '';
            $text = !empty($row['text']) ? $row['text'] : '';
            if (!$title && !$text && !$icon_url)
              continue;
            ?>
            <div class="mice-benefit">

              <?php if ($title): ?>
                <div class="mice-benefit__title numfont"><?= esc_html($title); ?></div>
              <?php endif; ?>

              <?php if ($text): ?>
                <div class="mice-benefit__text"><?= esc_html(wp_strip_all_tags($text)); ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>


  <?= get_template_part('template-parts/projects/slider') ?>

  <section class="page-mice-cta">
    <div class="container">
      <h2 class="h2">Оставьте заявку</h2>
      <p class="page-mice-cta__description">И мы проконсультируем вас по всем вопросам</p>
      <div class="page-mice-cta__wrap">

        <form action="">
          <div class="input-item">
            <label for="phone">Телефон *</label>
            <input type="tel"
                   name="phone"
                   id="phone"
                   placeholder="+7 (___) ___-__-__">

            <div class="error-message"
                 data-field="phone">
            </div>
          </div>

          <div class="input-item">
            <label for="phone">Телефон *</label>
            <input type="tel"
                   name="phone"
                   id="phone"
                   placeholder="+7 (___) ___-__-__">

            <div class="error-message"
                 data-field="phone">
            </div>
          </div>
          <div class="input-item">
            <label for="phone">Телефон *</label>
            <input type="tel"
                   name="phone"
                   id="phone"
                   placeholder="+7 (___) ___-__-__">

            <div class="error-message"
                 data-field="phone">
            </div>
          </div>
          <div class="input-item">
            <label for="phone">Телефон *</label>
            <input type="tel"
                   name="phone"
                   id="phone"
                   placeholder="+7 (___) ___-__-__">

            <div class="error-message"
                 data-field="phone">
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>

  <?php
  the_content();
  ?>
</main>

<?php get_footer('mice'); ?>