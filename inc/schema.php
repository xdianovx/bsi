<?php
/**
 * Schema.org JSON-LD для ключевых CPT.
 * Yoast выдаёт базовые WebPage / Organization / BreadcrumbList.
 * Здесь добавляем: TravelAgency, TouristTrip, Hotel, Event, Course, Review.
 */

// ── Yoast: Organization → TravelAgency ──────────────────────

add_filter('wpseo_schema_graph', function (array $graph): array {
    foreach ($graph as &$piece) {
        if (!isset($piece['@type'])) {
            continue;
        }
        if ($piece['@type'] === 'Organization' || $piece['@type'] === ['Organization']) {
            $piece['@type'] = ['Organization', 'TravelAgency'];
            $piece['description'] = 'Туроператор BSI Group — туры, отели, образование за рубежом, MICE.';
            $piece['telephone'] = '+7 (495) 730-25-15';
            $piece['address'] = [
                '@type' => 'PostalAddress',
                'addressCountry' => 'RU',
                'addressLocality' => 'Москва',
            ];
        }
    }
    unset($piece);

    return $graph;
});

// ── JSON-LD output ──────────────────────────────────────────

add_action('wp_head', function () {
    if (is_singular('tour')) {
        bsi_schema_tour();
    } elseif (is_singular('event')) {
        bsi_schema_event();
    } elseif (is_singular('hotel')) {
        bsi_schema_hotel();
    } elseif (is_singular('education')) {
        bsi_schema_education();
    }
}, 99);

function bsi_schema_json(array $data): void
{
    $data = array_filter($data, function ($v) {
        return $v !== '' && $v !== null && $v !== [];
    });
    $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json) {
        echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }
}

function bsi_schema_gallery_images(array $gallery, int $limit = 5): array
{
    $images = [];
    foreach (array_slice($gallery, 0, $limit) as $img) {
        $url = '';
        if (is_array($img) && !empty($img['url'])) {
            $url = $img['url'];
        } elseif (is_string($img)) {
            $url = $img;
        }
        if ($url) {
            $images[] = $url;
        }
    }
    return $images;
}

// ── TouristTrip ─────────────────────────────────────────────

function bsi_schema_tour(): void
{
    $id = get_the_ID();
    $title = get_the_title($id);
    $url = get_permalink($id);
    $desc = get_the_excerpt($id);
    if (!$desc) {
        $desc = wp_trim_words(wp_strip_all_tags(get_the_content(null, false, $id)), 30, '…');
    }

    $country_id = function_exists('bsi_get_tour_primary_country_id')
        ? bsi_get_tour_primary_country_id((int) $id) : 0;
    $country_name = $country_id ? get_the_title($country_id) : '';

    $duration   = function_exists('get_field') ? trim((string) get_field('tour_duration', $id)) : '';
    $price_from = function_exists('get_field') ? trim((string) get_field('price_from', $id)) : '';
    $gallery    = function_exists('get_field') ? (array) get_field('tour_gallery', $id) : [];

    $images = bsi_schema_gallery_images($gallery);
    if (!$images) {
        $thumb = get_the_post_thumbnail_url($id, 'large');
        if ($thumb) {
            $images = [$thumb];
        }
    }

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'TouristTrip',
        'name'        => $title,
        'description' => $desc,
        'url'         => $url,
        'image'       => $images,
        'provider'    => [
            '@type' => 'TravelAgency',
            'name'  => 'BSI Group',
            'url'   => home_url('/'),
        ],
    ];

    if ($country_name) {
        $schema['itinerary'] = [
            '@type' => 'Place',
            'name'  => $country_name,
        ];
    }

    if ($price_from) {
        $price_num = preg_replace('/[^\d]/', '', $price_from);
        if ($price_num) {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'price'         => $price_num,
                'priceCurrency' => 'RUB',
                'availability'  => 'https://schema.org/InStock',
                'url'           => $url,
            ];
        }
    }

    bsi_schema_json($schema);
}

// ── Event (событийный тур) ──────────────────────────────────

