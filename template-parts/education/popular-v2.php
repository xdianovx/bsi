<?php

// Получаем ID всех популярных постов для формирования списка стран
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

// Данные будут загружены через AJAX
?>

<?php if (!empty($promo_countries)): ?>
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

            <div class="popular-education__content">
                <div class="swiper popular-education-slider">
                    <div class="swiper-wrapper">
                        <!-- Контент загружается через AJAX -->
                    </div>
                </div>
            </div>

        </div>
    </section>
<?php endif; ?>
