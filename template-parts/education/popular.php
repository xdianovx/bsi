<?php

// ВРЕМЕННО: Отключаем загрузку из админки, используем только тестовые данные
$use_test_data = true; // Переключить на false для использования данных из админки

$items = [];
$promo_countries = [];

if (!$use_test_data) {
    // Загрузка данных из админки
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

        if (class_exists('Collator')) {
            $collator = new Collator('ru_RU');
            usort($promo_countries, function ($a, $b) use ($collator) {
                return $collator->compare($a->post_title, $b->post_title);
            });
        } else {
            usort($promo_countries, function ($a, $b) {
                return mb_strcasecmp($a->post_title, $b->post_title);
            });
        }
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
    if (function_exists('get_field')) {
        $price_val = get_field('education_price', $education_id);

        if (is_string($price_val) && $price_val !== '') {
            $price = (string) $price_val;
        }

        $education_programs = get_field('education_programs', $education_id);
        $education_programs = is_array($education_programs) ? $education_programs : [];

        if (empty($price) && !empty($education_programs)) {
            $prices = [];
            foreach ($education_programs as $program) {
                $program_price = '';
                if (isset($program['program_price_per_week'])) {
                    $program_price = (string) $program['program_price_per_week'];
                } elseif (isset($program['price_per_week'])) {
                    $program_price = (string) $program['price_per_week'];
                }
                if ($program_price) {
                    preg_match('/[\d\s]+/', $program_price, $matches);
                    if (!empty($matches[0])) {
                        $prices[] = (int) str_replace(' ', '', $matches[0]);
                    }
                }
            }

            if (!empty($prices)) {
                $min_price_value = min($prices);
                $price = number_format($min_price_value, 0, ',', ' ') . ' ₽/неделя';
            }
        }

        if (!empty($price)) {
            $price = format_price_text($price);
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
                $program_age_min = isset($program['program_age_min']) && $program['program_age_min'] !== '' ? (int) $program['program_age_min'] : 0;
                $program_age_max = isset($program['program_age_max']) && $program['program_age_max'] !== '' ? (int) $program['program_age_max'] : 0;

                if ($program_age_min > 0) {
                    $ages_min[] = $program_age_min;
                }
                if ($program_age_max > 0) {
                    $ages_max[] = $program_age_max;
                }

                $date_from = isset($program['program_checkin_date_from']) ? (string) $program['program_checkin_date_from'] : '';
                if ($date_from) {
                    $all_dates[] = $date_from;
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
        'booking_url' => $booking_url,
        'age_min' => $age_min,
        'age_max' => $age_max,
        'nearest_date' => $nearest_date,
    ];
}

if (!empty($items)) {
    usort($items, function ($a, $b) {
        $priority_a = !empty($a['priority']) && $a['priority'] === true;
        $priority_b = !empty($b['priority']) && $b['priority'] === true;

        // Приоритетные элементы идут первыми
        if ($priority_a && !$priority_b) {
            return -1;
        }
        if (!$priority_a && $priority_b) {
            return 1;
        }

        // Если оба приоритетные или оба не приоритетные - сортируем по цене
        $price_a = 0;
        $price_b = 0;

        if (!empty($a['price'])) {
            preg_match('/[\d\s]+/', $a['price'], $matches_a);
            if (!empty($matches_a[0])) {
                $price_a = (int) str_replace(' ', '', $matches_a[0]);
            }
        }

        if (!empty($b['price'])) {
            preg_match('/[\d\s]+/', $b['price'], $matches_b);
            if (!empty($matches_b[0])) {
                $price_b = (int) str_replace(' ', '', $matches_b[0]);
            }
        }

        return $price_a <=> $price_b;
    });
}
} // Конец блока if (!$use_test_data)

if ($use_test_data || empty($items)) {
    // Тестовые данные - URL будут преобразованы в SSO flow в education/card.php
    $test_items = [
        [
            'id' => 0,
            'url' => 'https://bsigroup.ru/education/english-in-cyprus/',
            'image' => 'https://www.bsistudy.ru/upload/resize_cache/tour/3/e/0/640_410_2/3e0fd1a965b4ba16188364fd561738bf.jpg',
            'title' => 'English in Cyprus (Ларнака)',
            'flag' => 'https://bsigroup.ru/wp-content/uploads/2025/11/cy.svg',
            'country_title' => 'Кипр',
            'price' => '211 190 ₽ / 2 недели',
            'languages' => ['Английский'],
            'programs' => ['Каникулярные программы'],
            'country_id' => 0,
            'country_slug' => '',
            'show_price_from' => true,
            'booking_url' => 'https://bsigroup.ru/education/english-in-cyprus/',
            'age_min' => 12,
            'age_max' => 17,
            'nearest_date' => '28.06.2026',
        ],
        [
            'id' => 0,
            'url' => 'https://bsigroup.ru/education/queen-mary-university-gruppovoj-zaezd',
            'image' => 'https://www.bsistudy.ru/upload/tour/1/8/b/18b8ba5ab26aaaaf7217341bf8f753bc.jpg',
            'title' => 'Queen Mary University (Лондон)',
            'flag' => 'https://bsigroup.ru/wp-content/uploads/2025/11/gb.svg',
            'country_title' => 'Великобритания',
            'price' => '335 023 ₽ / 2 недели',
            'languages' => ['Английский'],
            'programs' => ['Групповой заезд'],
            'country_id' => 0,
            'country_slug' => '',
            'show_price_from' => true,
            'booking_url' => 'https://bsigroup.ru/education/queen-mary-university-gruppovoj-zaezd',
            'age_min' => 11,
            'age_max' => 17,
            'nearest_date' => '28.06.2026',
            'priority' => true,
        ],
        [
            'id' => 0,
            'url' => 'https://bsigroup.ru/education/sir-george-kembridzh/',
            'image' => 'https://www.bsistudy.ru/upload/resize_cache/tour/b/8/4/640_410_2/b84cd42b2093a94c73cfb9513321f6bf.jpg',
            'title' => 'Sir George (Кембридж)',
            'flag' => 'https://bsigroup.ru/wp-content/uploads/2025/11/gb.svg',
            'country_title' => 'Великобритания',
            'price' => '153 762 ₽ / 1 неделя',
            'languages' => ['Английский'],
            'programs' => ['Каникулярные программы'],
            'country_id' => 0,
            'country_slug' => '',
            'show_price_from' => true,
            'booking_url' => 'https://bsigroup.ru/education/sir-george-kembridzh/',
            'age_min' => 14,
            'age_max' => 17,
            'nearest_date' => '08.02.2026',
        ],
        [
            'id' => 0,
            'url' => 'https://bsigroup.ru/education/discovery-summer-kollingem',
            'image' => 'https://www.bsistudy.ru/upload/resize_cache/tour/2/c/e/640_410_2/2ce95308a6d57f51a1d43b02738c0431.JPG',
            'title' => 'Discovery Summer (Лондон)',
            'flag' => 'https://bsigroup.ru/wp-content/uploads/2025/11/gb.svg',
            'country_title' => 'Великобритания',
            'price' => '76 332 ₽ / 1 неделя',
            'languages' => ['Английский'],
            'programs' => ['Каникулярные программы'],
            'country_id' => 0,
            'country_slug' => '',
            'show_price_from' => true,
            'booking_url' => 'https://bsigroup.ru/education/discovery-summer-kollingem',
            'age_min' => 5,
            'age_max' => 17,
            'nearest_date' => '22.06.2026',
        ],

    ];

    usort($test_items, function ($a, $b) {
        $priority_a = !empty($a['priority']) && $a['priority'] === true;
        $priority_b = !empty($b['priority']) && $b['priority'] === true;

        // Приоритетные элементы идут первыми
        if ($priority_a && !$priority_b) {
            return -1;
        }
        if (!$priority_a && $priority_b) {
            return 1;
        }

        // Если оба приоритетные или оба не приоритетные - сортируем по цене
        $price_a = 0;
        $price_b = 0;

        if (!empty($a['price'])) {
            preg_match('/[\d\s]+/', $a['price'], $matches_a);
            if (!empty($matches_a[0])) {
                $price_a = (int) str_replace(' ', '', $matches_a[0]);
            }
        }

        if (!empty($b['price'])) {
            preg_match('/[\d\s]+/', $b['price'], $matches_b);
            if (!empty($matches_b[0])) {
                $price_b = (int) str_replace(' ', '', $matches_b[0]);
            }
        }

        return $price_a <=> $price_b;
    });

    $items = $test_items;
}

// Формируем список стран для фильтра из массива $items
$filter_countries = [];

if (!empty($items)) {
    $countries_map = [];
    
    foreach ($items as $item) {
        $country_title = !empty($item['country_title']) ? trim((string) $item['country_title']) : '';
        $flag_url = !empty($item['flag']) ? (string) $item['flag'] : '';
        
        if ($country_title && !isset($countries_map[$country_title])) {
            // Создаем slug из названия страны
            $country_slug = sanitize_title($country_title);
            
            $countries_map[$country_title] = [
                'title' => $country_title,
                'slug' => $country_slug,
                'flag' => $flag_url,
            ];
        }
    }
    
    $filter_countries = array_values($countries_map);
    
    // Сортируем страны по названию
    if (class_exists('Collator')) {
        $collator = new Collator('ru_RU');
        usort($filter_countries, function ($a, $b) use ($collator) {
            return $collator->compare($a['title'], $b['title']);
        });
    } else {
        usort($filter_countries, function ($a, $b) {
            return mb_strcasecmp($a['title'], $b['title']);
        });
    }
}

// Если есть реальные страны из админки, используем их, иначе используем страны из массива
$countries_for_filter = !empty($promo_countries) ? $promo_countries : $filter_countries;
?>

<?php if (!empty($items)): ?>
    <section class="popular-education__section">
        <div class="container">
            <div class="title-wrap news-slider__title-wrap">
                <div class="news-slider__title-wrap-left">
                    <h2 class="h2 news-slider__title">Популярные программы образования</h2>
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

            <div class="promo-filter popular-education-filter">
                <button class="promo-filter__btn --all active js-promo-filter-btn" data-country="">
                    Все
                </button>

                <?php if (!empty($promo_countries)): ?>
                    <?php // Реальные страны из админки ?>
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
                <?php elseif (!empty($filter_countries)): ?>
                    <?php // Страны из тестовых данных ?>
                    <?php foreach ($filter_countries as $country): ?>
                        <?php
                        $country_title = trim((string) $country['title']);
                        $country_slug = (string) $country['slug'];
                        $flag_url = !empty($country['flag']) ? (string) $country['flag'] : '';
                        ?>

                        <button class="promo-filter__btn js-promo-filter-btn" data-country="<?php echo esc_attr($country_title); ?>"
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
                <?php endif; ?>
            </div>

            <div class="popular-education__content">
                <div class="swiper popular-education-slider">
                    <div class="swiper-wrapper">
                        <?php foreach ($items as $item): ?>
                            <?php
                            // Для фильтрации используем country_title и slug
                            $filter_country = !empty($item['country_title']) ? trim((string) $item['country_title']) : '';
                            $filter_country_slug = !empty($item['country_slug']) ? (string) $item['country_slug'] : '';
                            
                            // Если slug пустой, создаем из названия
                            if (empty($filter_country_slug) && !empty($filter_country)) {
                                $filter_country_slug = sanitize_title($filter_country);
                            }
                            
                            // Для реальных данных используем country_id, для тестовых - country_title
                            $data_country = !empty($item['country_id']) && $item['country_id'] > 0 
                                ? (string) $item['country_id'] 
                                : $filter_country;
                            ?>
                            <div class="swiper-slide" data-country="<?php echo esc_attr($data_country); ?>"
                                data-country-slug="<?php echo esc_attr($filter_country_slug); ?>">
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