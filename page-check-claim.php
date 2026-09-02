<?php
/*
Template Name: Проверка заявки
*/

/**
 * Проверка статуса заявки по номеру — служебная страница для менеджеров.
 *
 * Форма шлёт номер в admin-ajax (inc/requests/ajax-check-claim.php), тот ходит в
 * публичный check_confirm Само и возвращает строки статуса. Страница закрыта от
 * индексации (inc/seo.php: noindex + исключение из sitemap по шаблону).
 *
 * @package bsi
 */

declare(strict_types=1);

get_header();

$page_id = get_queried_object_id();
$page_content = trim((string) get_post_field('post_content', $page_id));
?>

<main class="site-main claim-check-page">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section class="archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 archive-page__title"><?php the_title(); ?></h1>

        <?php if ($page_content !== '') : ?>
          <div class="archive-page__excerpt">
            <?php echo wp_kses_post(apply_filters('the_content', $page_content)); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="claim-check-page__content">
    <div class="container">
      <div class="claim-check">

        <form class="claim-check__form js-claim-check-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" novalidate>
          <input type="hidden" name="action" value="check_claim">

          <div class="claim-check__row">
            <div class="input-item claim-check__field">
              <label for="claim-check-input">Номер заявки</label>
              <input
                id="claim-check-input"
                type="text"
                name="claim"
                inputmode="numeric"
                pattern="[0-9]*"
                autocomplete="off"
                maxlength="10"
                placeholder="Например, 4593"
                class="js-claim-check-input">
              <span class="error-message js-field-error" data-error-for="claim"></span>
            </div>

            <button type="submit" class="btn btn-accent claim-check__submit" data-default-label="Проверить">Проверить</button>
          </div>
        </form>

        <div class="claim-check__result js-claim-check-result" hidden></div>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
