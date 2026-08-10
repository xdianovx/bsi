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

/**
 * Путь текущего запроса без слешей по краям и без подкаталога установки.
 */
function bsi_seo_request_path(): string
{
    $path = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');

    $home_path = trim((string) wp_parse_url(home_url(), PHP_URL_PATH), '/');
    if ($home_path !== '' && strpos($path, $home_path . '/') === 0) {
        $path = substr($path, strlen($home_path) + 1);
    } elseif ($home_path !== '' && $path === $home_path) {
        $path = '';
    }

    return $path;
}

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
        'country_events'      => ['label' => 'Событийные туры', 'slug' => 'sobytiynye-tury'],
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

/**
 * Выбирает предлог «в» или «во» по началу слова:
 * во Францию, во Вьетнам, но в Венгрию, в Италию.
 */
function bsi_seo_preposition_v(string $word): string
{
    $word = trim($word);
    if ($word === '') {
        return 'в';
    }

    $first = mb_strtolower(mb_substr($word, 0, 1));
    if ($first !== 'в' && $first !== 'ф') {
        return 'в';
    }

    $second = mb_strtolower(mb_substr($word, 1, 1));
    $vowels = ['а', 'е', 'ё', 'и', 'о', 'у', 'ы', 'э', 'ю', 'я'];

    return in_array($second, $vowels, true) ? 'в' : 'во';
}

