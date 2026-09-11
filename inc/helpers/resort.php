<?php

/**
 * Контекст курорта — страница /country/{country}/{region}/{resort}/.
 *
 * Курорт связан со страной цепочкой мет: терм `resort` → `resort_region`
 * → терм `region` → `region_country` → запись CPT `country`. Цепочка нужна
 * почти в каждом блоке страницы, поэтому собрана в одном месте и кешируется
 * в статике на время запроса.
 */

if (!function_exists('bsi_resort_context')) {
  /**
   * @return array{
   *   term: WP_Term|null,
   *   region: WP_Term|null,
   *   country_id: int,
   *   country_title: string,
   *   country_url: string,
   *   flag_url: string
   * }
   */
  function bsi_resort_context(int $term_id): array
  {
    static $cache = [];

    if (isset($cache[$term_id])) {
      return $cache[$term_id];
    }

    $empty = [
      'term' => null,
      'region' => null,
      'country_id' => 0,
      'country_title' => '',
      'country_url' => '',
      'flag_url' => '',
    ];

    if ($term_id <= 0) {
      return $empty;
    }

    $term = get_term($term_id, 'resort');
    if (!($term instanceof WP_Term)) {
      return $empty;
    }

    $context = $empty;
    $context['term'] = $term;

    if (!function_exists('get_field')) {
      $cache[$term_id] = $context;

      return $context;
    }

    $region_id = (int) get_field('resort_region', 'term_' . $term_id);
    if ($region_id > 0) {
      $region = get_term($region_id, 'region');
      if ($region instanceof WP_Term) {
        $context['region'] = $region;

        $country_id = (int) get_field('region_country', 'term_' . $region_id);
        if ($country_id > 0 && get_post_status($country_id) === 'publish') {
          $context['country_id'] = $country_id;
          $context['country_title'] = (string) get_the_title($country_id);
          $context['country_url'] = (string) get_permalink($country_id);
          $context['flag_url'] = function_exists('bsi_get_country_flag_url')
            ? (string) bsi_get_country_flag_url($country_id)
            : '';
        }
      }
    }

    $cache[$term_id] = $context;

    return $context;
  }
}

if (!function_exists('bsi_resort_posts')) {
  /**
   * Записи курорта одного типа: ID в порядке названия.
   *
   * @return int[]
   */
  function bsi_resort_posts(int $term_id, string $post_type, int $limit = -1): array
  {
    if ($term_id <= 0 || $post_type === '') {
      return [];
    }

    $ids = get_posts([
      'post_type' => $post_type,
      'post_status' => 'publish',
      'posts_per_page' => $limit,
      'fields' => 'ids',
      'orderby' => 'title',
      'order' => 'ASC',
      'no_found_rows' => true,
      'tax_query' => [
        [
          'taxonomy' => 'resort',
          'field' => 'term_id',
          'terms' => [$term_id],
        ],
      ],
    ]);

    return is_array($ids) ? array_map('intval', $ids) : [];
  }
}

if (!function_exists('bsi_resort_count')) {
  /**
   * Сколько записей типа привязано к курорту. Считается один раз за запрос:
   * числа нужны и плиткам фактов в шапке, и заголовкам секций.
   */
  function bsi_resort_count(int $term_id, string $post_type): int
  {
    static $cache = [];

    $key = $term_id . ':' . $post_type;
    if (isset($cache[$key])) {
      return $cache[$key];
    }

    $cache[$key] = count(bsi_resort_posts($term_id, $post_type));

    return $cache[$key];
  }
}

if (!function_exists('bsi_resort_url')) {
  /**
   * Ссылка на курорт: /country/{country}/{region}/{resort}/.
   * term_link для `resort` даёт её же (фильтр в country.php), но здесь
   * не нужен запрос к базе за родителями, если контекст уже собран.
   */
  function bsi_resort_url(int $term_id): string
  {
    $term = get_term($term_id, 'resort');

    return ($term instanceof WP_Term) ? (string) get_term_link($term) : '';
  }
}

