<?php
/**
 * Template Name: MICE — лендинг (BSI MICE)
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

$bsimice_defaults = [
    'hero_title' => 'организация MICE мероприятий',
    'hero_subtitle' => 'Более 35 лет организуем конференции, инсентив-туры, тимбилдинг и деловые мероприятия для корпоративных клиентов по России и за рубежом.',
    'hero_btn_primary_text' => 'Обсудить',
    'hero_btn_secondary_text' => 'Посмотреть проекты',
    'plates' => [
        ['text' => 'Единая онлайн-система управления поездками, персональный менеджер и полная документальная отчётность — всё, чтобы деловые поездки не отвлекали вас от бизнеса.'],
        ['text' => 'BSI Group — надёжный партнёр для корпоративного travel-менеджмента. Мы работаем с компаниями любого масштаба: от небольшого бизнеса до крупных корпораций.'],
    ],
    'about_heading' => 'Что такое MICE?',
    'about_subtitle' => 'MICE — международная аббревиатура для индустрии корпоративных мероприятий. Расшифровывается как Meetings (встречи), Incentives (инсентив-туры), Conferences (конференции) и Events (мероприятия).',
    'about_items' => [
        ['letter' => 'M', 'title_rest' => 'eetings', 'description' => 'Деловые встречи, переговоры, рабочие сессии. Организуем переговорные комнаты, технику и кейтеринг.'],
        ['letter' => 'I', 'title_rest' => 'ncentives', 'description' => 'Мотивационные туры и программы вознаграждения для сотрудников и партнёров. Россия и зарубежные направления.'],
        ['letter' => 'C', 'title_rest' => 'onferences', 'description' => 'Конференции, форумы, съезды. Подбор площадок, техническое обеспечение, регистрация участников.'],
        ['letter' => 'E', 'title_rest' => 'vents', 'description' => 'Корпоративные праздники, тимбилдинг, событийный маркетинг, презентации и церемонии награждения.'],
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
    'services_heading' => 'наши услуги',
    'services' => array_fill(0, 6, [
        'title' => 'Конференции и форумы',
        'description' => 'Организация под ключ: площадка, техника, регистрация, программа. До нескольких тысяч участников.',
    ]),
    'guarantees' => [
        ['title' => 'Проверенные поставщики', 'text' => 'Единая онлайн-система управления поездками, персональный менеджер и полная документальная отчётность — всё, чтобы деловые поездки не отвлекали вас от бизнеса.'],
        ['title' => 'Полное сопровождение', 'text' => 'Единая онлайн-система управления поездками, персональный менеджер и полная документальная отчётность — всё, чтобы деловые поездки не отвлекали вас от бизнеса.'],
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
        $hero_title = $bsimice_defaults['hero_title'];
    }

    $hero_subtitle_acf = function_exists('get_field') ? get_field('bsimice_hero_subtitle') : null;
    $hero_subtitle_from_acf = $hero_subtitle_acf !== null && $hero_subtitle_acf !== '';
    $hero_subtitle = $hero_subtitle_from_acf ? $hero_subtitle_acf : $bsimice_defaults['hero_subtitle'];
    $hero_subtitle_html = bsimice_format_textarea($hero_subtitle, $hero_subtitle_from_acf);

    $btn1_text = $bsimice_defaults['hero_btn_primary_text'];
    $btn1_href = bsimice_hero_link('', 'bsimice-contact');
    $btn2_text = $bsimice_defaults['hero_btn_secondary_text'];
    $btn2_href = bsimice_hero_link('', 'projects');

    $plates_acf = function_exists('get_field') ? get_field('bsimice_plates') : null;
    $plates_from_acf = !empty($plates_acf) && is_array($plates_acf);
    $plates = $plates_from_acf ? $plates_acf : $bsimice_defaults['plates'];

    $about_heading = function_exists('get_field') ? get_field('bsimice_about_heading') : '';
    if ($about_heading === '' || $about_heading === null) {
        $about_heading = $bsimice_defaults['about_heading'];
    }
    $about_subtitle_acf = function_exists('get_field') ? get_field('bsimice_about_subtitle') : null;
    $about_subtitle_from_acf = $about_subtitle_acf !== null && $about_subtitle_acf !== '';
    $about_subtitle = $about_subtitle_from_acf ? $about_subtitle_acf : $bsimice_defaults['about_subtitle'];
    $about_subtitle_html = bsimice_format_textarea($about_subtitle, $about_subtitle_from_acf);

    $about_items_acf = function_exists('get_field') ? get_field('bsimice_about_items') : null;
    $about_items_from_acf = !empty($about_items_acf) && is_array($about_items_acf);
    $about_items = $about_items_from_acf ? $about_items_acf : $bsimice_defaults['about_items'];

    $prove_heading = function_exists('get_field') ? get_field('bsimice_prove_heading') : '';
    if ($prove_heading === '' || $prove_heading === null) {
        $prove_heading = $bsimice_defaults['prove_heading'];
    }
    $prove_paragraphs_acf = function_exists('get_field') ? get_field('bsimice_prove_paragraphs') : null;
    $prove_paragraphs_from_acf = !empty($prove_paragraphs_acf) && is_array($prove_paragraphs_acf);
    $prove_paragraphs = $prove_paragraphs_from_acf ? $prove_paragraphs_acf : $bsimice_defaults['prove_paragraphs'];
    $prove_image = function_exists('get_field') ? get_field('bsimice_prove_image') : null;
    $prove_image_url = is_array($prove_image) && !empty($prove_image['url'])
        ? $prove_image['url']
        : get_template_directory_uri() . '/img/mice/5.png';
    $prove_image_alt = is_array($prove_image) && !empty($prove_image['alt']) ? (string) $prove_image['alt'] : get_the_title();

    $stats_acf = function_exists('get_field') ? get_field('bsimice_stats') : null;
    $stats = !empty($stats_acf) && is_array($stats_acf) ? $stats_acf : $bsimice_defaults['stats'];

    $services_heading = function_exists('get_field') ? get_field('bsimice_services_heading') : '';
    if ($services_heading === '' || $services_heading === null) {
        $services_heading = $bsimice_defaults['services_heading'];
    }
    $services_acf = function_exists('get_field') ? get_field('bsimice_services') : null;
    $services_from_acf = !empty($services_acf) && is_array($services_acf);
    $services = $services_from_acf ? $services_acf : $bsimice_defaults['services'];

    $guarantees_acf = function_exists('get_field') ? get_field('bsimice_guarantees') : null;
    $guarantees_from_acf = !empty($guarantees_acf) && is_array($guarantees_acf);
    $guarantees = $guarantees_from_acf ? $guarantees_acf : $bsimice_defaults['guarantees'];

    // Заголовок: приоритет — родительская страница MICE; затем локальное поле; иначе дефолт.
    $reviews_heading = function_exists('get_field') ? get_field('bsimice_reviews_heading') : '';
    if ($reviews_heading === '' || $reviews_heading === null) {
        $reviews_heading = $bsimice_defaults['reviews_heading'];
    }
    if (function_exists('bsi_get_mice_page_reviews_heading')) {
        $parent_heading = bsi_get_mice_page_reviews_heading();
        if ($parent_heading !== '') {
            $reviews_heading = $parent_heading;
        }
    }

    // Отзывы: ВСЕГДА из родительской MICE-страницы. Локальные bsimice_reviews оставлены
    // в админке как резерв, но в рендере не используются.
    $parent_reviews = function_exists('bsi_get_mice_page_reviews_rows') ? bsi_get_mice_page_reviews_rows() : [];
    if ($parent_reviews !== []) {
        $reviews = $parent_reviews;
        $reviews_from_acf = true;
    } else {
        $reviews = $bsimice_defaults['reviews'];
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

        <section id="bsimice-advantages">
            <div class="container">
                <div class="plates">
                    <?php foreach ($plates as $plate):
                        $ptext = is_array($plate) ? ($plate['text'] ?? '') : '';
                        if ($ptext === '') {
                            continue;
                        }
                        ?>
                        <div class="plate">
                            <span
                                class="plate__text"><?php echo bsimice_format_textarea($ptext, $plates_from_acf); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="mice-about">
            <div class="container">
                <h2 class="mice-about__heading"><?php echo esc_html($about_heading); ?></h2>
                <p class="mice-about__subtitle">
                    <?php echo $about_subtitle_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </p>

                <div class="mice-about__items">
                    <?php foreach ($about_items as $item):
                        if (!is_array($item)) {
                            continue;
                        }
                        $letter = $item['letter'] ?? '';
                        $title_rest = $item['title_rest'] ?? '';
                        $desc = $item['description'] ?? '';
                        if ($letter === '' && $title_rest === '' && $desc === '') {
                            continue;
                        }
                        ?>
                        <div class="mice-about__item">
                            <p class="mice-about__item-title">
                                <span><?php echo esc_html($letter); ?></span><?php echo esc_html($title_rest); ?>
                            </p>
                            <span
                                class="mice-about__item-desc"><?php echo bsimice_format_textarea($desc, $about_items_from_acf); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="bsimice-proove" class="mice-proove">
            <div class="container">
                <div class="mice-proove__top">
                    <div class="mice-proove__content">
                        <h2><?php echo esc_html($prove_heading); ?></h2>
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
                                <p><?php echo bsimice_format_textarea($p, $prove_paragraphs_from_acf); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
                            <p class="mice-proove__stat-title"><?php echo esc_html($st); ?></p>
                            <p class="mice-proove__stat-text"><?php echo esc_html($sx); ?></p>
                            <span class="mice-proove__stat-bg"><?php echo esc_html($sbg); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

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
                <div class="services__guarantees">
                    <?php foreach ($guarantees as $g):
                        if (!is_array($g)) {
                            continue;
                        }
                        $gt = $g['title'] ?? '';
                        $gx = $g['text'] ?? '';
                        if ($gt === '' && $gx === '') {
                            continue;
                        }
                        ?>
                        <div class="services__guarantee">
                            <div class="services__guarantee-title"><?php echo esc_html($gt); ?></div>
                            <div class="services__guarantee-box">
                                <?php echo bsimice_format_textarea($gx, $guarantees_from_acf); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
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
        ?>

        <?php get_template_part('template-parts/news/news-slider', null, [
            'section_id' => 'mice-news',
            'extra_class' => 'mice-news-section',
        ]); ?>

        <?php
        set_query_var('mice_consultation_cfg', [
            'section_class' => 'visa-page-consultation__section bsimice-page-consultation',
            'section_id' => 'bsimice-contact',
            'heading' => 'Бесплатная консультация',
            'description' => 'Оставьте заявку — обсудим формат MICE-мероприятия и подберём решение под ваши задачи',
        ]);
        get_template_part('template-parts/mice/consultation-form-section');
        ?>
    </main>

    <?php get_template_part('template-parts/mice/consultation-form-modal'); ?>

    <?php
endwhile;

get_footer();