function bsi_seo_virtual_description(?array $vp): string
{
    if (!$vp || !$vp['country']) {
        return '';
    }

    $n = $vp['country']->post_title;

    // Конструкции «Туры в …» требуют винительного падежа: «в Италию»,
    // а не «в Италия». Названия после двоеточия остаются именительными.
    $acc = $n;
    if (function_exists('bsi_country_accusative_title')) {
        $resolved = trim((string) bsi_country_accusative_title((int) $vp['country']->ID));
        if ($resolved !== '') {
            $acc = $resolved;
        }
    }

    $v = bsi_seo_preposition_v($acc);

    $map = [
        'country_tours'       => "Туры {$v} {$acc} от туроператора BSI Group. Подбор тура, бронирование онлайн, актуальные цены.",
        'country_hotels'      => "Каталог отелей: {$n}. Описания, фото, рейтинги. Подбор отеля от BSI Group.",
        'country_promos'      => "Акции и спецпредложения на туры {$v} {$acc} от BSI Group. Горящие туры, скидки.",
        'country_resorts'     => "Курорты: {$n}. Описания курортов, лучшие отели, пляжи. BSI Group.",
        'country_memo'        => "Памятка туристу: {$n}. Полезная информация для путешественников от BSI Group.",
        'country_entry_rules' => "Правила въезда {$v} {$acc}: актуальные требования, документы, визы. BSI Group.",
        'country_education'   => "Обучение за рубежом: {$n}. Языковые школы, образовательные программы. BSI Group.",
        'country_visa'        => "Оформление визы: {$n}. Требования, документы, сроки оформления. BSI Group.",
        'country_news'        => "Новости туризма: {$n}. Актуальная информация от туроператора BSI Group.",
        'country_excursions'  => "Экскурсии {$v} {$acc}: программы, цены, расписание. Бронирование от туроператора BSI Group.",
        'country_events'      => "Событийные туры {$v} {$acc}: поездки на концерты, спортивные и культурные события. Программы, отели, цены. BSI Group.",
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

// Заголовок главной собирался из названия страницы («Главная — BSI»,
// 13 символов) и не содержал ни одного запроса, по которым сайт ищут.
// Заданный вручную в Yoast заголовок в приоритете.

add_filter('wpseo_title', function ($title): string {
    if (!is_front_page()) {
        return (string) $title;
    }

    $front_id = (int) get_option('page_on_front');
    $manual = $front_id
        ? trim((string) get_post_meta($front_id, '_yoast_wpseo_title', true))
        : '';

    if ($manual !== '') {
        return (string) $title;
    }

    return 'BSI Group — туроператор: туры, отели, круизы, обучение за рубежом';
}, 20);

// ── Fallback-описания для страниц без контента в post_content ──
// Лендинги (page-*.php) собраны в шаблонах, поэтому автогенерация
// из post_content для них ничего не даёт. Ключ — путь страницы
// (get_page_uri), значение — готовое описание.

function bsi_seo_page_descriptions(): array
{
    return [
        'tury' => 'Туры от туроператора BSI Group: пляжный отдых, экскурсионные и комбинированные программы по всему миру. Подбор тура, актуальные цены, бронирование.',
        'turagenstvam' => 'Раздел для турагентств BSI Group: агентский договор, документы, обучение, вебинары и рекламные туры. Условия и начало сотрудничества.',
        'novosti' => 'Новости туроператора BSI Group: направления, спецпредложения, изменения правил въезда и события отрасли.',
        'akczii' => 'Акции и спецпредложения на туры от BSI Group: скидки на отели, раннее бронирование, сезонные предложения.',
        'akczii/arhiv-akczij' => 'Архив завершённых акций и спецпредложений туроператора BSI Group.',
        'vizy' => 'Оформление виз с BSI Group: список документов, сроки и стоимость по странам. Визовая поддержка для туристов и агентств.',
        'strahovanie' => 'Страхование путешественников от BSI Group: медицинские полисы, страховка от невыезда, условия и стоимость.',
        'kruizy' => 'Круизы от туроператора BSI Group: морские и речные маршруты, лайнеры, каюты, цены и даты отправления.',
        'obrazovanie-za-rubezhom' => 'Образование за рубежом с BSI Group: языковые школы, детские лагеря, программы для взрослых. Подбор программы и подача заявки.',
        'sobytiynye-tury' => 'Событийные туры BSI Group: поездки на концерты, спортивные и культурные события. Программы, отели, цены.',
        'gde-kupit' => 'Где купить туры BSI Group: список офисов и агентств-партнёров по городам России.',
        'bonusnaya-programma' => 'Бонусная программа BSI Group для турагентств: условия начисления, размер комиссии и порядок выплат.',
        'fit' => 'Индивидуальные туры FIT от BSI Group: маршрут под запрос, подбор отелей, экскурсий и трансферов.',
        'business' => 'Деловой туризм BSI Group: организация бизнес-поездок, MICE-мероприятий, корпоративных программ.',
        'business/bsimice' => 'BSI MICE: организация корпоративных мероприятий, конференций, инсентив-туров и деловых поездок под ключ.',
        'business/biznes-trevel' => 'Бизнес-тревел от BSI Group: командировки, авиабилеты, отели и трансферы для корпоративных клиентов.',
        'turistam' => 'Полезная информация для туристов от BSI Group: памятки, правила въезда, документы и порядок оплаты туров.',
        'country' => 'Страны и направления BSI Group: туры, отели, курорты, визы и правила въезда по каждой стране.',
        'kontakty' => 'Контакты туроператора BSI Group: адреса офисов, телефоны, электронная почта и режим работы.',
        'o-nas' => 'О туроператоре BSI Group: направления работы, опыт и команда компании.',
        'o-kompanii' => 'О компании BSI Group: история, направления деятельности и принципы работы туроператора.',
        'nagrady' => 'Награды и достижения туроператора BSI Group за годы работы на туристическом рынке.',
        'vakansii' => 'Вакансии BSI Group: открытые позиции в туроператорской компании и условия работы.',
        'finobespechenie' => 'Финансовое обеспечение туроператора BSI Group: реестровые сведения, договоры и гарантии.',
        'sposoby-oplaty' => 'Способы оплаты туров BSI Group: банковская карта, безналичный расчёт, оплата в офисе.',
        'agentskij-dogovor' => 'Агентский договор BSI Group: условия сотрудничества и порядок заключения договора с турагентством.',
        'nachalo-sotrudnichestva' => 'Начало сотрудничества с BSI Group: порядок подключения турагентства, документы и первые шаги.',
        'priglashaem-k-sotrudnichestvu' => 'BSI Group приглашает к сотрудничеству турагентства и партнёров. Условия работы и контакты.',
        'gds-instrukcziya' => 'Инструкция по работе с системой бронирования GDS от BSI Group для агентств.',
        'zayavlenie-na-vozvrat' => 'Заявление на возврат денежных средств по туру BSI Group: бланк и порядок подачи.',
        'priem-i-vydacha-dokumentov' => 'Приём и выдача документов в офисе BSI Group: адрес, график работы и порядок обращения.',
        'elektronnyj-dokumentooborot' => 'Электронный документооборот с BSI Group: подключение, форматы и порядок обмена документами.',
    ];
}

/**
 * Описания для архивов CPT (ключ — post_type).
 */
function bsi_seo_archive_descriptions(): array
{
    return [
        'hotel' => 'Каталог отелей от BSI Group: описания, фото, рейтинги и удобства. Подбор отеля по странам и курортам.',
        'education' => 'Программы образования за рубежом от BSI Group: языковые курсы, школы и лагеря. Подбор по стране, возрасту и программе.',
        'documentation' => 'Материалы для турагентств BSI Group: документы, инструкции, обучение и мероприятия.',
        'news' => 'Новости туроператора BSI Group: направления, спецпредложения и события отрасли.',
        'promo' => 'Акции и спецпредложения на туры от BSI Group.',
        'excursion' => 'Экскурсии от BSI Group: программы, расписание, цены и бронирование.',
        'tour' => 'Туры от туроператора BSI Group: направления, программы, даты и цены.',
        'country' => 'Страны и направления BSI Group: туры, отели, курорты, визы и правила въезда.',
        'project' => 'Проекты туроператора BSI Group.',
        'insurance' => 'Страхование путешественников от BSI Group: программы и условия.',
        'visa' => 'Визовая поддержка BSI Group: оформление виз по странам.',
        'agency_event' => 'Мероприятия для турагентств от BSI Group: вебинары, обучение и рекламные туры.',
    ];
}

/**
 * Обрезает описание до длины, которую показывают поисковики.
 *
 * Описания страниц стран доходили до 1555 символов: Yoast берёт текст
 * из контента целиком, а в выдаче видно около 160.
 */
function bsi_seo_trim_description(string $text, int $max = 170): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if ($text === '' || mb_strlen($text) <= $max) {
        return $text;
    }

    $cut = mb_substr($text, 0, $max);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $max * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }

    return rtrim($cut, " \t\n\r\0\x0B.,;:—-") . '…';
}

