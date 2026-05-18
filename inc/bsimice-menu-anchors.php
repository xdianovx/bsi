<?php
/**
 * На странице с шаблоном page-bsimice.php пункты меню с классами (экран «Классы CSS»):
 * - bsimice-scroll-services     → #bsimice-services (Услуги)
 * - bsimice-scroll-advantages   → #bsimice-advantages (Преимущества — плашки под hero)
 * - bsimice-scroll-projects     → #projects (Проекты)
 * - bsimice-scroll-contact      → #bsimice-contact (форма на лендинге) или #mice-contact (страница MICE)
 */

add_filter('nav_menu_link_attributes', 'bsimice_nav_menu_anchor_href', 10, 4);

/**
 * @param array<string, string>|mixed $atts
 * @param \WP_Post|object $item
 * @param mixed $args
 * @param int $depth
 * @return array<string, string>
 */
function bsimice_nav_menu_anchor_href($atts, $item, $args, $depth): array
{
  if (!is_array($atts)) {
    $atts = [];
  }

  if (!is_object($item) || !isset($item->classes)) {
    return $atts;
  }

  // is_page_template() / get_permalink() без ID опираются на main query — в админке и части запросов это даёт мусор или fatal.
  if (!is_singular('page')) {
    return $atts;
  }

  $page_id = (int) get_queried_object_id();
  if ($page_id <= 0) {
    return $atts;
  }

  $tpl = get_page_template_slug($page_id);
  if ($tpl !== 'page-bsimice.php' && $tpl !== 'page-mice.php') {
    return $atts;
  }

  $classes = is_array($item->classes) ? $item->classes : [];
  $base = get_permalink($page_id);
  if (!$base) {
    return $atts;
  }

  if ($tpl === 'page-bsimice.php') {
    if (in_array('bsimice-scroll-services', $classes, true)) {
      $atts['href'] = $base . '#bsimice-services';
    }
    if (in_array('bsimice-scroll-advantages', $classes, true)) {
      $atts['href'] = $base . '#bsimice-advantages';
    }
    if (in_array('bsimice-scroll-projects', $classes, true)) {
      $atts['href'] = $base . '#projects';
    }
    if (in_array('bsimice-scroll-contact', $classes, true)) {
      $atts['href'] = $base . '#bsimice-contact';
    }
  }

  if ($tpl === 'page-mice.php' && in_array('bsimice-scroll-contact', $classes, true)) {
    $atts['href'] = $base . '#mice-contact';
  }

  return $atts;
}
