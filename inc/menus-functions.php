<?php


add_action('after_setup_theme', 'theme_register_nav_menu');

function theme_register_nav_menu()
{
    register_nav_menu('header_nav', 'Мегаменю в шапке');
    register_nav_menu('mobile_nav', 'Мобильное меню');
    register_nav_menu('footer_nav', 'Меню в подвале');
    register_nav_menu('mice_header_nav', 'MICE: меню в шапке');
}


/**
 * Кастомный Walker для мегаменю.
 *
 * При глубине 0 выводит <li class="header-menu__item"><a ...>
 * При глубине 1 — заголовок колонки и обёртку .mega-menu__col.
 * При глубине 2 — списки ссылок внутри колонок.
 */
class BSI_Mega_Menu_Walker extends Walker_Nav_Menu
{

    public function start_lvl(&$output, $depth = 0, $args = array())
    {
        if ($depth === 0) {
            // начинаем блок мегаменю
            $output .= "\n<div class=\"mega-menu\"><div class=\"mega-menu__inner\">\n";
        } elseif ($depth === 1) {
            // открываем список ссылок внутри колонки
            $output .= "<ul class=\"mega-menu__list \">\n";
        }
    }

    public function end_lvl(&$output, $depth = 0, $args = array())
    {
        if ($depth === 0) {
            $output .= "</div></div>\n";          // закрываем mega-menu__inner и mega-menu
        } elseif ($depth === 1) {
            $output .= "</ul>\n";                 // закрываем список ссылок
        }
    }

    public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
    {

        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $title = esc_html($item->title);
        $url = esc_url($item->url);

        // ----------------------------------------
        // ACTIVE STATE
        // ----------------------------------------
        $is_active = false;
        $active_classes = [
            'current-menu-item',
            'current-menu-parent',
            'current-menu-ancestor',
            'current_page_item',
            'current_page_parent'
        ];
        foreach ($active_classes as $ac) {
            if (in_array($ac, $classes, true)) {
                $is_active = true;
                break;
            }
        }

        // ----------------------------------------
        if ($depth === 0 && in_array('auto-country', $classes, true)) {

            // Верхний пункт меню
            $output .= '<li class="header-menu__item --countries">';
            $output .= '<a href="' . $url . '" class="header-menu__link --head' . ($is_active ? ' is-active' : '') . '">' . $title . '</a>';

            // Начинаем мегаменю
            $output .= '<div class="mega-menu mega-menu--countries"><div class="mega-menu__inner">';
            $output .= '<div class="countries-letter-list countries-letter-list__menu countries-letter-list--menu">';

            // Загружаем страны
            $countries = get_posts([
                'post_type' => 'country',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
                'post_parent' => 0,
            ]);

            // Группируем по первой букве
            $groups = [];

            foreach ($countries as $country) {
                $country_title = (string) $country->post_title;
                $letter = mb_strtoupper(mb_substr($country_title, 0, 1, 'UTF-8'), 'UTF-8');

                // нормализация (по желанию)
                if ($letter === 'Ё')
                    $letter = 'Е';

                $country_link = get_permalink($country->ID);

                $flag = function_exists('get_field') ? get_field('flag', $country->ID) : '';
                $flag_url = '';
                if ($flag) {
                    $flag_url = is_array($flag) && !empty($flag['url']) ? esc_url($flag['url']) : esc_url($flag);
                }

                $is_visa = function_exists('get_field') ? get_field('is_visa', $country->ID) : false;
                $visa_text = $is_visa ? 'Требуется виза' : 'Виза не нужна';

                // Собираем HTML ссылки (как у тебя было)
                $item_html = '<a href="' . esc_url($country_link) . '" class="countries-letter__link">';
                if ($flag_url) {
                    $item_html .= '<img src="' . $flag_url . '" alt="' . esc_attr($country_title) . '" class="countries-letter__flag">';
                }
                $item_html .= '<div class="countries-letter__info">';
                $item_html .= '<p class="countries-letter__name">' . esc_html($country_title) . '</p>';
                $item_html .= '<div class="countries-letter__visa">' . esc_html($visa_text) . '</div>';
                $item_html .= '</div>';
                $item_html .= '</a>';

                $groups[$letter][] = $item_html;
            }

            // Сортируем буквы (обычно уже ок, но на всякий)
            if (class_exists('Collator')) {
                $collator = new Collator('ru_RU');
                uksort($groups, function ($a, $b) use ($collator) {
                    return $collator->compare($a, $b);
                });
            } else {
                ksort($groups);
            }

            // Вывод групп: БУКВА + список
            foreach ($groups as $letter => $items) {
                $output .= '<div class="countries-letter-group">';
                $output .= '<div class="countries-letter-group__letter">' . esc_html($letter) . '</div>';
                $output .= '<div class="countries-letter-group__items">';
                $output .= implode('', $items);
                $output .= '</div>';
                $output .= '</div>';
            }

            // Закрываем обёртки
            $output .= '</div>'; // countries-letter-list
            $output .= '</div></div>'; // mega-menu__inner + mega-menu

            return;
        }

        // ----------------------------------------
        // DEFAULT MENU OUTPUT (non-country)
        // ----------------------------------------

        if ($depth === 0) {

            $output .= '<li class="header-menu__item">';
            $output .= '<a href="' . $url . '" class="header-menu__link --head' .
                ($is_active ? ' is-active' : '') . '">' . $title . '</a>';

        } elseif ($depth === 1) {

            $output .= '<div class="mega-menu__col">';
            $output .= '<div class="mega-menu__title' .
                ($is_active ? ' is-active' : '') . '">' . $title . '</div>';

        } elseif ($depth === 2) {

            $output .= '<li class="mega-menu__item">';
            $output .= '<a href="' . $url . '" class="mega-menu__link' .
                ($is_active ? ' is-active' : '') . '">' . $title . '</a>';
        }
    }

