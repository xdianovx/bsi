<?php

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
          'title' => 'Образование <br> за рубежом',
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
                                   alt="<?= esc_attr(trim(preg_replace('/\s+/u', ' ', strip_tags($item['title'])))); ?>">
                              <p class="main-pages__item_title"><?= $item['title']; ?></p>
                         </div>


                    </a>
               <?php endforeach; ?>
          </div>
     </div>
</section>