if (!function_exists('bsi_resort_siblings')) {
  /**
   * Соседние курорты того же региона — для перелинковки внизу страницы.
   *
   * @return WP_Term[]
   */
  function bsi_resort_siblings(int $term_id, int $limit = 12): array
  {
    $context = bsi_resort_context($term_id);
    if (!($context['region'] instanceof WP_Term)) {
      return [];
    }

    $terms = get_terms([
      'taxonomy' => 'resort',
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
      'number' => $limit + 1,
      'exclude' => [$term_id],
      'meta_query' => [
        [
          'key' => 'resort_region',
          'value' => $context['region']->term_id,
          'compare' => '=',
        ],
      ],
    ]);

    if (is_wp_error($terms) || empty($terms)) {
      return [];
    }

    return array_slice($terms, 0, $limit);
  }
}

if (!function_exists('bsi_resort_map_points')) {
  /**
   * Точки для карты курорта — достопримечательности с координатами.
   * Формат совпадает с каталогом страны, карту рисует тот же модуль
   * `js/modules/sights-map.js`.
   *
   * @param int[] $sight_ids
   * @return array{points: array<int, array<string, mixed>>, icons: array<string, string>}
   */
  function bsi_resort_map_points(array $sight_ids): array
  {
    $points = [];
    $icons = [];

    if (!function_exists('bsi_get_sight_coordinates')) {
      return ['points' => $points, 'icons' => $icons];
    }

    foreach ($sight_ids as $sight_id) {
      $sight_id = (int) $sight_id;
      $coords = bsi_get_sight_coordinates($sight_id);
      if ($coords === null) {
        continue;
      }

      $icon = 'map-pin';
      $types = get_the_terms($sight_id, 'sight_type');
      if (!is_wp_error($types) && !empty($types)) {
        $icon = bsi_sight_type_icon((int) $types[0]->term_id);
      }

      $inner = bsi_sight_icon_inner($icon);
      if ($inner !== '') {
        $icons[$icon] = $inner;
      }

      $points[] = [
        'lat' => $coords['lat'],
        'lng' => $coords['lng'],
        'title' => get_the_title($sight_id),
        'url' => get_permalink($sight_id),
        'icon' => $icon,
        'image' => (string) get_the_post_thumbnail_url($sight_id, 'medium'),
      ];
    }

    return ['points' => $points, 'icons' => $icons];
  }
}

if (!function_exists('bsi_resort_locative')) {
  /**
   * Курорт в предложном падеже: «Отдых в Лондоне», а не «в Лондон».
   *
   * Точного склонения топонимов правилами не построить, поэтому порядок такой:
   * ACF-поле на терме → простые правила для типовых окончаний → имя как есть.
   * Заимствованные на -о/-и/-е/-у (Осло, Тбилиси, Сочи) не склоняются вовсе.
   */
  function bsi_resort_locative(int $term_id): string
  {
    $term = get_term($term_id, 'resort');
    if (!($term instanceof WP_Term)) {
      return '';
    }

    $name = trim((string) $term->name);
    if ($name === '') {
      return '';
    }

    if (function_exists('get_field')) {
      $override = trim((string) get_field('resort_title_locative', 'resort_' . $term_id));
      if ($override !== '') {
        return $override;
      }
    }

    /* Составные названия склоняем по последнему слову: «Сент Питер Порт» → «Порте» */
    $words = preg_split('~\s+~u', $name);
    $last = (string) array_pop($words);
    $prefix = empty($words) ? '' : implode(' ', $words) . ' ';

    $lower = mb_strtolower($last);
    $tail1 = mb_substr($lower, -1);
    $tail2 = mb_substr($lower, -2);

    /* Несклоняемые: Осло, Тбилиси, Сочи, Тарту */
    if (in_array($tail1, ['о', 'и', 'е', 'у', 'ю', 'э'], true)) {
      return $name;
    }

    /* Венеция → Венеции, но Анталья → Анталье */
    if ($tail2 === 'ия') {
      return $prefix . mb_substr($last, 0, -1) . 'и';
    }

    if ($tail1 === 'а' || $tail1 === 'я') {
      return $prefix . mb_substr($last, 0, -1) . 'е';
    }

    if ($tail1 === 'ь' || $tail1 === 'й') {
      return $prefix . mb_substr($last, 0, -1) . 'е';
    }

    /* Согласная: Лондон → Лондоне, Белфаст → Белфасте */
    if (preg_match('~[бвгджзклмнпрстфхцчшщ]$~u', $lower)) {
      return $prefix . $last . 'е';
    }

    return $name;
  }
}

