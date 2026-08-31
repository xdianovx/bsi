<?php
/**
 * AJAX: отклик на вакансию (template-parts/vacancy/apply-form.php).
 *
 * Резюме НЕ попадает в медиатеку и в wp-content/uploads: файл копируется во
 * временный каталог PHP, уходит вложением в письмо и удаляется в finally.
 * Так резюме кандидата не получает публичный URL и не индексируется.
 *
 * @package bsi
 */

declare(strict_types=1);

/** Максимальный размер резюме, МБ. */
if (!defined('BSI_VACANCY_RESUME_MAX_MB')) {
  define('BSI_VACANCY_RESUME_MAX_MB', 5);
}

add_action('wp_ajax_vacancy_apply', 'bsi_handle_vacancy_apply');
add_action('wp_ajax_nopriv_vacancy_apply', 'bsi_handle_vacancy_apply');

/**
 * Разрешённые форматы резюме: расширение => MIME-тип.
 *
 * @return array<string, string>
 */
function bsi_vacancy_resume_mimes(): array
{
  return [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'rtf' => 'application/rtf',
    'odt' => 'application/vnd.oasis.opendocument.text',
  ];
}

/**
 * Проверяет загруженный файл и кладёт его во временный каталог.
 *
 * @param array<string, mixed> $file Элемент $_FILES['resume'].
 * @return array{path: string, name: string}|WP_Error
 */
function bsi_vacancy_prepare_resume(array $file)
{
  $error_code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

  if ($error_code === UPLOAD_ERR_INI_SIZE || $error_code === UPLOAD_ERR_FORM_SIZE) {
    return new WP_Error('resume_size', 'Файл слишком большой. Максимум ' . BSI_VACANCY_RESUME_MAX_MB . ' МБ.');
  }

  if ($error_code !== UPLOAD_ERR_OK) {
    return new WP_Error('resume_upload', 'Не удалось загрузить файл. Попробуйте ещё раз.');
  }

  $tmp_name = (string) ($file['tmp_name'] ?? '');
  if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
    return new WP_Error('resume_upload', 'Не удалось загрузить файл. Попробуйте ещё раз.');
  }

  $max_bytes = BSI_VACANCY_RESUME_MAX_MB * 1024 * 1024;
  if ((int) ($file['size'] ?? 0) > $max_bytes) {
    return new WP_Error('resume_size', 'Файл слишком большой. Максимум ' . BSI_VACANCY_RESUME_MAX_MB . ' МБ.');
  }

  $allowed = bsi_vacancy_resume_mimes();
  $original_name = sanitize_file_name((string) ($file['name'] ?? 'resume'));

  // Проверяем связку «расширение ↔ реальный MIME» силами WordPress.
  $checked = wp_check_filetype_and_ext($tmp_name, $original_name, $allowed);
  $ext = is_string($checked['ext'] ?? null) ? $checked['ext'] : '';
  $type = is_string($checked['type'] ?? null) ? $checked['type'] : '';

  if ($ext === '' || $type === '' || !isset($allowed[$ext])) {
    return new WP_Error('resume_type', 'Допустимы только PDF, DOC, DOCX, RTF и ODT.');
  }

  // Имя генерируем сами — пользовательское в путь не попадает.
  $target = trailingslashit(get_temp_dir()) . 'bsi-resume-' . wp_generate_password(12, false) . '.' . $ext;

  if (!@move_uploaded_file($tmp_name, $target)) {
    return new WP_Error('resume_upload', 'Не удалось сохранить файл. Попробуйте ещё раз.');
  }

  @chmod($target, 0600);

  return [
    'path' => $target,
    'name' => $original_name,
  ];
}

/**
 * Обработчик отклика на вакансию.
 *
 * @return void
 */
function bsi_handle_vacancy_apply(): void
{
  $nonce = isset($_POST['vacancy_nonce']) ? sanitize_text_field(wp_unslash($_POST['vacancy_nonce'])) : '';
  if (!wp_verify_nonce($nonce, 'bsi_vacancy_apply')) {
    wp_send_json_error(['message' => 'Страница устарела. Обновите её и попробуйте снова.']);
  }

  // Ловушка для ботов: поле скрыто, у человека оно всегда пустое.
  if (trim((string) ($_POST['website'] ?? '')) !== '') {
    wp_send_json_success(['message' => 'Отклик отправлен']);
  }

  $errors = BSI_Mailer::validate_contact_fields($_POST, ['require_email' => true]);

  $vacancy_id = isset($_POST['vacancy_id']) ? absint($_POST['vacancy_id']) : 0;
  if ($vacancy_id === 0 || get_post_type($vacancy_id) !== 'vacancy') {
    wp_send_json_error(['message' => 'Вакансия не найдена. Обновите страницу.']);
  }

  $resume = null;
  if (!empty($_FILES['resume']) && (int) ($_FILES['resume']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $prepared = bsi_vacancy_prepare_resume($_FILES['resume']);
    if (is_wp_error($prepared)) {
      $errors['resume'] = $prepared->get_error_message();
    } else {
      $resume = $prepared;
    }
  }

  if (!empty($errors)) {
    if ($resume !== null) {
      @unlink($resume['path']);
    }
    wp_send_json_error([
      'message' => 'Пожалуйста, исправьте ошибки в форме',
      'errors' => $errors,
    ]);
  }

  $name = sanitize_text_field(trim((string) ($_POST['name'] ?? '')));
  $email = sanitize_email(trim((string) ($_POST['email'] ?? '')));
  $phone = sanitize_text_field(trim((string) ($_POST['phone'] ?? '')));
  $comment = sanitize_textarea_field(trim((string) ($_POST['comment'] ?? '')));

  // Заголовок и получателя берём из БД: POST-значения подделываются.
  $vacancy_title = get_the_title($vacancy_id);
  $page_url = (string) get_permalink($vacancy_id);
  $to = BSI_VACANCY_APPLY_EMAIL;

  try {
    $result = BSI_Mailer::send([
      'to' => $to,
      'subject' => 'Отклик на вакансию: ' . $vacancy_title,
      'template' => 'vacancy-apply',
      'data' => [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'comment' => $comment,
        'vacancy_title' => $vacancy_title,
        'page_url' => $page_url,
        'resume_name' => $resume['name'] ?? '',
      ],
      'reply_to' => $email,
      'attachments' => $resume !== null ? [$resume['path']] : [],
    ]);
  } finally {
    if ($resume !== null) {
      @unlink($resume['path']);
    }
  }

  if ($result['success']) {
    wp_send_json_success(['message' => 'Спасибо! Отклик отправлен — скоро вернёмся с ответом.']);
  }

  wp_send_json_error(['message' => $result['message']]);
}
