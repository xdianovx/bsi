<?php
/**
 * Каталог отелей страны из хаба BSIHOTELS.
 * Города хаба показываем как курорты — так их называет сайт.
 *
 * @var WP_Post $args['country']
 */

$country = $args['country'] ?? null;
if (!$country instanceof WP_Post) {
  return;
}

$catalog_url = bsi_hotels_api_catalog_url($country);

$catalog = bsi_hotels_api_catalog_query($country);

$list = $catalog['list'];
$resorts = $catalog['resorts'];
$error = $catalog['error'];
$resort = $catalog['resort'];
$per_page = $catalog['per_page'];
$paged = max(1, (int) get_query_var('paged'));

$resort_name = '';
foreach ($resorts as $city) {
  if (($city['slug'] ?? '') === $resort) {
    $resort_name = (string) ($city['name'] ?? '');
    break;
  }
}
?>

<div class="country-hotels__catalog js-hotels-catalog"
     data-country="<?= (int) $country->ID; ?>"
     data-url="<?= esc_url($catalog_url); ?>">
  <?php get_template_part('template-parts/pages/country/hotels-api-list', null, [
    'country' => $country,
    'catalog' => $catalog,
    'paged' => $paged,
  ]); ?>
</div>
