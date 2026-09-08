<?php

/**
 * Группировка админ-меню: типы записей собраны в тематические хабы,
 * служебные пункты — внизу за разделителем.
 *
 * Регистрации CPT не трогаем: пункты переносятся уже после того, как их
 * добавили все плагины и типы записей (приоритет 999 на `admin_menu`).
 * Так порядок не зависит от того, кто и когда зарегистрировал меню.
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Хабы верхнего уровня и их содержимое (слаги пунктов меню).
 *
 * Элемент `items` — либо слаг существующего пункта меню, либо готовая тройка
 * [название, право, слаг] для своего пункта (таксономии, отдельные страницы).
 *
 * @return array<string, array{title:string, icon:string, items:array}>
 */
function bsi_admin_menu_hubs(): array
{
  return [
    'bsi-hub-products' => [
      'title' => 'Продукты',
      'icon' => 'dashicons-palmtree',
      'items' => [
        'edit.php?post_type=tour',
        'edit.php?post_type=event',
        'edit.php?post_type=hotel',
        'edit.php?post_type=education',
        'edit.php?post_type=visa',
        'edit.php?post_type=insurance',
        'edit.php?post_type=offer_collection',
        'mice-pages',
      ],
    ],
    'bsi-hub-destinations' => [
      'title' => 'Направления',
      'icon' => 'dashicons-location-alt',
      'items' => [
        'edit.php?post_type=country',
        /* Таксономии стран — сразу под «Странами», ими пользуются постоянно. */
        ['Регионы', 'manage_categories', 'edit-tags.php?taxonomy=region&post_type=country'],
        ['Курорты', 'manage_categories', 'edit-tags.php?taxonomy=resort&post_type=country'],
        'edit.php?post_type=excursion',
        'edit.php?post_type=tourist_memo',
        'edit.php?post_type=entry_rules',
        'edit.php?post_type=hotel_info',
        'edit.php?post_type=hotel_deposit',
      ],
    ],
    'bsi-hub-content' => [
      'title' => 'Контент',
      'icon' => 'dashicons-media-document',
      'items' => [
        'edit.php?post_type=page',
        'edit.php?post_type=news',
        'edit.php?post_type=promo',
        'sections',
        'edit.php?post_type=project',
        'edit.php?post_type=award',
        'edit.php?post_type=partner',
      ],
    ],
    'bsi-hub-agencies' => [
      'title' => 'Агентствам и HR',
      'icon' => 'dashicons-groups',
      'items' => [
        'edit.php?post_type=documentation',
        'edit.php?post_type=agency_event',
        'edit.php?post_type=vacancy',
      ],
    ],
    'bsi-hub-settings' => [
      'title' => 'Настройки сайта',
      'icon' => 'dashicons-admin-generic',
      'items' => [
        'main_info',
        'currency-settings',
        'bsi-tour-prices-cache',
        'maintenance-modal-settings',
      ],
    ],
  ];
}

/**
 * Служебные пункты — нижняя группа за разделителем, в этом порядке.
 *
 * @return string[]
 */
function bsi_admin_menu_bottom(): array
{
  return [
    'upload.php',
    'edit.php',                           // Записи (не используются, но не прячем)
    'edit.php?post_type=acf-field-group',
    'wpseo_dashboard',
    'duplicator',
    'themes.php',
    'plugins.php',
    'users.php',
    'options-general.php',
    'tools.php',
  ];
}

/**
 * Создание хабов. Приоритет 998 — до переноса пунктов.
 */
add_action('admin_menu', function () {
  foreach (bsi_admin_menu_hubs() as $slug => $hub) {
    add_menu_page(
      $hub['title'],
      $hub['title'],
      'edit_pages',
      $slug,
      'bsi_render_menu_hub',
      $hub['icon']
    );
  }
}, 998);

/**
 * Перенос пунктов внутрь хабов + разделитель перед служебной группой.
 */
