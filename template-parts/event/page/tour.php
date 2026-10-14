<?php
/**
 * «Тур с билетом» — одна карточка с шагами. Макет 1961:1675:
 * Даты поездки (конструктор Само) → Проживание (пакеты) → Билет в туре → Входит / Оплачивается отдельно.
 * Пакеты: из админки (отели по пакетам), цены — из Само (crosstour-event.js подставляет по названию пакета).
 */
$event = $args['event'];
$both = $args['both'];
$json = $args['json'];
$sectors = $event['offers']['sectors'];
$keys = array_values(array_filter(array_column($json['dates'], 'key'), static fn($k) => $k !== BSI_EVENT_PASS_KEY));
$first_key = $keys[0] ?? '';
$tour_sector = $event['tour_sector'];
$packages = $event['packages'];
$calendar = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>';
$star = '<svg class="single-event__star" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>';
?>
<section class="single-event__tour single-event__column-card" id="tur"<?= $both ? ' data-offer-format-only="tour"' . ($event['default_format'] !== 'tour' ? ' hidden' : '') : ''; ?>>
  <div class="single-event__tour-head">
    <h2 class="h2">Тур с билетом</h2>
    <?php if ($tour_sector !== ''): ?>
      <p class="single-event__group-lead">В тур входит билет <b><?= esc_html($tour_sector); ?></b>. Другой билет — <a href="#" class="single-event__group-link" data-offer-consult>через консультацию</a>.</p>
    <?php endif; ?>
  </div>

  <?php if ($event['crosstour']): ?>
    <div class="single-event__tour-step" data-crosstour-builder id="podbor-tura" data-samo-url="<?= esc_attr($event['samo_url']); ?>">
      <h3 class="single-event__tour-step-title">Даты поездки</h3>
      <div class="single-event__builder-skeleton" data-builder-skeleton>
        <div class="single-event__builder-skeleton-chips">
          <?php for ($i = 0; $i < 5; $i++): ?><span class="ui-skeleton single-event__builder-skeleton-chip"></span><?php endfor; ?>
        </div>
        <div class="single-event__builder-skeleton-chips">
          <?php for ($i = 0; $i < 3; $i++): ?><span class="ui-skeleton single-event__builder-skeleton-chip"></span><?php endfor; ?>
        </div>
      </div>
      <div class="single-event__builder" data-builder-controls hidden>
        <div class="single-event__builder-group" data-builder-group="dates">
          <span class="single-event__builder-label">Заезд</span>
          <div class="ui-choices" data-builder-dates></div>
        </div>
        <div class="single-event__builder-group" data-builder-group="nights">
          <span class="single-event__builder-label">Ночей</span>
          <div class="ui-choices" data-builder-nights></div>
        </div>
        <p class="single-event__builder-summary"><?= $calendar; ?><span class="numfont" data-builder-summary></span></p>
      </div>
      <div class="single-event__tour-empty" data-builder-error hidden>
        <p class="single-event__tour-empty-title">Цены тура сейчас недоступны</p>
        <p class="single-event__tour-empty-text">Менеджер посчитает тур на нужные даты и пришлёт варианты.</p>
        <button type="button" class="btn btn-gray single-event__tour-empty-btn" data-offer-consult>Получить расчёт</button>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($packages) || $event['crosstour']): ?>
    <div class="single-event__tour-step" data-crosstour-hotels id="varianty-prozhivaniya">
      <h3 class="single-event__tour-step-title">Проживание</h3>
      <div class="single-event__packages" data-packages>
        <?php foreach ($packages as $i => $pkg): ?>
          <label class="ui-option ui-option--top single-event__package-option<?= $i === 0 ? ' is-active' : ''; ?>" data-offer-package="<?= esc_attr($pkg['key']); ?>" data-package-title="<?= esc_attr($pkg['title']); ?>" data-package-stars="<?= esc_attr($pkg['stars']); ?>">
            <input type="radio" class="ui-checkbox__input" name="event_tour_package" value="<?= esc_attr($pkg['key']); ?>"<?= $i === 0 ? ' checked' : ''; ?>>
            <span class="ui-checkbox__mark ui-checkbox__mark--mono"></span>
            <span class="ui-option__body">
              <span class="ui-option__label"><?= esc_html($pkg['title']); ?></span>
              <?php if ($pkg['stars'] !== ''): ?>
                <span class="ui-option__sub single-event__package-meta"><span class="numfont"><?= esc_html($pkg['stars']); ?></span><?= $star; ?><span data-package-meta></span></span>
              <?php endif; ?>
            </span>
            <span class="ui-option__price numfont" data-package-price>по запросу</span>
          </label>
        <?php endforeach; ?>
      </div>
      <?php foreach ($packages as $i => $pkg): ?>
        <div class="single-event__package-hotels" data-offer-package-hotels="<?= esc_attr($pkg['key']); ?>"<?= $i === 0 ? '' : ' hidden'; ?>>
          <p class="single-event__package-hotels-label">Отели пакета «<?= esc_html($pkg['title']); ?>»</p>
          <?php foreach ($pkg['hotels'] as $hotel): ?>
            <div class="single-event__package-hotel">
              <p class="single-event__package-hotel-head">
                <span class="single-event__package-hotel-name"><?= esc_html($hotel['name']); ?></span>
                <?php if ((int) $hotel['stars'] > 0): ?>
                  <span class="single-event__package-hotel-stars"><span class="numfont"><?= esc_html($hotel['stars']); ?></span><?= $star; ?></span>
                <?php endif; ?>
              </p>
              <?php if ($hotel['descr'] !== ''): ?>
                <p class="single-event__package-hotel-text"><?= esc_html($hotel['descr']); ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <p class="single-event__package-hotels-note">или равноценный отель той же категории</p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php $tour_sectors = !empty($sectors) && $tour_sector !== ''; ?>
  <?php if ($tour_sectors || $event['scheme_url'] !== ''): ?>
    <div class="single-event__tour-step">
      <h3 class="single-event__tour-step-title">Билет в туре</h3>
      <div class="single-event__tickets-body<?= $event['scheme_url'] === '' ? ' single-event__tickets-body--no-scheme' : ''; ?>">
        <?php if ($event['scheme_url'] !== ''): ?>
          <figure class="single-event__venue">
            <a class="single-event__venue-map" href="<?= esc_url($event['scheme_url']); ?>" data-fancybox="event-scheme-tour">
              <img src="<?= esc_url($event['scheme_url']); ?>" alt="Схема площадки" loading="lazy">
            </a>
            <figcaption class="single-event__venue-map-note">Схема примерная: расположение секторов зависит от площадки и шоу. Точное место подтвердит менеджер.</figcaption>
          </figure>
        <?php endif; ?>
        <?php if ($tour_sectors): ?>
        <div class="single-event__tickets-list">
          <?php foreach ($sectors as $sec): ?>
            <?php $in_tour = $sec['name'] === $tour_sector; ?>
            <label class="ui-option ui-option--top single-event__ticket-option<?= $in_tour ? ' is-active' : ''; ?>" data-offer-tour-sector="<?= esc_attr($sec['name']); ?>"<?= $in_tour ? ' data-in-tour' : ''; ?>>
              <input type="radio" class="ui-checkbox__input" name="event_tour_sector" value="<?= esc_attr($sec['name']); ?>"<?= $in_tour ? ' checked' : ''; ?>>
              <span class="ui-checkbox__mark ui-checkbox__mark--mono"></span>
              <span class="ui-option__body">
                <span class="ui-option__label">
                  <?php if ($sec['color'] !== ''): ?>
                    <span class="single-event__ticket-dot" style="background:<?= esc_attr($sec['color']); ?>"></span>
                  <?php endif; ?>
                  <?= esc_html($sec['name']); ?>
                </span>
                <?php $sub = implode(' · ', array_filter([$sec['description'], $in_tour ? '' : 'доплата к туру'])); ?>
                <?php if ($sub !== ''): ?>
                  <span class="ui-option__sub"><?= esc_html($sub); ?></span>
                <?php endif; ?>
              </span>
              <?php if ($in_tour): ?>
                <span class="ui-option__price">в туре</span>
              <?php else: ?>
                <?php foreach ($keys as $key): ?>
                  <?php $extra = bsi_event_sector_tour_extra($sec, $sectors, $tour_sector, $key); ?>
                  <span class="ui-option__price numfont<?= $extra ? ' js-event-price' : ''; ?>" data-offer-key="<?= esc_attr($key); ?>"<?= $key !== $first_key ? ' hidden' : ''; ?><?php if ($extra): ?><?= bsi_event_price_attrs($extra); ?> data-price-prefix="+"<?php endif; ?>><?= esc_html($extra ? '+' . number_format((int) $extra['rub'], 0, ',', ' ') . ' ₽' : 'по запросу'); ?></span>
                <?php endforeach; ?>
              <?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php get_template_part('template-parts/event/page/price-columns', null, ['event' => $event]); ?>
</section>
