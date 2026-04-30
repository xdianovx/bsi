<?php
/**
 * Секция формы заявки MICE (как на page-bsimice).
 *
 * Перед подключением: set_query_var('mice_consultation_cfg', [...])
 *
 * @var array $cfg {
 *   @type string $section_class Классы на <section>
 *   @type string $section_id    id якоря
 *   @type string $heading
 *   @type string $description
 * }
 */

$cfg = get_query_var('mice_consultation_cfg', []);
$cfg = wp_parse_args(is_array($cfg) ? $cfg : [], [
  'section_class' => 'visa-page-consultation__section bsimice-page-consultation',
  'section_id' => 'bsimice-contact',
  'heading' => 'Бесплатная консультация',
  'description' => 'Оставьте заявку — обсудим формат MICE-мероприятия и подберём решение под ваши задачи',
]);

?>

<section class="<?php echo esc_attr($cfg['section_class']); ?>" id="<?php echo esc_attr($cfg['section_id']); ?>">
  <div class="container">
    <h2 class="h2"><?php echo esc_html($cfg['heading']); ?></h2>
    <p class="visa-consultation-form__descr"><?php echo esc_html($cfg['description']); ?></p>
    <form id="bsimice-consultation-form" class="visa-consultation-form">
      <div class="form-row form-row-3">
        <div class="input-item white">
          <label for="bsimice-name">Имя *</label>
          <input type="text" name="name" id="bsimice-name" placeholder="Введите ваше имя" required>
        </div>
        <div class="input-item white">
          <label for="bsimice-phone">Телефон *</label>
          <input type="tel" name="phone" id="bsimice-phone" placeholder="+7 (___) ___-__-__" required>
        </div>
        <div class="input-item white">
          <label for="bsimice-email">E-mail *</label>
          <input type="email" name="email" id="bsimice-email" placeholder="name@example.com" autocomplete="email"
            required>
        </div>
      </div>
      <div class="form-row">
        <div class="input-item white">
          <label for="bsimice-wishes">Пожелания</label>
          <textarea name="wishes" id="bsimice-wishes" rows="5"
            placeholder="Опишите задачу, формат мероприятия, численность, сроки"></textarea>
        </div>
      </div>
      <input type="hidden" name="source_page_title" value="<?php echo esc_attr(get_the_title()); ?>">
      <input type="hidden" name="source_page_url" value="<?php echo esc_attr(get_permalink()); ?>">
      <?php
      if (function_exists('bsi_render_privacy_consent_checkbox')) {
        bsi_render_privacy_consent_checkbox([
          'variant' => 'input-item',
          'checkbox_id' => 'bsimice-privacy',
          'wrapper_class' => 'white',
          'html_required' => true,
        ]);
      }
      ?>
      <div class="visa-consultation-form__bottom">
        <button type="submit" class="btn btn-accent fit-form__btn-submit">
          Отправить
        </button>
      </div>
      <div id="bsimice-consultation-form-status" class="form-status"></div>
    </form>
  </div>
</section>