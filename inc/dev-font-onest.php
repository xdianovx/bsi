<?php

/**
 * ВРЕМЕННО. Примерка шрифта Onest вместо Inter.
 *
 * ── ПЕРЕКЛЮЧАТЕЛЬ ──────────────────────────────────────────
 * Одна строка ниже, BSI_PREVIEW_FONT:
 *   'onest' — сайт набран Onest
 *   'inter' — всё как было, файл ничего не делает
 * На один запрос можно переопределить параметром ?font=onest / ?font=inter —
 * удобно сравнивать одну и ту же страницу.
 * ───────────────────────────────────────────────────────────
 *
 * Файлы Onest лежат локально в fonts/onest (4 подреза, вариативные,
 * вес 200-900). CDN не используется: Google Fonts может быть недоступен,
 * да и лишний RTT в первой отрисовке ни к чему.
 *
 * Как устроено: семейство «Inter» переобъявляется через @font-face на файлы
 * Onest, плюс поверх идёт тотальный перебой font-family. Скомпилированный
 * CSS темы не трогается вообще, поэтому откат — правка одной строки.
 *
 * Иконочные шрифты (swiper-icons, fancybox) объявлены на псевдоэлементах,
 * селектор «*» их не задевает — стрелки слайдеров работают.
 *
 * font-display:block, а не swap: со swap первый кадр рисуется системным
 * шрифтом и текст потом заметно скачет. Block придерживает отрисовку до
 * загрузки, а файлы локальные и мелкие, поэтому пауза незаметна.
 * Все четыре подреза уходят в preload, чтобы сократить её до минимума.
 *
 * Совсем убрать примерку: закомментировать require в functions.php
 * и удалить этот файл вместе с папкой fonts/onest.
 *
 * @package BSI
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BSI_PREVIEW_FONT')) {
    define('BSI_PREVIEW_FONT', 'onest'); // ← 'onest' или 'inter'
}

/**
 * Включена ли примерка на текущем запросе.
 */
function bsi_preview_font_enabled(): bool
{
    $param = isset($_GET['font']) ? strtolower(sanitize_key(wp_unslash($_GET['font']))) : '';

    if ($param === 'inter') {
        return false;
    }

    if ($param === 'onest') {
        return true;
    }

    return BSI_PREVIEW_FONT === 'onest';
}

/**
 * Подрезы Onest: имя файла в fonts/onest + unicode-range из Google Fonts.
 */
function bsi_preview_font_subsets(): array
{
    return [
        'cyrillic-ext' => [
            'file'  => 'Onest-cyrillic-ext.woff2',
            'range' => 'U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F',
        ],
        'cyrillic' => [
            'file'  => 'Onest-cyrillic.woff2',
            'range' => 'U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116',
        ],
        'latin-ext' => [
            'file'  => 'Onest-latin-ext.woff2',
            'range' => 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF',
        ],
        'latin' => [
            'file'  => 'Onest-latin.woff2',
            'range' => 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD',
        ],
    ];
}

/**
 * Пока примеряем Onest, preload локального Inter только тратит трафик.
 */
add_action('init', function (): void {
    if (bsi_preview_font_enabled()) {
        remove_action('wp_head', 'bsi_preload_fonts', 1);
    }
}, 20);

/**
 * Preload — приоритет 1, до всего остального в <head>. Чем раньше начнётся
 * загрузка, тем короче пауза, которую держит font-display:block.
 */
add_action('wp_head', function (): void {
    if (!bsi_preview_font_enabled()) {
        return;
    }

    $uri = get_template_directory_uri() . '/fonts/onest/';

    echo "<!-- ВРЕМЕННО: примерка Onest, inc/dev-font-onest.php -->\n";

    // Все четыре подреза: суммарно 64 КБ с того же домена — дешевле,
    // чем задержка отрисовки на догрузке недостающего подреза.
    foreach (bsi_preview_font_subsets() as $subset) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous">' . "\n",
            esc_url($uri . $subset['file'])
        );
    }
}, 1);

/**
 * Приоритет 20 — после wp_print_styles() (8), чтобы правило перебило
 * @font-face из main.min.css.
 */
add_action('wp_head', function (): void {
    if (!bsi_preview_font_enabled()) {
        return;
    }

    $uri     = get_template_directory_uri() . '/fonts/onest/';
    $subsets = bsi_preview_font_subsets();
    $css     = '';

    // Семейство «Inter» переопределяем, чтобы подхватилась вся тема.
    // Второе имя, «Onest», нужно для перебоя ниже и ручных проверок.
    foreach (['Inter', 'Onest'] as $family) {
        foreach ($subsets as $subset) {
            $css .= sprintf(
                '@font-face{font-family:"%s";font-style:normal;font-weight:200 900;font-display:block;src:url(%s) format("woff2");unicode-range:%s}',
                $family,
                esc_url($uri . $subset['file']),
                $subset['range']
            );
        }
    }

    // Селектор «*» намеренно тотальный: задача примерки — не оставить
    // ни одного участка со старым шрифтом, включая инлайновые стили.
    $css .= '*{font-family:"Onest",sans-serif !important}';

    echo '<style id="bsi-preview-font">' . $css . '</style>' . "\n";
}, 20);
