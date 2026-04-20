<?php
global $country_resorts_data;

/* Данные страны из router/template_redirect */
$country = $country_resorts_data['country'] ?? null;
$country_slug = $country_resorts_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  get_header();
  get_template_part('404');
  get_footer();
  exit;
}

$country_id = $country->ID;
$country_title = (string) $country->post_title;

$country_title_genitive = '';
if ($country_id && function_exists('get_field')) {
  $country_case_fields = [
    'country_title_genitive',
    'country_name_genitive',
    'title_genitive',
    'name_genitive',
  ];

  foreach ($country_case_fields as $field_name) {
    $value = trim((string) get_field($field_name, $country_id));
    if ($value !== '') {
      $country_title_genitive = $value;
      break;
    }
  }
}

if ($country_title_genitive === '' && $country_title !== '') {
  $country_genitive_map = [
    'Австрия' => 'Австрии',
    'Азербайджан' => 'Азербайджана',
    'Албания' => 'Албании',
    'Армения' => 'Армении',
    'Бахрейн' => 'Бахрейна',
    'Белоруссия' => 'Белоруссии',
    'Бельгия' => 'Бельгии',
    'Бруней' => 'Брунея',
    'Бутан' => 'Бутана',
    'Великобритания' => 'Великобритании',
    'Венгрия' => 'Венгрии',
    'Вьетнам' => 'Вьетнама',
    'Греция' => 'Греции',
    'Грузия' => 'Грузии',
    'Египет' => 'Египта',
    'Индия' => 'Индии',
    'Индонезия' => 'Индонезии',
    'Ирландия' => 'Ирландии',
    'Испания' => 'Испании',
    'Италия' => 'Италии',
    'Казахстан' => 'Казахстана',
    'Камбоджа' => 'Камбоджи',
    'Катар' => 'Катара',
    'Кипр' => 'Кипра',
    'Китай' => 'Китая',
    'Лаос' => 'Лаоса',
    'Люксембург' => 'Люксембурга',
    'Маврикий' => 'Маврикия',
    'Малайзия' => 'Малайзии',
    'Мальдивы' => 'Мальдив',
    'Мьянма' => 'Мьянмы',
    'Непал' => 'Непала',
    'Нидерланды' => 'Нидерландов',
    'ОАЭ' => 'ОАЭ',
    'Оман' => 'Омана',
    'Португалия' => 'Португалии',
    'Россия' => 'России',
    'Саудовская Аравия' => 'Саудовской Аравии',
    'Сейшелы' => 'Сейшел',
    'Сербия' => 'Сербии',
    'Сингапур' => 'Сингапура',
    'Словакия' => 'Словакии',
    'Словения' => 'Словении',
    'США' => 'США',
    'Таиланд' => 'Таиланда',
    'Турция' => 'Турции',
    'Узбекистан' => 'Узбекистана',
    'Филиппины' => 'Филиппин',
    'Франция' => 'Франции',
    'Хорватия' => 'Хорватии',
    'Черногория' => 'Черногории',
    'Чехия' => 'Чехии',
    'Швейцария' => 'Швейцарии',
    'Шри-Ланка' => 'Шри-Ланки',
    'Южная Корея' => 'Южной Кореи',
    'Япония' => 'Японии',
  ];

  $country_title_genitive = $country_genitive_map[$country_title] ?? $country_title;
}

$country_resorts_h1 = $country_title_genitive !== ''
  ? 'Курорты ' . $country_title_genitive
  : 'Курорты';

/* Регионы страны */
$regions = get_terms([
  'taxonomy' => 'region',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
  'meta_query' => [
    [
      'key' => 'region_country',
      'value' => $country_id,
      'compare' => '=',
    ],
  ],
]);

if (empty($regions) || is_wp_error($regions)) {
  $regions = [];
}

get_header(); ?>

<main class="site-main">
  <?php
  /* Хлебные крошки */
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section>
    <div class="container">
      <div class="coutry-page__wrap">

        <!-- Aside -->
        <aside class="coutry-page__aside">
          <?= get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <!-- Content -->
        <div class="page-country__content">

          <!-- Заголовок -->
          <h1 class="h1 country-promos__title">
            <?= esc_html($country_resorts_h1); ?>
          </h1>

          <!-- Список регионов/курортов -->
          <?php get_template_part('template-parts/pages/country/region-resort', null, ['regions' => $regions]); ?>

        </div>

      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>