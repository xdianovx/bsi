<?php
/**
 * Выбор даты концерта и формата «Тур с билетом / Только билет». Макет 1957:1426.
 * Одна дата — шага даты нет; один формат — шага формата нет; ни того ни другого — блока нет.
 * Логика — js/modules/event-offer.js ([data-offer-date], [data-offer-format]).
 */
$event = $args['event'];
$both = $args['both'];
$json = $args['json'];
$offers = $event['offers'];
$dates = $json['dates'];
$default = $event['default_format'];
$first_key = $dates[0]['key'] ?? '';
$pick_date = count($dates) > 1;

if (!$pick_date && !$both) {
  return;
}

// Атрибуты элемента, видимого только в одном формате.
$only = static function (string $format) use ($both, $default): string {
  if (!$both) {
    return '';
  }
  return ' data-offer-format-only="' . esc_attr($format) . '"' . ($format !== $default ? ' hidden' : '');
};
$tour_price = $event['tour_price'];
?>
<section class="single-event__format single-event__column-card" id="format">
  <?php if ($pick_date): ?>
    <div class="single-event__format-step">
      <h2 class="h2">Выберите дату</h2>
      <div class="ui-choices">
        <?php foreach ($dates as $d): ?>
          <?php
          $is_pass = $d['key'] === BSI_EVENT_PASS_KEY;
          $ticket_from = $offers['ticket_from'][$d['key']] ?? null;
          ?>
          <button type="button" class="ui-choice ui-choice--check<?= $d['key'] === $first_key ? ' is-active' : ''; ?>"
            data-offer-date="<?= esc_attr($d['key']); ?>"<?= $is_pass ? $only('ticket') : ''; ?>>
            <span class="ui-choice__mark"></span>
            <span class="ui-choice__body">
              <span class="ui-choice__label"><?= esc_html($is_pass ? 'Все дни' : $d['label']); ?></span>
              <?php if ($event['has_tickets']): ?>
                <span class="ui-choice__sub numfont<?= $ticket_from ? ' js-event-price' : ''; ?>"<?= $only('ticket'); ?><?= bsi_event_price_attrs($ticket_from, true); ?>><?= esc_html($ticket_from ? bsi_event_price_text($ticket_from, true) : ($is_pass ? 'абонемент' : 'по запросу')); ?></span>
              <?php endif; ?>
              <?php if ($event['has_tour'] && !$is_pass && !empty($d['hasTour'])): ?>
                <span class="ui-choice__sub numfont<?= $tour_price ? ' js-event-price' : ''; ?>"<?= $only('tour'); ?><?= $event['crosstour'] ? ' data-crosstour-price' : ''; ?><?= bsi_event_price_attrs($tour_price, true); ?>><?= esc_html($tour_price ? bsi_event_price_text($tour_price, true) : 'тур'); ?></span>
              <?php elseif ($event['has_tour'] && !$is_pass): ?>
                <span class="ui-choice__sub"<?= $only('tour'); ?>>только билеты</span>
              <?php endif; ?>
            </span>
          </button>
        <?php endforeach; ?>
      </div>
      <p class="single-event__format-place" data-offer-place><?= esc_html($dates[0]['place'] ?? ''); ?></p>
    </div>
  <?php endif; ?>

  <?php if ($both): ?>
    <div class="single-event__format-step">
      <h3 class="single-event__format-title">Тур или только билет</h3>
      <div class="single-event__format-choices">
        <label class="ui-option<?= $default === 'tour' ? ' is-active' : ''; ?>" data-offer-format="tour">
          <input type="radio" class="ui-checkbox__input" name="event_offer_format" value="tour"<?= $default === 'tour' ? ' checked' : ''; ?>>
          <span class="ui-checkbox__mark ui-checkbox__mark--mono"></span>
          <span class="ui-option__body">
            <span class="ui-option__label">Тур с билетом</span>
            <span class="ui-option__sub" data-offer-tour-on>Билет, отель и программа</span>
            <span class="ui-option__sub" data-offer-tour-off hidden>На эту дату только билеты</span>
          </span>
          <span class="ui-option__price numfont<?= $tour_price ? ' js-event-price' : ''; ?>" data-offer-tour-on<?= $event['crosstour'] ? ' data-crosstour-price' : ''; ?><?= bsi_event_price_attrs($tour_price, true); ?>><?= esc_html($tour_price ? bsi_event_price_text($tour_price, true) : ''); ?></span>
        </label>
        <label class="ui-option<?= $default === 'ticket' ? ' is-active' : ''; ?>" data-offer-format="ticket">
          <input type="radio" class="ui-checkbox__input" name="event_offer_format" value="ticket"<?= $default === 'ticket' ? ' checked' : ''; ?>>
          <span class="ui-checkbox__mark ui-checkbox__mark--mono"></span>
          <span class="ui-option__body">
            <span class="ui-option__label">Только билет</span>
            <span class="ui-option__sub">Без проживания и доп. услуг</span>
          </span>
          <?php if (!empty($offers['ticket_min'])): ?>
            <span class="ui-option__price numfont js-event-price"<?= bsi_event_price_attrs($offers['ticket_min'], true); ?>><?= esc_html(bsi_event_price_text($offers['ticket_min'], true)); ?></span>
          <?php endif; ?>
        </label>
      </div>
    </div>
  <?php endif; ?>
</section>
