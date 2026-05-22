<?php
/**
 * Программа экскурсии по дням (single-excursion.php).
 *
 * @var array $excursion_program_rows  Repeater excursion_program
 */

$rows = get_query_var('excursion_program_rows') ?: [];
if (!is_array($rows) || empty($rows)) {
  return;
}
?>

<section class="accordion tour-program single-excursion__program">
  <div class="tour-program__acc-head accordion__head">
    <h2 class="h2 tour-program__title">Программа экскурсии</h2>
    <button class="btn-expand accordion__toggle-all" type="button">Раскрыть все</button>
  </div>

  <div class="accordion__list tour-program__list">
    <?php foreach ($rows as $i => $day): ?>
      <?php
      $day_title = !empty($day['day_title']) ? (string) $day['day_title'] : '';
      $day_text = !empty($day['day_content']) ? (string) $day['day_content'] : '';
      if (!$day_title && !$day_text) {
        continue;
      }
      ?>
      <div class="accordion__item tour-program__day">
        <button class="accordion__btn tour-program__day-btn" type="button">
          <span class="accordion__title">
            <?= esc_html($day_title ?: ('День ' . ($i + 1))); ?>
          </span>
          <span class="accordion__icon" aria-hidden="true">
            <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/chevron-d.svg'); ?>" alt="">
          </span>
        </button>
        <div class="accordion__panel">
          <div class="accordion__content">
            <?php if ($day_text): ?>
              <div class="editor-content">
                <?= wp_kses_post($day_text); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
