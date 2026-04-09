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

declare(strict_types=1);

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

add_filter('wpseo_title', function (string $title): string {
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_title($vp);

    return $custom !== '' ? $custom : $title;
});

// ── Yoast: <meta name="description"> ───────────────────────

add_filter('wpseo_metadesc', function (string $desc): string {
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_description($vp);
    if ($custom !== '') {
        return $custom;
    }

    if ($desc !== '') {
        return $desc;
    }

    // Fallback: автогенерация description из excerpt/content для CPT singles
    if (is_singular()) {
        $post = get_queried_object();
        if (!($post instanceof WP_Post)) {
            return $desc;
        }

        $public_cpt = [
            'tour', 'hotel', 'country', 'news', 'event', 'education',
            'promo', 'visa', 'insurance', 'review', 'project',
            'service', 'agency_event', 'documentation',
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

add_filter('wpseo_canonical', function (string $canonical): string {
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_canonical($vp);

    return $custom !== '' ? $custom : $canonical;
}, 5);

// ── Yoast: Open Graph title ─────────────────────────────────

add_filter('wpseo_opengraph_title', function (string $title): string {
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_title($vp);

    return $custom !== '' ? $custom : $title;
});

// ── Yoast: Open Graph description ───────────────────────────

add_filter('wpseo_opengraph_desc', function (string $desc): string {
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
    }
}, 1);

