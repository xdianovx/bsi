<?php
/**
 * AJAX обработчик для формы FIT
 */

add_action('wp_ajax_fit_form', 'handle_fit_form');
add_action('wp_ajax_nopriv_fit_form', 'handle_fit_form');

function handle_fit_form()
{
  $token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
  bsi_recaptcha_verify_or_die($token);

  // Логируем все входящие данные для отладки
  // error_log('FIT Form Data: ' . print_r($_POST, true));

  $errors = [];
  $client_type = sanitize_text_field($_POST['client_type'] ?? 'corporate');

  // Валидация ФИО
  $full_name = sanitize_text_field($_POST['full_name'] ?? '');
  if (empty($full_name)) {
    $errors['full_name'] = 'Введите ФИО';
  }

  // Валидация email
  $email = sanitize_email($_POST['email'] ?? '');
  if (empty($email)) {
    $errors['email'] = 'Введите email';
  } elseif (!is_email($email)) {
    $errors['email'] = 'Неверный формат email';
  }

  // Валидация телефона
  $phone = sanitize_text_field($_POST['phone'] ?? '');
  if (empty($phone)) {
    $errors['phone'] = 'Введите телефон';
  } else {
    // Проверка что телефон содержит достаточно цифр
    $phone_digits = preg_replace('/\D/', '', $phone);
    if (strlen($phone_digits) < 10) {
      $errors['phone'] = 'Введите корректный номер телефона';
    }
  }

  // Валидация для корпоративных клиентов (Турагентство)
  if ($client_type === 'corporate') {
    $company_name = sanitize_text_field($_POST['company_name'] ?? '');
    if (empty($company_name)) {
      $errors['company_name'] = 'Введите название организации';
    }

    $inn = sanitize_text_field($_POST['inn'] ?? '');
    if (empty($inn)) {
      $errors['inn'] = 'Введите ИНН организации';
    } else {
      $inn_digits = preg_replace('/\D/', '', $inn);
      if (strlen($inn_digits) !== 10 && strlen($inn_digits) !== 12) {
        $errors['inn'] = 'ИНН должен содержать 10 или 12 цифр';
      }
    }
  }

  // Валидация обязательных полей
  $country_id = intval($_POST['country_id'] ?? 0);
  if (empty($country_id)) {
    $errors['country_id'] = 'Выберите страну';
  }

  $departure_start = sanitize_text_field($_POST['departure_start'] ?? '');
  $departure_end = sanitize_text_field($_POST['departure_end'] ?? '');
  if (empty($departure_start) || empty($departure_end)) {
    $errors['departure_range'] = 'Выберите интервал вылета';
  }

  $tour_duration = sanitize_text_field($_POST['tour_duration'] ?? '');
  if (empty($tour_duration)) {
    $errors['tour_duration'] = 'Выберите продолжительность тура';
  }

  $hotel_stars = sanitize_text_field($_POST['hotel_stars'] ?? '');
  if (empty($hotel_stars)) {
    $errors['hotel_stars'] = 'Выберите звездность отеля';
  }

  $budget = sanitize_text_field($_POST['budget'] ?? '');
  if (empty($budget)) {
    $errors['budget'] = 'Укажите бюджет';
  } else {
    // Проверяем, что бюджет содержит хотя бы одну цифру
    $budget_digits = preg_replace('/\D/', '', $budget);
    if (empty($budget_digits)) {
      $errors['budget'] = 'Укажите корректный бюджет';
    }
  }

  // Явно убираем ошибку privacy_agreement, если она была добавлена где-то еще
  unset($errors['privacy_agreement']);

  // Если есть ошибки - возвращаем их
  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Исправьте ошибки в форме',
      'errors' => $errors
    ]);
  }

  // Собираем данные для письма
  $country_name = '';
  if ($country_id) {
    $country_post = get_post($country_id);
    if ($country_post) {
      $country_name = $country_post->post_title;
    }
  }

  $budget = sanitize_text_field($_POST['budget'] ?? '');
  $services = isset($_POST['services']) && is_array($_POST['services'])
    ? array_map('sanitize_text_field', $_POST['services'])
    : [];
  $comments = sanitize_textarea_field($_POST['comments'] ?? '');
  $adults_count = intval($_POST['adults_count'] ?? 0);
  $children_count = intval($_POST['children_count'] ?? 0);
  $children_ages = [];
  if (isset($_POST['children_ages'])) {
    $ages_json = sanitize_text_field($_POST['children_ages']);
    $children_ages = json_decode($ages_json, true);
    if (!is_array($children_ages)) {
      $children_ages = [];
    }
  }

  // Формируем HTML сообщение для email
  // Укажите здесь email получателя заявок (или оставьте пустым для использования email администратора)
  $recipient_email = 'dianov.js@gmail.com'; // Например: 'your-email@example.com'

  // Если email не указан, используем email администратора WordPress
  if (empty($recipient_email) || !is_email($recipient_email)) {
    $recipient_email = get_bloginfo('admin_email');
  }

  $client_type_label = $client_type === 'corporate' ? 'Турагентство' : 'Частный клиент';

  // Формируем HTML письмо
  $html_message = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    h1 { color: #dc2626; border-bottom: 2px solid #dc2626; padding-bottom: 10px; }
    .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #dc2626; }
    .section-title { font-weight: bold; font-size: 16px; margin-bottom: 10px; color: #dc2626; }
    .field { margin: 8px 0; }
    .field-label { font-weight: bold; display: inline-block; min-width: 180px; }
    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Новая заявка с формы FIT</h1>
    
    <div class="section">
      <div class="section-title">Тип клиента</div>
      <div class="field">
        <span class="field-label">Тип:</span>
        <span>' . esc_html($client_type_label) . '</span>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Контактные данные</div>
      <div class="field">
        <span class="field-label">ФИО:</span>
        <span>' . esc_html($full_name) . '</span>
      </div>
      <div class="field">
        <span class="field-label">Email:</span>
        <span><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></span>
      </div>
      <div class="field">
        <span class="field-label">Телефон:</span>
        <span><a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></span>
      </div>';

  if ($client_type === 'corporate') {
    $html_message .= '
      <div class="field">
        <span class="field-label">Название организации:</span>
        <span>' . esc_html($company_name) . '</span>
      </div>
      <div class="field">
        <span class="field-label">ИНН:</span>
        <span>' . esc_html($inn) . '</span>
      </div>';
  }

  $html_message .= '
    </div>

    <div class="section">
      <div class="section-title">Параметры тура</div>';

  if ($country_name) {
    $html_message .= '
      <div class="field">
        <span class="field-label">Страна:</span>
        <span>' . esc_html($country_name) . '</span>
      </div>';
  }

  if ($departure_start && $departure_end) {
    $start_date = date('d.m.Y', strtotime($departure_start));
    $end_date = date('d.m.Y', strtotime($departure_end));
    $html_message .= '
      <div class="field">
        <span class="field-label">Интервал вылета:</span>
        <span>' . esc_html($start_date . ' - ' . $end_date) . '</span>
      </div>';
  }

  if ($tour_duration) {
    $html_message .= '
      <div class="field">
        <span class="field-label">Продолжительность тура:</span>
        <span>' . esc_html($tour_duration) . '</span>
      </div>';
  }

  if ($budget) {
    $budget_formatted = number_format((float) str_replace(' ', '', $budget), 0, '', ' ');
    $html_message .= '
      <div class="field">
        <span class="field-label">Бюджет:</span>
        <span>' . esc_html($budget_formatted) . ' руб.</span>
      </div>';
  }

  if ($hotel_stars) {
    $stars_label = $hotel_stars === 'any' ? 'Любая' : $hotel_stars . ' ' . ($hotel_stars == 5 ? 'звезд' : ($hotel_stars == 1 ? 'звезда' : 'звезды'));
    $html_message .= '
      <div class="field">
        <span class="field-label">Звездность отеля:</span>
        <span>' . esc_html($stars_label) . '</span>
      </div>';
  }

  $html_message .= '
    </div>

    <div class="section">
      <div class="section-title">Количество человек</div>
      <div class="field">
        <span class="field-label">Взрослых:</span>
        <span>' . esc_html($adults_count) . '</span>
      </div>';

  if ($children_count > 0) {
    $html_message .= '
      <div class="field">
        <span class="field-label">Детей:</span>
        <span>' . esc_html($children_count) . '</span>
      </div>';
    if (!empty($children_ages)) {
      $html_message .= '
      <div class="field">
        <span class="field-label">Возраста детей:</span>
        <span>' . esc_html(implode(', ', $children_ages)) . '</span>
      </div>';
    }
  }

  $html_message .= '
    </div>';

  if (!empty($services)) {
    $service_names = [
      'flight' => 'Авиаперелет',
      'hotel' => 'Отель',
      'transfer' => 'Трансфер',
      'guide' => 'Гид',
      'excursion' => 'Экскурсия',
      'insurance' => 'Страховка',
      'visa' => 'Виза',
    ];
    $selected_services = array_map(function ($val) use ($service_names) {
      return $service_names[$val] ?? $val;
    }, $services);

    $html_message .= '
    <div class="section">
      <div class="section-title">Выбранные услуги</div>
      <div class="field">
        <span>' . esc_html(implode(', ', $selected_services)) . '</span>
      </div>
    </div>';
  }

  if ($comments) {
    $html_message .= '
    <div class="section">
      <div class="section-title">Комментарии</div>
      <div class="field">
        <span>' . nl2br(esc_html($comments)) . '</span>
      </div>
    </div>';
  }

  $html_message .= '
    <div class="footer">
      <p><strong>Дата отправки:</strong> ' . date('d.m.Y H:i:s') . '</p>
      <p><strong>IP адрес:</strong> ' . esc_html($_SERVER['REMOTE_ADDR'] ?? 'не определен') . '</p>
    </div>
  </div>
</body>
</html>';

  // Формируем plain text версию для совместимости
  $text_message = "Новая заявка с формы FIT\n\n";
  $text_message .= "Тип клиента: $client_type_label\n\n";
  $text_message .= "Контактные данные:\n";
  $text_message .= "ФИО: $full_name\n";
  $text_message .= "Email: $email\n";
  $text_message .= "Телефон: $phone\n";

  if ($client_type === 'corporate') {
    $text_message .= "Название организации: $company_name\n";
    $text_message .= "ИНН: $inn\n";
  }
  $text_message .= "\nПараметры тура:\n";
  if ($country_name) {
    $text_message .= "Страна: $country_name\n";
  }
  if ($departure_start && $departure_end) {
    $start_date = date('d.m.Y', strtotime($departure_start));
    $end_date = date('d.m.Y', strtotime($departure_end));
    $text_message .= "Интервал вылета: $start_date - $end_date\n";
  }
  if ($tour_duration) {
    $text_message .= "Продолжительность тура: $tour_duration\n";
  }
  if ($budget) {
    $text_message .= "Бюджет: $budget\n";
  }
  if ($hotel_stars) {
    $stars_label = $hotel_stars === 'any' ? 'Любая' : $hotel_stars . ' ' . ($hotel_stars == 5 ? 'звезд' : ($hotel_stars == 1 ? 'звезда' : 'звезды'));
    $text_message .= "Звездность отеля: $stars_label\n";
  }
  $text_message .= "\nКоличество человек:\n";
  $text_message .= "Взрослых: $adults_count\n";
  if ($children_count > 0) {
    $text_message .= "Детей: $children_count\n";
    if (!empty($children_ages)) {
      $text_message .= "Возраста детей: " . implode(', ', $children_ages) . "\n";
    }
  }
  if (!empty($services)) {
    $service_names = [
      'flight' => 'Авиаперелет',
      'hotel' => 'Отель',
      'transfer' => 'Трансфер',
      'guide' => 'Гид',
      'excursion' => 'Экскурсия',
      'insurance' => 'Страховка',
      'visa' => 'Виза',
    ];
    $selected_services = array_map(function ($val) use ($service_names) {
      return $service_names[$val] ?? $val;
    }, $services);
    $text_message .= "\nВыбранные услуги: " . implode(', ', $selected_services) . "\n";
  }
  if ($comments) {
    $text_message .= "\nКомментарии:\n$comments\n";
  }
  $text_message .= "\n---\n";
  $text_message .= "Дата отправки: " . date('d.m.Y H:i:s') . "\n";
  $text_message .= "IP адрес: " . ($_SERVER['REMOTE_ADDR'] ?? 'не определен') . "\n";

  // Настройка заголовков для HTML письма
  $headers = array(
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
  );

  // Отправка письма
  $subject = 'Новая заявка FIT - ' . $client_type_label;
  $sent = wp_mail($recipient_email, $subject, $html_message, $headers);

  if ($sent) {
    wp_send_json_success([
      'message' => 'Форма успешно отправлена!'
    ]);
  } else {
    wp_send_json_error([
      'message' => 'Ошибка отправки письма'
    ]);
  }
}