/**
 * Нормализует текст в описание нужной длины.
 */
function bsi_seo_prepare_description(string $text): string
{
    $text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags(strip_shortcodes($text))));

    if ($text === '') {
        return '';
    }

    return wp_trim_words($text, 30, '…');
}

/**
 * Подбирает description для контекстов, где Yoast оставляет пустое значение:
 * главная, страницы-лендинги, архивы CPT, таксономии.
 */
function bsi_seo_fallback_description(): string
{
    if (is_front_page()) {
        return 'BSI Group — туроператор с 1989 года: туры по всему миру, отели, круизы, образование за рубежом, визы и страхование. Подбор и бронирование для туристов и агентств.';
    }

    if (is_singular()) {
        $post = get_queried_object();
        if (!($post instanceof WP_Post)) {
            return '';
        }

        $public_types = [
            'page', 'post',
            'tour', 'hotel', 'country', 'news', 'event', 'education',
            'promo', 'visa', 'insurance', 'project',
            'agency_event', 'documentation', 'excursion',
        ];
        if (!in_array($post->post_type, $public_types, true)) {
            return '';
        }

        // Для лендингов готовое описание точнее обрезка контента шаблона.
        if ($post->post_type === 'page') {
            $uri = untrailingslashit((string) get_page_uri($post));
            $map = bsi_seo_page_descriptions();
            if (isset($map[$uri])) {
                return $map[$uri];
            }
        }

        $text = bsi_seo_prepare_description((string) $post->post_excerpt);
        if ($text === '') {
            $text = bsi_seo_prepare_description((string) $post->post_content);
        }

        // Записи из общих серий (туры одной линейки, программы одной
        // школы) начинаются одинаковым текстом — обрезка даёт дубли.
        // Заголовок у них уникален: содержит длительность, города и
        // сезон, поэтому ставим его в начало описания.
        if (in_array($post->post_type, ['tour', 'education', 'excursion', 'event'], true)) {
            $prefix = trim(wp_strip_all_tags(get_the_title($post)));
            if ($prefix !== '') {
                $text = ($text === '')
                    ? $prefix . ' — туроператор BSI Group.'
                    : $prefix . '. ' . wp_trim_words($text, 22, '…');
            }
        }

        if ($text !== '') {
            return $text;
        }

        return get_the_title($post) . ' — туроператор BSI Group.';
    }

    if (is_post_type_archive()) {
        $queried_type = get_query_var('post_type');
        if (is_array($queried_type)) {
            $queried_type = reset($queried_type);
        }
        $post_type = (string) $queried_type;

        $map = bsi_seo_archive_descriptions();
        if (isset($map[$post_type])) {
            return $map[$post_type];
        }

        $obj = get_post_type_object($post_type);
        if ($obj) {
            return $obj->labels->name . ' — туроператор BSI Group.';
        }
    }

    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $text = bsi_seo_prepare_description((string) $term->description);
            if ($text !== '') {
                return $text;
            }

            return $term->name . ' — подборка от туроператора BSI Group.';
        }
    }

    if (is_search()) {
        return 'Результаты поиска по сайту туроператора BSI Group.';
    }

    return '';
}

// ── Yoast: <meta name="description"> ───────────────────────

