<?php
add_action('admin_menu', 'remove_admin_menu_items');
function remove_admin_menu_items()
{
  remove_menu_page('edit-comments.php');

  add_menu_page(
    'Секции',
    'Секции',
    'manage_options',
    'sections',
    'render_sections_page',
    'dashicons-screenoptions',
    32

  );


  remove_menu_page('edit.php');

  remove_menu_page('tools.php');
}

function render_sections_page()
{
  ?>
  <div class="wrap">
    <h1>Секции сайта</h1>

    <p>
      Все, что не имеет страниц
    </p>

    <h2>Блоки:</h2>
    <ul>
      <?php
      global $submenu;

      if (!empty($submenu['sections'])) {
        foreach ($submenu['sections'] as $item) {
          $label = $item[0];
          $slug = $item[2];

          if ($slug === 'sections') {
            continue;
          }
          $url = admin_url($slug);
          echo '<li><a href="' . esc_url($url) . '">' . wp_kses_post($label) . '</a></li>';
        }
      } else {
        echo '<li>Пока нет ни одной секции.</li>';
      }
      ?>
    </ul>
  </div>
  <?php
}


add_action('admin_menu', 'bsi_add_mice_menu');
function bsi_add_mice_menu()
{
  add_menu_page(
    'MICE',
    'MICE',
    'edit_pages',
    'mice-pages',
    'bsi_render_mice_pages',
    'dashicons-groups',
    24
  );
}

function bsi_render_mice_pages()
{
  $mice_page = get_pages([
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'page-mice.php',
    'number'     => 1,
  ]);

  $main_page = !empty($mice_page) ? $mice_page[0] : null;
  ?>
  <div class="wrap">
    <h1 class="wp-heading-inline">MICE</h1>

    <?php if ($main_page): ?>
      <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page&post_parent=' . $main_page->ID)); ?>"
         class="page-title-action">Добавить дочернюю страницу</a>
    <?php else: ?>
      <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>"
         class="page-title-action">Создать MICE-страницу</a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if ($main_page): ?>
      <table class="wp-list-table widefat fixed striped pages">
        <thead>
          <tr>
            <th>Название</th>
            <th>Статус</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Главная страница
          $status_label = get_post_status($main_page->ID) === 'publish' ? 'Опубликована' : 'Черновик';
          ?>
          <tr>
            <td><strong>
              <a href="<?php echo esc_url(get_edit_post_link($main_page->ID)); ?>">
                <?php echo esc_html($main_page->post_title); ?>
              </a>
            </strong> — <em>Главная (шаблон MICE)</em></td>
            <td><?php echo esc_html($status_label); ?></td>
            <td>
              <a href="<?php echo esc_url(get_edit_post_link($main_page->ID)); ?>">Редактировать</a>
              &nbsp;|&nbsp;
              <a href="<?php echo esc_url(get_permalink($main_page->ID)); ?>" target="_blank">Просмотр</a>
            </td>
          </tr>

          <?php
          // Дочерние страницы
          $children = get_pages([
            'parent'      => $main_page->ID,
            'sort_column' => 'menu_order',
            'sort_order'  => 'ASC',
          ]);

          foreach ($children as $child):
            $child_status = get_post_status($child->ID) === 'publish' ? 'Опубликована' : 'Черновик';
          ?>
          <tr>
            <td>&nbsp;&nbsp;&nbsp;&nbsp;↳
              <a href="<?php echo esc_url(get_edit_post_link($child->ID)); ?>">
                <?php echo esc_html($child->post_title); ?>
              </a>
            </td>
            <td><?php echo esc_html($child_status); ?></td>
            <td>
              <a href="<?php echo esc_url(get_edit_post_link($child->ID)); ?>">Редактировать</a>
              &nbsp;|&nbsp;
              <a href="<?php echo esc_url(get_permalink($child->ID)); ?>" target="_blank">Просмотр</a>
            </td>
          </tr>
          <?php endforeach; ?>

        </tbody>
      </table>

    <?php else: ?>
      <p>Страница с шаблоном <strong>MICE</strong> не найдена. Создайте страницу и назначьте ей шаблон «MICE».</p>
    <?php endif; ?>
  </div>
  <?php
}


add_action('admin_menu', 'add_cpt_separator', 99);

function add_cpt_separator()
{
  global $menu;

  // Две линии-разделителя для групп (см. bsi_admin_menu_order).
  $menu[58] = ['', 'read', 'bsi-separator-1', '', 'wp-menu-separator'];
  $menu[59] = ['', 'read', 'cpt-separator', '', 'wp-menu-separator'];

  ksort($menu);
}

/**
 * Упорядочивание верхнего уровня админ-меню по группам:
 *   Консоль / Страницы / Новости / Туры / Отели
 *   ──────
 *   Страны / Памятки туристам / Правила въезда
 *   ──────
 *   остальные пункты (Секции, MICE …) — в исходном порядке
 *
 * «Информация об отелях» — не пункт меню (ACF-поле внутри каждой «Страны»),
 * поэтому в сайдбаре не размещается.
 */
add_filter('custom_menu_order', '__return_true');
add_filter('menu_order', 'bsi_admin_menu_order');

function bsi_admin_menu_order($menu_ord)
{
  if (!is_array($menu_ord)) {
    return $menu_ord;
  }

  $desired = [
    'index.php',                      // Консоль
    'edit.php?post_type=page',        // Страницы
    'edit.php?post_type=news',        // Новости
    'edit.php?post_type=tour',        // Туры
    'edit.php?post_type=hotel',       // Отели
    'bsi-separator-1',                // ──────
    'edit.php?post_type=country',     // Страны
    'edit.php?post_type=tourist_memo',// Памятки туристам
    'edit.php?post_type=entry_rules', // Правила въезда
    'edit.php?post_type=hotel_info',  // Информация об отелях
    'cpt-separator',                  // ──────
  ];

  // Остальное (Секции, MICE и пр.) — в исходном порядке, без дублей.
  $rest = array_values(array_diff($menu_ord, $desired));

  return array_merge($desired, $rest);
}

/**
 * Стиль линий-разделителей в сайдбаре админки.
 */
add_action('admin_head', 'bsi_admin_menu_separator_css');

function bsi_admin_menu_separator_css()
{
  echo '<style>
    #adminmenu li.wp-menu-separator {
      height: 1px;
      padding: 0 8px;
      margin: 6px 0;
      cursor: inherit;
      background: #fff;
      opacity: .3;
    }
  </style>';
}

