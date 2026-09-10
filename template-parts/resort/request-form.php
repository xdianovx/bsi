<?php

/**
 * Секция заявки на странице курорта — форма прямо на странице, без модалки.
 *
 * Ожидает в $args: resort, resort_locative, resort_accusative, country, section.
 * Обработчик AJAX action `resort_request` — inc/requests/ajax-resort-request.php.
 * Биндинг и цели Метрики — js/modules/forms/resort-request-form.js.
 */

$resort = (string) ($args['resort'] ?? '');
$locative = (string) ($args['resort_locative'] ?? '');
$accusative = (string) ($args['resort_accusative'] ?? '');
$country = (string) ($args['country'] ?? '');
$section = (string) ($args['section'] ?? 'hub');

/* Заголовок, текст и цель Метрики зависят от раздела: у обучения свой
   запрос и своя воронка, мешать его с общими заявками по курорту нельзя. */
$copy = [
  'obuchenie' => [
    'goal' => 'education_request',
    'heading' => $locative !== '' ? 'Учёба в ' . $locative : 'Подобрать программу обучения',
    'text' => 'Расскажем про школы и курсы, поможем выбрать программу под возраст и уровень '
      . 'языка, посчитаем стоимость с проживанием и перелётом.',
    'button' => 'Подобрать программу',
  ],
  'default' => [
    'goal' => 'resort_request',
    'heading' => $accusative !== '' ? 'Собираетесь в ' . $accusative . '?' : 'Собираетесь в поездку?',
    'text' => 'Оставьте контакты — менеджер подберёт отель и программу, посчитает стоимость '
      . 'и ответит на вопросы.',
    'button' => 'Отправить заявку',
  ],
];

$cta = $copy[$section] ?? $copy['default'];
?>

<section class="resort-request" id="request">
  <div class="resort-request-intro">
    <h2 class="resort-request-title"><?= esc_html($cta['heading']); ?></h2>
    <p class="resort-request-text"><?= esc_html($cta['text']); ?></p>
  </div>

  <form class="resort-request-form js-resort-request-form"
        data-ym-goal="<?= esc_attr($cta['goal']); ?>" novalidate>
    <input type="hidden" name="action" value="resort_request">
    <input type="hidden" name="resort_name" class="js-form-resort-name" value="<?= esc_attr($resort); ?>">
    <input type="hidden" name="resort_country" class="js-form-resort-country" value="<?= esc_attr($country); ?>">
    <input type="hidden" name="resort_section" class="js-form-resort-section" value="<?= esc_attr($section); ?>">
    <input type="hidden" name="page_url" class="js-form-page-url">
    <input type="hidden" name="referrer" class="js-form-referrer">

    <div class="resort-request-row">
      <div class="input-item resort-request-field">
        <label for="resort-request-name">Имя <span class="resort-request-req">*</span></label>
        <input type="text" id="resort-request-name" name="name" required data-field="name" autocomplete="name"
          placeholder="Ваше имя">
        <span class="resort-request-error js-field-error" data-error-for="name"></span>
      </div>

      <div class="input-item resort-request-field">
        <label for="resort-request-phone">Телефон <span class="resort-request-req">*</span></label>
        <input type="tel" id="resort-request-phone" name="phone" class="js-phone-mask" required data-field="phone"
          autocomplete="tel" placeholder="+7 (___) ___-__-__">
        <span class="resort-request-error js-field-error" data-error-for="phone"></span>
      </div>

      <div class="input-item resort-request-field">
        <label for="resort-request-email">Почта</label>
        <input type="email" id="resort-request-email" name="email" data-field="email" autocomplete="email"
          placeholder="Необязательно">
        <span class="resort-request-error js-field-error" data-error-for="email"></span>
      </div>
    </div>

    <div class="input-item resort-request-field">
      <label for="resort-request-comment">Комментарий</label>
      <textarea id="resort-request-comment" name="comment" rows="3" data-field="comment"
        placeholder="Даты, сколько человек, что интересует"></textarea>
      <span class="resort-request-error js-field-error" data-error-for="comment"></span>
    </div>

    <div class="resort-request-footer">
      <?php
      if (function_exists('bsi_render_privacy_consent_checkbox')) {
        bsi_render_privacy_consent_checkbox([
          'variant' => 'input-item',
          'checkbox_id' => 'resort-request-privacy',
        ]);
      }
      ?>

      <button type="submit" class="btn btn-accent resort-request-submit"
        data-default-label="<?= esc_attr($cta['button']); ?>">
        <?= esc_html($cta['button']); ?>
      </button>
    </div>

    <p class="resort-request-success js-resort-request-success" hidden>
      Заявка отправлена — менеджер свяжется с вами в ближайшее время.
    </p>
  </form>
</section>
