<?php

/**
 * SEO для страниц, которые отдаёт хаб отелей: каталог курорта и карточка отеля.
 * Общий каталог страны уже описан в inc/seo.php как виртуальная подстраница.
 *
 * Фильтры Yoast перехватываются с приоритетом 20 — позже общих правил стран,
 * чтобы заголовок курорта не был затёрт заголовком страны.
 */

/**
 * Данные текущей страницы отеля, если запрос отдаёт хаб.
 *
 * @return array{hotel: array, slug: string, country: WP_Post}|null
 */
function bsi_hotels_api_seo_hotel(): ?array
{
  global $bsi_hotels_api_hotel;

  return is_array($bsi_hotels_api_hotel ?? null) ? $bsi_hotels_api_hotel : null;
}

/**
 * Курорт текущей страницы каталога.
 *
 * @return array{country: WP_Post, resort: array{slug: string, name: string}}|null
 */
function bsi_hotels_api_seo_resort(): ?array
{
  $country_slug = (string) get_query_var('country_hotels');
  if ($country_slug === '' || (string) get_query_var('country_hotel_resort') === '') {
    return null;
  }

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country instanceof WP_Post) {
    return null;
  }

  $resort = bsi_hotels_api_current_resort((int) $country->ID);

  return $resort ? ['country' => $country, 'resort' => $resort] : null;
}

/** Заголовок и описание страницы курорта. */
function bsi_hotels_api_seo_resort_meta(array $data): array
{
  $name = $data['resort']['name'];
  $country = $data['country']->post_title;
  $paged = (int) get_query_var('paged');

  $title = "Отели: {$name}, {$country}";
  if ($paged > 1) {
    $title .= ' — страница ' . $paged;
  }

  return [
    'title' => $title . ' | ' . get_bloginfo('name'),
    'description' => "Отели курорта {$name} ({$country}): звёздность, расположение и цена за ночь. Подбор отеля под даты и бюджет, бронирование от туроператора BSI Group.",
  ];
}

/** Заголовок и описание карточки отеля. */
function bsi_hotels_api_seo_hotel_meta(array $data): array
{
  $hotel = $data['hotel'];
  $name = (string) $hotel['name'];
  $stars = (int) ($hotel['stars'] ?? 0);
  $city = (string) ($hotel['city']['name'] ?? '');
  $country = (string) ($hotel['country']['name'] ?? $data['country']->post_title);

  $place = $city !== '' ? "{$city}, {$country}" : $country;
  $stars_text = $stars ? " {$stars}*" : '';

  $description = "{$name}{$stars_text} — {$place}.";

  $rate = bsi_hotels_api_min_rate($hotel);
  if ($rate) {
    $price = bsi_hotels_api_format_price([
      'amount' => $rate['amount'],
      'currency' => $rate['currency'],
    ]);
    $description .= " Цены от {$price} за ночь.";
  }

  $description .= ' Номера, удобства и условия проживания. Бронирование от туроператора BSI Group.';

  return [
    'title' => "{$name}{$stars_text}, {$place} | " . get_bloginfo('name'),
    'description' => $description,
  ];
}

add_filter('wpseo_title', function ($title) {
  $hotel = bsi_hotels_api_seo_hotel();
  if ($hotel) {
    return bsi_hotels_api_seo_hotel_meta($hotel)['title'];
  }

  $resort = bsi_hotels_api_seo_resort();
  if ($resort) {
    return bsi_hotels_api_seo_resort_meta($resort)['title'];
  }

  return $title;
}, 20);

add_filter('wpseo_metadesc', function ($desc) {
  $hotel = bsi_hotels_api_seo_hotel();
  if ($hotel) {
    return bsi_hotels_api_seo_hotel_meta($hotel)['description'];
  }

  $resort = bsi_hotels_api_seo_resort();
  if ($resort) {
    return bsi_hotels_api_seo_resort_meta($resort)['description'];
  }

  return $desc;
}, 20);

add_filter('wpseo_canonical', function ($canonical) {
  $url = bsi_hotels_api_seo_current_url();

  return $url !== '' ? $url : $canonical;
}, 20);

add_filter('wpseo_opengraph_url', function ($url) {
  $own = bsi_hotels_api_seo_current_url();

  return $own !== '' ? $own : $url;
}, 20);

/**
 * Канонический адрес страницы из хаба. Пагинация каноникализируется на себя,
 * иначе поисковик не доходит до отелей со второй страницы.
 */
