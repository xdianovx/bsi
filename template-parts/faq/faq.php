<?php
/**
 * FAQ Accordion Section
 *
 * Передавать через get_template_part( 'template-parts/faq/faq', null, $args ):
 *   - eyebrow  (string)  — надпись-эйбро над заголовком (необязательно)
 *   - title    (string)  — заголовок секции
 *   - items    (array)   — массив вопросов: [ ['question' => '', 'answer' => ''], ... ]
 */

$eyebrow = isset($args['eyebrow']) ? (string) $args['eyebrow'] : '';
$title   = isset($args['title'])   ? (string) $args['title']   : '';
$items   = isset($args['items']) && is_array($args['items']) ? $args['items'] : [];

if (empty($items)) {
    return;
}
?>

<section class="faq-section">
    <div class="container">
        <div class="faq__inner">

            <div class="faq__head">
                <?php if ($eyebrow): ?>
                    <div class="faq__eyebrow">
                        <span class="faq__eyebrow-line"></span>
                        <span class="faq__eyebrow-text"><?= esc_html($eyebrow) ?></span>
                        <span class="faq__eyebrow-line"></span>
                    </div>
                <?php endif; ?>

                <?php if ($title): ?>
                    <h2 class="faq__title"><?= esc_html($title) ?></h2>
                <?php endif; ?>
            </div>

            <div class="accordion faq__list">
                <?php foreach ($items as $index => $item):
                    $question = isset($item['question']) ? (string) $item['question'] : '';
                    $answer   = isset($item['answer'])   ? (string) $item['answer']   : '';
                    if (!$question && !$answer) {
                        continue;
                    }
                    $is_open = ($index === 0);
                    $panel_id = 'faq-panel-' . (int) $index;
                ?>
                    <div class="accordion__item faq__item<?= $is_open ? ' is-open' : '' ?>">
                        <button
                            class="accordion__btn faq__btn"
                            type="button"
                            aria-expanded="<?= $is_open ? 'true' : 'false' ?>"
                            aria-controls="<?= esc_attr($panel_id) ?>"
                        >
                            <span class="faq__question"><?= esc_html($question) ?></span>
                            <span class="accordion__icon faq__icon" aria-hidden="true">
                                <img src="<?= get_template_directory_uri() ?>/img/icons/chevron-d.svg" alt="">
                            </span>
                        </button>

                        <div
                            class="accordion__panel faq__panel"
                            id="<?= esc_attr($panel_id) ?>"
                            <?= $is_open ? '' : 'hidden' ?>
                            aria-hidden="<?= $is_open ? 'false' : 'true' ?>"
                        >
                            <div class="faq__answer">
                                <?= wp_kses_post($answer) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</section>
