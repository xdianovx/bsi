<?php
$education = get_query_var('education');

$education_id = 0;
$education_url = '';
$education_image = '';
$education_title = '';
$education_flag = '';
$country_title = '';
$price = '';
$languages = [];
$programs = [];
$show_from = true;
$booking_url = '';
$age_min = 0;
$age_max = 0;
$nearest_date = '';

if ($education && is_array($education)) {
  if (!empty($education['id'])) {
    $education_id = (int) $education['id'];
  } elseif (!empty($education['url'])) {
    $education_id = (int) url_to_postid($education['url']);
  }

  $education_url = !empty($education['url']) ? (string) $education['url'] : '';
  $education_image = !empty($education['image']) ? (string) $education['image'] : '';
  $education_title = !empty($education['title']) ? (string) $education['title'] : '';
  $education_flag = !empty($education['flag']) ? (string) $education['flag'] : '';
  $country_title = !empty($education['country_title']) ? (string) $education['country_title'] : '';
  $price = !empty($education['price']) ? (string) $education['price'] : '';
  $languages = !empty($education['languages']) && is_array($education['languages']) ? $education['languages'] : [];
  $programs = !empty($education['programs']) && is_array($education['programs']) ? $education['programs'] : [];
  $show_from = isset($education['show_price_from']) ? ($education['show_price_from'] !== false) : true;
  $booking_url = !empty($education['booking_url']) ? (string) $education['booking_url'] : '';
  $age_min = !empty($education['age_min']) ? (int) $education['age_min'] : 0;
  $age_max = !empty($education['age_max']) ? (int) $education['age_max'] : 0;
  $nearest_date = !empty($education['nearest_date']) ? (string) $education['nearest_date'] : '';
} else {
  $education_id = (int) get_the_ID();
  if (!$education_id) {
    return;
  }
  $education_url = get_permalink($education_id);
  $education_title = get_the_title($education_id);
}

if (!$education_id && empty($education_title)) {
  return;
}

if (!$education_url && $education_id) {
  $education_url = get_permalink($education_id);
}

if (!$education_title && $education_id) {
  $education_title = get_the_title($education_id);
}

if (!$education_image && $education_id) {
  $thumb = get_the_post_thumbnail_url($education_id, 'large');
  if ($thumb) {
    $education_image = (string) $thumb;
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
          $education_image = (string) $img;
        }
      }
    }
  }
}

if (!$education_flag && $education_id) {
  $country_id = 0;
  if (function_exists('get_field')) {
    $c = get_field('education_country', $education_id);
    if ($c instanceof WP_Post) {
      $country_id = (int) $c->ID;
    } elseif (is_array($c)) {
      $country_id = (int) reset($c);
    } else {
      $country_id = (int) $c;
    }
  }

  if ($country_id) {
    if (!$country_title) {
      $country_title = (string) get_the_title($country_id);
    }

    if (function_exists('get_field')) {
      $flag_field = get_field('flag', $country_id);
      if ($flag_field) {
        if (is_array($flag_field) && !empty($flag_field['url'])) {
          $education_flag = (string) $flag_field['url'];
        } elseif (is_string($flag_field)) {
          $education_flag = (string) $flag_field;
        }
      }
    }
  }
}

if (empty($languages) && $education_id) {
  $lang_terms = wp_get_post_terms($education_id, 'education_language', ['fields' => 'names']);
  $languages = is_wp_error($lang_terms) ? [] : $lang_terms;
}

if (empty($programs) && $education_id) {
  $prog_terms = wp_get_post_terms($education_id, 'education_program', ['fields' => 'names']);
  $programs = is_wp_error($prog_terms) ? [] : $prog_terms;
}

if (($age_min === 0 && $age_max === 0) && $education_id && function_exists('get_field')) {
  $education_programs = get_field('education_programs', $education_id);
  $education_programs = is_array($education_programs) ? $education_programs : [];

  if (!empty($education_programs)) {
    $ages_min = [];
    $ages_max = [];

    foreach ($education_programs as $program) {
      $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
      $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;

      if ($program_age_min > 0) {
        $ages_min[] = $program_age_min;
      }
      if ($program_age_max > 0) {
        $ages_max[] = $program_age_max;
      }
    }

    if (!empty($ages_min)) {
      $age_min = min($ages_min);
    }
    if (!empty($ages_max)) {
      $age_max = max($ages_max);
    }
  }
}

