<?php

/**
 * Контакты отеля: телефон, адрес, сайт. Есть только у отелей WordPress.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$contacts = $view['contacts'] ?? [];

if (!$contacts) {
  return;
}
?>

<section class="hp-section hp-contacts" id="hotel-contacts">
  <div class="container">
    <h2 class="h2 hp-section__title">Контакты отеля</h2>

    <ul class="hp-contacts__list">
      <?php if (!empty($contacts['phone'])): ?>
        <li class="hp-contacts__item">
          <span class="hp-contacts__label">Телефон</span>
          <a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $contacts['phone'])); ?>"><?= esc_html($contacts['phone']); ?></a>
        </li>
      <?php endif; ?>

      <?php if (!empty($contacts['address'])): ?>
        <li class="hp-contacts__item">
          <span class="hp-contacts__label">Адрес</span>
          <span><?= esc_html($contacts['address']); ?></span>
        </li>
      <?php endif; ?>

      <?php if (!empty($contacts['website'])): ?>
        <li class="hp-contacts__item">
          <span class="hp-contacts__label">Сайт</span>
          <a href="<?= esc_url($contacts['website']); ?>" target="_blank" rel="nofollow noopener">Официальный сайт</a>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</section>
