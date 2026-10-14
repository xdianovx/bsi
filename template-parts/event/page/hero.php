<?php
/**
 * Первый экран события. Макет: десктоп 1957:1372, мобилка 2010:7937.
 * Десктоп — фото фоном, затемнение слева (псевдоэлемент), заголовок → факты → кнопки.
 * Мобилка — фото полосой сверху, ниже чёрная плашка: заголовок, «время · площадка», цена, одна кнопка.
 */
$event = $args['event'];
$both = $args['both'];
$finished = $event['finished'];
$price = $event['price_min'];

if ($finished) {
  $cta_label = 'Сообщить о новых датах';
  $cta_href = '#status';
} elseif ($both) {
  $cta_label = 'Купить билет или тур';
  $cta_href = $args['choose_href'];
} elseif ($event['has_tour']) {
  $cta_label = 'Купить тур';
  $cta_href = $args['choose_href'];
} elseif ($event['has_tickets']) {
  $cta_label = 'Купить билет';
  $cta_href = $args['choose_href'];
} else {
  // Ни дат, ни секторов, ни тура (старая запись) — выбирать нечего, только консультация.
  $cta_label = '';
  $cta_href = '';
}

$facts = array_filter([
  'Даты' => $event['dates_label'],
  'Начало' => $event['time'],
  'Площадка' => $event['place'],
], static fn($v) => trim((string) $v) !== '');
$meta = implode(' · ', array_filter([$event['time'], $event['place']]));
?>
<section class="single-event__hero<?= $event['cover_url'] === '' ? ' single-event__hero--no-poster' : ''; ?>"
  <?php if ($event['cover_url'] !== ''): ?>style="--single-event-hero-bg:url(<?= esc_url($event['cover_url']); ?>)"<?php endif; ?>>
  <div class="single-event__hero-media"></div>

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="single-event__hero-breadcrumbs breadcrumbs" id="breadcrumbs"><p>', '</p></div>');
  }
  ?>

  <div class="single-event__hero-content">
    <h1 class="single-event__hero-title"><?= esc_html($event['title']); ?></h1>

    <?php if ($event['subtitle'] !== ''): ?>
      <p class="single-event__hero-subtitle"><?= esc_html($event['subtitle']); ?></p>
    <?php endif; ?>

    <?php if (!empty($facts) || ($price && !$finished)): ?>
      <ul class="single-event__hero-facts">
        <?php foreach ($facts as $label => $value): ?>
          <li class="single-event__hero-fact">
            <span class="single-event__hero-fact-label"><?= esc_html($label); ?></span>
            <span class="single-event__hero-fact-value"><?= esc_html($value); ?></span>
          </li>
        <?php endforeach; ?>
        <?php if ($price && !$finished): ?>
          <li class="single-event__hero-fact single-event__hero-fact--price">
            <span class="single-event__hero-fact-label">Цена</span>
            <span class="single-event__hero-fact-value numfont js-event-price"<?= bsi_event_price_attrs($price, true); ?>><?= esc_html(bsi_event_price_text($price, true)); ?></span>
          </li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>

    <?php if ($meta !== ''): ?>
      <p class="single-event__hero-meta"><?= esc_html($meta); ?></p>
    <?php endif; ?>
    <?php if ($price && !$finished): ?>
      <p class="single-event__hero-price numfont js-event-price"<?= bsi_event_price_attrs($price, true); ?>><?= esc_html(bsi_event_price_text($price, true)); ?></p>
    <?php endif; ?>

    <div class="single-event__hero-actions">
      <?php if ($cta_label !== ''): ?>
        <a href="<?= esc_attr($cta_href); ?>" class="btn btn-accent single-event__hero-btn"><?= esc_html($cta_label); ?></a>
      <?php endif; ?>
      <?php if (!$finished): ?>
        <button type="button" class="btn single-event__hero-btn <?= $cta_label !== '' ? 'single-event__hero-btn--ghost' : 'btn-accent'; ?>" data-offer-consult>Получить консультацию</button>
      <?php endif; ?>
    </div>
  </div>
</section>
