<?php
$program = $args['program'] ?? [];
if (empty($program)) {
  return;
}

$price_per_week = $program['program_price_per_week'] ?? '';
$age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
$age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;
$duration_min = isset($program['program_duration_min']) ? (int) $program['program_duration_min'] : 0;
$duration_max = isset($program['program_duration_max']) ? (int) $program['program_duration_max'] : 0;
$description = $program['program_description'] ?? '';
$checkin_dates = $program['program_checkin_dates'] ?? [];
$accommodation_options = $program['program_accommodation_options'] ?? [];

$age_text = '';
if ($age_min > 0 && $age_max > 0) {
  if ($age_min === $age_max) {
    $age_text = $age_min . ' лет';
  } else {
    $age_text = $age_min . '-' . $age_max . ' лет';
  }
} elseif ($age_min > 0) {
  $age_text = 'от ' . $age_min . ' лет';
} elseif ($age_max > 0) {
  $age_text = 'до ' . $age_max . ' лет';
}

$duration_text = '';
if ($duration_min > 0 && $duration_max > 0) {
  if ($duration_min === $duration_max) {
    $duration_text = $duration_min . ' ' . ($duration_min === 1 ? 'неделя' : ($duration_min < 5 ? 'недели' : 'недель'));
  } else {
    $duration_text = $duration_min . '-' . $duration_max . ' недель';
  }
} elseif ($duration_min > 0) {
  $duration_text = 'от ' . $duration_min . ' ' . ($duration_min === 1 ? 'недели' : 'недель');
} elseif ($duration_max > 0) {
  $duration_text = 'до ' . $duration_max . ' недель';
}

$nearest_date = '';
if (!empty($checkin_dates) && is_array($checkin_dates)) {
  $dates = [];
  foreach ($checkin_dates as $date_item) {
    $date_str = is_array($date_item) ? ($date_item['checkin_date'] ?? '') : '';
    if ($date_str) {
      $dates[] = $date_str;
    }
  }
  if (!empty($dates)) {
    sort($dates);
    $today = date('Y-m-d');
    foreach ($dates as $date_str) {
      if ($date_str >= $today) {
        $nearest_date = $date_str;
        break;
      }
    }
    if (!$nearest_date && !empty($dates)) {
      $nearest_date = $dates[0];
    }
  }
}

$accommodation_names = [];
if (!empty($accommodation_options)) {
  if (!is_array($accommodation_options)) {
    $accommodation_options = [$accommodation_options];
  }
  foreach ($accommodation_options as $acc_id) {
    $term = get_term((int) $acc_id, 'education_accommodation_type');
    if ($term && !is_wp_error($term)) {
      $accommodation_names[] = $term->name;
    }
  }
}
?>

<div class="education-program-card">
  <?php if (!empty($price_per_week)): ?>
    <div class="education-program-card__price">
      <?php echo esc_html($price_per_week); ?> / неделя
    </div>
  <?php endif; ?>

  <div class="education-program-card__info">
    <?php if ($age_text): ?>
      <div class="education-program-card__age">
        <span class="education-program-card__label">Возраст:</span>
        <span class="education-program-card__value"><?php echo esc_html($age_text); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($duration_text): ?>
      <div class="education-program-card__duration">
        <span class="education-program-card__label">Продолжительность:</span>
        <span class="education-program-card__value"><?php echo esc_html($duration_text); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($nearest_date): ?>
      <div class="education-program-card__date">
        <span class="education-program-card__label">Ближайшая дата заселения:</span>
        <span class="education-program-card__value"><?php echo esc_html(date_i18n('d.m.Y', strtotime($nearest_date))); ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($accommodation_names)): ?>
      <div class="education-program-card__accommodation">
        <span class="education-program-card__label">Варианты проживания:</span>
        <span class="education-program-card__value"><?php echo esc_html(implode(', ', $accommodation_names)); ?></span>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($description)): ?>
    <div class="education-program-card__description">
      <?php echo wp_kses_post($description); ?>
    </div>
  <?php endif; ?>
</div>

