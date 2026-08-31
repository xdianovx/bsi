<?php
/*
Template Name: Вакансии
*/

/**
 * Каталог вакансий /vakansii/.
 *
 * Архив CPT отключён (has_archive => false), каталог — обычная WP-страница,
 * чтобы редактор мог править вводный текст, а SEO-описание бралось из inc/seo.php.
 *
 * @package bsi
 */

declare(strict_types=1);

get_header();

// Данные страницы берём напрямую: ниже global $post переиспользуется под цикл вакансий.
$page_id = get_queried_object_id();
$page_title = get_the_title($page_id);
$page_content = trim((string) get_post_field('post_content', $page_id));

// Фильтра по отделам нет сознательно: вакансий единицы, чипсы только шумят.
$vacancies_query = new WP_Query(bsi_vacancy_query_args());
$vacancies = bsi_vacancy_sort_hot_first($vacancies_query->posts);
?>

<main class="site-main vacancies-page">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section class="archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 archive-page__title"><?php echo esc_html($page_title); ?></h1>

        <?php if ($page_content !== '') : ?>
          <div class="archive-page__excerpt vacancies-page__intro">
            <?php echo wp_kses_post(apply_filters('the_content', $page_content)); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="archive-page__content-section vacancies-page__content">
    <div class="container">

      <?php if (!empty($vacancies)) : ?>
        <div class="vacancies-grid">
          <?php
          global $post;
          foreach ($vacancies as $post) :
            setup_postdata($post);
            get_template_part('template-parts/vacancy/card');
          endforeach;
          wp_reset_postdata();
          ?>
        </div>
      <?php else : ?>
        <div class="vacancies-empty">
          <p class="vacancies-empty__title">Сейчас открытых вакансий нет.</p>
          <p class="vacancies-empty__text">
            Мы всё равно рассмотрим ваше резюме — присылайте его на
            <a href="mailto:<?php echo esc_attr(BSI_VACANCY_APPLY_EMAIL); ?>"><?php echo esc_html(BSI_VACANCY_APPLY_EMAIL); ?></a>,
            и мы вернёмся, когда появится подходящая позиция.
          </p>
        </div>
      <?php endif; ?>

    </div>
  </section>

</main>

<?php get_footer(); ?>