add_action('admin_menu', function () {
  global $menu, $submenu;

  /* Слаг пункта => ключ в $menu */
  $by_slug = [];
  foreach ($menu as $key => $item) {
    if (!empty($item[2])) {
      $by_slug[$item[2]] = $key;
    }
  }

  foreach (bsi_admin_menu_hubs() as $hub_slug => $hub) {
    /* Первым подпунктом WordPress дублирует сам хаб — убираем дубль,
       но сохраняем страницы, которые уже зарегистрировали в этом хабе
       (например «Импорт экскурсий»): без них WP отдаёт 403. */
    $registered = array_values(array_filter(
      $submenu[$hub_slug] ?? [],
      static fn(array $item): bool => $item[2] !== $hub_slug
    ));
    $submenu[$hub_slug] = [];

    foreach ($hub['items'] as $item_slug) {
      /* Готовая тройка [название, право, слаг] — добавляем как есть. */
      if (is_array($item_slug)) {
        if (current_user_can($item_slug[1])) {
          $submenu[$hub_slug][] = $item_slug;
        }
        continue;
      }

      if (!isset($by_slug[$item_slug])) {
        continue;
      }

      $item = $menu[$by_slug[$item_slug]];
      $submenu[$hub_slug][] = [$item[0], $item[1], $item[2]];
      unset($menu[$by_slug[$item_slug]]);
    }

    foreach ($registered as $registered_item) {
      $submenu[$hub_slug][] = $registered_item;
    }

    if (empty($submenu[$hub_slug])) {
      unset($submenu[$hub_slug]);
    }
  }

  /* Подменю хабов — первыми в $submenu: get_admin_page_parent() возвращает
     первого найденного родителя, и без этого он находил бы собственное
     подменю типа записи. Сами подменю типов сохраняем — иначе WP отдаёт 403
     на их страницах (например «Re-Order» плагина Post Types Order). */
    $hub_keys = array_keys(bsi_admin_menu_hubs());
  $reordered = [];
  foreach ($hub_keys as $hub_key) {
    if (isset($submenu[$hub_key])) {
      $reordered[$hub_key] = $submenu[$hub_key];
    }
  }
  foreach ($submenu as $key => $items) {
    if (!isset($reordered[$key])) {
      $reordered[$key] = $items;
    }
  }
  $submenu = $reordered;

  $menu['58.9'] = ['', 'read', 'bsi-separator-bottom', '', 'wp-menu-separator'];

  ksort($menu);
}, 999);

/**
 * Порядок верхнего уровня: Консоль → хабы → разделитель → служебное.
 */
add_filter('custom_menu_order', '__return_true', 9999);
add_filter('menu_order', function ($menu_ord) {
  if (!is_array($menu_ord)) {
    return $menu_ord;
  }

  $top = array_merge(['index.php'], array_keys(bsi_admin_menu_hubs()));
  $bottom = bsi_admin_menu_bottom();
  $separators = ['separator1', 'separator2', 'separator-last', 'bsi-separator-bottom'];

  /* Всё, что не разложено по группам, остаётся в середине в исходном порядке. */
  $middle = array_values(array_diff($menu_ord, $top, $bottom, $separators));

  $bottom_present = array_values(array_intersect($bottom, $menu_ord));
  $bottom_block = $bottom_present ? array_merge(['bsi-separator-bottom'], $bottom_present) : [];

  return array_merge(array_values(array_intersect($top, $menu_ord)), $middle, $bottom_block);
}, 9999);

/**
 * Подсветка активного хаба на страницах перенесённых типов записей.
 */
