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

// Название школы для формы
$school_name = get_query_var('school_name', '');
if (empty($school_name) && isset($args['school_name'])) {
  $school_name = $args['school_name'];
}

$program_title = $program['program_title'] ?? '';
$price_per_week = $program['program_price_per_week'] ?? '';
$age_min = isset($program['program_age_min']) && $program['program_age_min'] !== '' ? (int) $program['program_age_min'] : 0;
$age_max = isset($program['program_age_max']) && $program['program_age_max'] !== '' ? (int) $program['program_age_max'] : 0;
$duration = isset($program['program_duration']) ? (int) $program['program_duration'] : 0;
$description = $program['program_description'] ?? '';
$checkin_date_from = $program['program_checkin_date_from'] ?? '';
$checkin_date_to = $program['program_checkin_date_to'] ?? '';
$accommodation_options = isset($program['program_accommodation_options']) ? $program['program_accommodation_options'] : [];
$meal_options = isset($program['program_meal_options']) ? $program['program_meal_options'] : [];

// Данные о визе
$visa_required = !empty($program['program_visa_required']);
$visa_price = isset($program['program_visa_price']) ? (int) $program['program_visa_price'] : 0;

// Дополнительные услуги
$additional_services = isset($program['program_additional_services']) ? $program['program_additional_services'] : [];
if (!is_array($additional_services)) {
  $additional_services = [];
}

// Нормализуем данные - ACF может вернуть разные форматы
if (!is_array($accommodation_options)) {
  $accommodation_options = $accommodation_options ? [$accommodation_options] : [];
}
if (!is_array($meal_options)) {
  $meal_options = $meal_options ? [$meal_options] : [];
}

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
    $age_text = 'с ' . $age_min . ' лет';
  } else {
    $age_text = $age_min . '-' . $age_max . ' лет';
  }
} elseif ($age_min > 0) {
  $age_text = 'с ' . $age_min . ' лет';
} elseif ($age_max > 0) {
  $age_text = 'до ' . $age_max . ' лет';
}

$duration_text = '';
if ($duration > 0) {
  $duration_text = $duration . ' ' . ($duration === 1 ? 'неделя' : ($duration < 5 ? 'недели' : 'недель'));
}

$nearest_date_formatted = '';
$date_for_modal = '';
if ($checkin_date_from) {
  // Форматируем дату для модалки (с DD.MM.YYYY)
  $date_obj = DateTime::createFromFormat('Y-m-d', $checkin_date_from);
  if ($date_obj) {
    $date_for_modal = 'с ' . $date_obj->format('d.m.Y');
  }

  if ($checkin_date_to && $checkin_date_to !== $checkin_date_from) {
    $nearest_date_formatted = format_date_short($checkin_date_from, $checkin_date_to);
  } else {
    $nearest_date_formatted = format_date_short($checkin_date_from);
  }
}

$accommodation_names = [];
foreach ($accommodation_options as $acc_id) {
  $acc_id = (int) $acc_id;
  if ($acc_id > 0) {
    $term = get_term($acc_id, 'education_accommodation_type');
    if ($term && !is_wp_error($term)) {
      $accommodation_names[] = $term->name;
    }
  }
}

$meal_names = [];
foreach ($meal_options as $meal_id) {
  $meal_id = (int) $meal_id;
  if ($meal_id > 0) {
    $term = get_term($meal_id, 'education_meal_type');
    if ($term && !is_wp_error($term)) {
      $meal_names[] = $term->name;
    }
  }
}

// Формируем текст о проживании/питании
$accommodation_parts = [];

if (!empty($accommodation_names)) {
  $accommodation_parts = $accommodation_names;
} else {
  $accommodation_parts = ['Без проживания'];
}

// Добавляем питание или "Без питания"
if (!empty($meal_names)) {
  $accommodation_parts = array_merge($accommodation_parts, $meal_names);
} else {
  $accommodation_parts[] = 'Без питания';
}

$accommodation_text = implode(', ', $accommodation_parts);

// Форматируем цену
$price_formatted = '';
$price_numeric = 0;
if (!empty($price_per_week)) {
  $price_formatted = format_price_with_from($price_per_week, true);
  // Извлекаем числовое значение цены
  $price_numeric = (int) preg_replace('/[^\d]/', '', $price_per_week);
}

// Подготавливаем данные дополнительных услуг для JSON
$services_for_json = [];
foreach ($additional_services as $service) {
  if (!empty($service['service_title'])) {
    $services_for_json[] = [
      'title' => $service['service_title'],
      'price' => (int) ($service['service_price'] ?? 0),
      'note' => $service['service_note'] ?? '',
    ];
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

        <?php if ($accommodation_text): ?>
          <div class="education-program-card__subtitle"><?php echo esc_html($accommodation_text); ?></div>
        <?php endif; ?>

        <?php if ($age_text || $duration_text || $nearest_date_formatted): ?>
          <div class="education-program-card__info-row">
            <?php if ($age_text): ?>
              <span class="education-program-card__info-value"><?php echo esc_html($age_text); ?></span>
              <?php if ($duration_text || $nearest_date_formatted): ?>
                <span class="education-program-card__info-separator"></span>
              <?php endif; ?>
            <?php endif; ?>

            <?php if ($duration_text): ?>
              <span class="education-program-card__info-value"><?php echo esc_html($duration_text); ?></span>
              <?php if ($nearest_date_formatted): ?>
                <span class="education-program-card__info-separator"></span>
              <?php endif; ?>
            <?php endif; ?>

            <?php if ($nearest_date_formatted): ?>
              <span class="education-program-card__info-value">
                <span class="education-program-card__date-label">Ближайшие даты:</span>
                <?php echo esc_html($nearest_date_formatted); ?>
              </span>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="education-program-card__divider"></div>

        <div class="education-program-card__footer">
          <?php if (!empty($description)): ?>
            <button type="button" class="education-program-card__toggle js-education-program-toggle" aria-expanded="false"
              aria-controls="program-content-<?php echo esc_attr($program_index); ?>">
              <span class="education-program-card__toggle-text">Подробнее</span>
              <span class="education-program-card__toggle-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
              </span>
            </button>
          <?php endif; ?>

          <div class="education-program-card__actions">
            <?php if ($price_formatted): ?>
              <div class="education-program-card__price"><?php echo esc_html($price_formatted); ?></div>
            <?php endif; ?>

            <button type="button" class="education-program-card__book-btn btn btn-accent js-program-booking-btn"
              data-program-title="<?php echo esc_attr($program_title); ?>"
              data-program-date="<?php echo esc_attr($date_for_modal); ?>"
              data-program-age="<?php echo esc_attr($age_text); ?>"
              data-program-duration="<?php echo esc_attr($duration_text); ?>"
              data-program-accommodation="<?php echo esc_attr($accommodation_text); ?>"
              data-program-price="<?php echo esc_attr($price_numeric); ?>"
              data-program-visa-required="<?php echo $visa_required ? '1' : '0'; ?>"
              data-program-visa-price="<?php echo esc_attr($visa_price); ?>"
              data-program-services="<?php echo esc_attr(wp_json_encode($services_for_json)); ?>"
              data-school-name="<?php echo esc_attr($school_name); ?>">
              Забронировать
            </button>
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