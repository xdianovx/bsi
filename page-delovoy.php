<?php
/**
 * Template Name: MICE — деловой туризм (BSI MICE)
 */
get_header('mice');

if (!function_exists('bsimice_format_textarea')) {
    /**
     * @param string|null $value
     */
    function bsimice_format_textarea($value, bool $from_acf): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if ($from_acf) {
            return wp_kses_post((string) $value);
        }

        return wp_kses_post(nl2br(esc_html((string) $value)));
    }
}

if (!function_exists('bsimice_hero_link')) {
    /**
     * Пустой URL → якорь на этой странице; иначе внешняя ссылка или #fragment.
     *
     * @param string|null $acf_url
     */
    function bsimice_hero_link($acf_url, string $default_fragment_id): string
    {
        $default_fragment_id = ltrim($default_fragment_id, '#');
        $acf_url = is_string($acf_url) ? trim($acf_url) : '';

        if ($acf_url !== '' && $acf_url !== '#') {
            if (strpos($acf_url, '#') === 0) {
                return get_permalink() . $acf_url;
            }

            return $acf_url;
        }

        return get_permalink() . '#' . $default_fragment_id;
    }
}

$delovoy_defaults = [
    'hero_title' => 'Деловой туризм и корпоративные поездки',
    'hero_subtitle' => 'Персональный менеджер, прозрачная отчётность и контроль бюджета — организуем деловые поездки и сопровождение для компаний любого масштаба.',
    'hero_btn_primary_text' => 'Консультация',
    'hero_btn_secondary_text' => 'Проекты',
    'services_heading' => 'наши услуги',
    'services' => array_fill(0, 6, [
        'title' => 'Конференции и форумы',
        'description' => 'Организация под ключ: площадка, техника, регистрация, программа. До нескольких тысяч участников.',
    ]),
    'why_heading' => 'почему нас выбирают',
    'why_description' => 'Более 400 корпоративных клиентов из России и зарубежья выбрали BSI Group. 30 лет опыта, прозрачные цены и полная ответственность за каждую командировку.',
    'why_items' => [
        [
            'title' => 'Квалифицированный персонал',
            'text' => 'Деловые встречи, переговоры, рабочие сессии. Организуем переговорные комнаты, технику и кейтеринг.',
        ],
        [
            'title' => 'Финансовая надёжность',
            'text' => 'Мотивационные туры и программы вознаграждения для сотрудников и партнёров. Россия и зарубежные направления.',
        ],
        [
            'title' => 'Прямые договоры с GDS',
            'text' => 'Конференции, форумы, съезды. Подбор площадок, техническое обеспечение, регистрация участников.',
        ],
    ],
    'prove_heading' => 'Более 35 лет сопровождаем вас в деловых поездках',
    'prove_paragraphs' => [
        ['text' => 'BSI Group — один из старейших игроков рынка корпоративных мероприятий России. С 1990 года мы организуем MICE-проекты любой сложности: от переговорной на 10 человек до международного форума с тысячей участников.'],
        ['text' => 'Наша команда — профессионалы, влюблённые в своё дело. Мы не перекладываем задачи на субподрядчиков без контроля — каждый проект ведёт персональный менеджер от первого звонка до финальной отчётности.'],
    ],
    'stats' => [
        ['title' => '35 лет', 'text' => 'на рынке', 'bg_label' => '35 лет'],
        ['title' => '35 лет', 'text' => 'на рынке', 'bg_label' => '500 +'],
        ['title' => '35 лет', 'text' => 'на рынке', 'bg_label' => '35 лет'],
        ['title' => '35 лет', 'text' => 'на рынке', 'bg_label' => '35 лет'],
    ],
    'reviews_heading' => 'Нас Благодарят',
    'reviews' => array_fill(0, 5, [
        'quote' => 'Разнообразный и богатый опыт говорит нам, что выбранный нами инновационный путь в значительной степени обусловливает важность своевременного выполнения сверхзадачи. Равным образом, курс на социально-ориентированный национальный проект позволяет выполнить важные задания по разработке благоприятных перспектив.',
        'author_name' => 'Иванов Иван Иванович',
        'author_title' => 'Генеральный директор «Суперкомпашка»',
    ]),
];

