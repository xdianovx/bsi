<?php
/**
 * «Билеты» — формат «Только билет». Макет 1963:1731: схема 440 слева, список секторов справа.
 * Сектор выбирается (radio) — в сайдбаре «Билет» и цена; «Купить билет» открывает заявку с сектором.
 */
$event = $args['event'];
$both = $args['both'];
$json = $args['json'];
$sectors = $event['offers']['sectors'];
$keys = array_column($json['dates'], 'key');
$first_key = $keys[0] ?? '';

// Выбран по умолчанию: сектор тура, иначе самый дешёвый на первую дату.
$selected = $event['tour_sector'];
if ($selected === '' || !in_array($selected, array_column($sectors, 'name'), true)) {
  $min = null;
  foreach ($sectors as $sec) {
    $p = $sec['prices'][$first_key] ?? null;
    if ($p && ($min === null || $p['rub'] < $min)) {
      $min = $p['rub'];
      $selected = $sec['name'];
    }
  }
}
?>
<section class="single-event__tickets single-event__column-card" id="bilety"<?= $both ? ' data-offer-format-only="ticket"' . ($event['default_format'] !== 'ticket' ? ' hidden' : '') : ''; ?>>
  <div class="single-event__tickets-head">
    <h2 class="h2">Билеты</h2>
    <p class="single-event__tickets-lead"><?= empty($sectors)
      ? 'Цены и наличие билетов — по запросу: оставьте заявку, менеджер подберёт места.'
      : 'Цены за один билет на выбранную дату. Наличие мест и итоговую цену подтвердит менеджер.'; ?></p>
  </div>
  <div class="single-event__tickets-body<?= $event['scheme_url'] === '' ? ' single-event__tickets-body--no-scheme' : ''; ?>">
    <?php if ($event['scheme_url'] !== ''): ?>
      <figure class="single-event__venue">
        <a class="single-event__venue-map" href="<?= esc_url($event['scheme_url']); ?>" data-fancybox="event-scheme">
          <img src="<?= esc_url($event['scheme_url']); ?>" alt="Схема площадки" loading="lazy">
        </a>
        <figcaption class="single-event__venue-map-note">Схема примерная: расположение секторов зависит от площадки и шоу.</figcaption>
      </figure>
    <?php endif; ?>
    <?php if (!empty($sectors)): ?>
    <div class="single-event__tickets-list">
      <p class="single-event__tickets-label">Сектор</p>
      <?php foreach ($sectors as $sec): ?>
        <?php $active = $sec['name'] === $selected; ?>
        <label class="ui-option ui-option--top single-event__ticket-option<?= $active ? ' is-active' : ''; ?>" data-offer-ticket="<?= esc_attr($sec['name']); ?>">
          <input type="radio" class="ui-checkbox__input" name="event_ticket_sector" value="<?= esc_attr($sec['name']); ?>"<?= $active ? ' checked' : ''; ?>>
          <span class="ui-checkbox__mark ui-checkbox__mark--mono"></span>
          <span class="ui-option__body">
            <span class="ui-option__label">
              <?php if ($sec['color'] !== ''): ?>
                <span class="single-event__ticket-dot" style="background:<?= esc_attr($sec['color']); ?>"></span>
              <?php endif; ?>
              <?= esc_html($sec['name']); ?>
            </span>
            <?php if ($sec['description'] !== ''): ?>
              <span class="ui-option__sub"><?= esc_html($sec['description']); ?></span>
            <?php endif; ?>
          </span>
          <?php foreach ($keys as $key): ?>
            <?php $sp = $sec['prices'][$key] ?? null; ?>
            <span class="ui-option__price numfont<?= $sp ? ' js-event-price' : ''; ?>" data-offer-key="<?= esc_attr($key); ?>"<?= bsi_event_price_attrs($sp, true); ?><?= $key !== $first_key ? ' hidden' : ''; ?>><?= esc_html($sp ? bsi_event_price_text($sp, true) : 'по запросу'); ?></span>
          <?php endforeach; ?>
        </label>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php if (!$event['has_tour']): ?>
    <?php get_template_part('template-parts/event/page/price-columns', null, ['event' => $event]); ?>
  <?php endif; ?>
</section>