/* Приоритет 999: плагины (Post Types Order, Yoast) правят parent_file позже. */
add_filter('parent_file', function ($parent_file) {
  global $submenu_file, $current_screen;

  if (!$current_screen) {
    return $parent_file;
  }

  $post_type = (string) $current_screen->post_type;
  $candidates = [];
  if ($post_type !== '') {
    $candidates[] = 'edit.php?post_type=' . $post_type;
  }
  if (!empty($_GET['page'])) {
    $candidates[] = sanitize_text_field(wp_unslash($_GET['page']));
  }
  if (!empty($current_screen->taxonomy)) {
    $candidates[] = 'edit-tags.php?taxonomy=' . $current_screen->taxonomy . '&post_type=' . $post_type;
    $candidates[] = 'edit-tags.php?taxonomy=' . $current_screen->taxonomy . '&post_type=country';
  }

  foreach (bsi_admin_menu_hubs() as $hub_slug => $hub) {
    $slugs = array_map(
      static fn($item) => is_array($item) ? $item[2] : $item,
      $hub['items']
    );

    foreach ($slugs as $item_slug) {
      if (in_array($item_slug, $candidates, true)) {
        $submenu_file = $item_slug;
        return $hub_slug;
      }
    }
  }

  return $parent_file;
}, 999);

/**
 * Страница хаба — плитки со ссылками на его разделы.
 */
function bsi_render_menu_hub(): void
{
  global $submenu;

  $hub_slug = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
  $hubs = bsi_admin_menu_hubs();

  if (!isset($hubs[$hub_slug])) {
    return;
  }
  ?>
  <div class="wrap">
    <h1><?= esc_html($hubs[$hub_slug]['title']); ?></h1>

    <div class="bsi-hub-grid">
      <?php foreach (($submenu[$hub_slug] ?? []) as $item): ?>
        <?php
        if (!current_user_can($item[1])) {
          continue;
        }

        $item_title = trim(wp_strip_all_tags($item[0]));
        $item_slug = (string) $item[2];

        /* Для типов записей добавляем «Добавить» и таксономии —
           в сайдбаре этих подпунктов больше нет. */
        $links = [];
        if (preg_match('/^edit\.php\?post_type=([\w-]+)$/', $item_slug, $m)) {
          $post_type = $m[1];
          $links[] = ['Добавить', 'post-new.php?post_type=' . $post_type];

          foreach (get_object_taxonomies($post_type, 'objects') as $taxonomy) {
            if (empty($taxonomy->show_ui)) {
              continue;
            }
            $links[] = [
              $taxonomy->labels->name,
              'edit-tags.php?taxonomy=' . $taxonomy->name . '&post_type=' . $post_type,
            ];
          }

          /* Страницы, которые плагины вешают подпунктом типа записи
             (например «Re-Order» у Post Types Order): в сайдбаре их нет,
             показываем здесь. */
          foreach (($submenu[$item_slug] ?? []) as $sub) {
            if ($sub[2] === $item_slug || !str_contains($sub[2], 'page=')) {
              continue;
            }
            if (!current_user_can($sub[1])) {
              continue;
            }
            $links[] = [trim(wp_strip_all_tags($sub[0])), $sub[2]];
          }
        }
        ?>
        <div class="bsi-hub-card">
          <a class="bsi-hub-card-title" href="<?= esc_url(admin_url($item_slug)); ?>"><?= esc_html($item_title); ?></a>

          <?php if ($links): ?>
            <div class="bsi-hub-card-links">
              <?php foreach ($links as $link): ?>
                <a href="<?= esc_url(admin_url($link[1])); ?>"><?= esc_html($link[0]); ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <style>
    .bsi-hub-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 12px;
      margin-top: 20px;
    }

    .bsi-hub-card {
      padding: 16px 18px;
      background: #fff;
      border: 1px solid #dcdcde;
      border-radius: 4px;
    }

    .bsi-hub-card-title {
      display: block;
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      color: #1d2327;
    }

    .bsi-hub-card-title:hover {
      color: #2271b1;
    }

    .bsi-hub-card-links {
      display: flex;
      flex-wrap: wrap;
      gap: 4px 12px;
      margin-top: 8px;
      font-size: 13px;
    }

    .bsi-hub-card-links a {
      text-decoration: none;
    }
  </style>
  <?php
}
