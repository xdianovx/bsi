<?php
/**
 * Карточка вакансии в каталоге /vakansii/.
 *
 * Ожидает установленный глобальный пост (внутри цикла WP_Query).
 * Hover — только смена цвета заголовка, без теней и сдвигов (design-guidelines.md).
 *
 * @package bsi
 */

declare(strict_types=1);

$vacancy_id = get_the_ID();
$is_hot = (bool) get_field('is_hot', $vacancy_id);
$salary = function_exists('bsi_vacancy_salary_label') ? bsi_vacancy_salary_label($vacancy_id) : '';

// Зарплата уходит в футер к кнопке, в списке фактов её не дублируем.
$facts = function_exists('bsi_vacancy_facts') ? bsi_vacancy_facts($vacancy_id) : [];
$facts = array_filter($facts, static fn(array $f): bool => $f['label'] !== 'Зарплата');
$duties = function_exists('bsi_vacancy_list') ? bsi_vacancy_list('duties', $vacancy_id) : [];
?>

<article <?php post_class('vacancy-card'); ?>>
  <div class="vacancy-card__head">
    <h2 class="vacancy-card__title">
      <a href="<?php the_permalink(); ?>" class="vacancy-card__link"><?php the_title(); ?></a>
    </h2>

    <?php if ($is_hot) : ?>
      <span class="vacancy-card__badge">Срочно</span>
    <?php endif; ?>
  </div>

  <?php if (!empty($facts)) : ?>
    <ul class="vacancy-card__facts">
      <?php foreach ($facts as $fact) : ?>
        <li class="vacancy-card__fact">
          <?php if (function_exists('bsi_ui_icon_markup')) : ?>
            <?php echo bsi_ui_icon_markup($fact['icon'], 18, 'vacancy-card__fact-icon'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <?php endif; ?>
          <span><?php echo esc_html($fact['value']); ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php
  $preview = get_the_excerpt();
  if ($preview === '' && !empty($duties)) {
    $preview = implode(' ', array_slice($duties, 0, 2));
  }
  ?>
  <?php if ($preview !== '') : ?>
    <p class="vacancy-card__excerpt"><?php echo esc_html(wp_trim_words($preview, 26, '…')); ?></p>
  <?php endif; ?>

  <div class="vacancy-card__bottom">
    <a href="<?php the_permalink(); ?>" class="btn btn-accent vacancy-card__more">Подробнее</a>

    <?php if ($salary !== '') : ?>
      <p class="vacancy-card__salary"><?php echo esc_html($salary); ?></p>
    <?php endif; ?>
  </div>
</article>
