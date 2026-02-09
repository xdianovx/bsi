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
        <?php
        $current_page_id = get_the_ID();
        $child_pages = get_posts([
          'post_type' => 'page',
          'post_parent' => $current_page_id,
          'numberposts' => -1,
          'orderby' => 'menu_order',
          'order' => 'ASC',
          'post_status' => 'publish',
        ]);

        if (!empty($child_pages)):
          foreach ($child_pages as $child):
            $child_id = (int) $child->ID;
            $child_title = get_the_title($child_id);
            $child_url = get_permalink($child_id);
            $child_excerpt = get_the_excerpt($child_id);
            $child_image = '';

            // Получаем Featured Image
            if (has_post_thumbnail($child_id)) {
              $child_image = get_the_post_thumbnail_url($child_id, 'full');
            }
            ?>
            <div class="mice-hero-item">
              <h3 class="mice-hero-item__title"><?php echo esc_html($child_title); ?></h3>

              <?php if ($child_excerpt): ?>
                <div class="mice-hero-item__descr"><?php echo wp_kses_post($child_excerpt); ?></div>
              <?php endif; ?>

              <a href="<?php echo esc_url($child_url); ?>" class="mice-hero-item__link link-arrow">
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

              <?php if ($child_image): ?>
                <img src="<?php echo esc_url($child_image); ?>"
                     class="mice-hero-item__bg"
                     alt="<?php echo esc_attr($child_title); ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

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


  <?= get_template_part('template-parts/news/news-slider') ?>
  <?= get_template_part('template-parts/awards/slider') ?>
  <?= get_template_part('template-parts/reviews/slider') ?>
  <?= get_template_part('template-parts/partners/partners-slider') ?>




  <section class="page-mice-cta">
    <div class="container">
      <h2 class="h2">Оставьте заявку</h2>
      <p class="page-mice-cta__description">И мы проконсультируем вас по всем вопросам</p>
      <div class="page-mice-cta__wrap">

        <form action="">
          <div class="form-row form-row-2">
            <div class="input-item white ">
              <label for="name">Имя *</label>
              <input type="text"
                     name="name"
                     id="name"
                     placeholder="Ваше имя">

              <div class="error-message"
                   data-field="phone">
              </div>
            </div>

            <div class="input-item white">
              <label for="company">Компания</label>
              <input type="text"
                     name="company"
                     id="company"
                     placeholder="Название">

              <div class="error-message"
                   data-field="phone">
              </div>
            </div>

            <div class="input-item white">
              <label for="phone">Телефон *</label>
              <input type="tel"
                     name="phone"
                     id="phone"
                     placeholder="+7 (___) ___-__-__">

              <div class="error-message"
                   data-field="phone">
              </div>
            </div>
            <div class="input-item white">
              <label for="email">Email</label>
              <input type="email"
                     name="email"
                     id="email"
                     placeholder="Почта">

              <div class="error-message"
                   data-field="phone">
              </div>
            </div>
          </div>

          <div class="">
            <div id="form-status"></div>
            <button type="submit"
                    class="btn btn-accent page-mice-cta-submit">
              Отправить
            </button>

            <p class="form-policy fit-form__policy">
              Нажимая на кнопку "Отправить", вы соглашаетесь с <a
                 href="http://localhost:8888/bsinew/politika-v-otnoshenii-obrabotki-personalnyh-dannyh/"
                 class="policy-link">
                нашей политикой обработки персональных данных
              </a>
            </p>
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