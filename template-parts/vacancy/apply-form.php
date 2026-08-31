<?php
/**
 * Форма отклика на вакансию (секция #vacancy-apply на single-vacancy.php).
 *
 * AJAX action `vacancy_apply` — inc/requests/ajax-vacancy-apply.php.
 * JS-биндинг — js/modules/forms/vacancy-apply-form.js.
 *
 * Резюме не сохраняется в медиатеку: файл уходит вложением в письмо и удаляется.
 *
 * @package bsi
 */

declare(strict_types=1);

$vacancy_id = (int) ($args['vacancy_id'] ?? get_the_ID());
$vacancy_title = get_the_title($vacancy_id);
$hh_url = trim((string) get_field('hh_url', $vacancy_id));
$max_mb = defined('BSI_VACANCY_RESUME_MAX_MB') ? BSI_VACANCY_RESUME_MAX_MB : 5;
?>

<section class="vacancy-apply" id="vacancy-apply">
  <div class="vacancy-apply__inner">
    <h2 class="h2 vacancy-apply__title">Откликнуться на вакансию</h2>
    <p class="vacancy-apply__lead">Заполните форму — скоро вернёмся с ответом.</p>

    <form class="vacancy-apply__form js-vacancy-apply-form" novalidate enctype="multipart/form-data">
      <input type="hidden" name="action" value="vacancy_apply">
      <input type="hidden" name="vacancy_id" value="<?php echo esc_attr((string) $vacancy_id); ?>">
      <input type="hidden" name="page_url" class="js-form-page-url" value="<?php echo esc_url(get_permalink($vacancy_id)); ?>">
      <?php wp_nonce_field('bsi_vacancy_apply', 'vacancy_nonce', false); ?>

      <?php // Ловушка для ботов: настоящий человек это поле не видит и не заполняет. ?>
      <div class="vacancy-apply__trap" aria-hidden="true">
        <label for="vacancy-apply-website">Не заполняйте это поле</label>
        <input type="text" id="vacancy-apply-website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="input-item vacancy-apply__field">
        <label for="vacancy-apply-name">Имя и фамилия <span class="vacancy-apply__req">*</span></label>
        <input type="text" id="vacancy-apply-name" name="name" required data-field="name" autocomplete="name"
          placeholder="Как к вам обращаться">
        <span class="form-error js-field-error" data-error-for="name"></span>
      </div>

      <div class="input-item vacancy-apply__field">
        <label for="vacancy-apply-phone">Телефон <span class="vacancy-apply__req">*</span></label>
        <input type="tel" id="vacancy-apply-phone" name="phone" class="js-phone-mask" required data-field="phone"
          autocomplete="tel" placeholder="+7 (___) ___-__-__">
        <span class="form-error js-field-error" data-error-for="phone"></span>
      </div>

      <div class="input-item vacancy-apply__field">
        <label for="vacancy-apply-email">Почта <span class="vacancy-apply__req">*</span></label>
        <input type="email" id="vacancy-apply-email" name="email" required data-field="email" autocomplete="email"
          placeholder="name@example.com">
        <span class="form-error js-field-error" data-error-for="email"></span>
      </div>

      <div class="vacancy-apply__field vacancy-apply__file-item">
        <span class="vacancy-apply__file-label" id="vacancy-apply-resume-label">Резюме</span>
        <input type="file" id="vacancy-apply-resume" name="resume" class="js-vacancy-resume"
          accept=".pdf,.doc,.docx,.rtf,.odt" data-field="resume">
        <label class="vacancy-apply__file" for="vacancy-apply-resume" aria-labelledby="vacancy-apply-resume-label">
          <span class="vacancy-apply__file-btn">Выбрать файл</span>
          <span class="vacancy-apply__file-name js-vacancy-resume-name">Файл не выбран</span>
        </label>
        <p class="vacancy-apply__hint">PDF, DOC, DOCX, RTF или ODT, до <?php echo (int) $max_mb; ?> МБ.</p>
        <span class="form-error js-field-error" data-error-for="resume"></span>
      </div>

      <div class="input-item vacancy-apply__field vacancy-apply__field--wide">
        <label for="vacancy-apply-comment">Комментарий</label>
        <textarea id="vacancy-apply-comment" name="comment" rows="4" data-field="comment"
          placeholder="Коротко об опыте, ссылка на резюме или портфолио"></textarea>
        <span class="form-error js-field-error" data-error-for="comment"></span>
      </div>

      <?php
      if (function_exists('bsi_render_privacy_consent_checkbox')) {
        bsi_render_privacy_consent_checkbox([
          'variant' => 'input-item',
          'checkbox_id' => 'vacancy-apply-privacy',
          'wrapper_class' => 'vacancy-apply__field--wide',
        ]);
      }
      ?>

      <div class="vacancy-apply__actions vacancy-apply__field--wide">
        <button type="submit" class="btn btn-accent vacancy-apply__submit" data-default-label="Отправить отклик">
          Отправить отклик
        </button>

        <?php if ($hh_url !== '') : ?>
          <a class="btn btn-white vacancy-apply__hh" href="<?php echo esc_url($hh_url); ?>" target="_blank"
            rel="noopener nofollow">
            Откликнуться на hh.ru
          </a>
        <?php endif; ?>
      </div>

      <p class="vacancy-apply__status form-status js-vacancy-apply-status"></p>
    </form>
  </div>
</section>