if (!function_exists('bsi_resort_description_text')) {
  /**
   * Описание курорта без служебных заглушек.
   *
   * У части курортов в description лежит «Раздел находится в стадии
   * наполнения…» — для читателя и для поиска это пустая страница,
   * поэтому такой текст не выводим и за контент не считаем.
   */
  function bsi_resort_description_text(int $term_id): string
  {
    $term = get_term($term_id, 'resort');
    if (!($term instanceof WP_Term)) {
      return '';
    }

    $raw = trim((string) $term->description);
    if ($raw === '') {
      return '';
    }

    $plain = mb_strtolower(wp_strip_all_tags($raw));

    $stubs = ['стадии наполнения', 'раздел в разработке', 'информация появится'];
    foreach ($stubs as $stub) {
      if (mb_strpos($plain, $stub) !== false) {
        return '';
      }
    }

    return $raw;
  }
}

if (!function_exists('bsi_resort_is_indexable')) {
  /**
   * Пускать ли курорт в индекс.
   *
   * Курортов больше полутора сотен, а наполнены единицы: у большинства нет
   * ни описания, ни записей. Пустая страница в индексе — тонкий контент,
   * поэтому открываем только те, где есть что читать.
   */
  function bsi_resort_is_indexable(int $term_id): bool
  {
    if ($term_id <= 0) {
      return false;
    }

    $term = get_term($term_id, 'resort');
    if (!($term instanceof WP_Term)) {
      return false;
    }

    if (bsi_resort_description_text($term_id) !== '') {
      return true;
    }

    if (function_exists('get_field')) {
      $excerpt = trim((string) get_field('resort_excerpt', 'resort_' . $term_id));
      if ($excerpt !== '') {
        return true;
      }
    }

    /* Без текста — смотрим на карточки: три записи уже дают полезную страницу */
    $items = bsi_resort_count($term_id, 'sight')
      + bsi_resort_count($term_id, 'excursion')
      + bsi_resort_count($term_id, 'hotel')
      + bsi_resort_count($term_id, 'education')
      + bsi_resort_count($term_id, 'tour');

    return $items >= 3;
  }
}

if (!function_exists('bsi_resort_thin_term_ids')) {
  /**
   * ID курортов, которым нечего показать — их не отдаём в sitemap.
   * Пересчёт тяжёлый (запрос на тип записи), поэтому сутки живёт в transient.
   *
   * @return int[]
   */
  function bsi_resort_thin_term_ids(): array
  {
    $cached = get_transient('bsi_resort_thin_terms');
    if (is_array($cached)) {
      return $cached;
    }

    $terms = get_terms([
      'taxonomy' => 'resort',
      'hide_empty' => false,
      'fields' => 'ids',
    ]);

    $thin = [];

    if (!is_wp_error($terms)) {
      foreach ($terms as $term_id) {
        if (!bsi_resort_is_indexable((int) $term_id)) {
          $thin[] = (int) $term_id;
        }
      }
    }

    set_transient('bsi_resort_thin_terms', $thin, DAY_IN_SECONDS);

    return $thin;
  }
}

/* Правка курорта или его записей могла наполнить страницу — пересчитываем список */
add_action('edited_resort', function () {
  delete_transient('bsi_resort_thin_terms');
});

