<?php
/**
 * «Как проходит бронирование» на странице события — три шага, текст общий для всех событий.
 * Под шагами — ссылки на доступные форматы (макет 1957:1558).
 *
 *   get_template_part('template-parts/event/booking-steps', null, ['has_tour' => true, 'has_tickets' => true]);
 */

$has_tour = !empty($args['has_tour']);
$has_tickets = !empty($args['has_tickets']);
$choose_href = $args['choose_href'] ?? '#format';
$what = array_filter([
  $has_tickets ? 'на билет — через форму на сайте' : '',
  $has_tour ? 'на тур — онлайн или у менеджера' : '',
]);
$steps = [
  [
    'title' => 'Оставляете заявку',
    'text' => !empty($what) ? mb_strtoupper(mb_substr(implode(', ', $what), 0, 1)) . mb_substr(implode(', ', $what), 1) . '.' : 'Оставьте контакты — менеджер свяжется с вами.',
  ],
  [
    'title' => 'Менеджер подтверждает',
    'text' => 'Проверяет наличие мест и итоговую цену, отвечает на вопросы.',
  ],
  [
    'title' => 'Оплата и билеты',
    'text' => 'После оплаты присылаем электронные билеты и документы по поездке.',
  ],
];
?>
<section class="single-event__column-card single-event__steps" id="kak-zabronirovat">
  <h2 class="h2">Как проходит бронирование</h2>
  <ol class="single-event__steps-list">
    <?php foreach ($steps as $i => $step): ?>
      <li class="single-event__step">
        <span class="single-event__step-head">
          <span class="single-event__step-num numfont"><?= (int) $i + 1; ?></span>
          <?php if ($i < count($steps) - 1): ?><span class="single-event__step-line"></span><?php endif; ?>
        </span>
        <span class="single-event__step-body">
          <span class="single-event__step-title"><?= esc_html($step['title']); ?></span>
          <span class="single-event__step-text"><?= esc_html($step['text']); ?></span>
          <?php if ($i === 0 && ($has_tickets || $has_tour)): ?>
            <span class="single-event__step-links">
              <?php if ($has_tickets): ?>
                <button type="button" class="single-event__step-link" data-offer-action="ticket">Купить билет</button>
              <?php endif; ?>
              <?php if ($has_tour): ?>
                <a href="<?= esc_attr($choose_href); ?>" class="single-event__step-link">Купить тур ↑</a>
              <?php endif; ?>
            </span>
          <?php endif; ?>
        </span>
      </li>
    <?php endforeach; ?>
  </ol>
  <p class="single-event__steps-note">Условия возврата зависят от организатора — менеджер расскажет о них до оплаты.</p>
</section>
