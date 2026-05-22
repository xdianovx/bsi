<?php
/**
 * SEO: Meta-теги для виртуальных страниц стран и CPT fallback.
 *
 * Проблема: 9 подстраниц вида /country/{slug}/tours/ и т.д. используют
 * template_redirect + exit, и Yoast не может определить контекст —
 * выдаёт дубль title страны или пустой title.
 *
 * Решение: перехватываем фильтры Yoast (wpseo_title, wpseo_metadesc,
 * wpseo_canonical, wpseo_opengraph_*) и генерируем корректные мета-теги.
 * Также добавляем fallback через document_title_parts на случай
 * деактивации Yoast.
 */

function bsi_seo_virtual_sections(): array
{
    return [
        'country_tours'       => ['label' => 'Туры',            'slug' => 'tours'],
        'country_hotels'      => ['label' => 'Отели',           'slug' => 'hotel'],
        'country_promos'      => ['label' => 'Акции',           'slug' => 'promo'],
        'country_resorts'     => ['label' => 'Курорты',         'slug' => 'kurorty'],
        'country_memo'        => ['label' => 'Памятка туристу', 'slug' => 'pamyatka'],
        'country_entry_rules' => ['label' => 'Правила въезда',  'slug' => 'pravila-vyezda'],
        'country_education'   => ['label' => 'Обучение',        'slug' => 'obuchenie'],
        'country_visa'        => ['label' => 'Виза',            'slug' => 'visa'],
        'country_news'        => ['label' => 'Новости',         'slug' => 'novosti'],
        'country_excursions'  => ['label' => 'Экскурсии',       'slug' => 'ekskursii'],
    ];
}

/**
 * Определяет виртуальную подстраницу страны по query vars.
 * Результат кешируется на время запроса.
 *
 * @return array{qv: string, label: string, slug: string, country: WP_Post|null}|null
 */
function bsi_seo_detect_virtual_page(): ?array
{
    static $result = false;

    if ($result !== false) {
        return $result;
    }

    $result = null;

    foreach (bsi_seo_virtual_sections() as $qv => $info) {
        $val = (string) get_query_var($qv);
        if ($val === '') {
            continue;
        }

        $country = get_page_by_path($val, OBJECT, 'country');

        $result = [
            'qv'      => $qv,
            'label'   => $info['label'],
            'slug'    => $info['slug'],
            'country' => ($country instanceof WP_Post) ? $country : null,
        ];
        break;
    }

    return $result;
}

function bsi_seo_virtual_title(?array $vp): string
{
    if (!$vp || !$vp['country']) {
        return '';
    }

    $site = get_bloginfo('name');

    return $vp['country']->post_title . ' — ' . $vp['label'] . ' | ' . $site;
}

function bsi_seo_virtual_description(?array $vp): string
{
    if (!$vp || !$vp['country']) {
        return '';
    }

    $n = $vp['country']->post_title;

    $map = [
        'country_tours'       => "Туры в {$n} от туроператора BSI Group. Подбор тура, бронирование онлайн, актуальные цены.",
        'country_hotels'      => "Каталог отелей: {$n}. Описания, фото, рейтинги. Подбор отеля от BSI Group.",
        'country_promos'      => "Акции и спецпредложения на туры в {$n} от BSI Group. Горящие туры, скидки.",
        'country_resorts'     => "Курорты: {$n}. Описания курортов, лучшие отели, пляжи. BSI Group.",
        'country_memo'        => "Памятка туристу: {$n}. Полезная информация для путешественников от BSI Group.",
        'country_entry_rules' => "Правила въезда в {$n}: актуальные требования, документы, визы. BSI Group.",
        'country_education'   => "Обучение за рубежом: {$n}. Языковые школы, образовательные программы. BSI Group.",
        'country_visa'        => "Оформление визы: {$n}. Требования, документы, сроки оформления. BSI Group.",
        'country_news'        => "Новости туризма: {$n}. Актуальная информация от туроператора BSI Group.",
        'country_excursions'  => "Экскурсии в {$n}: программы, цены, расписание. Бронирование от туроператора BSI Group.",
    ];

    return $map[$vp['qv']] ?? '';
}

