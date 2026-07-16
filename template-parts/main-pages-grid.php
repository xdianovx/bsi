<?php

$cruise_pages = get_pages(
     [
          'meta_key' => '_wp_page_template',
          'meta_value' => 'page-cruise.php',
          'number' => 1,
     ]
);

$cruise_url = ! empty($cruise_pages) ? get_permalink($cruise_pages[0]->ID) : '#';

$education_page = get_page_by_path('obrazovanie-za-rubezhom');
$education_url = $education_page ? get_permalink($education_page->ID) : '#';

$main_page_items = [
     [
          'title' => 'индивидуальный запрос',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'fit.png',

     ],
     [
          'title' => 'VIP УСЛУГИ',
          'url' => 'https://bsivip.ru/',
          'target' => '_blank',
          'img' => 'vip.png',

     ],
     [
          'title' => 'Образование',
          'url' => $education_url,
          'target' => '',
          'img' => 'edu.png',

     ],
     [
          'title' => 'Страхование',
          'url' => get_permalink(get_page_by_path('strahovanie')),
          'target' => '',
          'img' => 'esur.png',

     ],
     [
          'title' => 'Круизы',
          'url' => $cruise_url,
          'target' => '',
          'img' => 'https://bsigroup.ru/wp-content/uploads/2026/04/ship.png',

     ],

];
?>

<section class="main-pages-grid__section">
     <div class="container">
          <div class="main-pages-grid">
               <?php foreach ($main_page_items as $item): ?>
                    <a href="<?= esc_url($item['url']) ?>" class="main-pages__item" <?= !empty($item['target']) ? 'target="' . esc_attr($item['target']) . '" rel="noopener noreferrer"' : '' ?>>
                         <div class="main-page__item-top">
                              <?php $item_img_src = preg_match('#^https?://#i', $item['img']) ? $item['img'] : get_template_directory_uri() . '/img/page-grid/' . $item['img']; ?>
                              <img class="main-pages__item-img"
                                   src="<?= esc_url($item_img_src); ?>"
                                   alt="<?= esc_attr(strip_tags($item['title'])); ?>">
                              <p class="main-pages__item_title"><?= $item['title']; ?></p>
                         </div>


                    </a>
               <?php endforeach; ?>
          </div>
     </div>
</section>