while (have_posts()):
    the_post();

    $hero_bg = function_exists('get_field') ? get_field('bsimice_hero_bg') : null;
    $hero_bg_url = is_array($hero_bg) && !empty($hero_bg['url'])
        ? $hero_bg['url']
        : get_template_directory_uri() . '/img/mice/4.png';
    $hero_bg_alt = is_array($hero_bg) && !empty($hero_bg['alt']) ? (string) $hero_bg['alt'] : get_the_title();

    $hero_title = function_exists('get_field') ? get_field('bsimice_hero_title') : '';
    if ($hero_title === '' || $hero_title === null) {
        $hero_title = $delovoy_defaults['hero_title'];
    }

    $hero_subtitle_acf = function_exists('get_field') ? get_field('bsimice_hero_subtitle') : null;
    $hero_subtitle_from_acf = $hero_subtitle_acf !== null && $hero_subtitle_acf !== '';
    $hero_subtitle = $hero_subtitle_from_acf ? $hero_subtitle_acf : $delovoy_defaults['hero_subtitle'];
    $hero_subtitle_html = bsimice_format_textarea($hero_subtitle, $hero_subtitle_from_acf);

    $btn1_text = $delovoy_defaults['hero_btn_primary_text'];
    $btn1_href = bsimice_hero_link('', 'bsimice-contact');
    $btn2_text = $delovoy_defaults['hero_btn_secondary_text'];
    $btn2_href = bsimice_hero_link('', 'projects');

    $services_heading = function_exists('get_field') ? get_field('delovoy_services_heading') : '';
    if ($services_heading === '' || $services_heading === null) {
        $services_heading = $delovoy_defaults['services_heading'];
    }

    $services_acf = function_exists('get_field') ? get_field('delovoy_services') : null;
    $services_from_acf = !empty($services_acf) && is_array($services_acf);
    $services = $services_from_acf ? $services_acf : $delovoy_defaults['services'];

    $why_heading = function_exists('get_field') ? get_field('delovoy_why_heading') : '';
    if ($why_heading === '' || $why_heading === null) {
        $why_heading = $delovoy_defaults['why_heading'];
    }

    $why_desc_acf = function_exists('get_field') ? get_field('delovoy_why_description') : null;
    $why_desc_from_acf = $why_desc_acf !== null && $why_desc_acf !== '';
    $why_description = $why_desc_from_acf ? $why_desc_acf : $delovoy_defaults['why_description'];
    $why_description_html = bsimice_format_textarea($why_description, $why_desc_from_acf);

    $why_items_acf = function_exists('get_field') ? get_field('delovoy_why_items') : null;
    $why_items_from_acf = !empty($why_items_acf) && is_array($why_items_acf);
    $why_items = $why_items_from_acf ? $why_items_acf : $delovoy_defaults['why_items'];

    $prove_heading = function_exists('get_field') ? get_field('delovoy_prove_heading') : '';
    if ($prove_heading === '' || $prove_heading === null) {
        $prove_heading = $delovoy_defaults['prove_heading'];
    }
    $prove_paragraphs_acf = function_exists('get_field') ? get_field('delovoy_prove_paragraphs') : null;
    $prove_paragraphs_from_acf = !empty($prove_paragraphs_acf) && is_array($prove_paragraphs_acf);
    $prove_paragraphs = $prove_paragraphs_from_acf ? $prove_paragraphs_acf : $delovoy_defaults['prove_paragraphs'];
    $prove_image = function_exists('get_field') ? get_field('delovoy_prove_image') : null;
    $prove_image_url = is_array($prove_image) && !empty($prove_image['url'])
        ? $prove_image['url']
        : get_template_directory_uri() . '/img/mice/5.png';
    $prove_image_alt = is_array($prove_image) && !empty($prove_image['alt']) ? (string) $prove_image['alt'] : get_the_title();

    $stats_acf = function_exists('get_field') ? get_field('delovoy_stats') : null;
    $stats = !empty($stats_acf) && is_array($stats_acf) ? $stats_acf : $delovoy_defaults['stats'];

    // Заголовок: приоритет — родительская страница MICE; затем локальное поле; иначе дефолт.
    $reviews_heading = function_exists('get_field') ? get_field('delovoy_reviews_heading') : '';
    if ($reviews_heading === '' || $reviews_heading === null) {
        $reviews_heading = $delovoy_defaults['reviews_heading'];
    }
    if (function_exists('bsi_get_mice_page_reviews_heading')) {
        $parent_heading = bsi_get_mice_page_reviews_heading();
        if ($parent_heading !== '') {
            $reviews_heading = $parent_heading;
        }
    }

    // Отзывы: ВСЕГДА из родительской MICE-страницы. Локальные delovoy_reviews оставлены
    // в админке как резерв, но в рендере не используются.
    $parent_reviews = function_exists('bsi_get_mice_page_reviews_rows') ? bsi_get_mice_page_reviews_rows() : [];
    if ($parent_reviews !== []) {
        $reviews = $parent_reviews;
        $reviews_from_acf = true;
    } else {
        $reviews = $delovoy_defaults['reviews'];
        $reviews_from_acf = false;
    }

    $service_icon_svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 7.601L5.006 15.791C4.86496 15.9837 4.79751 16.2204 4.81585 16.4585C4.83419 16.6966 4.93711 16.9202 5.106 17.089L5.923 17.907C6.09435 18.0782 6.32199 18.1813 6.56366 18.1973C6.80532 18.2133 7.04458 18.1411 7.237 17.994L15.09 12M16.5 21.174C15.5 20.5 14.372 20 13 20C10.942 20 9.072 22.356 7 22C4.928 21.644 4.225 18.631 5.5 17.5M21 7C21 9.76142 18.7614 12 16 12C13.2386 12 11 9.76142 11 7C11 4.23858 13.2386 2 16 2C18.7614 2 21 4.23858 21 7Z" stroke="#EF0101" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>';
    ?>


    <main class="bsimice-page">
        <div class="hero-wrapper">
            <?php if (function_exists('yoast_breadcrumb')): ?>
                <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
            <?php endif; ?>
            <div class="hero-bg">
                <img src="<?php echo esc_url($hero_bg_url); ?>" alt="<?php echo esc_attr($hero_bg_alt); ?>">
            </div>
            <section class="hero">
                <div class="container">
                    <div class="hero__top">
                        <h1 class="hero__title"><?php echo esc_html($hero_title); ?></h1>
                        <p class="hero__subtitle">
                            <?php echo $hero_subtitle_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </p>

                        <div class="hero__buttons">
                            <?php if ($btn1_text !== ''): ?>
                                <a href="<?php echo esc_url($btn1_href); ?>"
                                    class="hero__btn hero__btn--primary"><?php echo esc_html($btn1_text); ?></a>
                            <?php endif; ?>
                            <?php if ($btn2_text !== ''): ?>
                                <a href="<?php echo esc_url($btn2_href); ?>"
                                    class="hero__btn hero__btn--secondary"><?php echo esc_html($btn2_text); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section id="bsimice-services" class="services">
            <div class="container">
                <h2 class="services__heading"><?php echo esc_html($services_heading); ?></h2>
                <div class="services__list">
                    <?php foreach ($services as $svc):
                        if (!is_array($svc)) {
                            continue;
                        }
                        $stitle = $svc['title'] ?? '';
                        $sdesc = $svc['description'] ?? '';
                        $icon = $svc['icon'] ?? null;
                        $icon_url = is_array($icon) && !empty($icon['url']) ? $icon['url'] : '';
                        $icon_lucide_name = isset($svc['icon_lucide']) ? (string) $svc['icon_lucide'] : '';
                        $icon_lucide_svg = '';
                        if ($icon_url === '' && $icon_lucide_name !== '' && function_exists('bsi_lucide_icon')) {
                            $icon_lucide_svg = bsi_lucide_icon($icon_lucide_name);
                        }
                        if ($stitle === '' && $sdesc === '') {
                            continue;
                        }
                        ?>
                        <div class="services__item">
                            <div class="services__item-title">
                                <div class="services__item-icon">
                                    <?php if ($icon_url !== ''): ?>
                                        <img src="<?php echo esc_url($icon_url); ?>" alt="" width="24" height="24">
                                    <?php elseif ($icon_lucide_svg !== ''): ?>
                                        <?php echo $icon_lucide_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline lucide SVG ?>
                                    <?php else: ?>
                                        <?php echo $service_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG ?>
                                    <?php endif; ?>
                                </div>
                                <?php echo esc_html($stitle); ?>
                            </div>
                            <div class="services__item-desc">
                                <?php echo bsimice_format_textarea($sdesc, $services_from_acf); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>


        <section class="mice-why-section">
            <div class="container">
                <h2 class="h2 mice-why__heading services__heading"><?php echo esc_html($why_heading); ?></h2>
                <p class="mice-section-description">
                    <?php echo $why_description_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </p>

                <div class="mice-why__items">
                    <?php foreach ($why_items as $why_row):
                        if (!is_array($why_row)) {
                            continue;
                        }
                        $wit = $why_row['title'] ?? '';
                        $wix = $why_row['text'] ?? '';
                        if ($wit === '' && $wix === '') {
                            continue;
                        }
                        ?>
                        <div class="mice-why__item">
                            <?php if ($wit !== ''): ?>
                                <h3 class="mice-why__item_title"><?php echo esc_html($wit); ?></h3>
                            <?php endif; ?>
                            <?php if ($wix !== ''): ?>
                                <p class="mice-why__item_text">
                                    <?php echo bsimice_format_textarea($wix, $why_items_from_acf); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="bsimice-proove" class="mice-proove">
            <div class="container">
                <div class="mice-proove__top">
                    <div class="mice-proove__content">
                        <h2>
                            <?php echo esc_html($prove_heading); ?>
                        </h2>
                        <div class="mice-proove__body">
                            <?php foreach ($prove_paragraphs as $row):
                                if (!is_array($row)) {
                                    continue;
                                }
                                $p = $row['text'] ?? '';
                                if ($p === '') {
                                    continue;
                                }
                                ?>
                                <p>
                                    <?php echo bsimice_format_textarea($p, $prove_paragraphs_from_acf); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mice-proove__media">
                        <img src="<?php echo esc_url($prove_image_url); ?>" alt="<?php echo esc_attr($prove_image_alt); ?>">
                    </div>
                </div>
                <div class="mice-proove__stats">
                    <?php foreach ($stats as $stat):
                        if (!is_array($stat)) {
                            continue;
                        }
                        $st = $stat['title'] ?? '';
                        $sx = $stat['text'] ?? '';
                        $sbg = $stat['bg_label'] ?? '';
                        if ($st === '' && $sx === '' && $sbg === '') {
                            continue;
                        }
                        ?>
                        <div class="mice-proove__stat">
                            <p class="mice-proove__stat-title">
                                <?php echo esc_html($st); ?>
                            </p>
                            <p class="mice-proove__stat-text">
                                <?php echo esc_html($sx); ?>
                            </p>
                            <span class="mice-proove__stat-bg">
                                <?php echo esc_html($sbg); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php get_template_part('template-parts/projects/slider'); ?>

        <?php
        set_query_var('bsimice_reviews_slider_reviews', $reviews);
        set_query_var('bsimice_reviews_slider_heading', $reviews_heading);
        set_query_var('bsimice_reviews_slider_from_acf', $reviews_from_acf);
        set_query_var('bsimice_reviews_section_id', 'mice-reviews');

        get_template_part('template-parts/mice/reviews-slider');

        get_template_part('template-parts/news/news-slider', null, ['section_id' => 'mice-news']);
        ?>

        <?php
        set_query_var('mice_consultation_cfg', [
            'section_class' => 'visa-page-consultation__section bsimice-page-consultation',
            'section_id' => 'bsimice-contact',
            'heading' => 'Бесплатная консультация',
            'description' => 'Оставьте заявку — обсудим деловой туризм и командировки, подберём формат сопровождения под ваши задачи',
        ]);
        get_template_part('template-parts/mice/consultation-form-section');
        ?>
    </main>

    <?php get_template_part('template-parts/mice/consultation-form-modal'); ?>

    <?php
endwhile;

get_footer();
