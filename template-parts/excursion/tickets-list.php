<?php
/**
 * Список билетов экскурсии (single-excursion.php).
 *
 * @var array<int, array> $excursion_tickets_rows  Output of bsi_get_excursion_tickets_rows()
 * @var int    $excursion_post_id
 * @var string $excursion_post_title
 */

$rows = get_query_var('excursion_tickets_rows') ?: [];
$excursion_id = (int) (get_query_var('excursion_post_id') ?: 0);
$excursion_title = (string) (get_query_var('excursion_post_title') ?: '');

if (empty($rows) || !is_array($rows)) {
  return;
}
?>

<section class="single-event__dates-section single-excursion__tickets-section">
  <div class="single-excursion__tickets-head">
    <h2 class="h2 single-event__dates-title single-excursion__tickets-title">Стоимость билетов</h2>
    <label class="ui-checkbox single-excursion__tickets-currency-toggle">
      <input type="checkbox"
             class="ui-checkbox__input js-education-show-original-currency"
             name="show_original_currency_tickets"
             value="1">
      <span class="ui-checkbox__mark"></span>
      <span class="ui-checkbox__text">Стоимость в валюте</span>
    </label>
  </div>
  <ul class="single-event__dates-list single-excursion__tickets-list">
    <?php foreach ($rows as $r): ?>
      <?php
      $name = (string) ($r['name'] ?? '');
      $description = (string) ($r['description'] ?? '');
      if ($name === '' && $description === '' && empty($r['price_rub'])) {
        continue;
      }
      $price_rub = isset($r['price_rub']) ? (int) $r['price_rub'] : 0;
      $price_original = isset($r['price_original']) ? (float) $r['price_original'] : 0;
      $price_currency = (string) ($r['price_currency'] ?? '');
      ?>
      <li class="single-event__dates-row single-excursion__ticket-row">
        <div class="single-event__dates-row-left">
          <span class="single-event__dates-dot" aria-hidden="true"></span>
          <div class="single-event__dates-meta">
            <?php if ($name !== ''): ?>
              <span class="single-event__dates-meta-txt single-excursion__ticket-name"><?= esc_html($name); ?></span>
            <?php endif; ?>
            <?php if ($description !== ''): ?>
              <span class="single-event__dates-sep" aria-hidden="true"></span>
              <span class="single-event__dates-meta-txt single-excursion__ticket-desc"><?= esc_html($description); ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="single-event__dates-row-right">
          <?php if ($price_rub > 0): ?>
            <span class="single-event__dates-price numfont js-excursion-price"
                  data-price-rub="<?= esc_attr((string) $price_rub); ?>"
                  <?php if ($price_original > 0 && $price_currency !== ''): ?>
                  data-price-original="<?= esc_attr((string) $price_original); ?>"
                  data-price-currency="<?= esc_attr($price_currency); ?>"
                  <?php endif; ?>><?= esc_html(number_format($price_rub, 0, ',', ' ')); ?> ₽</span>
          <?php else: ?>
            <span class="single-event__dates-price numfont">по запросу</span>
          <?php endif; ?>
          <span class="single-event__dates-sep" aria-hidden="true"></span>
          <span class="single-event__dates-book-wrap">
            <button type="button" class="single-event__dates-book js-excursion-booking-btn"
                    data-excursion-id="<?= esc_attr((string) $excursion_id); ?>"
                    data-excursion-title="<?= esc_attr($excursion_title); ?>"
                    data-excursion-date="<?= esc_attr($name); ?>">забронировать</button>
          </span>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
