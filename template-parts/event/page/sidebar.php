<?php
/**
 * Сайдбар события: шапка, состав заказа, цена, действия, валюта и телефоны. Макет 1957:1396.
 * На мобилке — шторка снизу (1985:3232), открывается стрелкой липкой панели (1984:3678).
 * Значения строк заказа обновляет js/modules/event-offer.js ([data-summary]).
 */
$event = $args['event'];
$both = $args['both'];
$json = $args['json'];
$default = $event['default_format'];
$first = $json['dates'][0] ?? [];
$first_package = $event['packages'][0] ?? null;
$offers = $event['offers'];

$only = static function (string $format) use ($both, $default): string {
  if (!$both) {
    return '';
  }
  return ' data-offer-format-only="' . esc_attr($format) . '"' . ($format !== $default ? ' hidden' : '');
};
$star = '<svg class="single-event__star" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>';
$chevron = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>';
?>
<aside class="single-event__about-aside" id="bronirovanie">
  <div class="single-event__booking-widget" data-offer-sheet>
    <div class="event-offer-summary__head">
      <div class="event-offer-summary__info">
        <p class="event-offer-summary__title"><?= esc_html(bsi_event_short_title($event['title'])); ?></p>
        <?php if ($event['date_place'] !== ''): ?>
          <p class="event-offer-summary__place" data-offer-summary-place><?= esc_html($event['date_place']); ?></p>
        <?php endif; ?>
      </div>
      <button type="button" class="event-offer-summary__close" data-offer-sheet-close><?= $chevron; ?></button>
    </div>

    <div class="event-offer-summary" data-offer-summary>
      <dl class="event-offer-summary__order">
        <div class="event-offer-summary__order-row">
          <dt class="event-offer-summary__order-label">Концерт</dt>
          <dd class="event-offer-summary__order-value" data-summary="concert"><?= esc_html($first['label'] ?? $event['dates_label']); ?></dd>
        </div>
        <?php if ($event['has_tour']): ?>
          <div class="event-offer-summary__order-row" data-summary-row="trip" hidden>
            <dt class="event-offer-summary__order-label">Поездка</dt>
            <dd class="event-offer-summary__order-value numfont" data-summary="trip"></dd>
          </div>
          <?php if ($event['tour_sector'] !== ''): ?>
            <div class="event-offer-summary__order-row"<?= $only('tour'); ?>>
              <dt class="event-offer-summary__order-label">Билет</dt>
              <dd class="event-offer-summary__order-value" data-summary="tour-ticket"><?= esc_html($event['tour_sector']); ?></dd>
            </div>
          <?php endif; ?>
          <?php if ($first_package): ?>
            <div class="event-offer-summary__order-row"<?= $only('tour'); ?>>
              <dt class="event-offer-summary__order-label">Проживание</dt>
              <dd class="event-offer-summary__order-value" data-summary="package"><?= esc_html($first_package['title']); ?> <span class="event-offer-summary__stars"><span class="numfont"><?= esc_html($first_package['stars']); ?></span><?= $star; ?></span></dd>
            </div>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($event['has_tickets']): ?>
          <div class="event-offer-summary__order-row"<?= $only('ticket'); ?>>
            <dt class="event-offer-summary__order-label">Билет</dt>
            <dd class="event-offer-summary__order-value" data-summary="ticket"></dd>
          </div>
        <?php endif; ?>
      </dl>

      <?php if ($event['has_tour']): ?>
        <div class="event-offer-summary__price-box"<?= $only('tour'); ?>>
          <span class="event-offer-summary__price numfont<?= $event['tour_price'] ? ' js-event-price' : ''; ?>" data-offer-summary-price="tour"<?= $event['crosstour'] ? ' data-crosstour-price' : ''; ?><?= bsi_event_price_attrs($event['tour_price'], true); ?>><?= esc_html($event['tour_price'] ? bsi_event_price_text($event['tour_price'], true) : 'Цена по запросу'); ?></span>
          <span class="event-offer-summary__note">за человека при размещении вдвоём</span>
        </div>
      <?php endif; ?>
      <?php if ($event['has_tickets']): ?>
        <div class="event-offer-summary__price-box"<?= $only('ticket'); ?>>
          <span class="event-offer-summary__price numfont<?= !empty($offers['ticket_min']) ? ' js-event-price' : ''; ?>" data-offer-summary-price="ticket"<?= bsi_event_price_attrs($offers['ticket_min'] ?? null, true); ?>><?= esc_html(!empty($offers['ticket_min']) ? bsi_event_price_text($offers['ticket_min'], true) : 'по запросу'); ?></span>
          <span class="event-offer-summary__note">за один билет</span>
        </div>
      <?php endif; ?>

      <div class="event-offer-summary__actions">
        <?php if ($event['has_tour']): ?>
          <a href="<?= esc_url(($first['tourHref'] ?? '') !== '' ? $first['tourHref'] : '#'); ?>" class="btn btn-accent event-offer-summary__btn"
            data-offer-tour-link target="_blank" rel="nofollow noopener"<?= $only('tour'); ?>>Забронировать тур</a>
          <button type="button" class="btn btn-accent event-offer-summary__btn" data-offer-action="tour_sector" hidden>Оставить заявку</button>
        <?php endif; ?>
        <?php if ($event['has_tickets']): ?>
          <button type="button" class="btn btn-accent event-offer-summary__btn" data-offer-action="ticket"<?= $only('ticket'); ?>>Купить билет</button>
        <?php endif; ?>
        <button type="button" class="btn btn-gray event-offer-summary__btn" data-offer-consult>Получить консультацию</button>
      </div>
    </div>

    <div class="single-event__widget-extra">
      <label class="ui-checkbox ui-currency-toggle">
        <input type="checkbox" class="ui-checkbox__input js-education-show-original-currency" name="show_original_currency_event" value="1">
        <span class="ui-checkbox__mark"></span>
        <span class="ui-checkbox__text">Показать в валюте</span>
      </label>
      <p class="single-event__widget-phones">
        <?php foreach ($event['phones'] as $phone): ?>
          <a class="numfont" href="tel:<?= esc_attr($phone['tel']); ?>"><?= esc_html($phone['label']); ?></a>
        <?php endforeach; ?>
      </p>
    </div>
  </div>
</aside>

<div class="event-offer-bar" data-offer-bar hidden>
  <div class="event-offer-bar__info">
    <span class="event-offer-bar__label" data-offer-bar-label></span>
    <span class="event-offer-bar__price numfont" data-offer-bar-price></span>
  </div>
  <button type="button" class="event-offer-bar__toggle" data-offer-sheet-open><?= $chevron; ?></button>
</div>
<div class="event-offer-sheet-backdrop" data-offer-sheet-close hidden></div>