    public function end_el(&$output, $item, $depth = 0, $args = array())
    {
        if ($depth === 0) {
            $output .= "</li>\n";
        } elseif ($depth === 1) {
            $output .= "</div>\n";  // закрываем .mega-menu__col
        } elseif ($depth === 2) {
            $output .= "</li>\n";
        }
    }
}


class Mobile_Nav_Walker extends Walker_Nav_Menu
{
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '<div class="mobile-nav__submenu">';
        }
    }

    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '</div>';
        }
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $classes = is_array($item->classes) ? $item->classes : [];
        $has_children = in_array('menu-item-has-children', $classes, true);

        $is_active = (
            in_array('current-menu-item', $classes, true) ||
            in_array('current-menu-parent', $classes, true) ||
            in_array('current-menu-ancestor', $classes, true) ||
            in_array('current_page_item', $classes, true) ||
            in_array('current_page_parent', $classes, true)
        );

        $title = esc_html($item->title);
        $url = !empty($item->url) ? esc_url($item->url) : '#';
        $target = !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
        $rel = !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';

        if ($depth === 0) {
            $output .= '<div class="mobile-nav__item' . ($is_active ? ' active' : '') . '">';
            $output .= '<a href="' . $url . '" class="mobile-nav__link"' . ($has_children ? ' aria-expanded="false"' : '') . $target . $rel . '>';
            $output .= '<span>' . $title . '</span>';

            if ($has_children) {
                $output .= '<div class="mobile-nav__link-chevron">';
                $output .= '<img src="' . esc_url(get_template_directory_uri() . '/img/icons/chevron-d-red.svg') . '" alt="">';
                $output .= '</div>';
            }

            $output .= '</a>';
            return;
        }

        if ($depth === 1) {
            $output .= '<a href="' . $url . '" class="mobile-nav__link mobile-nav__link--child"' . $target . $rel . '>';
            $output .= '<span>' . $title . '</span>';
            $output .= '</a>';
        }
    }

    public function end_el(&$output, $item, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '</div>';
        }
    }
}

add_filter('nav_menu_link_attributes', function ($atts, $item, $args) {
    if (!isset($args->theme_location) || $args->theme_location !== 'footer_nav') {
        return $atts;
    }

    $atts['class'] = isset($atts['class']) ? trim($atts['class'] . ' footer-link') : 'footer-link';
    return $atts;
}, 10, 3);