<?php

/**
 * Template Name: Bonus
 */
get_header();
?>

<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#EE3145" stroke="#EE3145"
    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin-icon lucide-map-pin">
    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
    <circle cx="12" cy="10" r="3" />
</svg>

<main class="bonus-page">
    <?php if (function_exists('yoast_breadcrumb')): ?>
        <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
    <?php endif; ?>

    <section>
        <div class="container">
            <?php the_title('<h1 class="h1 bonus-page__title">', '</h1>'); ?>
        </div>
    </section>

    <!-- Бегущие строки -->
    <?php
    $marquee_icon = get_field('bonus_marquee_icon');
    // Если иконка не задана, используем иконку по умолчанию
    if (!$marquee_icon) {
        $marquee_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EE3145" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin-check-icon lucide-map-pin-check"><path d="M19.43 12.935c.357-.967.57-1.955.57-2.935a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 1.202 0 32.197 32.197 0 0 0 .813-.728"/><circle cx="12" cy="10" r="3"/><path d="m16 18 2 2 4-4"/></svg>';
    }


    if (have_rows('bonus_marquee_items')): ?>
        <section class="bonus-marquee" style="--marquee-speed: 30s;">
            <div class="bonus-marquee__row bonus-marquee__row--left">
                <div class="bonus-marquee__track">
                    <div class="bonus-marquee__content">
                        <?php while (have_rows('bonus_marquee_items')):
                            the_row(); ?>
                            <?php $text = get_sub_field('text'); ?>
                            <?php if ($text): ?>
                                <span class="bonus-marquee__item"><?php echo esc_html($text); ?></span>
                            <?php endif; ?>
                            <?php if ($marquee_icon): ?>
                                <?php echo wp_kses($marquee_icon, [
                                    'svg' => ['xmlns' => [], 'width' => [], 'height' => [], 'viewbox' => [], 'viewBox' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => [], 'class' => []],
                                    'path' => ['d' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => []],
                                ]); ?>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <div class="bonus-marquee__row bonus-marquee__row--right">
                <div class="bonus-marquee__track">
                    <div class="bonus-marquee__content">
                        <?php while (have_rows('bonus_marquee_items')):
                            the_row(); ?>
                            <?php $text = get_sub_field('text'); ?>
                            <?php if ($text): ?>
                                <span class="bonus-marquee__item"><?php echo esc_html($text); ?></span>
                            <?php endif; ?>
                            <?php if ($marquee_icon): ?>
                                <?php echo wp_kses($marquee_icon, [
                                    'svg' => ['xmlns' => [], 'width' => [], 'height' => [], 'viewbox' => [], 'viewBox' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => [], 'class' => []],
                                    'path' => ['d' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => []],
                                ]); ?>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <!-- Уровни бонусов -->
    <?php
    $level_description_icon = get_field('bonus_level_description_icon');
    // Если иконка не задана, используем иконку по умолчанию
    if (!$level_description_icon) {
        $level_description_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin-icon lucide-map-pin"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>';
    }
    if (have_rows('bonus_levels')): ?>
        <section class="bonus-levels">
            <div class="container">
                <div class="bonus-levels__items">
                    <?php while (have_rows('bonus_levels')):
                        the_row(); ?>
                        <?php
                        $star_image = get_sub_field('star_image');
                        $level_name = get_sub_field('level_name');
                        $level_number = get_sub_field('level_number');
                        $level_info = get_sub_field('level_info');
                        ?>
                        <div class="bonus-levels__item">
                            <div class="bonus-level__top">
                                <div class="bonus-level__star">
                                    <div class="bonus-level__img">
                                        <?php if ($star_image): ?>
                                            <img src="<?php echo esc_url($star_image['url']); ?>"
                                                alt="<?php echo esc_attr($star_image['alt'] ?: 'Звезда'); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="bonus-level__name">
                                        <div class="bonus-level__title">
                                            <?php if ($level_name): ?>
                                                <h3><?php echo esc_html($level_name); ?></h3>
                                            <?php endif; ?>
                                            <?php if ($level_number): ?>
                                                <span><?php echo esc_html($level_number); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($level_info): ?>
                                            <div class="bonus-level__info">
                                                <?php echo esc_html($level_info); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php if (have_rows('level_descriptions')): ?>
                                <div class="bonus-level__bot">
                                    <div class="bonus-level__description-list">
                                        <?php while (have_rows('level_descriptions')):
                                            the_row(); ?>
                                            <?php
                                            $desc_title = get_sub_field('title');
                                            $desc_text = get_sub_field('text');
                                            ?>
                                            <div class="bonus-level__description-item">
                                                <div class="top">
                                                    <?php if ($level_description_icon): ?>
                                                        <?php echo wp_kses($level_description_icon, [
                                                            'svg' => ['xmlns' => [], 'width' => [], 'height' => [], 'viewbox' => [], 'viewBox' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => [], 'class' => []],
                                                            'path' => ['d' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => []],
                                                        ]); ?>
                                                    <?php endif; ?>
                                                    <?php if ($desc_title): ?>
                                                        <div class="__item_title"><?php echo esc_html($desc_title); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($desc_text): ?>
                                                    <div class="bot">
                                                        <?php echo esc_html($desc_text); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <!-- Ежегодная премия -->
    <?php
    $bonus_event_title = get_field('bonus_event_title');
    $bonus_event_text = get_field('bonus_event_text');
    ?>
    <?php if ($bonus_event_title || $bonus_event_text): ?>
        <section class="bonus-event">
            <div class="container">
                <?php if ($bonus_event_title): ?>
                    <h2 class="h2"><?php echo esc_html($bonus_event_title); ?></h2>
                <?php endif; ?>
                <?php if ($bonus_event_text): ?>
                    <p><?php echo esc_html($bonus_event_text); ?></p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    <!-- Важная информация -->
    <?php $bonus_info = get_field('bonus_info'); ?>
    <?php if ($bonus_info): ?>
        <section class="bonus-info">
            <div class="container">
                <h2 class="bonus-info__title">Важная информация:</h2>
                <div class="bonus-info__content editor-content">
                    <?php echo wp_kses_post($bonus_info); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>


<?php get_footer(); ?>