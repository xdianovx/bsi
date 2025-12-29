<?php

$popular_education_ids = get_posts([
    'post_type' => 'education',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
    'meta_query' => [
        [
            'key' => 'is_popular',
            'value' => '1',
            'compare' => '=',
        ],
    ],
]);

$country_ids = [];

if (!empty($popular_education_ids) && function_exists('get_field')) {
    foreach ($popular_education_ids as $education_id) {
        $c = get_field('education_country', $education_id);

        if ($c instanceof WP_Post) {
            $c = (int) $c->ID;
        } elseif (is_array($c)) {
            $c = (int) reset($c);
        } else {
            $c = (int) $c;
        }

        if ($c > 0) {
            $country_ids[] = $c;
        }
    }
}

$country_ids = array_values(array_unique(array_filter($country_ids)));

$promo_countries = [];

if (!empty($country_ids)) {
    $promo_countries = get_posts([
        'post_type' => 'country',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
        'post_parent' => 0,
        'post__in' => $country_ids,
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);
}

$education_posts = [];

if (!empty($popular_education_ids)) {
    $education_posts = get_posts([
        'post_type' => 'education',
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'post__in' => $popular_education_ids,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);
}

$items = [];

foreach ($education_posts as $education_post) {
    $education_id = (int) $education_post->ID;

    $country_id = 0;
    if (function_exists('get_field')) {
        $country_val = get_field('education_country', $education_id);
        if ($country_val instanceof WP_Post) {
            $country_id = (int) $country_val->ID;
        } elseif (is_array($country_val)) {
            $country_id = (int) reset($country_val);
        } else {
            $country_id = (int) $country_val;
        }
    }

    $country_title = $country_id ? (string) get_the_title($country_id) : '';
    $country_slug = $country_id ? (string) get_post_field('post_name', $country_id) : '';

    $flag_url = '';
    if ($country_id && function_exists('get_field')) {
        $flag_field = get_field('flag', $country_id);
        if ($flag_field) {
            if (is_array($flag_field) && !empty($flag_field['url'])) {
                $flag_url = (string) $flag_field['url'];
            } elseif (is_string($flag_field)) {
                $flag_url = (string) $flag_field;
            }
        }
    }

    $image_url = '';
    $thumb = get_the_post_thumbnail_url($education_id, 'large');
    if ($thumb) {
        $image_url = (string) $thumb;
    } else {
        $gallery = function_exists('get_field') ? get_field('education_gallery', $education_id) : [];
        $gallery = is_array($gallery) ? $gallery : [];
        if (!empty($gallery[0])) {
            if (is_array($gallery[0]) && !empty($gallery[0]['ID'])) {
                $first_id = (int) $gallery[0]['ID'];
            } elseif (is_numeric($gallery[0])) {
                $first_id = (int) $gallery[0];
            }
            if ($first_id) {
                $img = wp_get_attachment_image_url($first_id, 'large');
                if ($img) {
                    $image_url = (string) $img;
                }
            }
        }
    }

    $price = '';
    $show_from = true;
    if (function_exists('get_field')) {
        $price_val = get_field('education_price', $education_id);
        $show_from_field = get_field('show_price_from', $education_id);
        $show_from = $show_from_field !== false;

        if (is_string($price_val) && $price_val !== '') {
            $price = (string) $price_val;
        }

        $education_programs = get_field('education_programs', $education_id);
        $education_programs = is_array($education_programs) ? $education_programs : [];

        if (empty($price) && !empty($education_programs)) {
            $prices = [];
            foreach ($education_programs as $program) {
                $program_price = isset($program['price_per_week']) ? (string) $program['price_per_week'] : '';
                if ($program_price) {
                    preg_match('/[\d\s]+/', $program_price, $matches);
                    if (!empty($matches[0])) {
                        $prices[] = (int) str_replace(' ', '', $matches[0]);
                    }
                }
            }

            if (!empty($prices)) {
                $min_price_value = min($prices);
                $price = number_format($min_price_value, 0, ',', ' ') . ' ₽';
            }
        }
    }

    $languages = wp_get_post_terms($education_id, 'education_language', ['fields' => 'names']);
    $languages = is_wp_error($languages) ? [] : $languages;

    $programs = wp_get_post_terms($education_id, 'education_program', ['fields' => 'names']);
    $programs = is_wp_error($programs) ? [] : $programs;

    $booking_url = '';
    if (function_exists('get_field')) {
        $booking_url_val = get_field('education_booking_url', $education_id);
        if ($booking_url_val) {
            $booking_url = trim((string) $booking_url_val);
        }
    }

    $age_min = 0;
    $age_max = 0;
    $nearest_date = '';

    if (function_exists('get_field')) {
        $education_programs = get_field('education_programs', $education_id);
        $education_programs = is_array($education_programs) ? $education_programs : [];

        if (!empty($education_programs)) {
            $ages_min = [];
            $ages_max = [];
            $all_dates = [];

            foreach ($education_programs as $program) {
                $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
                $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;

                if ($program_age_min > 0) {
                    $ages_min[] = $program_age_min;
                }
                if ($program_age_max > 0) {
                    $ages_max[] = $program_age_max;
                }

                $program_dates = isset($program['program_checkin_dates']) ? $program['program_checkin_dates'] : [];
                $program_dates = is_array($program_dates) ? $program_dates : [];

                foreach ($program_dates as $date_item) {
                    $checkin_date = is_array($date_item) ? ($date_item['checkin_date'] ?? '') : '';
                    if ($checkin_date) {
                        $all_dates[] = $checkin_date;
                    }
                }
            }

            if (!empty($ages_min)) {
                $age_min = min($ages_min);
            }
            if (!empty($ages_max)) {
                $age_max = max($ages_max);
            }

            if (!empty($all_dates)) {
                $today = date('Y-m-d');
                $future_dates = array_filter($all_dates, function ($date) use ($today) {
                    return $date >= $today;
                });

                if (!empty($future_dates)) {
                    sort($future_dates);
                    $nearest_date = $future_dates[0];
                } elseif (!empty($all_dates)) {
                    sort($all_dates);
                    $nearest_date = $all_dates[0];
                }
            }
        }
    }

    $items[] = [
        'id' => $education_id,
        'url' => get_permalink($education_id),
        'image' => $image_url,
        'title' => get_the_title($education_id),
        'flag' => $flag_url,
        'country_title' => $country_title,
        'price' => $price,
        'languages' => $languages,
        'programs' => $programs,
        'country_id' => $country_id,
        'country_slug' => $country_slug,
        'show_price_from' => $show_from,
        'booking_url' => $booking_url,
        'age_min' => $age_min,
        'age_max' => $age_max,
        'nearest_date' => $nearest_date,
    ];
}

if (empty($items)) {
    $test_items = [
        [
            'id' => 0,
            'url' => 'https://www.bsistudy.ru/scountry/gbr/school/36329/center/37188/?id=201575237',
            'image' => 'https://www.bsistudy.ru/upload/tour/1/8/b/18b8ba5ab26aaaaf7217341bf8f753bc.jpg',
            'title' => 'Queen Mary University',
            'flag' => 'http://webscape.beget.tech/bsi/wp-content/uploads/2025/11/gb.svg',
            'country_title' => 'Великобритания',
            'price' => '335 023 ₽ / 1 неделя',
            'languages' => ['Английский'],
            'programs' => ['Каникулярные программы'],
            'country_id' => 0,
            'country_slug' => '',
            'show_price_from' => true,
            'booking_url' => 'https://www.bsistudy.ru/scountry/gbr/school/36329/center/37188/?id=201575237',
            'age_min' => 11,
            'age_max' => 17,
            'nearest_date' => '28.06.2026',
        ],
        [
            'id' => 0,
            'url' => 'https://www.bsistudy.ru/scountry/gbr/school/9991/center/13372/?id=201558434&LANGUAGES=1&DURATION=14,15',
            'image' => 'https://www.bsistudy.ru/upload/resize_cache/tour/b/8/4/640_410_2/b84cd42b2093a94c73cfb9513321f6bf.jpg',
            'title' => 'Sir George (Кембридж)',
            'flag' => 'http://webscape.beget.tech/bsi/wp-content/uploads/2025/11/gb.svg',
            'country_title' => 'Великобритания',
            'price' => '225 528 ₽',
            'languages' => ['Английский'],
            'programs' => ['Каникулярные программы'],
            'country_id' => 0,
            'country_slug' => '',
            'show_price_from' => true,
            'booking_url' => 'https://www.bsistudy.ru/scountry/gbr/school/9991/center/13372/?id=201558434&LANGUAGES=1&DURATION=14,15',
            'age_min' => 14,
            'age_max' => 17,
            'nearest_date' => '04.01.2026',
        ],
        [
            'id' => 0,
            'url' => 'https://www.bsistudy.ru/scountry/gbr/school/9981/center/11373/?id=201575244&LANGUAGES=1&DURATION=14,15',
            'image' => 'https://www.bsistudy.ru/upload/resize_cache/tour/2/c/e/640_410_2/2ce95308a6d57f51a1d43b02738c0431.JPG',
            'title' => 'Discovery Summer Коллингем',
            'flag' => 'http://webscape.beget.tech/bsi/wp-content/uploads/2025/11/gb.svg',
            'country_title' => 'Великобритания',
            'price' => '129 651 ₽',
            'languages' => ['Английский'],
            'programs' => ['Каникулярные программы'],
            'country_id' => 0,
            'country_slug' => '',
            'show_price_from' => true,
            'booking_url' => 'https://www.bsistudy.ru/scountry/gbr/school/9981/center/11373/?id=201575244&LANGUAGES=1&DURATION=14,15',
            'age_min' => 5,
            'age_max' => 17,
            'nearest_date' => '22.06.2026',
        ],
        [
            'id' => 0,
            'url' => 'https://www.bsistudy.ru/scountry/cyp/school/36469/center/38452/?id=201534186&LANGUAGES=1&DURATION=15',
            'image' => 'https://www.bsistudy.ru/upload/resize_cache/tour/3/e/0/640_410_2/3e0fd1a965b4ba16188364fd561738bf.jpg',
            'title' => 'English in Cyprus (Ларнака)',
            'flag' => 'http://webscape.beget.tech/bsi/wp-content/uploads/2025/11/cy.svg',
            'country_title' => 'Кипр',
            'price' => '209 155 ₽',
            'languages' => ['Английский'],
            'programs' => ['Каникулярные программы'],
            'country_id' => 0,
            'country_slug' => '',
            'show_price_from' => true,
            'booking_url' => 'https://www.bsistudy.ru/scountry/cyp/school/36469/center/38452/?id=201534186&LANGUAGES=1&DURATION=15',
            'age_min' => 12,
            'age_max' => 17,
            'nearest_date' => '28.06.2026',
        ],
    ];

    $items = $test_items;
}
?>

<?php if (!empty($items)): ?>
    <section class="popular-education__section">
        <div class="container">
            <div class="title-wrap news-slider__title-wrap">
                <div class="news-slider__title-wrap-left">
                    <h2 class="h2 news-slider__title">Популярные программы обучения</h2>
                    <div class="slider-arrow-wrap news-slider__arrows-wrap">
                        <div class="slider-arrow slider-arrow-prev popular-education-arrow-prev" tabindex="-1" role="button"
                            aria-label="Previous slide" aria-controls="swiper-wrapper-popular-education"
                            aria-disabled="true">
                        </div>
                        <div class="slider-arrow slider-arrow-next popular-education-arrow-next" tabindex="0" role="button"
                            aria-label="Next slide" aria-controls="swiper-wrapper-popular-education" aria-disabled="false">
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($promo_countries)): ?>
                <div class="promo-filter popular-education-filter">
                    <button class="promo-filter__btn --all active js-promo-filter-btn" data-country="">
                        Все
                    </button>

                    <?php foreach ($promo_countries as $country): ?>
                        <?php
                        $country_id = (int) $country->ID;
                        $country_title = (string) get_the_title($country_id);
                        $country_slug = (string) $country->post_name;

                        $flag_field = function_exists('get_field') ? get_field('flag', $country_id) : '';
                        $flag_url = '';

                        if ($flag_field) {
                            if (is_array($flag_field) && !empty($flag_field['url'])) {
                                $flag_url = (string) $flag_field['url'];
                            } elseif (is_string($flag_field)) {
                                $flag_url = (string) $flag_field;
                            }
                        }
                        ?>

                        <button class="promo-filter__btn js-promo-filter-btn" data-country="<?php echo esc_attr($country_id); ?>"
                            data-country-slug="<?php echo esc_attr($country_slug); ?>">
                            <?php if ($flag_url): ?>
                                <span class="promo-filter__flag-wrap">
                                    <img src="<?php echo esc_url($flag_url); ?>" alt="<?php echo esc_attr($country_title); ?>"
                                        class="promo-filter__flag">
                                </span>
                            <?php endif; ?>

                            <span class="promo-filter__title"><?php echo esc_html($country_title); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="popular-education__content">
                <div class="swiper popular-education-slider">
                    <div class="swiper-wrapper">
                        <?php foreach ($items as $item): ?>
                            <div class="swiper-slide" data-country="<?php echo esc_attr($item['country_id']); ?>"
                                data-country-slug="<?php echo esc_attr($item['country_slug']); ?>">
                                <?php
                                set_query_var('education', $item);
                                get_template_part('template-parts/education/card');
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </section>
<?php endif; ?>