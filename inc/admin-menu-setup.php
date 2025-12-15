<?php
// Скрытие пунктов меню в админке
add_action('admin_menu', 'remove_admin_menu_items');
function remove_admin_menu_items()
{
  // Скрыть комментарии
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

  // Скрыть медиафайлы
  // remove_menu_page('upload.php');

  // Скрыть страницы
  // remove_menu_page('edit.php?post_type=page');

  // Скрыть внешний вид
  // remove_menu_page('themes.php');

  // Скрыть плагины
  // remove_menu_page('plugins.php');

  // Скрыть пользователей
  // remove_menu_page('users.php');

  // Скрыть инструменты
  remove_menu_page('tools.php');

  // Скрыть настройки
  // remove_menu_page('options-general.php');
}




// Вкладка секции
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
          // $item = [0 => 'Название', 1 => capability, 2 => slug, ...]
          $label = $item[0];
          $slug = $item[2];

          // Пропускаем саму "Секции", чтобы не дублировать
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


add_action('admin_menu', 'add_cpt_separator', 99);

function add_cpt_separator()
{
  global $menu;
  $position = 59;

  $menu[$position] = [
    '',                   // page_title
    'read',               // capability
    'cpt-separator',      // slug (просто уникальная строка)
    '',                   // menu_title
    'wp-menu-separator',  // CSS-класс — делает именно линию
  ];

  ksort($menu);
}