function bsi_seo_virtual_canonical(?array $vp): string
{
    if (!$vp || !$vp['country']) {
        return '';
    }

    return trailingslashit(
        home_url('/country/' . $vp['country']->post_name . '/' . $vp['slug'])
    );
}

// ── Yoast: <title> ──────────────────────────────────────────

add_filter('wpseo_title', function ($title): string {
    $title = (string) $title;
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_title($vp);

    return $custom !== '' ? $custom : $title;
});

// ── Yoast: <meta name="description"> ───────────────────────

add_filter('wpseo_metadesc', function ($desc): string {
    $desc = (string) $desc;
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_description($vp);
    if ($custom !== '') {
        return $custom;
    }

    if ($desc !== '') {
        return $desc;
    }

    if (is_singular()) {
        $post = get_queried_object();
        if (!($post instanceof WP_Post)) {
            return $desc;
        }

        $public_cpt = [
            'tour', 'hotel', 'country', 'news', 'event', 'education',
            'promo', 'visa', 'insurance', 'project',
            'agency_event', 'documentation',
        ];
        if (!in_array($post->post_type, $public_cpt, true)) {
            return $desc;
        }

        $text = $post->post_excerpt;
        if ($text === '') {
            $text = wp_strip_all_tags(strip_shortcodes($post->post_content));
        }
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text !== '') {
            return wp_trim_words($text, 25, '…');
        }
    }

    return $desc;
});

// ── Yoast: <link rel="canonical"> ──────────────────────────
// Приоритет 5 — до фильтра canonical в tour.php (приоритет 10)

add_filter('wpseo_canonical', function ($canonical): string {
    $canonical = (string) $canonical;
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_canonical($vp);

    return $custom !== '' ? $custom : $canonical;
}, 5);

// ── Canonical: очистка GET-параметров фильтрации ────────────
// AJAX-фильтры (education, tours, events) добавляют ?sort=, ?region=
// и т.д. через replaceState — каждый вариант URL выглядит как
// отдельная страница. Canonical всегда должен указывать на чистый URL.
// Приоритет 20 — после всех остальных canonical-хэндлеров (virtual: 5, tour: 10).

add_filter('wpseo_canonical', function ($canonical): string {
    $canonical = (string) $canonical;
    if ($canonical === '') {
        return $canonical;
    }

    $clean = strtok($canonical, '?');

    return ($clean !== false) ? trailingslashit($clean) : $canonical;
}, 20);

// ── Yoast: Open Graph URL — аналогичная очистка ─────────────

add_filter('wpseo_opengraph_url', function ($url): string {
    $url = (string) $url;
    if ($url === '') {
        return $url;
    }

    $clean = strtok($url, '?');

    return ($clean !== false) ? trailingslashit($clean) : $url;
});

// ── Yoast: Open Graph title ─────────────────────────────────

add_filter('wpseo_opengraph_title', function ($title): string {
    $title = (string) $title;
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_title($vp);

    return $custom !== '' ? $custom : $title;
});

// ── Yoast: Open Graph description ───────────────────────────

add_filter('wpseo_opengraph_desc', function ($desc): string {
    $desc = (string) $desc;
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_description($vp);

    return $custom !== '' ? $custom : $desc;
});

// ── WordPress core fallback (если Yoast деактивирован) ──────

add_filter('document_title_parts', function (array $parts): array {
    if (defined('WPSEO_VERSION')) {
        return $parts;
    }

    $vp = bsi_seo_detect_virtual_page();
    if (!$vp || !$vp['country']) {
        return $parts;
    }

    $parts['title'] = $vp['country']->post_title . ' — ' . $vp['label'];

    return $parts;
});

add_action('wp_head', function (): void {
    if (defined('WPSEO_VERSION')) {
        return;
    }

    $vp = bsi_seo_detect_virtual_page();
    $desc = bsi_seo_virtual_description($vp);
    if ($desc !== '') {
        printf(
            '<meta name="description" content="%s">' . "\n",
            esc_attr($desc)
        );
    }

    $canonical = bsi_seo_virtual_canonical($vp);
    if ($canonical !== '') {
        printf(
            '<link rel="canonical" href="%s">' . "\n",
            esc_url($canonical)
        );
        return;
    }

    if (empty($_SERVER['QUERY_STRING'])) {
        return;
    }

    $clean = strtok(home_url(add_query_arg([])), '?');
    if ($clean !== false) {
        printf(
            '<link rel="canonical" href="%s">' . "\n",
            esc_url(trailingslashit($clean))
        );
    }
}, 1);

