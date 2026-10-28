<?php

/**
 * Варианты поискового запроса: как набрал человек, в другой раскладке и транслитом.
 * «Andre» → «андре», «,fkb» → «бали», «бали» → «bali». Задача #132.
 */

if (!function_exists('bsi_search_layout_swap')) {
  /**
   * Перекладывает строку между раскладками ЙЦУКЕН и QWERTY.
   * Направление — по тому, каких букв больше.
   */
  function bsi_search_layout_swap(string $q): string
  {
    $en = 'qwertyuiop[]asdfghjkl;\'zxcvbnm,.`';
    $ru = 'йцукенгшщзхъфывапролджэячсмитьбюё';
    $en_chars = str_split($en);
    $ru_chars = mb_str_split($ru);

    $lat = preg_match_all('/[a-z]/i', $q);
    $cyr = preg_match_all('/\p{Cyrillic}/u', $q);
    $from = $lat >= $cyr ? $en_chars : $ru_chars;
    $to = $lat >= $cyr ? $ru_chars : $en_chars;

    return strtr(mb_strtolower($q, 'UTF-8'), array_combine($from, $to));
  }
}

if (!function_exists('bsi_search_translit_to_cyr')) {
  /**
   * Латиница → кириллица по-простому: andre → андре, bali → бали, dubai → дубай.
   */
  function bsi_search_translit_to_cyr(string $q): string
  {
    $q = mb_strtolower($q, 'UTF-8');
    // «i» после гласной читаем как «й»: dubai → дубай.
    $q = preg_replace('/(?<=[aeiouy])i/', 'й', $q);

    return strtr($q, [
      'shch' => 'щ', 'sch' => 'щ', 'zh' => 'ж', 'kh' => 'х', 'ch' => 'ч', 'sh' => 'ш',
      'ts' => 'ц', 'yu' => 'ю', 'ya' => 'я', 'yo' => 'ё', 'ph' => 'ф',
      'a' => 'а', 'b' => 'б', 'c' => 'к', 'd' => 'д', 'e' => 'е', 'f' => 'ф', 'g' => 'г',
      'h' => 'х', 'i' => 'и', 'j' => 'дж', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н',
      'o' => 'о', 'p' => 'п', 'q' => 'к', 'r' => 'р', 's' => 'с', 't' => 'т', 'u' => 'у',
      'v' => 'в', 'w' => 'в', 'x' => 'кс', 'y' => 'й', 'z' => 'з',
    ]);
  }
}

