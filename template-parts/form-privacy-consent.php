<?php
/**
 * Согласие с политикой конфиденциальности (общая разметка).
 *
 * Ожидает переменные из bsi_render_privacy_consent_checkbox(): $variant, $checkbox_id,
 * $wrapper_class, $html_required, $privacy_url.
 *
 * @package bsi
 */

defined('ABSPATH') || exit;

$variant = $variant ?? 'visa-page';
$checkbox_id = $checkbox_id ?? 'privacy-consent';
$wrapper_class = isset($wrapper_class) ? trim((string) $wrapper_class) : '';
$html_required = !empty($html_required);
$privacy_url = isset($privacy_url) ? (string) $privacy_url : bsi_get_privacy_policy_url();

$req = $html_required ? ' required' : '';

$label_inner = static function () use ($checkbox_id, $privacy_url, $req) {
  ?>
  <label class="modal-program-booking__service-item modal-program-booking__service-item--policy"
    for="<?php echo esc_attr($checkbox_id); ?>">
    <input type="checkbox" id="<?php echo esc_attr($checkbox_id); ?>" name="privacy_agreement" value="on"
      class="modal-program-booking__service-checkbox" data-field="privacy_agreement" autocomplete="off" <?php echo $req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <span class="modal-program-booking__service-checkmark" aria-hidden="true"></span>
    <span class="modal-program-booking__service-text">Отправляя форму, я подтверждаю, что ознакомился<br>
      с <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener noreferrer">Политикой
        конфиденциальности</a> и даю согласие на обработку<br>
      своих персональных данных</span>
  </label>
  <?php
};

if ($variant === 'program-booking') {
  $root_class = 'modal-program-booking__form-group modal-program-booking__form-group--full modal-program-booking__form-group--privacy';
  if ($wrapper_class !== '') {
    $root_class .= ' ' . $wrapper_class;
  }
  ?>
  <div class="<?php echo esc_attr($root_class); ?>">
    <?php $label_inner(); ?>
    <span class="modal-program-booking__error js-field-error" data-error-for="privacy_agreement"></span>
  </div>
  <?php
} elseif ($variant === 'event-booking-cta') {
  $root_class = 'single-event__booking-cta-privacy';
  if ($wrapper_class !== '') {
    $root_class .= ' ' . $wrapper_class;
  }
  ?>
  <div class="<?php echo esc_attr($root_class); ?>">
    <label class="single-event__booking-cta-privacy-label" for="<?php echo esc_attr($checkbox_id); ?>">
      <input type="checkbox" id="<?php echo esc_attr($checkbox_id); ?>" name="privacy_agreement" value="on"
        class="single-event__booking-cta-privacy-input" autocomplete="off" data-field="privacy_agreement" <?php echo $req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
      <span class="single-event__booking-cta-privacy-box" aria-hidden="true"></span>
      <span class="single-event__booking-cta-privacy-text">Отправляя форму, я подтверждаю, что ознакомился с
        <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener noreferrer">Политикой
          конфиденциальности</a>
        и даю согласие на обработку своих персональных данных.</span>
    </label>
    <span class="single-event__booking-cta-privacy-error js-field-error" data-error-for="privacy_agreement"></span>
  </div>
  <?php
} elseif ($variant === 'input-item') {
  $root = 'input-item form-privacy-consent';
  if ($wrapper_class !== '') {
    $root .= ' ' . $wrapper_class;
  }
  ?>
  <div class="<?php echo esc_attr($root); ?>">
    <?php $label_inner(); ?>
    <span class="modal-program-booking__error js-field-error" data-error-for="privacy_agreement"></span>
  </div>
  <?php
} else {
  // visa-page: блок с серверными/клиентскими error-message
  $root = 'input-item form-privacy-consent';
  if ($wrapper_class !== '') {
    $root .= ' ' . $wrapper_class;
  }
  ?>
  <div class="<?php echo esc_attr($root); ?>">
    <?php $label_inner(); ?>
    <div class="error-message" data-field="privacy_agreement"></div>
  </div>
  <?php
}
