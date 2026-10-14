<?php
/**
 * «Событие завершено»: бейдж, пояснение и форма «Сообщить о новых датах». Макет 1987:3592 (компонент 1987:4047).
 */
$event = $args['event'];
$badges = [
  'past' => 'Событие прошло',
  'closed' => 'Продажи закрыты',
  'cancelled' => 'Событие отменено',
];
$label = bsi_event_dates_label($event['all_dates']);
$plural = count($event['all_dates']) > 1;
$type = mb_strtolower((string) ($event['type_names'][0] ?? ''));
$noun = $type === 'концерт' ? ($plural ? 'Концерты' : 'Концерт') : ($plural ? 'События' : 'Событие');
$texts = [
  'past' => trim($noun . ' ' . $label . ($label !== '' ? ' года' : '') . ' уже ' . ($plural ? 'прошли' : 'прошёл' . ($noun === 'Событие' ? 'о' : ''))) . '. Оставьте телефон — напишем, как только объявят новые даты.',
  'closed' => 'Продажи на это событие закрыты. Оставьте телефон — напишем, если появятся билеты или новые даты.',
  'cancelled' => 'Организатор отменил событие. Оставьте телефон — напишем, если объявят новые даты.',
];
?>
<section class="single-event__status" id="status">
  <div class="single-event__status-text">
    <span class="single-event__status-badge"><?= esc_html($badges[$event['status']] ?? 'Событие завершено'); ?></span>
    <h2 class="single-event__status-title">Сообщим, когда объявят новые даты</h2>
    <p class="single-event__status-lead"><?= esc_html($texts[$event['status']] ?? $texts['past']); ?></p>
  </div>
  <?php get_template_part('template-parts/event/page/phone-form', null, [
    'event' => $event,
    'kind' => 'notify',
    'label' => 'Сообщить о новых датах',
    'light' => true,
  ]); ?>
</section>
