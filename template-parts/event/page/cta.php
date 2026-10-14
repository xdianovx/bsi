<?php
/**
 * «Остались вопросы?» — телефон для звонка менеджера (тёмный блок во всю ширину).
 */
$event = $args['event'];
?>
<section class="single-event__booking-cta">
  <div class="container">
    <div class="single-event__booking-cta-inner">
      <div class="single-event__booking-cta-head">
        <h2 class="single-event__booking-cta-title">Остались вопросы?</h2>
        <p class="single-event__booking-cta-lead">Оставьте телефон — менеджер перезвонит и поможет выбрать дату, билет или тур.</p>
      </div>
      <?php get_template_part('template-parts/event/page/phone-form', null, [
        'event' => $event,
        'kind' => 'consult',
        'label' => 'Перезвоните мне',
      ]); ?>
    </div>
  </div>
</section>