function bsi_hotels_api_seo_current_url(): string
{
  $hotel = bsi_hotels_api_seo_hotel();
  if ($hotel) {
    return trailingslashit(bsi_hotels_api_catalog_url($hotel['country'])) . $hotel['slug'] . '/';
  }

  $resort = bsi_hotels_api_seo_resort();
  if (!$resort) {
    return '';
  }

  $url = bsi_hotels_api_resort_url(
    bsi_hotels_api_catalog_url($resort['country']),
    $resort['resort']['slug']
  );

  $paged = (int) get_query_var('paged');
  if ($paged > 1) {
    $url = trailingslashit($url . 'page/' . $paged);
  }

  return $url;
}

/**
 * Карточку без описания, фото, удобств и цен закрываем от индексации: такая
 * страница ничего не добавляет к каталогу. Ссылки при этом остаются рабочими.
 */
add_filter('wpseo_robots_array', function ($robots) {
  $GLOBALS['bsi_hotels_api_robots_printed'] = true;

  $hotel = bsi_hotels_api_seo_hotel();

  if ($hotel && bsi_hotels_api_hotel_is_thin($hotel['hotel'])) {
    $robots['index'] = 'noindex';
    $robots['follow'] = 'follow';
  }

  return $robots;
}, 20);

add_filter('wpseo_robots', function ($robots) {
  $GLOBALS['bsi_hotels_api_robots_printed'] = true;

  return $robots;
}, 20);

/**
 * Запрет индексации пустой карточки и описание — на случай, когда Yoast
 * выключен или не выводит эти теги на странице, которую отдаёт хаб.
 */
add_action('wp_head', function (): void {
  $hotel = bsi_hotels_api_seo_hotel();
  if (!$hotel) {
    return;
  }

  if (!defined('WPSEO_VERSION')) {
    $meta = bsi_hotels_api_seo_hotel_meta($hotel);
    printf('<meta name="description" content="%s">' . "\n", esc_attr($meta['description']));
  }

  // Yoast на этих страницах свой robots не печатает, хотя фильтр вызывает,
  // поэтому тег ставим сами.
  if (bsi_hotels_api_hotel_is_thin($hotel['hotel']) && empty($GLOBALS['bsi_hotels_api_robots_tag'])) {
    $GLOBALS['bsi_hotels_api_robots_tag'] = true;
    echo '<meta name="robots" content="noindex, follow">' . "\n";
  }
}, 100);

add_filter('document_title_parts', function (array $parts): array {
  $hotel = bsi_hotels_api_seo_hotel();
  if ($hotel) {
    $parts['title'] = bsi_hotels_api_seo_hotel_meta($hotel)['title'];
    unset($parts['site'], $parts['tagline']);
    return $parts;
  }

  $resort = bsi_hotels_api_seo_resort();
  if ($resort) {
    $parts['title'] = bsi_hotels_api_seo_resort_meta($resort)['title'];
    unset($parts['site'], $parts['tagline']);
  }

  return $parts;
}, 20);

/** Хлебные крошки Yoast для карточки отеля и страницы курорта. */
add_filter('wpseo_breadcrumb_links', function (array $links): array {
  $hotel = bsi_hotels_api_seo_hotel();
  $resort = bsi_hotels_api_seo_resort();

  if (!$hotel && !$resort) {
    return $links;
  }

  $country = $hotel ? $hotel['country'] : $resort['country'];

  $trail = [['url' => home_url('/'), 'text' => 'Главная']];

  // «Страны» — как в остальных крошках сайта.
  $countries_archive = get_post_type_archive_link('country');
  if ($countries_archive) {
    $trail[] = ['url' => $countries_archive, 'text' => 'Страны'];
  }

  $trail[] = ['url' => get_permalink($country), 'text' => get_the_title($country)];
  $trail[] = ['url' => bsi_hotels_api_catalog_url($country), 'text' => 'Отели'];

  if ($hotel) {
    $trail[] = ['url' => bsi_hotels_api_seo_current_url(), 'text' => (string) $hotel['hotel']['name']];
  } else {
    $trail[] = ['url' => bsi_hotels_api_seo_current_url(), 'text' => $resort['resort']['name']];
  }

  return $trail;
}, 20);

/**
 * rel=prev/next для пагинации каталога отелей. Яндекс по ним связывает
 * страницы одного списка.
 */
