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
 * @param \WP_Post $item
 * @param mixed $args
 * @param int $depth
 * @return array<string, string>
 */
function bsimice_nav_menu_anchor_href($atts, $item, $args, $depth): array
{
  if (!is_array($atts)) {
    $atts = [];
  }

  // В wp-admin нет корректного «главного» запроса страницы — is_page_template / get_permalink дают мусор и на части хостингов ловят fatal (редактор страниц MICE и др.).
  if (is_admin()) {
    return $atts;
  }

  if (!is_page_template('page-bsimice.php') && !is_page_template('page-mice.php')) {
    return $atts;
  }

  if (!is_object($item) || !isset($item->classes)) {
    return $atts;
  }

  $classes = is_array($item->classes) ? $item->classes : [];
  $base = get_permalink();
  if (!$base) {
    return $atts;
  }

  if (is_page_template('page-bsimice.php')) {
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

  if (is_page_template('page-mice.php') && in_array('bsimice-scroll-contact', $classes, true)) {
    $atts['href'] = $base . '#mice-contact';
  }

  return $atts;
}
