<?php
$program = get_query_var('program', []);
if (empty($program) && isset($args['program'])) {
  $program = $args['program'];
}
if (!is_array($program)) {
  return;
}

$program_index = get_query_var('program_index', 0);
if ($program_index === 0 && isset($args['program_index'])) {
  $program_index = $args['program_index'];
}

$program_title = $program['program_title'] ?? '';
$price_per_week = $program['program_price_per_week'] ?? '';
$age_min = isset($program['program_age_min']) && $program['program_age_min'] !== '' ? (int) $program['program_age_min'] : 0;
$age_max = isset($program['program_age_max']) && $program['program_age_max'] !== '' ? (int) $program['program_age_max'] : 0;
$duration = isset($program['program_duration']) ? (int) $program['program_duration'] : 0;
$description = $program['program_description'] ?? '';
$checkin_date_from = $program['program_checkin_date_from'] ?? '';
$checkin_date_to = $program['program_checkin_date_to'] ?? '';
$accommodation_options = $program['program_accommodation_options'] ?? [];

// Получаем URL бронирования: сначала из программы, если нет - из общего поля
$program_booking_url = isset($program['program_booking_url']) ? trim((string) $program['program_booking_url']) : '';
$general_booking_url = get_query_var('booking_url', '');
if (empty($general_booking_url) && isset($args['booking_url'])) {
  $general_booking_url = $args['booking_url'];
}
$general_booking_url = trim((string) $general_booking_url);

// Используем URL программы, если он есть, иначе общий URL
$booking_url = !empty($program_booking_url) ? $program_booking_url : $general_booking_url;

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
if ($duration > 0) {
  $duration_text = $duration . ' ' . ($duration === 1 ? 'неделя' : ($duration < 5 ? 'недели' : 'недель'));
}

$nearest_date = '';
$date_range_text = '';
if ($checkin_date_from) {
  $nearest_date = $checkin_date_from;
  if ($checkin_date_to && $checkin_date_to !== $checkin_date_from) {
    $date_from_obj = DateTime::createFromFormat('Y-m-d', $checkin_date_from);
    $date_to_obj = DateTime::createFromFormat('Y-m-d', $checkin_date_to);
    if ($date_from_obj && $date_to_obj) {
      $date_range_text = date_i18n('d.m.Y', $date_from_obj->getTimestamp()) . ' - ' . date_i18n('d.m.Y', $date_to_obj->getTimestamp());
    }
  } else {
    $date_from_obj = DateTime::createFromFormat('Y-m-d', $checkin_date_from);
    if ($date_from_obj) {
      $date_range_text = date_i18n('d.m.Y', $date_from_obj->getTimestamp());
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

<div class="single-education__program-item">
  <div class="education-program-card js-education-program-accordion">
    <div class="education-program-card__header">
      <div class="education-program-card__header-content">
        <?php if ($program_title): ?>
          <h3 class="education-program-card__title"><?php echo esc_html($program_title); ?></h3>
        <?php endif; ?>

        <div class="education-program-card__header-info">


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

            <?php if ($date_range_text): ?>
              <div class="education-program-card__date">
                <span class="education-program-card__label">Даты заселения:</span>
                <span class="education-program-card__value"><?php echo esc_html($date_range_text); ?></span>
              </div>
            <?php endif; ?>

            <?php if (!empty($accommodation_names)): ?>
              <div class="education-program-card__accommodation">
                <span class="education-program-card__label">Варианты проживания:</span>
                <span
                  class="education-program-card__value"><?php echo esc_html(implode(', ', $accommodation_names)); ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="education-program-card__footer">
            <?php if (!empty($description)): ?>
              <button type="button" class="education-program-card__toggle js-education-program-toggle"
                aria-expanded="false" aria-controls="program-content-<?php echo esc_attr($program_index); ?>">
                <span class="education-program-card__toggle-text">Подробнее</span>
                <span class="education-program-card__toggle-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" />
                  </svg>
                </span>
              </button>
            <?php endif; ?>
            <div class="education-program-card__footer-right">
              <?php if (!empty($price_per_week)): ?>
                <div class="education-program-card__price">
                  <?php echo esc_html($price_per_week); ?>
                </div>
              <?php endif; ?>
              <?php if ($booking_url): ?>
                <a href="<?php echo esc_url($booking_url); ?>" class="btn btn-accent education-program-card__booking-btn"
                  target="_blank" rel="noopener noreferrer">
                  Забронировать
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>


    </div>

    <?php if (!empty($description)): ?>
      <div class="education-program-card__content js-education-program-content"
        id="program-content-<?php echo esc_attr($program_index); ?>" hidden>
        <div class="education-program-card__description">
          <?php echo wp_kses_post($description); ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>