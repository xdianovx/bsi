<?php
/**
 * На странице с шаблоном page-bsimice.php пункты меню с классами:
 * - bsimice-scroll-projects → якорь #projects
 * - bsimice-scroll-contact   → якорь #bsimice-contact
 *
 * Внешний вид: Настройки экрана → пункт меню → «Классы CSS».
 */

add_filter('nav_menu_link_attributes', 'bsimice_nav_menu_anchor_href', 10, 4);

/**
 * @param array<string, string> $atts
 * @param WP_Post $item
 * @param stdClass $args
 * @param int $depth
 * @return array<string, string>
 */
function bsimice_nav_menu_anchor_href(array $atts, $item, $args, $depth): array
{
  if (!is_page_template('page-bsimice.php')) {
    return $atts;
  }

  $classes = is_array($item->classes) ? $item->classes : [];
  $base = get_permalink();
  if (!$base) {
    return $atts;
  }

  if (in_array('bsimice-scroll-projects', $classes, true)) {
    $atts['href'] = $base . '#projects';
  }
  if (in_array('bsimice-scroll-contact', $classes, true)) {
    $atts['href'] = $base . '#bsimice-contact';
  }

  return $atts;
}
