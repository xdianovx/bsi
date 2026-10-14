<?php
/**
 * Сайдбар события: группы «цена» и «действия» (вторичное — валюта, телефоны — в single-event.php).
 * Дата и формат выбираются в контенте (single-event.php, #format), здесь их не дублируем.
 *   Тур с билетом → «Забронировать тур» (Самотур, UTM добавляет JS) + «Получить консультацию»;
 *   Только билет  → «Купить билет» (заявка) + «Получить консультацию».
 * Логика — js/modules/event-offer.js.
 *
 *   get_template_part('template-parts/event/offer-widget', null, [...]);
 */

$offers = (array) ($args['offers'] ?? []);
$has_tickets = !empty($args['has_tickets']);
$has_tour = !empty($args['has_tour']);
$tour_price = $args['tour_price'] ?? null;
$default_format = (string) ($args['default_format'] ?? ($has_tour ? 'tour' : 'ticket'));
$both = $has_tickets && $has_tour;
$first_date = $args['json']['dates'][0] ?? [];
$tour_href = (string) ($first_date['tourHref'] ?? '');

// Атрибуты блока, видимого только в одном формате (при одном формате — всегда).
$only = static function (string $format) use ($both, $default_format): string {
  if (!$both) {
    return '';
  }
  return ' data-offer-format-only="' . esc_attr($format) . '"' . ($format !== $default_format ? ' hidden' : '');
};
?>
<div class="event-offer-summary" data-offer-summary>
  <?php if ($has_tour): ?>
    <div class="event-offer-summary__price-box"<?= $only('tour'); ?>>
      <span class="event-offer-summary__price numfont<?= $tour_price ? ' js-event-price' : ''; ?>" data-offer-summary-price
        <?= !empty($args['crosstour']) ? ' data-crosstour-price' : ''; ?><?= bsi_event_price_attrs($tour_price, true); ?>><?= esc_html($tour_price ? bsi_event_price_text($tour_price, true) : 'по запросу'); ?></span>
      <span class="event-offer-summary__note">за человека при размещении вдвоём</span>
    </div>
  <?php endif; ?>
  <?php if ($has_tickets): ?>
    <div class="event-offer-summary__price-box"<?= $only('ticket'); ?>>
      <span class="event-offer-summary__price numfont<?= !empty($offers['ticket_min']) ? ' js-event-price' : ''; ?>" data-offer-summary-price<?= bsi_event_price_attrs($offers['ticket_min'] ?? null, true); ?>><?= esc_html(!empty($offers['ticket_min']) ? bsi_event_price_text($offers['ticket_min'], true) : 'по запросу'); ?></span>
      <span class="event-offer-summary__note">за один билет</span>
    </div>
  <?php endif; ?>

  <div class="event-offer-summary__actions">
    <?php if ($has_tour): ?>
      <?php /* Ссылка на Самотур: дата/ночи и UTM подставляет JS. Нет ссылки — заявка на тур. */ ?>
      <a href="<?= esc_url($tour_href !== '' ? $tour_href : '#'); ?>" class="btn btn-accent event-offer-summary__btn"
        data-offer-tour-link target="_blank" rel="nofollow noopener"<?= $only('tour'); ?>>Забронировать тур</a>
    <?php endif; ?>
    <?php if ($has_tickets): ?>
      <button type="button" class="btn btn-accent event-offer-summary__btn" data-offer-action="ticket"<?= $only('ticket'); ?>>Купить билет</button>
    <?php endif; ?>
    <button type="button" class="btn btn-gray event-offer-summary__btn" data-offer-consult>Получить консультацию</button>
  </div>

  <?php /* Мобильная липкая панель: появляется, когда сайдбар ушёл за экран. */ ?>
  <div class="event-offer-bar" data-offer-bar hidden>
    <div class="event-offer-bar__info">
      <span class="event-offer-bar__label" data-offer-bar-label></span>
      <span class="event-offer-bar__price numfont" data-offer-bar-price></span>
    </div>
    <button type="button" class="btn btn-accent event-offer-bar__btn" data-offer-bar-action>Забронировать</button>
  </div>
</div>
