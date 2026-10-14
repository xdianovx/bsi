<?php
/**
 * Страница события (CPT `event`) по макетам 2026-10-08: Фигма qhOwVa1YNd0tg4TAH85JEb, страница 1949:1326.
 * ТЗ — wiki/docs/event-page-tz.md. Данные — bsi_event_page_data() (inc/helpers/event-page.php),
 * части страницы — template-parts/event/page/*. Логика выбора — js/modules/event-offer.js,
 * конструктор тура — js/modules/crosstour-event.js.
 */
$post_id = get_the_ID();
$event = bsi_event_page_data($post_id);
$finished = $event['finished'];
$both = $event['has_tour'] && $event['has_tickets'];
$json = bsi_event_page_json($event);
$related = bsi_event_related($post_id, $event['country_id'], $finished);
// Куда ведут «Купить тур / билет»: блок выбора #format есть, только если выбирать дату или формат (format.php).
$choose_href = (count($json['dates']) > 1 || $both) ? '#format' : ($event['has_tour'] ? '#tur' : '#bilety');
$args = ['event' => $event, 'both' => $both, 'json' => $json, 'choose_href' => $choose_href];

get_header();
?>

<main class="site-main single-event-page<?= $finished ? ' single-event-page--finished' : ''; ?>"
  data-event-offer data-event-id="<?= esc_attr((string) $post_id); ?>" data-event-title="<?= esc_attr($event['title']); ?>"
  data-event-time="<?= esc_attr($event['time']); ?>"<?php if ($event['crosstour']): ?> data-crosstour-event="<?= esc_attr((string) $post_id); ?>"<?php endif; ?>>
  <script type="application/json" data-event-offer-json><?= wp_json_encode($json, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

  <?php get_template_part('template-parts/event/page/hero', null, $args); ?>

  <section class="single-event__body">
    <div class="container">
      <?php if ($finished): ?>
        <?php get_template_part('template-parts/event/page/status', null, $args); ?>
      <?php endif; ?>

      <?php if ($finished && !empty($related)): ?>
        <?php get_template_part('template-parts/event/related-tours-slider', null, ['posts' => $related]); ?>
      <?php endif; ?>

      <div class="single-event__about-grid<?= $finished ? ' single-event__about-grid--wide' : ''; ?>">
        <div class="single-event__columns">
          <?php if (!$finished): ?>
            <?php get_template_part('template-parts/event/page/format', null, $args); ?>
            <?php if ($event['has_tickets']): ?>
              <?php get_template_part('template-parts/event/page/tickets', null, $args); ?>
            <?php endif; ?>
            <?php if ($event['has_tour']): ?>
              <?php get_template_part('template-parts/event/page/tour', null, $args); ?>
            <?php endif; ?>
          <?php endif; ?>

          <?php if (trim(wp_strip_all_tags($event['about'])) !== ''): ?>
            <section class="single-event__about-main single-event__column-card" id="o-sobytii" data-about>
              <h2 class="h2">О событии</h2>
              <div class="single-event__about-body editor-content" data-about-body><?= wp_kses_post($event['about']); ?></div>
              <button type="button" class="single-event__about-more" data-about-more hidden>
                <span>Читать полностью</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
              </button>
            </section>
          <?php endif; ?>

          <?php get_template_part('template-parts/event/booking-steps', null, [
            'has_tour' => $event['has_tour'],
            'has_tickets' => $event['has_tickets'],
            'choose_href' => $choose_href,
          ]); ?>

          <?php if (trim(wp_strip_all_tags($event['important'])) !== ''): ?>
            <section class="single-event__column-card single-event__important" id="vazhno-znat">
              <h2 class="h2">Важно знать</h2>
              <div class="editor-content"><?= wp_kses_post($event['important']); ?></div>
            </section>
          <?php endif; ?>

          <?php if (!$finished && !empty($event['program'])): ?>
            <section class="single-event__column-card single-event__legacy-program" id="programma">
              <h2 class="h2">Программа тура</h2>
              <div class="single-event__legacy-program-list">
                <?php foreach ($event['program'] as $day): ?>
                  <div class="single-event__legacy-program-day">
                    <?php if ($day['title'] !== ''): ?>
                      <h3 class="single-event__legacy-program-day-title"><?= esc_html($day['title']); ?></h3>
                    <?php endif; ?>
                    <div class="editor-content single-event__legacy-program-day-content"><?= wp_kses_post($day['content']); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <?php if (!$finished && trim(wp_strip_all_tags($event['additional'])) !== ''): ?>
            <section class="single-event__column-card single-event__legacy-additional" id="dopolnitelno">
              <h2 class="h2">Дополнительно</h2>
              <div class="editor-content"><?= wp_kses_post($event['additional']); ?></div>
            </section>
          <?php endif; ?>
        </div>

        <?php if (!$finished && ($event['has_tour'] || $event['has_tickets'])): ?>
          <?php get_template_part('template-parts/event/page/sidebar', null, $args); ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if (!$finished && !empty($related)): ?>
    <?php get_template_part('template-parts/event/related-tours-slider', null, ['posts' => $related]); ?>
  <?php endif; ?>

  <?php if (!$finished): ?>
    <?php get_template_part('template-parts/event/page/cta', null, $args); ?>
  <?php endif; ?>

  <?php if (!empty($event['faq'])): ?>
    <section class="single-event__faq">
      <div class="container">
        <div class="single-event__faq-inner">
          <h2 class="h2">Ответы на частые вопросы</h2>
          <div class="accordion single-event__faq-acc">
            <div class="accordion__list single-event__faq-list">
              <?php foreach ($event['faq'] as $faq): ?>
                <div class="accordion__item single-event__faq-item">
                  <button class="accordion__btn single-event__faq-btn" type="button">
                    <span class="accordion__title single-event__faq-question"><?= esc_html($faq['question']); ?></span>
                    <span class="accordion__icon">
                      <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/chevron-d.svg'); ?>" alt="">
                    </span>
                  </button>
                  <div class="accordion__panel">
                    <div class="accordion__content editor-content single-event__faq-answer"><?= wp_kses_post($faq['answer']); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php
get_template_part('template-parts/event/ticket-booking-modal');
get_footer();