// ── 301: редиректы со старых URL с числовыми ID ─────────────
// Миграция: старый сайт использовал /country/{slug}/tours/{ID}/
// и аналогичные паттерны. Ловим 404 → ищем пост по ID → 301.

add_action('template_redirect', function () {
    if (!is_404()) {
        return;
    }

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');

    if (!preg_match('#/(\d+)/?$#', $path, $m)) {
        return;
    }

    $post_id = (int) $m[1];
    if ($post_id < 1 || $post_id > 9999999) {
        return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
        return;
    }

    $public_types = get_post_types(['public' => true]);
    if (!isset($public_types[$post->post_type])) {
        return;
    }

    $canonical = get_permalink($post->ID);
    if (!$canonical) {
        return;
    }

    wp_redirect($canonical, 301);
    exit;
}, 1);

// ── robots.txt: блокировка фильтров и служебных URL ─────────

add_filter('robots_txt', function ($output, $public) {
    if (!$public) {
        return $output;
    }

    $lines = [
        'User-agent: *',
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Disallow: /wp-includes/',
        'Disallow: /wp-json/',
        '',
        '# Фильтры, сортировка, пагинация — дубли контента',
        'Disallow: /*?sort=',
        'Disallow: /*?region=',
        'Disallow: /*?resort=',
        'Disallow: /*?tour_type=',
        'Disallow: /*?country=',
        'Disallow: /*?program=',
        'Disallow: /*?language=',
        'Disallow: /*?type=',
        'Disallow: /*?accommodation=',
        'Disallow: /*?age=',
        'Disallow: /*?age_min=',
        'Disallow: /*?age_max=',
        'Disallow: /*?duration=',
        'Disallow: /*?duration_min=',
        'Disallow: /*?duration_max=',
        'Disallow: /*?date_from=',
        'Disallow: /*?date_to=',
        'Disallow: /*?group_arrival=',
        'Disallow: /*?archive=',
        'Disallow: /*?kind=',
        'Disallow: /*?direction=',
        'Disallow: /*?orderby=',
        'Disallow: /*?order=',
        'Disallow: /*?page=',
        'Disallow: /*?paged=',
        'Disallow: /*?s=',
        '',
        'Sitemap: https://bsigroup.ru/sitemap_index.xml',
    ];

    return implode("\n", $lines) . "\n";
}, 999, 2);

// ── Sitemap: виртуальные подстраницы стран ───────────────────

add_filter('wpseo_sitemap_index', function ($index) {
    $index .= '  <sitemap>' . "\n";
    $index .= '    <loc>' . esc_url(home_url('/country-sections-sitemap.xml')) . '</loc>' . "\n";
    $index .= '    <lastmod>' . gmdate('c') . '</lastmod>' . "\n";
    $index .= '  </sitemap>' . "\n";

    return $index;
});

add_action('init', function () {
    if (!class_exists('WPSEO_Sitemaps')) {
        return;
    }

    global $wpseo_sitemaps;
    if (!isset($wpseo_sitemaps) || !method_exists($wpseo_sitemaps, 'register_sitemap')) {
        return;
    }

    $wpseo_sitemaps->register_sitemap('country-sections', 'bsi_sitemap_country_sections');
});

function bsi_sitemap_country_sections() {
    $sections = bsi_seo_virtual_sections();

    $countries = get_posts([
        'post_type'      => 'country',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    if (empty($countries)) {
        return;
    }

    $xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($countries as $country) {
        foreach ($sections as $info) {
            $url = trailingslashit(
                home_url('/country/' . $country->post_name . '/' . $info['slug'])
            );
            $mod = get_the_modified_date('c', $country);

            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . esc_url($url) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $mod . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.6</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
    }

    $xml .= '</urlset>';

    global $wpseo_sitemaps;
    if (isset($wpseo_sitemaps)) {
        $wpseo_sitemaps->set_sitemap($xml);
    }
}