if (!function_exists('bsi_search_translit_to_lat')) {
  /**
   * Кириллица → латиница: бали → bali.
   */
  function bsi_search_translit_to_lat(string $q): string
  {
    return strtr(mb_strtolower($q, 'UTF-8'), [
      'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
      'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
      'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
      'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
      'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ]);
  }
}

if (!function_exists('bsi_search_query_variants')) {
  /**
   * Исходный запрос + раскладка + транслит, без повторов. Первым идёт исходный.
   *
   * @return string[]
   */
  function bsi_search_query_variants(string $q): array
  {
    $q = trim($q);
    if ($q === '') {
      return [];
    }

    // Транслит и от исходного, и от переключённого: «,heyj» → «бруно» → «bruno».
    $variants = [];
    foreach ([$q, bsi_search_layout_swap($q)] as $base) {
      $variants[] = $base;
      if (preg_match('/[a-z]/i', $base)) {
        $variants[] = bsi_search_translit_to_cyr($base);
      }
      if (preg_match('/\p{Cyrillic}/u', $base)) {
        $variants[] = bsi_search_translit_to_lat($base);
      }
    }

    $out = [];
    foreach ($variants as $v) {
      $v = trim($v);
      if ($v !== '' && !in_array(mb_strtolower($v, 'UTF-8'), array_map('mb_strtolower', $out), true)) {
        $out[] = $v;
      }
    }

    return $out;
  }
}

if (!function_exists('bsi_search_skeleton')) {
  /**
   * Согласный «скелет» слова для поиска по звучанию: викенд и weeknd → vknd,
   * бочелли и bocelli → bkl. Гласные убираем, похожие согласные склеиваем.
   */
  function bsi_search_skeleton(string $word): string
  {
    $s = bsi_search_translit_to_lat($word);
    $s = preg_replace('/[^a-z]/', '', $s);
    // «уи» в начале слова звучит как «w»: уикенд → vikend.
    $s = preg_replace('/^u(?=[aeiouy])/', 'v', $s);
    $s = strtr($s, ['shch' => 's', 'sch' => 's', 'dzh' => 'dz', 'ch' => 'k', 'ck' => 'k', 'sh' => 's', 'zh' => 'z', 'kh' => 'h', 'ph' => 'f', 'th' => 't']);
    $s = strtr($s, ['w' => 'v', 'c' => 'k', 'q' => 'k', 'j' => 'dz', 'x' => 'ks']);
    $s = preg_replace('/[aeiouyh]/', '', $s);

    return preg_replace('/(.)\1+/', '$1', $s);
  }
}

if (!function_exists('bsi_search_ids_by_sound')) {
  /**
   * Запасной поиск по звучанию заголовков, когда точный ничего не нашёл.
   * Каждое слово запроса (скелет от 3 согласных) должно быть началом скелета
   * какого-то слова заголовка.
   *
   * @return int[]
   */
  function bsi_search_ids_by_sound(array $args, string $q): array
  {
    $needles = [];
    foreach (preg_split('/\s+/u', trim($q)) as $word) {
      $sk = bsi_search_skeleton($word);
      if (strlen($sk) >= 3) {
        $needles[] = $sk;
      }
    }
    if ($needles === []) {
      return [];
    }

    $candidates = array_map('intval', (new WP_Query($args))->posts);
    if ($candidates === []) {
      return [];
    }

    // Скелеты заголовков — одним запросом и один раз на AJAX (фасеты зовут поиск повторно).
    static $skeletons = [];
    $missing = array_diff($candidates, array_keys($skeletons));
    if ($missing !== []) {
      global $wpdb;
      $rows = $wpdb->get_results('SELECT ID, post_title FROM ' . $wpdb->posts . ' WHERE ID IN (' . implode(',', $missing) . ')');
      foreach ($rows as $row) {
        $skeletons[(int) $row->ID] = array_map('bsi_search_skeleton', preg_split('/[\s\-–—«»"\'.,:;!?()]+/u', $row->post_title));
      }
    }

    $ids = [];
    foreach ($candidates as $id) {
      $words = $skeletons[$id] ?? [];
      $all = true;
      foreach ($needles as $needle) {
        $hit = false;
        foreach ($words as $w) {
          if ($w !== '' && strpos($w, $needle) === 0) {
            $hit = true;
            break;
          }
        }
        if (!$hit) {
          $all = false;
          break;
        }
      }
      if ($all) {
        $ids[] = $id;
      }
    }

    return $ids;
  }
}

if (!function_exists('bsi_search_ids_by_variants')) {
  /**
   * ID записей, которые находит обычный поиск WP хотя бы по одному варианту запроса.
   * Остальные условия берутся из $args (тип записи, статус, срок показа…).
   *
   * @return int[]
   */
  function bsi_search_ids_by_variants(array $args, string $q): array
  {
    unset($args['s'], $args['paged'], $args['orderby'], $args['order']);
    $args['posts_per_page'] = -1;
    $args['fields'] = 'ids';
    $args['no_found_rows'] = true;

    // Фасеты строят запрос несколько раз за один AJAX — ищем один раз.
    static $memo = [];
    $key = md5(wp_json_encode([$args, $q]));
    if (isset($memo[$key])) {
      return $memo[$key];
    }

    $ids = [];
    foreach (bsi_search_query_variants($q) as $variant) {
      $args['s'] = $variant;
      $ids = array_merge($ids, array_map('intval', (new WP_Query($args))->posts));
    }

    if ($ids === []) {
      unset($args['s']);
      $ids = bsi_search_ids_by_sound($args, $q);
    }

    return $memo[$key] = array_values(array_unique($ids));
  }
}
