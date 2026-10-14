<?php
/**
 * «В стоимость входит / Оплачивается отдельно» — в туре и, если тура нет, в «Билетах»
 * (старые события «только билеты» с заполненными списками, на проде 14.10).
 */
$event = $args['event'];
?>
  <?php if (!empty($event['included']) || !empty($event['extra'])): ?>
    <div class="single-event__price-columns">
      <?php if (!empty($event['included'])): ?>
        <div class="single-event__tour-step">
          <h3 class="single-event__tour-step-title">В стоимость входит</h3>
          <ul class="single-event__price-list">
            <?php foreach ($event['included'] as $item): ?>
              <li class="single-event__included-item">
                <svg class="single-event__included-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                <span class="single-event__item-body">
                  <span class="single-event__item-text"><?= esc_html($item['text']); ?></span>
                  <?php if ($item['note'] !== ''): ?>
                    <span class="single-event__item-note"><?= esc_html($item['note']); ?></span>
                  <?php endif; ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if (!empty($event['extra'])): ?>
        <div class="single-event__tour-step">
          <h3 class="single-event__tour-step-title">Оплачивается отдельно</h3>
          <ul class="single-event__price-list">
            <?php foreach ($event['extra'] as $item): ?>
              <li class="single-event__extra-item">
                <svg class="single-event__extra-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
                <span class="single-event__item-body">
                  <span class="single-event__item-text"><?= esc_html($item['text']); ?></span>
                  <?php if ($item['note'] !== ''): ?>
                    <span class="single-event__item-note"><?= esc_html($item['note']); ?></span>
                  <?php endif; ?>
                </span>
                <?php if ($item['price'] !== ''): ?>
                  <span class="single-event__extra-price numfont"><?= esc_html($item['price']); ?></span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
