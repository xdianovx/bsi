<?php
$education_id = (int) get_the_ID();
if (!$education_id) {
  return;
}

$title = get_the_title($education_id);
$url = get_permalink($education_id);

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

$country_title = $country_id ? (string) get_the_title($country_id) : '';
$flag_url = '';

if ($country_id && function_exists('get_field')) {
  $flag_field = get_field('flag', $country_id);
  if ($flag_field) {
    if (is_array($flag_field) && !empty($flag_field['url'])) {
      $flag_url = (string) $flag_field['url'];
    } elseif (is_string($flag_field)) {
      $flag_url = (string) $flag_field;
    }
  }
}

$image_url = '';
$thumb = get_the_post_thumbnail_url($education_id, 'large');
if ($thumb) {
  $image_url = (string) $thumb;
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
        $image_url = (string) $img;
      }
    }
  }
}

$price = '';
if (function_exists('get_field')) {
  $price = (string) get_field('education_price', $education_id);
}

// Получаем языки обучения
$languages = wp_get_post_terms($education_id, 'education_language', ['fields' => 'names']);
$languages = is_wp_error($languages) ? [] : $languages;

// Получаем программы обучения
$programs = wp_get_post_terms($education_id, 'education_program', ['fields' => 'names']);
$programs = is_wp_error($programs) ? [] : $programs;

// Получаем минимальную цену из программ
$min_price = '';
if (function_exists('get_field')) {
  $education_programs = get_field('education_programs', $education_id);
  $education_programs = is_array($education_programs) ? $education_programs : [];
  
  if (!empty($education_programs)) {
    $prices = [];
    foreach ($education_programs as $program) {
      $program_price = isset($program['price_per_week']) ? (string) $program['price_per_week'] : '';
      if ($program_price) {
        // Извлекаем число из строки цены
        preg_match('/[\d\s]+/', $program_price, $matches);
        if (!empty($matches[0])) {
          $prices[] = (int) str_replace(' ', '', $matches[0]);
        }
      }
    }
    
    if (!empty($prices)) {
      $min_price_value = min($prices);
      // Форматируем цену
      $min_price = number_format($min_price_value, 0, ',', ' ') . ' ₽/неделя';
    }
  }
}

$excerpt = get_the_excerpt($education_id);
?>

<a href="<?php echo esc_url($url); ?>" class="education-card">
  <?php if ($image_url): ?>
    <div class="education-card__media">
      <img class="education-card__image"
           src="<?php echo esc_url($image_url); ?>"
           alt="<?php echo esc_attr($title); ?>">
    </div>
  <?php endif; ?>

  <div class="education-card__body">
    <div class="education-card__head">
      <?php if ($flag_url || $country_title): ?>
        <div class="education-card__country">
          <?php if ($flag_url): ?>
            <span class="education-card__flag">
              <img src="<?php echo esc_url($flag_url); ?>"
                   alt="<?php echo esc_attr($country_title); ?>">
            </span>
          <?php endif; ?>
          <?php if ($country_title): ?>
            <span class="education-card__country-title"><?php echo esc_html($country_title); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <h3 class="education-card__title"><?php echo esc_html($title); ?></h3>

    <?php if (!empty($languages)): ?>
      <div class="education-card__languages">
        <span class="education-card__label">Языки:</span>
        <span class="education-card__value"><?php echo esc_html(implode(', ', array_slice($languages, 0, 3))); ?><?php echo count($languages) > 3 ? '...' : ''; ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($programs)): ?>
      <div class="education-card__programs">
        <span class="education-card__label">Программы:</span>
        <span class="education-card__value"><?php echo esc_html(implode(', ', array_slice($programs, 0, 2))); ?><?php echo count($programs) > 2 ? '...' : ''; ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($excerpt)): ?>
      <div class="education-card__excerpt">
        <?php echo wp_kses_post($excerpt); ?>
      </div>
    <?php endif; ?>

    <div class="education-card__footer">
      <?php if (!empty($min_price)): ?>
        <div class="education-card__price">
          от <?php echo esc_html($min_price); ?>
        </div>
      <?php elseif (!empty($price)): ?>
        <div class="education-card__price">
          <?php echo esc_html($price); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</a>