add_action('save_post', function ($post_id, $post) {
  if (!($post instanceof WP_Post)) {
    return;
  }

  if (in_array($post->post_type, ['sight', 'excursion', 'hotel', 'education', 'tour'], true)) {
    delete_transient('bsi_resort_thin_terms');
  }
}, 10, 2);

if (!function_exists('bsi_resort_education_item')) {
  /**
   * Данные карточки образования (`template-parts/education/card`).
   *
   * Карточка ждёт готовый массив, а собирают его сейчас в трёх местах
   * (`education-filter.php`, `popular-education-section.php`, `page-education.php`)
   * копипастой на полторы сотни строк. Здесь — компактный сборщик под секцию
   * курорта: цена, языки, программы, возраст, ссылка на бронь.
   *
   * @return array<string, mixed>
   */
  function bsi_resort_education_item(int $post_id): array
  {
    $country_id = function_exists('get_field') ? (int) get_field('education_country', $post_id) : 0;

    $resort_terms = get_the_terms($post_id, 'resort');
    $resort_title = (!is_wp_error($resort_terms) && !empty($resort_terms))
      ? (string) $resort_terms[0]->name
      : '';

    $image = (string) get_the_post_thumbnail_url($post_id, 'large');
    if ($image === '' && function_exists('get_field')) {
      $gallery = (array) get_field('education_gallery', $post_id);
      $first = $gallery[0] ?? null;
      $first_id = is_array($first) ? (int) ($first['ID'] ?? 0) : (int) $first;
      if ($first_id > 0) {
        $image = (string) wp_get_attachment_image_url($first_id, 'large');
      }
    }

    $programs_raw = function_exists('get_field') ? (array) get_field('education_programs', $post_id) : [];

    /* Цена — минимальная по программам, приведённая к рублям */
    $price = '';
    $price_data_attrs = [];
    $show_price_from = false;
    $min_value = 0;
    $min_program = null;

    foreach ($programs_raw as $program) {
      if (!is_array($program) || !function_exists('bsi_education_get_program_price_numeric_rub')) {
        continue;
      }

      $numeric = bsi_education_get_program_price_numeric_rub($program);
      if ($numeric > 0 && ($min_value === 0 || $numeric < $min_value)) {
        $min_value = $numeric;
        $min_program = $program;
      }
    }

    if ($min_value > 0) {
      /* Без «от» и без периода: карточка добавляет их сама
         (`education/card.php:355`), иначе выходит «от от … / 1-4 недели» */
      $price = format_number($min_value) . ' ₽';
      if (count($programs_raw) > 1) {
        $show_price_from = true;
      }

      if ($min_program !== null && function_exists('bsi_education_build_price_data_attrs')) {
        $price_data_attrs = bsi_education_build_price_data_attrs($min_program);
      }
    }

    /* Возраст — крайние значения по всем программам */
    $ages_min = [];
    $ages_max = [];
    foreach ($programs_raw as $program) {
      if (!is_array($program)) {
        continue;
      }
      if (!empty($program['program_age_min'])) {
        $ages_min[] = (int) $program['program_age_min'];
      }
      if (!empty($program['program_age_max'])) {
        $ages_max[] = (int) $program['program_age_max'];
      }
    }

    $languages = wp_get_post_terms($post_id, 'education_language', ['fields' => 'names']);
    $programs = wp_get_post_terms($post_id, 'education_program', ['fields' => 'names']);

    return [
      'id' => $post_id,
      'url' => (string) get_permalink($post_id),
      'image' => $image,
      'title' => (string) get_the_title($post_id),
      'flag' => ($country_id > 0 && function_exists('bsi_get_country_flag_url'))
        ? (string) bsi_get_country_flag_url($country_id)
        : '',
      'country_title' => $country_id > 0 ? (string) get_the_title($country_id) : '',
      'resort_title' => $resort_title,
      'price' => $price,
      'price_data_attrs' => $price_data_attrs,
      'show_price_from' => $show_price_from,
      'languages' => is_wp_error($languages) ? [] : $languages,
      'programs' => is_wp_error($programs) ? [] : $programs,
      'country_id' => $country_id,
      'booking_url' => function_exists('get_field')
        ? trim((string) get_field('education_booking_url', $post_id))
        : '',
      'age_min' => !empty($ages_min) ? min($ages_min) : 0,
      'age_max' => !empty($ages_max) ? max($ages_max) : 0,
      'checkin_dates_formatted' => '',
      'checkin_dates_remaining' => 0,
    ];
  }
}

