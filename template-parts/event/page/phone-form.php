<?php
/**
 * Короткая форма «имя + телефон» (js/modules/forms/event-ticket-form.js, #event-booking-cta-form).
 * На странице одна: «Остались вопросы?» у идущего события, «Сообщить о новых датах» у завершённого.
 */
$event = $args['event'];
$kind = (string) ($args['kind'] ?? 'consult');
$label = (string) ($args['label'] ?? 'Отправить');
$light = !empty($args['light']); // светлая карточка «Событие завершено»: серые поля, кнопка под ними
$input_class = 'single-event__booking-cta-input' . ($light ? ' single-event__booking-cta-input--light' : '');
$post_id = (int) $event['id'];
?>
<form id="event-booking-cta-form" class="single-event__booking-cta-form" novalidate
  data-event-id="<?= esc_attr((string) $post_id); ?>" data-event-title="<?= esc_attr($event['title']); ?>">
  <input type="hidden" name="action" value="event_ticket_booking">
  <input type="hidden" name="event_booking_minimal" value="1">
  <input type="hidden" name="booking_kind" value="<?= esc_attr($kind); ?>">
  <input type="hidden" name="event_title" value="<?= esc_attr($event['title']); ?>">
  <input type="hidden" name="page_url" value="<?= esc_url(get_permalink($post_id)); ?>">

  <div class="single-event__booking-cta-row<?= $light ? ' single-event__booking-cta-row--light' : ''; ?>">
    <div class="single-event__booking-cta-field">
      <input type="text" name="name" class="<?= esc_attr($input_class); ?>" placeholder="Имя" autocomplete="name" required data-field="name">
      <span class="single-event__booking-cta-field-error js-field-error" data-error-for="name"></span>
    </div>
    <div class="single-event__booking-cta-field">
      <input type="tel" name="phone" class="<?= esc_attr($input_class); ?> js-phone-mask" placeholder="Телефон" autocomplete="tel" required data-field="phone">
      <span class="single-event__booking-cta-field-error js-field-error" data-error-for="phone"></span>
    </div>
    <button type="submit" class="single-event__booking-cta-submit<?= $light ? ' single-event__booking-cta-submit--light' : ''; ?>" data-default-label="<?= esc_attr($label); ?>"><?= esc_html($label); ?></button>
  </div>

  <?php
  if (function_exists('bsi_render_privacy_consent_checkbox')) {
    bsi_render_privacy_consent_checkbox([
      'variant' => 'event-booking-cta',
      'checkbox_id' => 'event-booking-cta-privacy',
      'wrapper_class' => $light ? 'single-event__booking-cta-privacy--light' : '',
    ]);
  }
  ?>
</form>
