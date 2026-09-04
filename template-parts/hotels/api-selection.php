<?php
/**
 * Подборка отелей из хаба на главной. Список задаётся в «Подборка отелей».
 * Данные берутся одним запросом на отель — карточки лежат в кэше клиента.
 */

if (!function_exists('get_field')) {
  return;
}

$rows = get_field('hotels_api_selection', 'option');
if (!is_array($rows) || !$rows) {
  return;
}

$client = bsi_hotels_api();
if (!$client) {
  return;
}

$cards = [];

foreach ($rows as $row) {
  $slug = sanitize_title((string) ($row['slug'] ?? ''));
  $country_id = (int) ($row['country'] ?? 0);

  if ($slug === '' || $country_id <= 0) {
    continue;
  }

  $country = get_post($country_id);
  if (!$country instanceof WP_Post) {
    continue;
  }

  try {
    $hotel = $client->hotel($slug);
  } catch (HotelsApiException $e) {
    continue;
  }

  $rate = bsi_hotels_api_min_rate($hotel);
  $photos = is_array($hotel['photos'] ?? null) ? $hotel['photos'] : [];

  // Приводим карточку к форме элемента списка — шаблон карточки один.
  $cards[] = [
    'hotel' => [
      'slug' => $slug,
      'name' => (string) ($hotel['name'] ?? ''),
      'stars' => (int) ($hotel['stars'] ?? 0),
      'city' => $hotel['city'] ?? [],
      'photo' => (string) ($photos[0]['url'] ?? ''),
      'room_types' => count($hotel['room_types'] ?? []),
      'price_from' => $rate
        ? ['amount' => $rate['amount'], 'currency' => $rate['currency']]
        : null,
    ],
    'country_url' => bsi_hotels_api_catalog_url($country),
  ];
}

if (!$cards) {
  return;
}

$selection_title = (string) (get_field('hotels_api_selection_title', 'option') ?: 'Популярные отели');
?>

<section class="section api-hotels-selection">
  <div class="container">
    <h2 class="h2"><?= esc_html($selection_title); ?></h2>

    <div class="country-hotels__grid api-hotels-selection__grid">
      <?php foreach ($cards as $card): ?>
        <?php get_template_part('template-parts/hotels/api-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