add_filter('wpseo_metadesc', function ($desc): string {
    $desc = (string) $desc;
    $vp = bsi_seo_detect_virtual_page();
    $custom = bsi_seo_virtual_description($vp);
    if ($custom !== '') {
        return $custom;
    }

    if ($desc !== '') {
        return bsi_seo_trim_description($desc);
    }

    return bsi_seo_trim_description(bsi_seo_fallback_description());
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
    if ($custom !== '') {
        return $custom;
    }

    return $desc !== '' ? $desc : bsi_seo_fallback_description();
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
    if ($desc === '') {
        $desc = bsi_seo_fallback_description();
    }
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
// Приоритет PHP_INT_MAX — после Yoast (99999). Yoast вырезает из вывода
// дефолтный блок WordPress (User-agent + wp-admin) и дописывает свой,
// из-за чего файл начинался с Disallow без User-agent и содержал
// второй Sitemap. Отдаём файл целиком последними.

add_filter('robots_txt', function ($output, $public) {
    if (!$public) {
        return $output;
    }

    // Параметры фильтрации и сортировки каталогов. Пагинация
    // (page/paged) сюда НЕ входит: её закрытие мешает обходу
    // каталогов, от дублей там защищает canonical.
    $filter_params = [
        'sort', 'orderby', 'order',
        'region', 'resort', 'tour_type', 'program', 'accommodation',
        'age', 'age_min', 'age_max',
        'duration', 'duration_min', 'duration_max',
        'date_from', 'date_to', 'group_arrival',
        'archive', 'kind', 'direction',
        'country', 'type', 'language',
    ];

    $common = [
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Disallow: /wp-includes/',
        'Disallow: /wp-json/',
        'Disallow: /*?s=',
    ];

    $lines = ['User-agent: *'];
    $lines = array_merge($lines, $common);
    $lines[] = '';
    $lines[] = '# Фильтры и сортировка — дубли контента';
    foreach ($filter_params as $param) {
        $lines[] = 'Disallow: /*?' . $param . '=';
    }

    // Яндекс: Clean-param склеивает URL с параметрами и без,
    // не исключая страницы из обхода — точнее, чем Disallow.
    $lines[] = '';
    $lines[] = 'User-agent: Yandex';
    $lines = array_merge($lines, $common);
    foreach (array_chunk($filter_params, 12) as $chunk) {
        $lines[] = 'Clean-param: ' . implode('&', $chunk);
    }

    $lines[] = '';
    $lines[] = 'Sitemap: ' . home_url('/sitemap_index.xml');

    return implode("\n", $lines) . "\n";
}, PHP_INT_MAX, 2);

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

/**
 * Отсеивает записи country, которые на самом деле являются регионами.
 *
 * Для каждого региона создаётся парный пост country, но его постоянная
 * ссылка выглядит как /country/{страна}/{регион}/. Разделы вида
 * /country/{регион}/tours/ у таких записей отдают 404.
 */
function bsi_seo_country_is_top_level(WP_Post $country): bool
{
    $expected = trailingslashit(home_url('/country/' . $country->post_name));
    $actual = (string) get_permalink($country->ID);

    return trailingslashit($actual) === $expected;
}

/**
 * Проверяет, есть ли у страны контент конкретного раздела.
 *
 * Разделы «Виза», «Памятка» и «Правила въезда» отдают 404, когда
 * связанной записи нет. «Событийные туры» отдают 200 всегда, поэтому
 * без проверки в sitemap попали бы 59 пустых каталогов.
 */
function bsi_seo_country_section_exists(int $country_id, string $qv): bool
{
    $linked = [
        'country_visa'        => ['visa', 'visa_country'],
        'country_memo'        => ['tourist_memo', 'memo_country'],
        'country_entry_rules' => ['entry_rules', 'entry_rules_country'],
        'country_events'      => ['event', 'tour_country'],
    ];

    if (!isset($linked[$qv])) {
        return true;
    }

    [$post_type, $meta_key] = $linked[$qv];

    // tour_country хранит корневую страну ветки — поднимаемся к корню.
    if ($qv === 'country_events') {
        $parent = wp_get_post_parent_id($country_id);
        while ($parent) {
            $country_id = (int) $parent;
            $parent = wp_get_post_parent_id($country_id);
        }
    }

    $found = get_posts([
        'post_type'        => $post_type,
        'post_status'      => 'publish',
        'posts_per_page'   => 1,
        'fields'           => 'ids',
        'no_found_rows'    => true,
        'suppress_filters' => false,
        'meta_query'       => [
            [
                'key'     => $meta_key,
                'value'   => $country_id,
                'compare' => '=',
            ],
        ],
    ]);

    return !empty($found);
}

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
        if (!bsi_seo_country_is_top_level($country)) {
            continue;
        }

        foreach ($sections as $qv => $info) {
            if (!bsi_seo_country_section_exists((int) $country->ID, $qv)) {
                continue;
            }

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


// ── oEmbed: убираем служебные URL из обхода ─────────────────
// WordPress добавляет в <head> ссылки вида
// /wp-json/oembed/1.0/embed?url=... — поисковики идут по ним и
// тянут в индекс JSON/XML-ответы. Тема эти ссылки не использует.

add_action('init', function (): void {
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
});

// Ответы REST API не должны индексироваться, даже если робот
// дошёл до них по внешней ссылке.

add_filter('rest_post_dispatch', function ($response) {
    if ($response instanceof WP_REST_Response) {
        $response->header('X-Robots-Tag', 'noindex, follow');
    }

    return $response;
}, 10, 1);

// ── Служебные таксономии и CPT: вне sitemap и вне индекса ────
// Архивы фильтрующих таксономий («Гид», «Английский», «Отель»,
// регионы, курорты) дублируют нормальные страницы каталога:
// /?region=parizh повторяет /country/francziya/ile-de-france/parizh/.
// Регистрацию не трогаем — меняется только SEO-слой.

function bsi_seo_excluded_taxonomies(): array
{
    return [
        'region',
        'resort',
        'tour_include',
        'excursion_type',
        'excursion_include',
        'excursion_language',
        'education_language',
        'education_program',
        'education_meal_type',
        'education_accommodation_type',
        'visa_type',
        'insurance_type',
        'amenity',
        'meal_plan',
        'agency_event_kind',
        'agency_event_direction',
        'agency_item_type',
        'event_tour_type',
        'promo_type',
        'news_type',
        'offer_badge',
    ];
}

/**
 * CPT без самостоятельной посадочной ценности: карточки партнёров,
 * подборки для главной, памятки и правила въезда (выводятся внутри
 * разделов страны).
 */
function bsi_seo_excluded_post_types(): array
{
    return [
        'partner',
        'offer_collection',
        'tourist_memo',
        'entry_rules',
    ];
}

add_filter('wpseo_sitemap_exclude_taxonomy', function ($exclude, $taxonomy) {
    if (in_array($taxonomy, bsi_seo_excluded_taxonomies(), true)) {
        return true;
    }

    return $exclude;
}, 10, 2);

add_filter('wpseo_sitemap_exclude_post_type', function ($exclude, $post_type) {
    if (in_array($post_type, bsi_seo_excluded_post_types(), true)) {
        return true;
    }

    return $exclude;
}, 10, 2);

// Те же страницы закрываем от индексации: sitemap их больше не
// отдаёт, но робот может прийти по внутренней ссылке.

add_filter('wpseo_robots_array', function ($robots) {
    $noindex = false;

    if (is_author() || is_date()) {
        $noindex = true;
    } elseif (is_tax(bsi_seo_excluded_taxonomies())) {
        $noindex = true;
    } elseif (is_singular(bsi_seo_excluded_post_types())
        || is_post_type_archive(bsi_seo_excluded_post_types())
    ) {
        $noindex = true;
    }

    if ($noindex && is_array($robots)) {
        $robots['index'] = 'noindex';
    }

    return $robots;
}, 10, 1);

// ── llms.txt: описание сайта для ИИ-поиска ──────────────────
// Корень сайта не деплоится (выкатывается только папка темы),
// поэтому файл отдаём хуком, как и верификацию Яндекса.

add_action('init', function (): void {
    if (bsi_seo_request_path() !== 'llms.txt') {
        return;
    }

    $home = untrailingslashit(home_url());

    $lines = [
        '# BSI Group',
        '',
        '> Туроператор полного цикла, работает с 1989 года. Туры по всему миру,',
        '> подбор отелей, круизы, образование за рубежом, визовая поддержка,',
        '> страхование и деловой туризм. Работает с туристами и турагентствами.',
        '',
        '## Основные разделы',
        '',
        '- [Туры](' . $home . '/tury/): подбор и бронирование туров по направлениям',
        '- [Страны](' . $home . '/country/): по каждой стране — туры, отели, курорты, визы, правила въезда',
        '- [Отели](' . $home . '/hotels/): каталог отелей с описаниями и рейтингами',
        '- [Круизы](' . $home . '/kruizy/): морские и речные маршруты',
        '- [Образование за рубежом](' . $home . '/obrazovanie-za-rubezhom/): языковые школы, детские лагеря, программы для взрослых',
        '- [Событийные туры](' . $home . '/sobytiynye-tury/): поездки на концерты, спортивные и культурные события',
        '- [Визы](' . $home . '/vizy/): оформление виз, документы и сроки по странам',
        '- [Страхование](' . $home . '/strahovanie/): полисы для путешественников',
        '- [Деловой туризм](' . $home . '/business/): MICE, корпоративные и бизнес-поездки',
        '',
        '## Турагентствам',
        '',
        '- [Раздел для агентств](' . $home . '/turagenstvam/): договор, документы, условия сотрудничества',
        '- [Бонусная программа](' . $home . '/bonusnaya-programma/): комиссия и порядок выплат',
        '- [Где купить](' . $home . '/gde-kupit/): офисы и агентства-партнёры',
        '',
        '## Компания',
        '',
        '- [О компании](' . $home . '/o-kompanii/)',
        '- [Контакты](' . $home . '/kontakty/): адреса офисов, телефоны, режим работы',
        '- [Новости](' . $home . '/novosti/)',
        '',
        '## Дополнительно',
        '',
        '- [Карта сайта](' . $home . '/sitemap_index.xml)',
    ];

    header('Content-Type: text/plain; charset=UTF-8');
    echo implode("\n", $lines) . "\n";
    exit;
});

// ── 301: старые URL стран с ISO-кодами ──────────────────────
// На прежнем сайте страны жили по трёхбуквенным кодам
// (/country/fra/, /country/irl/visa), сейчас — по транслитерации
// (/country/francziya/). Яндекс до сих пор держит старые адреса в
// выдаче: /country/irl/visa входит в топ-50 по 52 запросам, а сайт
// отвечает 404. Переводим такие адреса на актуальные разделы.

function bsi_seo_legacy_country_codes(): array
{
    return [
        'alb' => 'albaniya',
        'arm' => 'armeniya',
        'aut' => 'avstriya',
        'aze' => 'azerbajdzhan',
        'bhr' => 'bahrejn',
        'bel' => 'belgiya',
        'blr' => 'belorussiya',
        'brn' => 'brunej',
        'btn' => 'butan',
        'cze' => 'chehiya',
        'mne' => 'chernogoriya',
        'phl' => 'filippiny',
        'fra' => 'francziya',
        'deu' => 'germaniya',
        'grc' => 'grecziya',
        'geo' => 'gruziya',
        'hrv' => 'hovatiya',
        'ind' => 'indiya',
        'idn' => 'indoneziya',
        'irl' => 'irlandiya',
        'esp' => 'ispaniya',
        'ita' => 'italiya',
        'khm' => 'kambodzha',
        'qat' => 'katar',
        'kaz' => 'kazahstan',
        'cyp' => 'kipr',
        'chn' => 'kitaj',
        'kgz' => 'kyrgyzstan',
        'lao' => 'laos',
        'lux' => 'lyuksemburg',
        'mys' => 'malajziya',
        'mdv' => 'maldivy',
        'mus' => 'mavrikij',
        'mmr' => 'myanma',
        'npl' => 'nepal',
        'nld' => 'niderlandy',
        'are' => 'oae',
        'omn' => 'oman',
        'pak' => 'pakistan',
        'prt' => 'portugaliya',
        'rus' => 'rossiya',
        'sau' => 'saudovskaya-araviya',
        'syc' => 'sejshely',
        'srb' => 'serbia',
        'lka' => 'shri-lanka',
        'che' => 'shvejczariya',
        'sgp' => 'singapur',
        'svk' => 'slovakiya',
        'svn' => 'sloveniya',
        'usa' => 'ssha',
        'tha' => 'tailand',
        'tur' => 'turcziya',
        'tkm' => 'turkmenistan',
        'uzb' => 'uzbekistan',
        'gbr' => 'velikobritaniya',
        'hun' => 'vengriya',
        'vnm' => 'vetnam',
        'jpn' => 'yaponiya',
        'kor' => 'yuzhnaya-koreya',
    ];
}

/**
 * Сопоставляет раздел старого URL с разделом нового.
 * Неизвестный раздел ведёт на страницу страны.
 */
function bsi_seo_legacy_country_target(string $base, array $parts, int $country_id): string
{
    $section = strtolower($parts[2] ?? '');

    switch ($section) {
        case 'visa':
            return bsi_seo_country_section_exists($country_id, 'country_visa')
                ? $base . 'visa/'
                : $base;

        case 'tip-tura':
        case 'tours':
            return $base . 'tours/';

        case 'hotels':
        case 'cities':
            return $base . 'hotel/';

        case 'kurorty':
        case 'resorts':
            return $base . 'kurorty/';

        case 'news':
        case 'novosti':
            return $base . 'novosti/';

        case 'promo':
        case 'akcii':
            return $base . 'promo/';

        case 'ekskursii':
        case 'excursions':
            return $base . 'ekskursii/';

        case 'obuchenie':
        case 'education':
            return $base . 'obuchenie/';

        default:
            return $base;
    }
}

add_action('template_redirect', function () {
    if (!is_404()) {
        return;
    }

    $path = bsi_seo_request_path();
    if ($path === '') {
        return;
    }

    $parts = explode('/', $path);

    // Страховая страница старого сайта — единственный /static/ в выдаче.
    if ($parts[0] === 'static' && ($parts[1] ?? '') === 'med_alfa') {
        wp_redirect(home_url('/strahovanie/'), 301);
        exit;
    }

    if ($parts[0] !== 'country' || count($parts) < 2) {
        return;
    }

    $codes = bsi_seo_legacy_country_codes();
    $code = strtolower($parts[1]);
    if (!isset($codes[$code])) {
        return;
    }

    $country = get_page_by_path($codes[$code], OBJECT, 'country');
    if (!$country instanceof WP_Post || $country->post_status !== 'publish') {
        return;
    }

    $base = trailingslashit(home_url('/country/' . $country->post_name));

    wp_redirect(bsi_seo_legacy_country_target($base, $parts, (int) $country->ID), 301);
    exit;
}, 1);

// ── 301: разделы страны, зарегистрированные дважды ──────────
// «Памятка» и «Правила въезда» получили по второму адресу: разделы
// завели повторно с другим slug, старое правило осталось. Обе пары
// открывали один шаблон, то есть каждая страна отдавала два одинаковых
// URL. Дублирующие правила удалены; здесь уводим уже известные
// поисковику адреса на основные. Приоритет 0 — раньше роутеров разделов.

add_action('template_redirect', function () {
    $path = bsi_seo_request_path();
    if ($path === '') {
        return;
    }

    $aliases = [
        'memo' => 'pamyatka',
        'entry-rules' => 'pravila-vyezda',
    ];

    if (!preg_match('#^country/([^/]+)/([^/]+)/?$#', $path, $m)) {
        return;
    }

    if (!isset($aliases[$m[2]])) {
        return;
    }

    wp_redirect(
        trailingslashit(home_url('/country/' . $m[1] . '/' . $aliases[$m[2]])),
        301
    );
    exit;
}, 0);

// ── Заголовки архивов CPT ───────────────────────────────────
// Стандартный archive.php выводит the_archive_title(), который для
// /hotels/ давал «Архивы: Отели» — служебная формулировка WordPress
// попадала и в H1, и в <title>.

add_filter('get_the_archive_title_prefix', '__return_empty_string');

/**
 * Заголовок H1 на странице архива (ключ — post_type).
 * Короткий: рядом уже есть контекст страницы.
 */
function bsi_seo_archive_titles(): array
{
    return [
        'hotel'         => 'Каталог отелей',
        'education'     => 'Образование за рубежом',
        'documentation' => 'Материалы для турагентств',
        'news'          => 'Новости',
        'promo'         => 'Акции и спецпредложения',
        'excursion'     => 'Экскурсии',
        'tour'          => 'Туры',
        'country'       => 'Страны и направления',
        'insurance'     => 'Страхование путешественников',
        'visa'          => 'Оформление виз',
        'agency_event'  => 'Мероприятия для турагентств',
        'project'       => 'Проекты',
    ];
}

/**
 * Заголовок для <title> архива — длиннее H1.
 *
 * В выдаче у пользователя нет контекста страницы, поэтому «Каталог
 * отелей» (21 символ вместе с именем сайта) не отвечает ни на один
 * запрос. Норма — 60-70 символов.
 */
function bsi_seo_archive_seo_titles(): array
{
    return [
        'hotel'         => 'Отели по всему миру — каталог с фото, рейтингами и ценами',
        'education'     => 'Образование за рубежом — языковые школы, лагеря и программы',
        'documentation' => 'Турагентствам — документы, обучение и условия работы',
        'news'          => 'Новости туризма — направления, спецпредложения и события',
        'promo'         => 'Акции и спецпредложения на туры — скидки и раннее бронирование',
        'excursion'     => 'Экскурсии — программы, расписание и цены',
        'tour'          => 'Туры по всему миру — подбор, цены и бронирование',
        'country'       => 'Страны и направления — туры, отели, курорты и визы',
        'insurance'     => 'Страхование путешественников — полисы и условия',
        'visa'          => 'Оформление виз — документы, сроки и стоимость по странам',
        'agency_event'  => 'Мероприятия для турагентств — вебинары и рекламные туры',
        'project'       => 'Проекты BSI Group',
    ];
}

add_filter('get_the_archive_title', function ($title) {
    if (!is_post_type_archive()) {
        return $title;
    }

    $queried_type = get_query_var('post_type');
    if (is_array($queried_type)) {
        $queried_type = reset($queried_type);
    }

    $map = bsi_seo_archive_titles();

    return $map[(string) $queried_type] ?? $title;
}, 20);

// Тот же заголовок — в <title> выдачи, если он не задан в Yoast вручную.

add_filter('wpseo_title', function ($title) {
    if (!is_post_type_archive()) {
        return $title;
    }

    $queried_type = get_query_var('post_type');
    if (is_array($queried_type)) {
        $queried_type = reset($queried_type);
    }

    $map = bsi_seo_archive_seo_titles();
    $custom = $map[(string) $queried_type] ?? '';

    // Заменяем только служебную заготовку («Архив …»), не трогая
    // заголовок, заданный в настройках Yoast вручную.
    if ($custom === '' || mb_stripos((string) $title, 'Архив') !== 0) {
        return $title;
    }

    return $custom . ' | ' . get_bloginfo('name');
}, 20);

// ── og:image для страниц без своей картинки ─────────────────
// Главная, страны и каталоги отдавались без og:image: ссылка в
// Telegram, ВК или WhatsApp выглядела голым текстом. Yoast выводит тег
// только когда изображение у страницы есть, поэтому подставляем своё.

/**
 * Подбирает изображение для соцсетей под текущий контекст.
 */
function bsi_seo_social_image_url(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = '';

    // Главная — первый баннер из ACF-подборки.
    if (is_front_page()) {
        $front_id = (int) get_option('page_on_front');
        $banners = ($front_id && function_exists('get_field')) ? get_field('banners', $front_id) : [];
        if (is_array($banners)) {
            foreach ($banners as $banner) {
                $cached = bsi_seo_social_image_accept((string) ($banner['img'] ?? ''));
                if ($cached !== '') {
                    return $cached;
                }
            }
        }
    }

    // Запись — миниатюра, галерея страны, затем картинка из контента.
    if (is_singular()) {
        $post_id = (int) get_queried_object_id();

        if ($post_id && has_post_thumbnail($post_id)) {
            $cached = bsi_seo_social_image_accept((string) get_the_post_thumbnail_url($post_id, 'large'));
            if ($cached !== '') {
                return $cached;
            }
        }

        if (is_singular('country') && function_exists('get_field')) {
            $gallery = get_field('galereya', $post_id);
            if (is_array($gallery)) {
                foreach ($gallery as $item) {
                    $url = is_array($item) ? ($item['url'] ?? '') : (string) $item;
                    $cached = bsi_seo_social_image_accept((string) $url);
                    if ($cached !== '') {
                        return $cached;
                    }
                }
            }
        }

        $content = (string) get_post_field('post_content', $post_id);
        if ($content !== '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m)) {
            $cached = bsi_seo_social_image_accept($m[1]);
            if ($cached !== '') {
                return $cached;
            }
        }
    }

    // Архивы и всё остальное — миниатюра свежей записи этого типа.
    if (is_post_type_archive() || is_tax() || is_category()) {
        $queried_type = get_query_var('post_type');
        if (is_array($queried_type)) {
            $queried_type = reset($queried_type);
        }

        $recent = get_posts([
            'post_type'      => $queried_type ?: 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_key'       => '_thumbnail_id',
            'no_found_rows'  => true,
        ]);

        if (!empty($recent)) {
            $cached = bsi_seo_social_image_accept((string) get_the_post_thumbnail_url($recent[0]->ID, 'large'));
            if ($cached !== '') {
                return $cached;
            }
        }
    }

    // Растрового изображения не нашлось. Логотип здесь не подходит:
    // он в SVG, а соцсети такие превью не показывают. Общую картинку
    // задают в «Yoast SEO → Соцсети → Изображение по умолчанию».
    return $cached;
}

/**
 * Пропускает только форматы, которые соцсети умеют показывать.
 */
function bsi_seo_social_image_accept(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    $ext = strtolower((string) pathinfo((string) wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $url : '';
}

add_filter('wpseo_frontend_presentation', function ($presentation) {
    if (!is_object($presentation) || !empty($presentation->open_graph_images)) {
        return $presentation;
    }

    $url = bsi_seo_social_image_url();
    if ($url === '') {
        return $presentation;
    }

    $presentation->open_graph_images = [
        ['url' => $url],
    ];

    if (empty($presentation->twitter_image)) {
        $presentation->twitter_image = $url;
    }

    return $presentation;
});

// ── Служебные приписки в заголовках записей ─────────────────
// Шаблон Yoast добавлял к заголовку тип записи: «… — образование за
// рубежом — BSI» (24 символа) или «… — Тур». Названия туров и программ
// и без того длинные, а в выдаче видно 60-70 символов — приписки
// вытесняли полезный текст, ничего не сообщая пользователю.

add_filter('wpseo_title', function ($title) {
    $types = ['tour', 'education', 'excursion', 'hotel', 'event', 'visa', 'insurance'];

    if (!is_singular($types)) {
        return $title;
    }

    $post_id = (int) get_queried_object_id();
    if (!$post_id) {
        return $title;
    }

    // Заголовок, вписанный в Yoast вручную, оставляем как есть.
    $manual = trim((string) get_post_meta($post_id, '_yoast_wpseo_title', true));
    if ($manual !== '') {
        return $title;
    }

    $post_title = trim(wp_strip_all_tags((string) get_the_title($post_id)));
    if ($post_title === '') {
        return $title;
    }

    return $post_title . ' | ' . get_bloginfo('name');
}, 25);