/* ───────────────────────────────────────────────────────────────────
 * Подстраницы курорта: /country/{c}/{region}/{resort}/{section}/
 *
 * Слайдер на хабе показывает 12 карточек — остальные в индекс не попадают
 * (робот не нажимает «Показать все»), да и запрос «экскурсии в Лондоне»
 * заслуживает своего URL. Поэтому крупные разделы вынесены на отдельные
 * страницы с пагинацией.
 * ─────────────────────────────────────────────────────────────────── */

if (!function_exists('bsi_resort_sections')) {
  /**
   * slug раздела => тип записи, заголовок (шаблон с падежом), partial карточки.
   *
   * @return array<string, array{post_type: string, title: string, plural: array{0:string,1:string,2:string}, template: string}>
   */
  function bsi_resort_sections(): array
  {
    return [
      'ekskursii' => [
        'crumb' => 'Экскурсии',
        'post_type' => 'excursion',
        'title' => 'Экскурсии в %s',
        'plural' => ['экскурсия', 'экскурсии', 'экскурсий'],
        'template' => 'template-parts/excursion/card-row',
      ],
      'dostoprimechatelnosti' => [
        'crumb' => 'Достопримечательности',
        'post_type' => 'sight',
        'title' => 'Достопримечательности в %s',
        'plural' => ['достопримечательность', 'достопримечательности', 'достопримечательностей'],
        'template' => 'template-parts/sight/card',
      ],
      'obuchenie' => [
        'crumb' => 'Обучение',
        'post_type' => 'education',
        'title' => 'Обучение в %s',
        'plural' => ['программа', 'программы', 'программ'],
        'template' => 'template-parts/education/card',
      ],
      'oteli' => [
        'crumb' => 'Отели',
        'post_type' => 'hotel',
        'title' => 'Отели в %s',
        'plural' => ['отель', 'отеля', 'отелей'],
        'template' => 'template-parts/hotels/card-row',
      ],
    ];
  }
}

if (!function_exists('bsi_resort_section_min_items')) {
  /**
   * Порог, ниже которого отдельная страница не нужна: пара карточек
   * в индексе — тонкий контент и дубль хаба.
   */
  function bsi_resort_section_min_items(): int
  {
    return 5;
  }
}

if (!function_exists('bsi_resort_section_url')) {
  /**
   * Ссылка на подстраницу раздела — пустая строка, если раздела быть не должно.
   */
  function bsi_resort_section_url(int $term_id, string $section): string
  {
    $sections = bsi_resort_sections();
    if (!isset($sections[$section])) {
      return '';
    }

    if (bsi_resort_count($term_id, $sections[$section]['post_type']) < bsi_resort_section_min_items()) {
      return '';
    }

    $base = bsi_resort_url($term_id);

    return $base !== '' ? trailingslashit($base) . $section . '/' : '';
  }
}

if (!function_exists('bsi_resort_section_field')) {
  /**
   * Поле подстраницы раздела с терма курорта.
   *
   * Подстраницы виртуальные, редактировать их напрямую нельзя, поэтому
   * заголовок, тексты и SEO лежат на терме — по полю на раздел
   * (`custom-fields/resort.php`, группа «Курорт — разделы»).
   */
  function bsi_resort_section_field(int $term_id, string $section, string $field): string
  {
    if ($term_id <= 0 || !function_exists('get_field')) {
      return '';
    }

    $name = 'resort_' . str_replace('-', '_', $section) . '_' . $field;

    return trim((string) get_field($name, 'resort_' . $term_id));
  }
}

