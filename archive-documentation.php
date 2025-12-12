<?php
get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <div class="container">

    <header class="archive-header">
      <h1 class="archive-title">Документация</h1>
      <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
    </header>

    <div class="docs-archive">

      <?php
      // Пагинация
      $paged = get_query_var('paged') ? (int) get_query_var('paged') : 1;

      // Кастомный запрос для документации
      $docs_query = new WP_Query([
        'post_type' => 'documentation',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        'paged' => $paged,
        'orderby' => 'menu_order',
        'order' => 'ASC',
      ]);
      ?>

      <?php if ($docs_query->have_posts()): ?>
        <div class="docs-archive__list">
          <?php while ($docs_query->have_posts()):
            $docs_query->the_post(); ?>
            <article id="post-<?php the_ID(); ?>"
                     <?php post_class('doc-card'); ?>>
              <a href="<?php the_permalink(); ?>"
                 class="doc-card__link">

                <?php if (has_post_thumbnail()): ?>
                  <div class="doc-card__image">
                    <?php the_post_thumbnail('medium_large'); ?>
                  </div>
                <?php endif; ?>

                <div class="doc-card__content">
                  <h2 class="doc-card__title"><?php the_title(); ?></h2>

                  <?php if (get_the_excerpt()): ?>
                    <div class="doc-card__excerpt">
                      <?php the_excerpt(); ?>
                    </div>
                  <?php endif; ?>

                  <span class="doc-card__more">Открыть документ</span>
                </div>
              </a>
            </article>
          <?php endwhile; ?>
        </div>

        <!-- Пагинация -->
        <div class="docs-pagination">
          <?php
          echo paginate_links([
            'total' => $docs_query->max_num_pages,
            'current' => $paged,
            'prev_text' => '&larr; Назад',
            'next_text' => 'Вперед &rarr;',
            'mid_size' => 2,
          ]);
          ?>
        </div>

      <?php else: ?>
        <div class="docs-empty">
          <p>Документация пока не добавлена.</p>
        </div>
      <?php endif; ?>

      <?php wp_reset_postdata(); ?>

    </div>

  </div>
</main>

<?php get_footer(); ?>