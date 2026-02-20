<?php

$main_page_items = [
     // [
     //      'title' => "Онлайн <br> бронирование",
     //      'url' => 'https://online.bsigroup.ru/default.php?page=search_tour',
     //      'target' => '_blank',
     //      'img' => 'online.png',

     // ],
     [
          'title' => 'индивидуальный запрос',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'fit.png',

     ],
     // [
     //      'title' => 'Круизы',
     //      'url' => 'https://online.bsigroup.ru/default.php?page=search_tour',
     //      'target' => '_blank',
     //      'img' => 'cruise.png',

     // ],
     // [
     //      'title' => 'Событийные <br> туры',
     //      'url' => 'https://past.bsigroup.ru/tip-tura/event-tours/',
     //      'target' => '_blank',
     //      'img' => 'event.png',

     // ],
     [
          'title' => 'Событийные <br> туры',
          'url' => '#',
          'target' => '',
          'img' => 'event.png',

     ],
     [
          'title' => 'VIP УСЛУГИ',
          'url' => 'https://bsivip.ru/',
          'target' => '_blank',
          'img' => 'vip.png',

     ],
     // [
     //      'title' => "Образование <br> за рубежом",
     //      // 'url' => get_permalink(get_page_by_path('obrazovanie-za-rubezhom')),
     //      'url' => 'https://www.bsistudy.ru',
     //      'target' => '_blank',
     //      'img' => 'edu.png',
     // ],
     [
          'title' => 'Страхование',
          'url' => get_permalink(get_page_by_path('strahovanie')),
          'target' => '',
          'img' => 'esur.png',

     ],
     // [
     //      'title' => 'Визы',
     //      'url' => get_permalink(get_page_by_path('vizy')),
     //      'target' => '',
     //      'img' => 'visa.png',

     // ],

     // [
     //      'title' => "Business Travel <br> & MICE",
     //      'url' => get_permalink(get_page_by_path('mice')),
     //      'target' => '',
     //      'img' => 'mice.png',

     // ],
     [
          'title' => 'Incoming',
          'url' => 'https://incoming.bsigroup.ru/',
          'target' => '_blank',
          'img' => 'incoming.png',

     ],

];
?>

<section class="main-pages-grid__section">
     <div class="container">
          <div class="main-pages-grid">
               <?php foreach ($main_page_items as $item): ?>
                    <a href="<?= esc_url($item['url']) ?>" class="main-pages__item" <?= !empty($item['target']) ? 'target="' . esc_attr($item['target']) . '" rel="noopener noreferrer"' : '' ?>>
                         <div class="main-page__item-top">
                              <img class="main-pages__item-img"
                                   src="<?= get_template_directory_uri() . '/img/page-grid/' . esc_attr($item['img']); ?>"
                                   alt="<?= esc_attr(strip_tags($item['title'])); ?>">
                              <p class="main-pages__item_title"><?= $item['title']; ?></p>
                         </div>


                    </a>
               <?php endforeach; ?>
          </div>
     </div>
</section>