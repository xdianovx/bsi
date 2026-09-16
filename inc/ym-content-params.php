<?php

/**
 * Параметры визита для Яндекс.Метрики на карточках контента.
 *
 * Цели формы показывают, на что оставили заявку. Чтобы видеть, что просто
 * смотрели, — какие туры, экскурсии, места и отели открывают, — на
 * single-страницах на <body> вешаются data-атрибуты, а `js/modules/ym-content-params.js`
 * отправляет их как параметры визита. В отчёте «Параметры визита» получается
 * дерево content → тип → страна → название, рядом считается конверсия в цель.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Тип записи → подпись в отчёте Метрики.
 *
 * @return array<string, string>
 */
function bsi_ym_content_types(): array
{
  return [
    'tour' => 'Туры',
    'excursion' => 'Экскурсии',
    'sight' => 'Достопримечательности',
    'hotel' => 'Отели',
    'education' => 'Образование',
    'event' => 'События',
    'country' => 'Страны',
  ];
}

/**
 * Страна записи — через существующие хелперы сущности.
 */
function bsi_ym_content_country(int $post_id, string $post_type): string
{
  $country_id = 0;

  if ($post_type === 'country') {
    $country_id = $post_id;
  } elseif ($post_type === 'sight' && function_exists('bsi_get_sight_country_id')) {
    $country_id = bsi_get_sight_country_id($post_id);
  } elseif ($post_type === 'excursion' && function_exists('bsi_get_excursion_country_id')) {
    $country_id = bsi_get_excursion_country_id($post_id);
  } elseif ($post_type === 'tour' && function_exists('bsi_get_tour_country_ids')) {
    $ids = bsi_get_tour_country_ids($post_id);
    $country_id = $ids ? (int) reset($ids) : 0;
  } elseif (function_exists('get_field')) {
    /* Остальные типы: поле вида {type}_country, если оно есть. */
    $value = get_field($post_type . '_country', $post_id);
    if ($value instanceof WP_Post) {
      $country_id = (int) $value->ID;
    } elseif (is_array($value) && ($first = reset($value)) instanceof WP_Post) {
      $country_id = (int) $first->ID;
    } elseif (is_numeric($value)) {
      $country_id = (int) $value;
    }
  }

  return $country_id > 0 ? (string) get_the_title($country_id) : '';
}

/**
 * Печать data-атрибутов внутри тега <body>. Вызывается из header.php.
 */
function bsi_ym_content_params_attrs(): void
{
  if (!is_singular()) {
    return;
  }

  $post_id = get_queried_object_id();
  $post_type = (string) get_post_type($post_id);
  $types = bsi_ym_content_types();

  if ($post_id <= 0 || !isset($types[$post_type])) {
    return;
  }

  $title = trim(wp_strip_all_tags((string) get_the_title($post_id)));
  if ($title === '') {
    return;
  }

  printf(
    ' data-ym-type="%s" data-ym-title="%s"',
    esc_attr($types[$post_type]),
    esc_attr($title)
  );

  $country = bsi_ym_content_country($post_id, $post_type);
  if ($country !== '') {
    printf(' data-ym-country="%s"', esc_attr($country));
  }
}
