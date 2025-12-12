<?php
/*
Template Name: Визы
*/

$steps = [
  [
    'num' => '1',
    'title' => 'Сбор документов',
    'descr' => 'Предоставьте нам информацию'
  ],
  [
    'num' => '2',
    'title' => 'Оформление',
    'descr' => 'Мы готовим документы для подачи'
  ],
  [
    'num' => '3',
    'title' => 'Подача в консульство',
    'descr' => 'Лично или через нашего представителя'
  ],
  [
    'num' => '4',
    'title' => 'Получение паспорта',
    'descr' => 'Лично или через нашего представителя'
  ],
];

get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section class="page-head archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 page-award__title archive-page__title ">
          <?php the_title(); ?>
        </h1>

        <p class="page-award__excerpt archive-page__excerpt"><?= get_the_excerpt() ?></p>
      </div>
    </div>
  </section>


  <section class="visa-page__info-section">
    <div class="container">

      <div class="visa-page__info">
        <div class="visa-info-item__wrap">

          <!-- item -->
          <div class="visa-info-item">
            <div class="visa-info-item__title">
              <div class="visa-info-item__icon"><svg xmlns="http://www.w3.org/2000/svg"
                     width="20"
                     height="20"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     class="lucide lucide-clock-icon lucide-clock">
                  <path d="M12 6v6l4 2" />
                  <circle cx="12"
                          cy="12"
                          r="10" />
                </svg></div>
              <p class="visa-info-item__key">Прием и выдача документов:</p>
            </div>
            <p class="visa-info-item__value">ПН – ПТ с 10:00 до 18:00</p>
          </div>

          <!-- item -->
          <div class="visa-info-item">
            <div class="visa-info-item__title">
              <div class="visa-info-item__icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="20"
                     height="20"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     class="lucide lucide-map-pin-house-icon lucide-map-pin-house">
                  <path
                        d="M15 22a1 1 0 0 1-1-1v-4a1 1 0 0 1 .445-.832l3-2a1 1 0 0 1 1.11 0l3 2A1 1 0 0 1 22 17v4a1 1 0 0 1-1 1z" />
                  <path d="M18 10a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 .601.2" />
                  <path d="M18 22v-3" />
                  <circle cx="10"
                          cy="10"
                          r="3" />
                </svg>
              </div>
              <p class="visa-info-item__key">Адрес:</p>
            </div>
            <p class="visa-info-item__value">
              г. Москва, ул. Садовая-Кудринская д. 2/62/35, строение 1, этаж 3. м. Баррикадная, тел.: <a
                 href="tel:+7 (495) 785-55-35">+7 (495) 785-55-35</a>
            </p>
          </div>

          <div class="callout callout-neutral">
            <h3 class="callout__title">
              Правила Выдачи документов по путевке:

            </h3>

            <p>
              Все документы по турам выдаются только при наличии: счет-подтверждения на тур, паспорта гражданина РФ

            </p>
          </div>



        </div>
      </div>
    </div>
  </section>


  <section class="visa-page-features__section">
    <div class="container">
      <h2 class="h2">Наши преимущества</h2>

      <div class="visa-page-features__wrap">
        <!-- item -->
        <div class="visa-page-features__item">
          <div class="visa-page-features__item__wrap">

            <div class="visa-page-features__item-title">
              Оформляем визы с 2005 года
            </div>
          </div>

        </div>

        <!-- item -->
        <div class="visa-page-features__item">
          <div class="visa-page-features__item__wrap">

            <div class="visa-page-features__item-title">
              Оформляем визы с 2005 года
            </div>
          </div>

        </div>

        <!-- item -->
        <div class="visa-page-features__item">
          <div class="visa-page-features__item__wrap">

            <div class="visa-page-features__item-title">
              Оформляем визы с 2005 года
            </div>
          </div>

        </div>

        <!-- item -->
        <div class="visa-page-features__item">
          <div class="visa-page-features__item__wrap">

            <div class="visa-page-features__item-title">
              Оформляем визы с 2005 года
            </div>
          </div>

        </div>

        <!-- item -->
        <div class="visa-page-features__item">
          <div class="visa-page-features__item__wrap">

            <div class="visa-page-features__item-title">
              Оформляем визы с 2005 года
            </div>
          </div>

        </div>

        <!-- item -->
        <div class="visa-page-features__item">
          <div class="visa-page-features__item__wrap">

            <div class="visa-page-features__item-title">
              Оформляем визы с 2005 года
            </div>
          </div>

        </div>
        <!-- item -->
        <div class="visa-page-features__item">
          <div class="visa-page-features__item__wrap">

            <div class="visa-page-features__item-title">
              Оформляем визы с 2005 года
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>


  <section class="visa-page-steps__section">
    <div class="container">
      <h2 class="h2">Процедура оформления</h2>

      <div class="visa-page-steps__wrap">
        <?php foreach ($steps as $step): ?>
          <div class="visa-page-steps-item">
            <div class="visa-page-steps-item__num"><?= $step['num'] ?></div>
            <div class="visa-page-steps-item__title"><?= $step['title'] ?></div>
            <div class="visa-page-steps-item__description"><?= $step['descr'] ?></div>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  </section>


  <section class="visa-page-consultation__section">
    <div class="container">
      <h2 class="h2">Бесплатная консультация</h2>

      <p>Форма важная</p>

      <form action="">
        <p>Страна</p>
        <p>Тип визы?</p>
        <p>Имя</p>
        <p>Гражданство</p>
        <p>Телефон</p>
        <p>Способ связи?</p>
      </form>
    </div>
  </section>


</main>

<?php
get_footer();