if (!function_exists('bsi_resort_section_h1')) {
  /**
   * Заголовок раздела: ручной с терма, иначе шаблон из bsi_resort_sections().
   */
  function bsi_resort_section_h1(int $term_id, string $section): string
  {
    $custom = bsi_resort_section_field($term_id, $section, 'h1');
    if ($custom !== '') {
      return $custom;
    }

    $sections = bsi_resort_sections();
    if (!isset($sections[$section])) {
      return '';
    }

    $term = get_term($term_id, 'resort');
    $locative = bsi_resort_locative($term_id);
    $subject = $locative !== '' ? $locative : (($term instanceof WP_Term) ? $term->name : '');

    return sprintf((string) $sections[$section]['title'], $subject);
  }
}

if (!function_exists('bsi_resort_genitive')) {
  /**
   * Курорт в родительном падеже: «Карта Лондона», а не «Карта Лондон».
   *
   * Те же правила, что в bsi_resort_locative(): заимствованные на -о/-и/-е/-у
   * не склоняются, у остальных меняется окончание. Переопределения нет —
   * при ошибке проще поправить ACF-локатив и формулировку заголовка.
   */
  function bsi_resort_genitive(int $term_id): string
  {
    $term = get_term($term_id, 'resort');
    if (!($term instanceof WP_Term)) {
      return '';
    }

    $name = trim((string) $term->name);
    if ($name === '') {
      return '';
    }

    $words = preg_split('~\s+~u', $name);
    $last = (string) array_pop($words);
    $prefix = empty($words) ? '' : implode(' ', $words) . ' ';

    $lower = mb_strtolower($last);
    $tail1 = mb_substr($lower, -1);
    $tail2 = mb_substr($lower, -2);

    /* Несклоняемые: Осло, Тбилиси, Сочи, Тарту */
    if (in_array($tail1, ['о', 'и', 'е', 'у', 'ю', 'э'], true)) {
      return $name;
    }

    if ($tail2 === 'ия' || $tail2 === 'ья') {
      return $prefix . mb_substr($last, 0, -1) . 'и';
    }

    /* Прага → Праги, Ницца → Ниццы */
    if ($tail1 === 'а') {
      $before = mb_substr($lower, -2, 1);
      $ending = in_array($before, ['г', 'к', 'х', 'ж', 'ч', 'ш', 'щ'], true) ? 'и' : 'ы';

      return $prefix . mb_substr($last, 0, -1) . $ending;
    }

    if ($tail1 === 'я') {
      return $prefix . mb_substr($last, 0, -1) . 'и';
    }

    if ($tail1 === 'ь' || $tail1 === 'й') {
      return $prefix . mb_substr($last, 0, -1) . 'я';
    }

    /* Согласная: Лондон → Лондона, Белфаст → Белфаста */
    if (preg_match('~[бвгджзклмнпрстфхцчшщ]$~u', $lower)) {
      return $prefix . $last . 'а';
    }

    return $name;
  }
}

if (!function_exists('bsi_resort_accusative')) {
  /**
   * Курорт в винительном падеже: «Собираетесь в Лондон?», «в Прагу?».
   *
   * У мужского и среднего рода винительный совпадает с именительным,
   * меняются только названия на -а/-я.
   */
  function bsi_resort_accusative(int $term_id): string
  {
    $term = get_term($term_id, 'resort');
    if (!($term instanceof WP_Term)) {
      return '';
    }

    $name = trim((string) $term->name);
    if ($name === '') {
      return '';
    }

    $words = preg_split('~\s+~u', $name);
    $last = (string) array_pop($words);
    $prefix = empty($words) ? '' : implode(' ', $words) . ' ';

    $lower = mb_strtolower($last);

    if (mb_substr($lower, -1) === 'а') {
      return $prefix . mb_substr($last, 0, -1) . 'у';
    }

    if (mb_substr($lower, -1) === 'я') {
      return $prefix . mb_substr($last, 0, -1) . 'ю';
    }

    return $name;
  }
}
