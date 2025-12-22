<?php

$main_page_items = [
     [
          'title' => "Онлайн <br> бронирование",
          'url' => 'https://online.bsigroup.ru/default.php?page=search_tour',
          'target' => '_blank',
          'img' => 'online.png',
       
     ],
     [
          'title' => 'FIT',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'fit.png',
       
     ],
     [
          'title' => 'Круизы',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'cruise.png',
       
     ],
     [
          'title' => 'События',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'event.png',
       
     ],
       [
          'title' => 'VIP-Concierge',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'vip.png',
       
     ],
     
        [
          'title' => "Образование <br> за рубежом",
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'edu.png',
       
     ],
     [
          'title' => 'Страхование',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'esur.png',
       
     ],
     [
          'title' => 'Визы',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'visa.png',
       
     ],

     [
          'title' => "Business Travel <br> & MICE",
          'url' => get_permalink(2301),
          'target' => '',
          'img' => 'mice.png',
       
     ],
     [
          'title' => 'Incoming',
          'url' => get_permalink(2064),
          'target' => '',
          'img' => 'incoming.png',
       
     ],
        
];
?>

<section class="main-pages-grid__section">
     <div class="container">
          <div class="main-pages-grid">
               <?php foreach ($main_page_items as $item): ?>
                    <a href="<?= esc_url($item['url']) ?>"
                       class="main-pages__item"
                       <?= !empty($item['target']) ? 'target="' . esc_attr($item['target']) . '"' : '' ?>>
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

