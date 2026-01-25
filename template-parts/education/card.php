<?php
$education = get_query_var('education');

$education_id = 0;
$education_url = '';
$education_image = '';
$education_title = '';
$education_flag = '';
$country_title = '';
$resort_title = '';
$price = '';
$languages = [];
$programs = [];
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
  $resort_title = !empty($education['resort_title']) ? (string) $education['resort_title'] : '';
  $price = !empty($education['price']) ? (string) $education['price'] : '';
  $languages = !empty($education['languages']) && is_array($education['languages']) ? $education['languages'] : [];
  $programs = !empty($education['programs']) && is_array($education['programs']) ? $education['programs'] : [];
  $booking_url = !empty($education['booking_url']) ? (string) $education['booking_url'] : '';
  $age_min = !empty($education['age_min']) && $education['age_min'] !== '' ? (int) $education['age_min'] : 0;
  $age_max = !empty($education['age_max']) && $education['age_max'] !== '' ? (int) $education['age_max'] : 0;
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

if (empty($resort_title) && $education_id && function_exists('get_field')) {
  $resort_field = get_field('education_resort', $education_id);
  if ($resort_field) {
    if ($resort_field instanceof WP_Term) {
      $resort_title = (string) $resort_field->name;
    } elseif (is_array($resort_field)) {
      $first_item = reset($resort_field);
      if ($first_item instanceof WP_Term) {
        $resort_title = (string) $first_item->name;
      } else {
        $resort_id = (int) $first_item;
        $resort_term = get_term($resort_id, 'resort');
        if ($resort_term && !is_wp_error($resort_term)) {
          $resort_title = (string) $resort_term->name;
        }
      }
    } else {
      $resort_id = (int) $resort_field;
      $resort_term = get_term($resort_id, 'resort');
      if ($resort_term && !is_wp_error($resort_term)) {
        $resort_title = (string) $resort_term->name;
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
      $program_age_min = isset($program['program_age_min']) && $program['program_age_min'] !== '' ? (int) $program['program_age_min'] : 0;
      $program_age_max = isset($program['program_age_max']) && $program['program_age_max'] !== '' ? (int) $program['program_age_max'] : 0;

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
      $date_from = isset($program['program_checkin_date_from']) ? (string) $program['program_checkin_date_from'] : '';
      if ($date_from) {
        $all_dates[] = $date_from;
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

  if (is_string($price_val) && $price_val !== '') {
    $price = (string) $price_val;
  }

  if (empty($price)) {
    $education_programs = get_field('education_programs', $education_id);
    $education_programs = is_array($education_programs) ? $education_programs : [];

    if (!empty($education_programs)) {
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
  }

  if (!empty($price)) {
    $price = format_price_text($price);
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
      <?php if ($country_title || $resort_title): ?>
        <div class="education-card__location-text">
          <?php if ($country_title): ?>
            <?php echo esc_html($country_title); ?>
          <?php endif; ?>
          <?php if ($country_title && $resort_title): ?>
            <span>, </span>
          <?php endif; ?>
          <?php if ($resort_title): ?>
            <span><?php echo esc_html($resort_title); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <h3 class="education-card__title"><?php echo esc_html($education_title); ?></h3>

    <?php if (!empty($programs)): ?>
      <div class="education-card__programs">
        <?php foreach (array_slice($programs, 0, 4) as $program): ?>
          <span
            class="education-card__program-tag <?php echo ($program === 'Групповой заезд') ? '--group-arrival' : ''; ?>"><?php echo esc_html($program); ?></span>
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
        Ближайший заезд: <?php echo esc_html(format_date_russian($nearest_date)); ?>
      </div>
    <?php endif; ?>



    <div class="education-card__actions">
      <?php
      $education_rel = 'noopener noreferrer';
      ?>
      <a href="<?php echo esc_url($education_url); ?>" target="_blank" rel="<?php echo esc_attr($education_rel); ?>"
        class="education-card__btn education-card__btn-details">
        Подробнее
      </a>
      <?php if ($price): ?>
        <?php
        // Временно кнопка с ценой ведет на страницу обучения
        $price_url = $education_url;
        $price_rel = 'noopener noreferrer';
        ?>
        <a href="<?php echo esc_url($price_url); ?>" target="_blank" rel="<?php echo esc_attr($price_rel); ?>"
          class="btn btn-accent education-card__btn education-card__btn-book">
          <?php echo esc_html($price); ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>