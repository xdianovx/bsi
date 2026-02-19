<?php
/**
 * Модальное окно предупреждения
 * Показывается пользователям при включении в настройках ACF Options
 */
$modal_enabled = get_field('maintenance_modal_enabled', 'option');
$modal_message = get_field('maintenance_modal_message', 'option');

if (!$modal_enabled || empty($modal_message)) {
  return;
}
?>
<div class="modal micromodal-slide" id="modal-maintenance-warning" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-maintenance-warning-title">
      <div class="modal__close-btn" data-micromodal-close>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
          class="lucide lucide-x-icon lucide-x">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </div>

      <div class="modal__content">
        <h2 class="modal__title" id="modal-maintenance-warning-title">Внимание</h2>
        <p class="modal__subtitle"><?php echo esc_html($modal_message); ?></p>
      </div>
    </div>
  </div>
</div>
