<?php

/**
 * Template Name: Bonus
 */
get_header();
?>



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
        $marquee_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M7.94 8.945H16.56M7.94 8.945H3M7.94 8.945L11.59 18.996M7.94 8.945L10.34 5.012L10.89 4M16.56 8.945H21.5M16.56 8.945L12.91 18.996M16.56 8.945L14.16 5.012L13.594 4M3 8.945C3 9.365 3.079 9.785 3.236 10.181C3.448 10.716 3.878 11.209 4.736 12.194L8.203 16.17C9.6 17.772 10.298 18.574 11.126 18.868C11.2787 18.922 11.4333 18.9647 11.59 18.996M3 8.945C3 8.525 3.079 8.105 3.236 7.708C3.448 7.173 3.878 6.681 4.736 5.695C5.203 5.161 5.436 4.894 5.706 4.687C6.10742 4.38107 6.5726 4.1695 7.067 4.068C7.401 4 7.755 4 8.464 4H10.89M11.59 18.996C12.0258 19.0822 12.4742 19.0822 12.91 18.996M10.89 4H13.594M21.5 8.945C21.5 9.365 21.421 9.785 21.264 10.181C21.052 10.716 20.622 11.209 19.764 12.194L16.297 16.17C14.9 17.772 14.202 18.574 13.374 18.868C13.2226 18.9216 13.0675 18.9643 12.91 18.996M21.5 8.945C21.5 8.525 21.421 8.105 21.264 7.708C21.052 7.173 20.622 6.681 19.764 5.695C19.297 5.161 19.064 4.894 18.794 4.687C18.3926 4.38107 17.9274 4.1695 17.433 4.068C17.099 4 16.745 4 16.036 4H13.594" stroke="#EE3145" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>';
    }
    if (have_rows('bonus_marquee_items')): ?>
    <section class="bonus-marquee" style="--marquee-speed: 30s;">
        <div class="bonus-marquee__row bonus-marquee__row--left">
            <div class="bonus-marquee__track">
                <div class="bonus-marquee__content">
                    <?php while (have_rows('bonus_marquee_items')): the_row(); ?>
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
                    <?php while (have_rows('bonus_marquee_items')): the_row(); ?>
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
        $level_description_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M7.94 8.945H16.56M7.94 8.945H3M7.94 8.945L11.59 18.996M7.94 8.945L10.34 5.012L10.89 4M16.56 8.945H21.5M16.56 8.945L12.91 18.996M16.56 8.945L14.16 5.012L13.594 4M3 8.945C3 9.365 3.079 9.785 3.236 10.181C3.448 10.716 3.878 11.209 4.736 12.194L8.203 16.17C9.6 17.772 10.298 18.574 11.126 18.868C11.2787 18.922 11.4333 18.9647 11.59 18.996M3 8.945C3 8.525 3.079 8.105 3.236 7.708C3.448 7.173 3.878 6.681 4.736 5.695C5.203 5.161 5.436 4.894 5.706 4.687C6.10742 4.38107 6.5726 4.1695 7.067 4.068C7.401 4 7.755 4 8.464 4H10.89M11.59 18.996C12.0258 19.0822 12.4742 19.0822 12.91 18.996M10.89 4H13.594M21.5 8.945C21.5 9.365 21.421 9.785 21.264 10.181C21.052 10.716 20.622 11.209 19.764 12.194L16.297 16.17C14.9 17.772 14.202 18.574 13.374 18.868C13.2226 18.9216 13.0675 18.9643 12.91 18.996M21.5 8.945C21.5 8.525 21.421 8.105 21.264 7.708C21.052 7.173 20.622 6.681 19.764 5.695C19.297 5.161 19.064 4.894 18.794 4.687C18.3926 4.38107 17.9274 4.1695 17.433 4.068C17.099 4 16.745 4 16.036 4H13.594" stroke="#EE3145" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>';
    }
    if (have_rows('bonus_levels')): ?>
    <section class="bonus-levels">
        <div class="container">
            <div class="bonus-levels__items">
                <?php while (have_rows('bonus_levels')): the_row(); ?>
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
                                    <img src="<?php echo esc_url($star_image['url']); ?>" alt="<?php echo esc_attr($star_image['alt'] ?: 'Звезда'); ?>">
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
                            <?php while (have_rows('level_descriptions')): the_row(); ?>
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
    <section class="bonus-event">
        <div class="container">
            <h2>
                Ежегодная премия BSI Group
            </h2>
            <p>
                Турагентства — лидеры продаж по итогам 2026 года приглашаются на декабрьское закрытое мероприятие — премию «Вселенная BSI Group» в Москве, где им вручаются именные дипломы, авторские звездные статуэтки и индивидуальные ценные подарки
            </p>
        </div>
    </section>
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