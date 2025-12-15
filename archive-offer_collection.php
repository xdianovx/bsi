<?php
get_header();

$collections = new WP_Query([
  'post_type' => 'offer_collection',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'orderby' => 'date',
  'order' => 'DESC',
]);
?>

<main class="site-main">
  <?php if (function_exists('yoast_breadcrumb')): ?>
    <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
  <?php endif; ?>

  <section class="archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 archive-page__title">Лучшие предложения</h1>
      </div>
    </div>
  </section>

  <section class="archive-page__content-section">
    <div class="container">
      <?php if ($collections->have_posts()): ?>
        <?php while ($collections->have_posts()):
          $collections->the_post(); ?>
          <?php
          $collection_id = get_the_ID();
          $sections = get_field('offer_sections', $collection_id) ?: [];
          ?>

          <?php if (!empty($sections)): ?>
            <div class="best-offers-section">
              <h2 class="h2 best-offers-section__title"><?= esc_html(get_the_title($collection_id)); ?></h2>

              <p class="best-offers-section__descr">
                <?= get_the_excerpt($collection_id); ?>
              </p>

              <?php foreach ($sections as $section): ?>
                <?php
                $section_title = $section['title'] ?? '';
                $items = $section['items'] ?? [];
                ?>

                <?php if ($section_title): ?>
                  <h3 class="h3 best-offers-section__subtitle">
                    <?= esc_html($section_title); ?>
                  </h3>
                <?php endif; ?>

                <?php if (!empty($items)): ?>
                  <div class="best-offers-grid">
                    <?php foreach ($items as $row): ?>
                      <?php
                      $post_obj = $row['post'] ?? null;
                      if (!$post_obj instanceof WP_Post)
                        continue;

                      $badges = $row['badges'] ?? [];
                      $tags = [];
                      if (is_array($badges)) {
                        foreach ($badges as $t) {
                          if (!empty($t->name))
                            $tags[] = $t->name;
                        }
                      }

                      $title = ($row['title_override'] ?? '') ?: get_the_title($post_obj->ID);
                      $url = ($row['link_override'] ?? '') ?: get_permalink($post_obj->ID);

                      $image = '';
                      if (!empty($row['image_override']['url'])) {
                        $image = $row['image_override']['url'];
                      } else {
                        $thumb = get_the_post_thumbnail_url($post_obj->ID, 'large');
                        if ($thumb)
                          $image = $thumb;
                      }

                      $type_obj = get_post_type_object($post_obj->post_type);
                      $type = $type_obj && !empty($type_obj->labels->singular_name) ? $type_obj->labels->singular_name : '';

                      $location_title = ($row['location_override'] ?? '') ?: '';
                      $price = $row['price'] ?? '';

                      $card = [
                        'url' => $url,
                        'image' => $image,
                        'type' => $type,
                        'tags' => $tags,
                        'title' => $title,
                        'flag' => '',
                        'location_title' => $location_title,
                        'price' => $price,
                      ];
                      ?>
                      <?php get_template_part('template-parts/best-offers/card', null, [
                        'best_offer' => $card,
                        'post_id' => $post_obj->ID,
                      ]); ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
      <?php else: ?>
        <div class="reviews-archive__empty">
          <p>Подборок пока нет.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>