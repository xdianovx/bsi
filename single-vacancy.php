<?php
/**
 * Страница вакансии /vakansii/{slug}/.
 *
 * Структура: факты → обязанности / требования / условия → свободный блок →
 * форма отклика → другие вакансии. Справа — sticky-блок с зарплатой и кнопкой.
 *
 * @package bsi
 */

declare(strict_types=1);

get_header();

while (have_posts()) :
  the_post();

  $vacancy_id = get_the_ID();
  $is_hot = (bool) get_field('is_hot', $vacancy_id);
  $facts = bsi_vacancy_facts($vacancy_id);
  $salary = bsi_vacancy_salary_label($vacancy_id);
  $hh_url = trim((string) get_field('hh_url', $vacancy_id));
  $extra_content = (string) get_field('extra_content', $vacancy_id);

  $blocks = [
    'Обязанности' => bsi_vacancy_list('duties', $vacancy_id),
    'Требования' => bsi_vacancy_list('requirements', $vacancy_id),
    'Условия' => bsi_vacancy_list('conditions', $vacancy_id),
  ];
  ?>

  <main class="site-main single-vacancy">

    <?php if (function_exists('yoast_breadcrumb')) {
      yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
    } ?>

    <div class="container">

      <header class="single-vacancy__head">
        <h1 class="h1 single-vacancy__title">
          <?php the_title(); ?>
          <?php if ($is_hot) : ?>
            <span class="single-vacancy__badge">Срочно</span>
          <?php endif; ?>
        </h1>
      </header>

      <div class="single-vacancy__layout">

        <div class="single-vacancy__main">

          <?php if (get_the_content()) : ?>
            <div class="single-vacancy__intro editor-content">
              <?php the_content(); ?>
            </div>
          <?php endif; ?>

          <?php foreach ($blocks as $block_title => $items) : ?>
            <?php if (empty($items)) {
              continue;
            } ?>
            <section class="single-vacancy__block">
              <h2 class="h3 single-vacancy__block-title"><?php echo esc_html($block_title); ?></h2>
              <ul class="single-vacancy__list">
                <?php foreach ($items as $item) : ?>
                  <li class="single-vacancy__list-item"><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endforeach; ?>

          <?php if (trim($extra_content) !== '') : ?>
            <section class="single-vacancy__block single-vacancy__extra editor-content">
              <?php echo wp_kses_post($extra_content); ?>
            </section>
          <?php endif; ?>

        </div>

        <aside class="single-vacancy__aside">
          <div class="single-vacancy__cta">
            <p class="single-vacancy__cta-label">Зарплата</p>
            <p class="single-vacancy__cta-salary"><?php echo esc_html($salary); ?></p>

            <a class="btn btn-accent single-vacancy__cta-btn" href="#vacancy-apply">Откликнуться</a>

            <?php if ($hh_url !== '') : ?>
              <a class="single-vacancy__cta-hh" href="<?php echo esc_url($hh_url); ?>" target="_blank"
                rel="noopener nofollow">Открыть на hh.ru</a>
            <?php endif; ?>

            <?php // Зарплата уже показана выше крупно — в списке её не повторяем. ?>
            <?php $side_facts = array_filter($facts, static fn(array $f): bool => $f['label'] !== 'Зарплата'); ?>
            <?php if (!empty($side_facts)) : ?>
              <ul class="single-vacancy__facts">
                <?php foreach ($side_facts as $fact) : ?>
                  <li class="single-vacancy__fact">
                    <span class="single-vacancy__fact-body">
                      <span class="single-vacancy__fact-label"><?php echo esc_html($fact['label']); ?></span>
                      <span class="single-vacancy__fact-value"><?php echo esc_html($fact['value']); ?></span>
                    </span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </aside>

      </div>

      <?php get_template_part('template-parts/vacancy/apply-form', null, ['vacancy_id' => $vacancy_id]); ?>

      <?php
      $other = new WP_Query(bsi_vacancy_query_args([
        'posts_per_page' => 3,
        'post__not_in' => [$vacancy_id],
      ]));
      ?>
      <?php if ($other->have_posts()) : ?>
        <section class="single-vacancy__other">
          <h2 class="h2 single-vacancy__other-title">Другие вакансии</h2>
          <div class="vacancies-grid">
            <?php while ($other->have_posts()) : $other->the_post(); ?>
              <?php get_template_part('template-parts/vacancy/card'); ?>
            <?php endwhile; ?>
          </div>
        </section>
      <?php endif; ?>
      <?php wp_reset_postdata(); ?>

    </div>

  </main>

<?php endwhile; ?>

<?php get_footer(); ?>
