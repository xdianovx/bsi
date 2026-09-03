<?php
/**
 * Хелперы CPT «Вакансии».
 *
 * Собирают нормализованные данные вакансии для карточки, single-страницы,
 * письма и schema.org JobPosting — чтобы форматирование зарплаты и подписи
 * словарей не дублировались по шаблонам.
 *
 * @package bsi
 */

declare(strict_types=1);

/** Адрес, на который уходят отклики по всем вакансиям. */
if (!defined('BSI_VACANCY_APPLY_EMAIL')) {
	define('BSI_VACANCY_APPLY_EMAIL', 'e.dushina@bsigroup.ru');
}

/**
 * Подписи типов занятости.
 *
 * @return array<string, string>
 */
function bsi_vacancy_employment_labels(): array
{
	return [
		'full' => 'Полная занятость',
		'part' => 'Частичная занятость',
		'project' => 'Проектная работа',
		'internship' => 'Стажировка',
	];
}

/**
 * Подписи требуемого опыта.
 *
 * @return array<string, string>
 */
function bsi_vacancy_experience_labels(): array
{
	return [
		'none' => 'Без опыта',
		'1-3' => 'Опыт от 1 года',
		'3-6' => 'Опыт от 3 лет',
		'6+' => 'Опыт от 6 лет',
	];
}

/**
 * Человекочитаемая зарплата.
 *
 * @param int $post_id ID вакансии.
 * @return string Пустая строка не возвращается — fallback «По договорённости».
 */
function bsi_vacancy_salary_label(int $post_id): string
{
	$type = (string) get_field('salary_type', $post_id);
	$from = (int) get_field('salary_from', $post_id);
	$to = (int) get_field('salary_to', $post_id);

	$fmt = static function (int $value): string {
		return function_exists('format_number')
			? format_number($value) . ' ₽'
			: number_format($value, 0, ',', ' ') . ' ₽';
	};

	switch ($type) {
		case 'from':
			return $from > 0 ? 'от ' . $fmt($from) : 'По договорённости';
		case 'exact':
			return $from > 0 ? $fmt($from) : 'По договорённости';
		case 'range':
			if ($from > 0 && $to > 0) {
				return $fmt($from) . ' — ' . $fmt($to);
			}
			if ($from > 0) {
				return 'от ' . $fmt($from);
			}
			return 'По договорённости';
		default:
			return 'По договорённости';
	}
}

/**
 * Пункты одного из repeater-списков вакансии.
 *
 * @param string $field   duties | requirements | conditions
 * @param int    $post_id ID вакансии.
 * @return string[] Непустые строки пунктов.
 */
function bsi_vacancy_list(string $field, int $post_id): array
{
	$rows = get_field($field, $post_id);

	if (!is_array($rows)) {
		return [];
	}

	$items = [];
	foreach ($rows as $row) {
		$text = is_array($row) ? trim((string) ($row['text'] ?? '')) : '';
		if ($text !== '') {
			$items[] = $text;
		}
	}

	return $items;
}

/**
 * Ключевые факты вакансии для чипсов и блока фактов.
 *
 * @param int $post_id ID вакансии.
 * @return array<int, array{icon: string, label: string, value: string}>
 */
function bsi_vacancy_facts(int $post_id): array
{
	$facts = [
		[
			'icon' => 'wallet',
			'label' => 'Зарплата',
			'value' => bsi_vacancy_salary_label($post_id),
		],
	];

	$employment = (string) get_field('employment_type', $post_id);
	$employment_label = bsi_vacancy_employment_labels()[$employment] ?? '';
	if ($employment_label !== '') {
		$facts[] = ['icon' => 'users', 'label' => 'Занятость', 'value' => $employment_label];
	}

	$experience = (string) get_field('experience', $post_id);
	$experience_label = bsi_vacancy_experience_labels()[$experience] ?? '';
	if ($experience_label !== '') {
		$facts[] = ['icon' => 'badge-check', 'label' => 'Опыт', 'value' => $experience_label];
	}

	$schedule = trim((string) get_field('schedule', $post_id));
	if ($schedule !== '') {
		$facts[] = ['icon' => 'clock', 'label' => 'График', 'value' => $schedule];
	}

	return $facts;
}

/**
 * Аргументы WP_Query для списка вакансий.
 *
 * Сортировка по дате: «срочные» поднимаются наверх уже в шаблоне
 * (bsi_vacancy_sort_hot_first), потому что meta_value_num-сортировка
 * выбрасывала бы вакансии без заполненного is_hot.
 * Срок показа учитывается через bsi_query_args_append_schedule().
 *
 * @param array<string, mixed> $args Переопределения.
 * @return array<string, mixed>
 */
function bsi_vacancy_query_args(array $args = []): array
{
	$defaults = [
		'post_type' => 'vacancy',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'ignore_sticky_posts' => true,
	];

	$args = array_merge($defaults, $args);

	return function_exists('bsi_query_args_append_schedule')
		? bsi_query_args_append_schedule($args)
		: $args;
}

/**
 * Поднимает «срочные» вакансии в начало списка, сохраняя порядок внутри групп.
 *
 * @param WP_Post[] $posts Записи вакансий.
 * @return WP_Post[]
 */
function bsi_vacancy_sort_hot_first(array $posts): array
{
	$hot = [];
	$rest = [];

	foreach ($posts as $post) {
		if (get_field('is_hot', $post->ID)) {
			$hot[] = $post;
		} else {
			$rest[] = $post;
		}
	}

	return array_merge($hot, $rest);
}
