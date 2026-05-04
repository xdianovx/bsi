<?php
/**
 * Template Name: MICE
 */
get_header('mice');
?>

<main class="mice-page">
  <?php
  $pid = get_the_ID();

  $mice_benefits_rows = function_exists('get_field') ? get_field('mice_benefits', 'option') : [];
  $mice_has_benefits = !empty($mice_benefits_rows) && is_array($mice_benefits_rows);

  $mice_editor_raw = trim((string) get_post_field('post_content', $pid, 'raw'));

  $reviews_heading_opt = function_exists('get_field') ? get_field('mice_reviews_slider_heading', 'option') : '';
  $reviews_heading = $reviews_heading_opt !== '' && $reviews_heading_opt !== null ? (string) $reviews_heading_opt : 'Нас Благодарят';

  $reviews_rows = function_exists('bsi_get_mice_parent_reviews_rows') ? bsi_get_mice_parent_reviews_rows() : [];
  $reviews_from_acf = $reviews_rows !== [];

  if (!$reviews_from_acf) {
    $reviews_opt = function_exists('get_field') ? get_field('mice_reviews_slider', 'option') : null;
    if (!empty($reviews_opt) && is_array($reviews_opt) && count($reviews_opt) > 0) {
      $reviews_rows = $reviews_opt;
      $reviews_from_acf = true;
    } elseif (function_exists('bsi_mice_reviews_slider_default_rows')) {
      $reviews_rows = bsi_mice_reviews_slider_default_rows();
      $reviews_from_acf = false;
    }
  }

  $mice_has_reviews = false;
  foreach ($reviews_rows as $mice_rev_row) {
    if (!is_array($mice_rev_row)) {
      continue;
    }
    $mice_q = $mice_rev_row['quote'] ?? '';
    $mice_a = $mice_rev_row['author_name'] ?? '';
    if ($mice_q !== '' || $mice_a !== '') {
      $mice_has_reviews = true;
      break;
    }
  }
  ?>

  <section id="mice-hero" class="mice-hero-section">
    <div class="container">
      <h1 class="h1 mice-page__title"><?php the_title(); ?></h1>
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
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-arrow-up-right-icon lucide-arrow-up-right">
                    <path d="M7 7h10v10"></path>
                    <path d="M7 17 17 7"></path>
                  </svg>
                </div>
              </a>

              <?php if ($child_image): ?>
                <img src="<?php echo esc_url($child_image); ?>" class="mice-hero-item__bg"
                  alt="<?php echo esc_attr($child_title); ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($mice_has_benefits): ?>
    <section id="mice-benefits" class="mice-benefits-section">
      <div class="container">
        <h2 class="h2 mice-benefits-section__title">Преимущества</h2>
        <div class="mice-benefit__wrap">

          <?php foreach ($mice_benefits_rows as $row):
            $icon_url = !empty($row['icon']['url']) ? $row['icon']['url'] : '';
            $title = !empty($row['title']) ? $row['title'] : '';
            $text = !empty($row['text']) ? $row['text'] : '';
            if (!$title && !$text && !$icon_url) {
              continue;
            }
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

  <?php get_template_part('template-parts/projects/slider'); ?>


  <?php get_template_part('template-parts/news/news-slider', null, [
    'filter_mice' => true,
    'section_id' => 'mice-news',
  ]); ?>
  <?php get_template_part('template-parts/awards/slider', null, [
    'filter_mice' => true,
    'section_id' => 'mice-awards',
  ]); ?>
  <?php if ($mice_has_reviews): ?>
    <?php
    set_query_var('bsimice_reviews_slider_reviews', $reviews_rows);
    set_query_var('bsimice_reviews_slider_heading', $reviews_heading);
    set_query_var('bsimice_reviews_slider_from_acf', $reviews_from_acf);
    set_query_var('bsimice_reviews_section_id', 'mice-reviews');
    get_template_part('template-parts/mice/reviews-slider');
    ?>
  <?php endif; ?>
  <?php get_template_part('template-parts/partners/partners-slider', null, [
    'filter_mice' => true,
    'section_id' => 'mice-partners',
  ]); ?>




  <?php
  $mice_cta_title = function_exists('get_field') ? get_field('mice_cta_title', 'option') : '';
  $mice_cta_description = function_exists('get_field') ? get_field('mice_cta_description', 'option') : '';
  set_query_var('mice_consultation_cfg', [
    'section_class' => 'visa-page-consultation__section mice-page-consultation',
    'section_id' => 'mice-contact',
    'heading' => $mice_cta_title !== '' && $mice_cta_title !== null ? (string) $mice_cta_title : 'Оставьте заявку',
    'description' => $mice_cta_description !== '' && $mice_cta_description !== null
      ? (string) $mice_cta_description
      : 'И мы проконсультируем вас по всем вопросам',
  ]);
  get_template_part('template-parts/mice/consultation-form-section');
  ?>

  <?php if ($mice_editor_raw !== ''): ?>
    <div id="mice-content" class="mice-page__content">
      <?php the_content(); ?>
    </div>
  <?php else: ?>
    <?php the_content(); ?>
  <?php endif; ?>
</main>

<?php get_template_part('template-parts/mice/consultation-form-modal'); ?>

<?php get_footer('mice'); ?>
