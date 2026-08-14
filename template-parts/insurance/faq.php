<?php
/**
 * Блок «Частые вопросы» — общий для всего раздела «Страхование».
 *
 * Данные лежат на странице с шаблоном page-insurance.php (ACF-поле insurance_faq)
 * и выводятся как в каталоге, так и на страницах страховых продуктов.
 *
 * @package bsi
 */

declare(strict_types=1);

$faq_page_id = function_exists('bsi_insurance_page_id') ? bsi_insurance_page_id() : 0;

if (!$faq_page_id || !function_exists('have_rows') || !have_rows('insurance_faq', $faq_page_id)) {
	return;
}

$faq_items = [];

while (have_rows('insurance_faq', $faq_page_id)):
	the_row();
	$question = (string) get_sub_field('question');
	$answer = (string) get_sub_field('answer');

	if ($question && $answer) {
		$faq_items[] = ['question' => $question, 'answer' => $answer];
	}
endwhile;

if (empty($faq_items)) {
	return;
}

get_template_part('template-parts/faq/faq', null, [
	'title' => 'Частые вопросы',
	'items' => $faq_items,
]);