if (empty($nearest_date) && $education_id && function_exists('get_field')) {
  $education_programs = get_field('education_programs', $education_id);
  $education_programs = is_array($education_programs) ? $education_programs : [];

  if (!empty($education_programs)) {
    $all_dates = [];

    foreach ($education_programs as $program) {
      $program_dates = isset($program['program_checkin_dates']) ? $program['program_checkin_dates'] : [];
      $program_dates = is_array($program_dates) ? $program_dates : [];

      foreach ($program_dates as $date_item) {
        $checkin_date = is_array($date_item) ? ($date_item['checkin_date'] ?? '') : '';
        if ($checkin_date) {
          $all_dates[] = $checkin_date;
        }
      }
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

if (empty($price) && $education_id && function_exists('get_field')) {
  $price_val = get_field('education_price', $education_id);
  $show_from_field = get_field('show_price_from', $education_id);
  $show_from = $show_from_field !== false;

  if (is_string($price_val) && $price_val !== '') {
    $price = (string) $price_val;
  }

  if (empty($price)) {
    $education_programs = get_field('education_programs', $education_id);
    $education_programs = is_array($education_programs) ? $education_programs : [];

    if (!empty($education_programs)) {
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
        $price = number_format($min_price_value, 0, ',', ' ') . ' ₽/неделя';
      }
    }
  }
}

if (empty($booking_url) && $education_id && function_exists('get_field')) {
  $booking_url_val = get_field('education_booking_url', $education_id);
  if ($booking_url_val) {
    $booking_url = trim((string) $booking_url_val);
  }
}
?>
<div class="education-card">

  <div class="education-card__media">
    <img src="<?php echo esc_url($education_image); ?>" alt="<?php echo esc_attr($education_title); ?>"
      class="education-card__image">
  </div>

  <div class="education-card__body">
    <div class="education-card__location">
      <?php if ($education_flag): ?>
        <div class="education-card__flag">
          <img src="<?php echo esc_url($education_flag); ?>" alt="">
        </div>
      <?php endif; ?>
      <?php if ($country_title): ?>
        <div class="education-card__location-text">
          <?php echo esc_html($country_title); ?>
        </div>
      <?php endif; ?>
    </div>

    <h3 class="education-card__title"><?php echo esc_html($education_title); ?></h3>

    <?php if (!empty($programs)): ?>
      <div class="education-card__programs">
        <?php foreach (array_slice($programs, 0, 4) as $program): ?>
          <span class="education-card__program-tag"><?php echo esc_html($program); ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($languages) || ($age_min > 0 || $age_max > 0)): ?>
      <div class="education-card__info-row">
        <?php if (!empty($languages)): ?>
          <span class="education-card__language">
            <?php echo esc_html(implode(', ', array_slice($languages, 0, 2))); ?>
          </span>
        <?php endif; ?>
        <?php if ($age_min > 0 || $age_max > 0): ?>
          <?php if (!empty($languages)): ?>
            <span class="education-card__separator">•</span>
          <?php endif; ?>
          <span class="education-card__age">
            <?php
            if ($age_min > 0 && $age_max > 0) {
              echo esc_html($age_min . '-' . $age_max . ' лет');
            } elseif ($age_min > 0) {
              echo esc_html('от ' . $age_min . ' лет');
            } elseif ($age_max > 0) {
              echo esc_html('до ' . $age_max . ' лет');
            }
            ?>
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($nearest_date): ?>
      <div class="education-card__date">
        Ближайший заезд: <?php
        $date_obj = DateTime::createFromFormat('Y-m-d', $nearest_date);
        if ($date_obj) {
          $day = $date_obj->format('j');
          $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря'
          ];
          $month_num = (int) $date_obj->format('n');
          $month_str = isset($months[$month_num]) ? $months[$month_num] : $date_obj->format('F');
          echo esc_html($day . ' ' . $month_str);
        } else {
          echo esc_html($nearest_date);
        }
        ?>
      </div>
    <?php endif; ?>



    <div class="education-card__actions">
      <a href="<?php echo esc_url($education_url); ?>" target="_blank"
        class="education-card__btn education-card__btn-details">
        Подробнее
      </a>
      <?php if ($booking_url && $price): ?>
        <a href="<?php echo esc_url($booking_url); ?>" target="_blank"
          class="btn btn-accent education-card__btn education-card__btn-book" target="_blank" rel="noopener nofollow">
          <?php echo esc_html(format_price_with_from($price, $show_from)); ?>
        </a>
      <?php elseif ($price): ?>
        <div class="education-card__price">
          <?php echo esc_html(format_price_with_from($price, $show_from)); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>