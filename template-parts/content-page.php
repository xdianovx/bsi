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
    if (bsi_is_page_empty()):
      ?>
      <div class="page-empty-message">
        Страница заполняется
      </div>
      <?php
    else:
      the_content();

      wp_link_pages(
        array(
          'before' => '<div class="page-links">' . esc_html__('Pages:', 'bsi'),
          'after' => '</div>',
        )
      );
    endif;
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