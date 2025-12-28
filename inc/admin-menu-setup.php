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