function bsi_schema_event(): void
{
    $id = get_the_ID();
    $title = get_the_title($id);
    $url = get_permalink($id);
    $desc = get_the_excerpt($id);

    $venue      = function_exists('get_field') ? trim((string) get_field('event_venue', $id)) : '';
    $event_time = function_exists('get_field') ? trim((string) get_field('event_time', $id)) : '';
    $checkin    = function_exists('get_field') ? trim((string) get_field('tour_checkin_dates', $id)) : '';
    $price_from = function_exists('get_field') ? trim((string) get_field('price_from', $id)) : '';
    $gallery    = function_exists('get_field') ? (array) get_field('tour_gallery', $id) : [];
    $hero_cover = function_exists('get_field') ? get_field('event_hero_cover', $id) : null;
    $hero_cover_url = '';
    if (is_array($hero_cover) && !empty($hero_cover['ID'])) {
        $hero_cover_url = (string) wp_get_attachment_image_url((int) $hero_cover['ID'], 'large');
    } elseif (is_array($hero_cover) && !empty($hero_cover['url'])) {
        $hero_cover_url = (string) $hero_cover['url'];
    }

    $images = bsi_schema_gallery_images($gallery);
    if ($hero_cover_url !== '') {
        $images = $images ? array_values(array_unique(array_merge([$hero_cover_url], $images))) : [$hero_cover_url];
    }
    if (!$images) {
        $thumb = get_the_post_thumbnail_url($id, 'large');
        if ($thumb) {
            $images = [$thumb];
        }
    }

    $country_id = function_exists('get_field') ? get_field('tour_country', $id) : 0;
    if ($country_id instanceof WP_Post) {
        $country_id = $country_id->ID;
    }
    if (is_array($country_id)) {
        $country_id = (int) reset($country_id);
    }
    $country_name = $country_id ? get_the_title((int) $country_id) : '';

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Event',
        'name'        => $title,
        'description' => $desc,
        'url'         => $url,
        'image'       => $images,
        'organizer'   => [
            '@type' => 'TravelAgency',
            'name'  => 'BSI Group',
            'url'   => home_url('/'),
        ],
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    ];

    if ($venue || $country_name) {
        $schema['location'] = [
            '@type' => 'Place',
            'name'  => $venue ?: $country_name,
        ];
        if ($country_name && $venue) {
            $schema['location']['address'] = [
                '@type'          => 'PostalAddress',
                'addressCountry' => $country_name,
            ];
        }
    }

    $min_price = function_exists('bsi_extract_price_number')
        ? bsi_extract_price_number($price_from)
        : (($price_from !== '') ? (float) preg_replace('/[^\d.]/', '', $price_from) : null);
    if ($min_price) {
        $schema['offers'] = [
            '@type'         => 'Offer',
            'price'         => $min_price,
            'priceCurrency' => 'RUB',
            'availability'  => 'https://schema.org/InStock',
            'url'           => $url,
        ];
    }

    bsi_schema_json($schema);
}

// ── Hotel ───────────────────────────────────────────────────

function bsi_schema_hotel(): void
{
    $id = get_the_ID();
    $title = get_the_title($id);
    $url = get_permalink($id);
    $desc = get_the_excerpt($id);

    $rating    = function_exists('get_field') ? (int) get_field('rating', $id) : 0;
    $city      = function_exists('get_field') ? trim((string) get_field('hotel_city', $id)) : '';
    $phone     = function_exists('get_field') ? trim((string) get_field('phone', $id)) : '';
    $address   = function_exists('get_field') ? trim((string) get_field('address', $id)) : '';
    $website   = function_exists('get_field') ? trim((string) get_field('website', $id)) : '';
    $gallery   = function_exists('get_field') ? (array) get_field('gallery', $id) : [];

    $country_id = function_exists('get_field') ? get_field('hotel_country', $id) : 0;
    $country_id = is_array($country_id) ? (int) reset($country_id) : (int) $country_id;
    $country_name = $country_id ? get_the_title($country_id) : '';

    $map_coords = function_exists('bsi_parse_map_coordinates') && function_exists('get_field')
        ? bsi_parse_map_coordinates(get_field('map_coordinates', $id)) : null;

    $images = bsi_schema_gallery_images($gallery);
    if (!$images) {
        $thumb = get_the_post_thumbnail_url($id, 'large');
        if ($thumb) {
            $images = [$thumb];
        }
    }

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Hotel',
        'name'        => $title,
        'description' => $desc,
        'url'         => $url,
        'image'       => $images,
    ];

    if ($rating > 0 && $rating <= 5) {
        $schema['starRating'] = [
            '@type'       => 'Rating',
            'ratingValue' => $rating,
        ];
    }

    $addr = [];
    if ($address) {
        $addr['streetAddress'] = $address;
    }
    if ($city) {
        $addr['addressLocality'] = $city;
    }
    if ($country_name) {
        $addr['addressCountry'] = $country_name;
    }
    if ($addr) {
        $addr['@type'] = 'PostalAddress';
        $schema['address'] = $addr;
    }

    if ($map_coords) {
        $schema['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $map_coords['lat'],
            'longitude' => $map_coords['lng'],
        ];
    }

    if ($phone) {
        $schema['telephone'] = $phone;
    }

    bsi_schema_json($schema);
}

// ── Course (education) ──────────────────────────────────────

function bsi_schema_education(): void
{
    $id = get_the_ID();
    $title = get_the_title($id);
    $url = get_permalink($id);
    $desc = get_the_excerpt($id);
    if (!$desc) {
        $desc = wp_trim_words(wp_strip_all_tags(get_the_content(null, false, $id)), 30, '…');
    }

    $country_id = 0;
    if (function_exists('get_field')) {
        $c = get_field('education_country', $id);
        if ($c instanceof WP_Post) {
            $country_id = (int) $c->ID;
        } elseif (is_array($c)) {
            $country_id = (int) reset($c);
        } else {
            $country_id = (int) $c;
        }
    }
    $country_name = $country_id ? get_the_title($country_id) : '';

    $thumb = get_the_post_thumbnail_url($id, 'large');

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Course',
        'name'        => $title,
        'description' => $desc,
        'url'         => $url,
        'provider'    => [
            '@type' => 'Organization',
            'name'  => 'BSI Group',
            'url'   => home_url('/'),
        ],
    ];

    if ($thumb) {
        $schema['image'] = $thumb;
    }

    if ($country_name) {
        $schema['locationCreated'] = [
            '@type' => 'Place',
            'name'  => $country_name,
        ];
    }

    bsi_schema_json($schema);
}

