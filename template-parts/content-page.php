<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bsi
 */

?>

<article id="post-<?php the_ID(); ?>"
         <?php post_class(); ?>>


         <header class="entry-header">
    <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
  </header><!-- .entry-header -->

  <?php bsi_post_thumbnail(); ?>

  <div class="entry-content">
    <?php
    // page.php уже собрал контент с id у H2 для оглавления.
    $page_toc_html = (string) get_query_var('page_toc_html', '');
    if ($page_toc_html !== '') {
      echo $page_toc_html; // phpcs:ignore WordPress.Security.EscapeOutput -- результат the_content
    } else {
      the_content();
    }

    wp_link_pages(
      array(
        'before' => '<div class="page-links">' . esc_html__('Pages:', 'bsi'),
        'after' => '</div>',
      )
    );
    ?>
  </div><!-- .entry-content -->

  <?php if (get_edit_post_link()): ?>
    <footer class="entry-footer">
      <?php
      edit_post_link(
        sprintf(
          wp_kses(
            /* translators: %s: Name of current post. Only visible to screen readers */
            __('Edit <span class="screen-reader-text">%s</span>', 'bsi'),
            array(
              'span' => array(
                'class' => array(),
              ),
            )
          ),
          wp_kses_post(get_the_title())
        ),
        '<span class="edit-link">',
        '</span>'
      );
      ?>
    </footer><!-- .entry-footer -->
  <?php endif; ?>
</article><!-- #post-<?php the_ID(); ?> -->