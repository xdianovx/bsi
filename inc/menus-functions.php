<?php


add_action('after_setup_theme', 'theme_register_nav_menu');

function theme_register_nav_menu()
{
    register_nav_menu('header_nav', 'Мегаменю в шапке');



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
            $output .= '<a href="' . $url . '" class="header-menu__link --head' .
                ($is_active ? ' is-active' : '') . '">' . $title . '</a>';

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

            foreach ($countries as $country) {

                $country_title = esc_html($country->post_title);
                $country_link = get_permalink($country->ID);

                $flag = get_field('flag', $country->ID);
                $flag_url = '';
                if ($flag) {
                    if (is_array($flag) && !empty($flag['url'])) {
                        $flag_url = esc_url($flag['url']);
                    } else {
                        $flag_url = esc_url($flag);
                    }
                }

                $is_visa = get_field('is_visa', $country->ID);
                $visa_text = $is_visa ? 'Виза нужна' : 'Виза не нужна';

                $output .= '<a href="' . $country_link . '" class="countries-letter__link">';

                if ($flag_url) {
                    $output .= '<img src="' . $flag_url . '" alt="' . $country_title . '" class="countries-letter__flag">';
                }

                $output .= '<div class="countries-letter__info">';
                $output .= '<p class="countries-letter__name">' . $country_title . '</p>';
                $output .= '<div class="countries-letter__visa">' . $visa_text . '</div>';
                $output .= '</div>'; // .info

                $output .= '</a>';
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