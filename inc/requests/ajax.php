<?php
add_action('wp_ajax_simple_contact_form', 'handle_simple_form');
add_action('wp_ajax_nopriv_simple_contact_form', 'handle_simple_form');

function handle_simple_form()
{
  $errors = [];
  $client_type = sanitize_text_field($_POST['client_type'] ?? 'private');

  $full_name = sanitize_text_field($_POST['full_name'] ?? '');
  if (empty($full_name)) {
    $errors['full_name'] = 'Введите ФИО';
  }

  $email = sanitize_email($_POST['email'] ?? '');
  if (empty($email)) {
    $errors['email'] = 'Введите email';
  } elseif (!is_email($email)) {
    $errors['email'] = 'Неверный формат email';
  }

  $phone = sanitize_text_field($_POST['phone'] ?? '');
  if (empty($phone)) {
    $errors['phone'] = 'Введите телефон';
  } else {
    $phone_digits = preg_replace('/\D/', '', $phone);
    if (strlen($phone_digits) < 10) {
      $errors['phone'] = 'Введите корректный номер телефона';
    }
  }

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

  $privacy_agreement = isset($_POST['privacy_agreement']) && $_POST['privacy_agreement'] === 'on';
  if (!$privacy_agreement) {
    $errors['privacy_agreement'] = 'Необходимо согласие на обработку персональных данных';
  }

  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Исправьте ошибки в форме',
      'errors' => $errors
    ]);
  }

  $country_id = intval($_POST['country_id'] ?? 0);
  $country_name = '';
  if ($country_id) {
    $country_post = get_post($country_id);
    if ($country_post) {
      $country_name = $country_post->post_title;
    }
  }

  $departure_start = sanitize_text_field($_POST['departure_start'] ?? '');
  $departure_end = sanitize_text_field($_POST['departure_end'] ?? '');
  $tour_duration = sanitize_text_field($_POST['tour_duration'] ?? '');
  $budget = sanitize_text_field($_POST['budget'] ?? '');
  $hotel_stars = sanitize_text_field($_POST['hotel_stars'] ?? '');
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

  $message = "Новая заявка с формы FIT\n\n";
  $message .= "Тип клиента: " . ($client_type === 'corporate' ? 'Корпоративный' : 'Частный') . "\n\n";
  
  $message .= "Контактные данные:\n";
  $message .= "ФИО: $full_name\n";
  $message .= "Email: $email\n";
  $message .= "Телефон: $phone\n";
  
  if ($client_type === 'corporate') {
    $company_name_val = sanitize_text_field($_POST['company_name'] ?? '');
    $inn_val = sanitize_text_field($_POST['inn'] ?? '');
    $message .= "Название организации: $company_name_val\n";
    $message .= "ИНН: $inn_val\n";
  }
  $message .= "\n";

  $message .= "Параметры тура:\n";
  if ($country_name) {
    $message .= "Страна: $country_name\n";
  }
  if ($departure_start && $departure_end) {
    $start_date = date('d.m.Y', strtotime($departure_start));
    $end_date = date('d.m.Y', strtotime($departure_end));
    $message .= "Интервал вылета: $start_date - $end_date\n";
  }
  if ($tour_duration) {
    $message .= "Продолжительность тура: $tour_duration\n";
  }
  if ($budget) {
    $message .= "Бюджет: $budget\n";
  }
  if ($hotel_stars) {
    $message .= "Звездность отеля: $hotel_stars\n";
  }
  
  $message .= "\nКоличество человек:\n";
  $message .= "Взрослых: $adults_count\n";
  if ($children_count > 0) {
    $message .= "Детей: $children_count\n";
    if (!empty($children_ages)) {
      $message .= "Возраста детей: " . implode(', ', $children_ages) . "\n";
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
    $selected_services = array_map(function($val) use ($service_names) {
      return $service_names[$val] ?? $val;
    }, $services);
    $message .= "\nВыбранные услуги: " . implode(', ', $selected_services) . "\n";
  }
  
  if ($comments) {
    $message .= "\nКомментарии:\n$comments\n";
  }

  $message .= "\n---\n";
  $message .= "Дата отправки: " . date('d.m.Y H:i:s') . "\n";
  $message .= "IP адрес: " . $_SERVER['REMOTE_ADDR'] . "\n";

  $subject = 'Новая заявка FIT - ' . ($client_type === 'corporate' ? 'Корпоративный клиент' : 'Частный клиент');
  $sent = wp_mail(get_bloginfo('admin_email'), $subject, $message);

  if ($sent) {
    wp_send_json_success('Форма отправлена!');
  } else {
    wp_send_json_error(['message' => 'Ошибка отправки письма']);
  }
}