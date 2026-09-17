<?php

/**
 * Карта сайта для страниц из хаба отелей: /oteli-sitemap.xml
 *
 * Yoast о них не знает — страницы виртуальные, постов в базе нет.
 * В карту попадают каталоги стран, страницы курортов и все отели, кроме
 * служебных записей поставщика. Отбирать по наличию номеров или цен нельзя:
 * хаб подтягивает их у поставщика при первом обращении к карточке, так что
 * до визита карточка выглядит пустой. Пустые остаются под noindex, но обойти
 * их поисковик должен — этим они и наполняются.
 */

const BSI_HOTELS_SITEMAP_SLUG = 'oteli-hotels.xml';

/** Имя раздела в карте Yoast: даёт /oteli-sitemap.xml */
const BSI_HOTELS_SITEMAP_YOAST = 'oteli';

/**
 * Карта Yoast — основная. Свой адрес остаётся запасным на случай, когда
 * плагин выключен: имя со словом sitemap Yoast перехватывает сам.
 */
add_action('init', function (): void {
  global $wpseo_sitemaps;

  if ($wpseo_sitemaps instanceof WPSEO_Sitemaps) {
    $wpseo_sitemaps->register_sitemap(BSI_HOTELS_SITEMAP_YOAST, 'bsi_hotels_api_render_yoast_sitemap');

    return;
  }

  add_rewrite_rule('^' . BSI_HOTELS_SITEMAP_SLUG . '$', 'index.php?bsi_hotels_sitemap=1', 'top');
}, 20);

/** Раздел отелей в индексе карты сайта. */
add_filter('wpseo_sitemap_index', function (string $index): string {
  if (!bsi_hotels_api_sitemap_cached()) {
    return $index;
  }

  return $index . sprintf(
    "<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>\n",
    esc_url(home_url('/' . BSI_HOTELS_SITEMAP_YOAST . '-sitemap.xml')),
    esc_html(gmdate('c'))
  );
});

/** Тело раздела для Yoast. */
function bsi_hotels_api_render_yoast_sitemap(): void
{
  $sitemap = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

  foreach (bsi_hotels_api_sitemap_cached() as $url) {
    $sitemap .= sprintf(
      "  <url>\n    <loc>%s</loc>\n    <changefreq>%s</changefreq>\n  </url>\n",
      esc_url($url['loc']),
      esc_html($url['changefreq'])
    );
  }

  $sitemap .= '</urlset>';

  global $wpseo_sitemaps;

  if ($wpseo_sitemaps instanceof WPSEO_Sitemaps) {
    $wpseo_sitemaps->set_sitemap($sitemap);
  }
}

/**
 * Адреса карты с суточным кешем: обход хаба по всем странам стоит десятки
 * запросов, на каждый показ карты его делать незачем.
 */
function bsi_hotels_api_sitemap_cached(): array
{
  $urls = get_transient('bsi_hotels_sitemap_urls');

  if (!is_array($urls)) {
    $urls = bsi_hotels_api_sitemap_urls();
    set_transient('bsi_hotels_sitemap_urls', $urls, 12 * HOUR_IN_SECONDS);
  }

  return $urls;
}

add_filter('query_vars', function (array $vars): array {
  $vars[] = 'bsi_hotels_sitemap';

  return $vars;
});

add_action('template_redirect', function (): void {
  if (!get_query_var('bsi_hotels_sitemap')) {
    return;
  }

  global $wp_query;
  $wp_query->is_404 = false;
  status_header(200);

  $urls = bsi_hotels_api_sitemap_cached();

  header('Content-Type: application/xml; charset=UTF-8');

  echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
  echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

  foreach ($urls as $url) {
    printf(
      "  <url>\n    <loc>%s</loc>\n    <changefreq>%s</changefreq>\n  </url>\n",
      esc_url($url['loc']),
      esc_html($url['changefreq'])
    );
  }

  echo '</urlset>';
  exit;
});

/**
 * @return array<int, array{loc: string, changefreq: string}>
 */
function bsi_hotels_api_sitemap_urls(): array
{
  $client = bsi_hotels_api();
  if (!$client) {
    return [];
  }

  $countries = get_posts([
    'post_type' => 'country',
    'post_status' => 'publish',
    'post_parent' => 0,
    'posts_per_page' => -1,
    'no_found_rows' => true,
  ]);

  $urls = [];

  foreach ($countries as $country) {
    $api_country = bsi_hotels_api_country_slug((int) $country->ID);
    if ($api_country === '') {
      continue;
    }

    $catalog_url = bsi_hotels_api_catalog_url($country);
    $urls[] = ['loc' => $catalog_url, 'changefreq' => 'daily'];

    try {
      foreach ($client->cities($api_country) as $city) {
        $slug = (string) ($city['slug'] ?? '');
        if ($slug !== '') {
          $urls[] = [
            'loc' => bsi_hotels_api_resort_url($catalog_url, $slug),
            'changefreq' => 'weekly',
          ];
        }
      }

      // Отель без номеров показывать нечем — такая карточка закрыта от
      // индексации, и в карте ей делать нечего.
      $page = 1;
      do {
        $list = $client->hotels([
          'country' => $api_country,
          'limit' => 100,
          'page' => $page,
          'sort' => 'id',
        ]);

        foreach (bsi_hotels_api_filter_items($list['items']) as $hotel) {
          $url = bsi_hotels_api_hotel_url($catalog_url, $hotel);
          if ($url !== '') {
            $urls[] = ['loc' => $url, 'changefreq' => 'weekly'];
          }
        }

        $page++;
      } while ($page <= (int) $list['pages'] && $page <= 30);
    } catch (HotelsApiException $e) {
      continue;
    }
  }

  return $urls;
}

/** Запасная ссылка в robots.txt, когда карту отдаёт не Yoast. */
add_filter('robots_txt', function (string $output): string {
  global $wpseo_sitemaps;

  if ($wpseo_sitemaps instanceof WPSEO_Sitemaps) {
    return $output;
  }

  return $output . "\nSitemap: " . home_url('/' . BSI_HOTELS_SITEMAP_SLUG) . "\n";
}, 20);
