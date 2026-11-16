<?php
/**
 * Галка «Оглавление по заголовкам» у страниц.
 * @see inc/helpers/page-toc.php
 */

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_bsi_page_toc',
    'title' => 'Оглавление',
    'fields' => [
      [
        'key' => 'field_bsi_page_toc',
        'label' => 'Оглавление по заголовкам',
        'name' => 'bsi_page_toc',
        'type' => 'true_false',
        'instructions' => 'Список разделов сбоку от текста. Собирается из заголовков H2 (не меньше двух).',
        'ui' => 1,
        'default_value' => 0,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'page',
        ],
        [
          'param' => 'page_template',
          'operator' => '==',
          'value' => 'default',
        ],
      ],
    ],
    'position' => 'side',
  ]);
});
