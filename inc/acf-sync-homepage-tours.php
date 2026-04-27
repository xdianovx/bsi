<?php
/**
 * Синхронизация «Популярный» (is_popular) у туров и списка relationship на главной (homepage_tour_items).
 *
 * - Сохранение главной: галочка выставляется у туров из списка, снимается у снятых со списка.
 * - Сохранение тура: снятие/включение «Популярный» обновляет relationship на главной.
 */

if (!function_exists('bsi_tour_is_popular_truthy')) {
  function bsi_tour_is_popular_truthy($value): bool
  {
    if ($value === true || $value === 1 || $value === '1') {
      return true;
    }
    if ($value === false || $value === 0 || $value === '0' || $value === '' || $value === null) {
      return false;
    }
    return (bool) $value;
  }
}

if (!function_exists('bsi_skip_homepage_tour_sync')) {
  function bsi_skip_homepage_tour_sync(): bool
  {
    return !empty($GLOBALS['bsi_skip_homepage_tour_popular_sync']);
  }
}

if (!function_exists('bsi_update_homepage_tour_items_field')) {
  function bsi_update_homepage_tour_items_field(int $front_page_id, array $ids): void
  {
    if ($front_page_id <= 0 || !function_exists('update_field')) {
      return;
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

    $GLOBALS['bsi_skip_homepage_tour_popular_sync'] = true;
    update_field('homepage_tour_items', $ids, $front_page_id);
    unset($GLOBALS['bsi_skip_homepage_tour_popular_sync']);
  }
}

/**
 * Старая relationship до сохранения (acf/save_post priority 1).
 */
add_action(
  'acf/save_post',
  function ($post_id) {
    if (bsi_skip_homepage_tour_sync()) {
      return;
    }
    $post_id = (int) $post_id;
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
      return;
    }
    $front = (int) get_option('page_on_front');
    if ($post_id <= 0 || $front <= 0 || $post_id !== $front) {
      return;
    }
    if (!function_exists('get_field')) {
      return;
    }
    $raw = get_field('homepage_tour_items', $post_id, false);
    if (!is_array($raw)) {
      $raw = [];
    }
    $GLOBALS['bsi_prev_homepage_tour_ids'] = array_values(
      array_filter(array_map('intval', $raw))
    );
  },
  1
);

/**
 * Главная сохранена: is_popular у туров = в списке / снят с списка.
 */
add_action(
  'acf/save_post',
  function ($post_id) {
    if (bsi_skip_homepage_tour_sync()) {
      return;
    }
    $post_id = (int) $post_id;
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
      return;
    }
    $front = (int) get_option('page_on_front');
    if ($post_id <= 0 || $front <= 0 || $post_id !== $front) {
      return;
    }
    if (!function_exists('get_field') || !function_exists('update_field')) {
      return;
    }

    $new_raw = get_field('homepage_tour_items', $post_id);
    $new = is_array($new_raw) ? array_values(array_filter(array_map('intval', $new_raw))) : [];

    $prev = isset($GLOBALS['bsi_prev_homepage_tour_ids']) && is_array($GLOBALS['bsi_prev_homepage_tour_ids'])
      ? $GLOBALS['bsi_prev_homepage_tour_ids']
      : [];
    unset($GLOBALS['bsi_prev_homepage_tour_ids']);

    foreach ($new as $tour_id) {
      if ($tour_id > 0) {
        update_field('is_popular', 1, $tour_id);
      }
    }

    $removed = array_diff($prev, $new);
    foreach ($removed as $tour_id) {
      if ($tour_id > 0) {
        update_field('is_popular', 0, $tour_id);
      }
    }
  },
  20
);

/**
 * Сохранён тур: «Популярный» ↔ список на главной.
 */
add_action(
  'acf/save_post',
  function ($post_id) {
    if (bsi_skip_homepage_tour_sync()) {
      return;
    }
    $post_id = (int) $post_id;
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
      return;
    }
    if ($post_id <= 0 || get_post_type($post_id) !== 'tour') {
      return;
    }
    if (!function_exists('get_field') || !function_exists('get_option')) {
      return;
    }

    $front = (int) get_option('page_on_front');
    if ($front <= 0) {
      return;
    }

    $is_pop = get_field('is_popular', $post_id, false);
    $on = bsi_tour_is_popular_truthy($is_pop);

    $list_raw = get_field('homepage_tour_items', $front, false);
    $list = is_array($list_raw) ? array_map('intval', $list_raw) : [];
    $in_list = in_array($post_id, $list, true);

    if ($on && !$in_list) {
      $list[] = $post_id;
      bsi_update_homepage_tour_items_field($front, $list);
    } elseif (!$on && $in_list) {
      $list = array_values(
        array_filter(
          $list,
          function ($id) use ($post_id) {
            return (int) $id !== $post_id;
          }
        )
      );
      bsi_update_homepage_tour_items_field($front, $list);
    }
  },
  25
);
