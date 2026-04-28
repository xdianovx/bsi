<?php
/**
 * Получатели заявок с лендинга MICE (page-bsimice).
 *
 * Добавить адреса:
 * add_filter('bsi_bsimice_lead_recipient_emails', function (array $emails) {
 *   $emails[] = 'partner@example.com';
 *   return $emails;
 * });
 */

if (!function_exists('bsi_get_bsimice_lead_recipient_emails')) {
  /**
   * @return string[]
   */
  function bsi_get_bsimice_lead_recipient_emails(): array
  {
    $emails = ['dianov.js@gmail.com'];
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
