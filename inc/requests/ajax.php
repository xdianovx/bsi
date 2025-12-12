<?php
add_action('wp_ajax_simple_contact_form', 'handle_simple_form');
add_action('wp_ajax_nopriv_simple_contact_form', 'handle_simple_form');

function handle_simple_form()
{
  $errors = [];


  // Валидация имени
  $name = sanitize_text_field($_POST['name'] ?? '');
  if (empty($name)) {
    $errors['name'] = 'Введите имя';
  }

  // Валидация email
  $email = sanitize_email($_POST['email'] ?? '');
  if (empty($email)) {
    $errors['email'] = 'Введите email';
  } elseif (!is_email($email)) {
    $errors['email'] = 'Неверный формат email';
  }

  // Если есть ошибки - возвращаем
  if (!empty($errors)) {
    wp_send_json_error([
      'message' => 'Исправьте ошибки в форме',
      'errors' => $errors
    ]);
  }

  // Отправка письма
  $message = "Имя: $name\nEmail: $email";
  $sent = wp_mail(get_bloginfo('admin_email'), 'Новая заявка', $message);

  if ($sent) {
    wp_send_json_success('Форма отправлена!');

  } else {
    wp_send_json_error(['message' => 'Ошибка отправки письма']);
  }
}