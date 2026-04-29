<?php
/**
 * Получатели заявок с форм MICE (bsimice_consultation_form: bsimice, delovoy, mice).
 *
 * По умолчанию: dianov.js@gmail.com, o.petrova@bsigroup.ru (все три MICE-страницы с формой консультации).
 *
 * Добавить адреса:
 * add_filter('bsi_bsimice_lead_recipient_emails', function (array $emails) {
 *   $emails[] = 'partner@example.com';
 *   return $emails;
 * });
 */

if (!function_exists('bsi_get_bsimice_lead_recipient_emails')) {
  /**
   * Получатели одной заявки (wp_mail получит массив — уйдёт всем).
   * Формы: page-mice.php, page-bsimice.php, page-delovoy.php — общий шаблон consultation-form-section + ajax bsimice_consultation_form.
   *
   * @return string[]
   */
  function bsi_get_bsimice_lead_recipient_emails(): array
  {
    $emails = [
      'dianov.js@gmail.com',
      'o.petrova@bsigroup.ru',
    ];
    /** @var string[] $emails */
    $emails = apply_filters('bsi_bsimice_lead_recipient_emails', $emails);
    $emails = array_map('sanitize_email', $emails);
    $emails = array_values(array_filter($emails));

    if ($emails === []) {
      $fallback = sanitize_email((string) get_bloginfo('admin_email'));
      if ($fallback !== '') {
        $emails = [$fallback];
      }
    }

    return $emails;
  }
}