add_action('wp_head', 'bsi_hotels_api_seo_pagination_links', 2);
function bsi_hotels_api_seo_pagination_links(): void
{
  if (!get_query_var('country_hotels') || bsi_hotels_api_seo_hotel()) {
    return;
  }

  $country_slug = (string) get_query_var('country_hotels');
  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country instanceof WP_Post) {
    return;
  }

  $catalog = bsi_hotels_api_catalog_query($country);
  $pages = (int) ($catalog['list']['pages'] ?? 0);
  if ($pages < 2) {
    return;
  }

  $paged = max(1, (int) get_query_var('paged'));

  $base = bsi_hotels_api_catalog_url($country);
  $resort = bsi_hotels_api_seo_resort();
  if ($resort) {
    $base = bsi_hotels_api_resort_url($base, $resort['resort']['slug']);
  }

  $page_url = static fn(int $n): string => $n > 1 ? trailingslashit($base . 'page/' . $n) : $base;

  if ($paged > 1) {
    printf('<link rel="prev" href="%s">' . "\n", esc_url($page_url($paged - 1)));
  }

  if ($paged < $pages) {
    printf('<link rel="next" href="%s">' . "\n", esc_url($page_url($paged + 1)));
  }
}

/**
 * Разметка каталога: ItemList с отелями текущей страницы.
 * Позиции сквозные, чтобы вторая страница продолжала первую.
 */
add_action('wp_footer', 'bsi_hotels_api_seo_itemlist_schema');
function bsi_hotels_api_seo_itemlist_schema(): void
{
  if (!get_query_var('country_hotels') || bsi_hotels_api_seo_hotel()) {
    return;
  }

  $country_slug = (string) get_query_var('country_hotels');
  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country instanceof WP_Post) {
    return;
  }

  $catalog = bsi_hotels_api_catalog_query($country);
  $items = $catalog['list']['items'];
  if (!$items) {
    return;
  }

  $catalog_url = bsi_hotels_api_catalog_url($country);
  $paged = max(1, (int) get_query_var('paged'));
  $per_page = (int) $catalog['per_page'];
  $offset = ($paged - 1) * $per_page;

  $list = [];
  foreach (array_values($items) as $i => $hotel) {
    $url = bsi_hotels_api_hotel_url($catalog_url, $hotel);
    if ($url === '') {
      continue;
    }

    $list[] = [
      '@type' => 'ListItem',
      'position' => $offset + $i + 1,
      'url' => $url,
      'name' => (string) ($hotel['name'] ?? ''),
    ];
  }

  if (!$list) {
    return;
  }

  bsi_hotels_api_print_schema([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => $list,
    'numberOfItems' => (int) $catalog['list']['total'],
  ]);
}

/**
 * Разметка карточки отеля. Цену не размечаем: ставка хаба — за ночь, в валюте
 * контракта поставщика, без нашего курса и наценки, и по ней нельзя купить.
 */
add_action('wp_footer', 'bsi_hotels_api_seo_hotel_schema');
function bsi_hotels_api_seo_hotel_schema(): void
{
  $data = bsi_hotels_api_seo_hotel();
  if (!$data) {
    return;
  }

  $hotel = $data['hotel'];

  if (bsi_hotels_api_hotel_is_thin($hotel)) {
    return;
  }

  $schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Hotel',
    'name' => (string) $hotel['name'],
    'url' => bsi_hotels_api_seo_current_url(),
    'address' => array_filter([
      '@type' => 'PostalAddress',
      'addressCountry' => (string) ($hotel['country']['name'] ?? ''),
      'addressLocality' => (string) ($hotel['city']['name'] ?? ''),
      'streetAddress' => (string) ($hotel['address'] ?? ''),
    ]),
  ];

  if (!empty($hotel['stars'])) {
    $schema['starRating'] = [
      '@type' => 'Rating',
      'ratingValue' => (int) $hotel['stars'],
      'bestRating' => 5,
    ];
  }

  if (!empty($hotel['description'])) {
    $schema['description'] = (string) $hotel['description'];
  }

  $photos = [];
  foreach ($hotel['photos'] ?? [] as $photo) {
    if (!empty($photo['url'])) {
      $photos[] = (string) $photo['url'];
    }
  }
  if ($photos) {
    $schema['image'] = $photos;
  }

  if (!empty($hotel['lat']) && !empty($hotel['lng'])) {
    $schema['geo'] = [
      '@type' => 'GeoCoordinates',
      'latitude' => (string) $hotel['lat'],
      'longitude' => (string) $hotel['lng'],
    ];
  }

  $amenities = [];
  foreach ($hotel['amenities'] ?? [] as $amenity) {
    if (!empty($amenity['name'])) {
      $amenities[] = [
        '@type' => 'LocationFeatureSpecification',
        'name' => (string) $amenity['name'],
        'value' => true,
      ];
    }
  }
  if ($amenities) {
    $schema['amenityFeature'] = $amenities;
  }

  bsi_hotels_api_print_schema($schema);
}

function bsi_hotels_api_print_schema(array $schema): void
{
  printf(
    '<script type="application/ld+json">%s</script>' . "\n",
    wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
  );
}
