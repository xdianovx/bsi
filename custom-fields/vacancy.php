<?php
/**
 * ACF-поля CPT «Вакансия».
 *
 * Структура: базовые параметры (зарплата, график, опыт) →
 * три списка (обязанности / требования / условия) → свободный блок →
 * ссылка на hh.ru.
 *
 * Метро, адрес офиса и контактное лицо сознательно не заводим: офис у компании
 * один, дублировать его в каждой вакансии незачем (решение 2026-08-31).
 *
 * Срок показа (bsi_active_from / bsi_active_until) добавляется автоматически
 * через inc/helpers/content-schedule.php — отдельных полей здесь нет.
 *
 * @package bsi
 */

declare(strict_types=1);

add_action('acf/init', function () {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}

	/** Repeater «список пунктов» — одинаковый для обязанностей / требований / условий. */
	$list_field = static function (string $key, string $name, string $label, string $button): array {
		return [
			'key' => $key,
			'label' => $label,
			'name' => $name,
			'type' => 'repeater',
			'layout' => 'block',
			'button_label' => $button,
			'sub_fields' => [
				[
					'key' => $key . '_text',
					'label' => 'Пункт',
					'name' => 'text',
					'type' => 'textarea',
					'rows' => 2,
					'new_lines' => '',
					'required' => 1,
				],
			],
		];
	};

	acf_add_local_field_group([
		'key' => 'group_vacancy_fields',
		'title' => 'Параметры вакансии',
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'location' => [
			[
				[
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'vacancy',
				],
			],
		],
		'fields' => [
			// ── Условия работы ──────────────────────────────────
			[
				'key' => 'field_vacancy_tab_main',
				'label' => 'Основное',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_vacancy_salary_type',
				'label' => 'Зарплата',
				'name' => 'salary_type',
				'type' => 'select',
				'choices' => [
					'negotiable' => 'По договорённости',
					'from' => 'От суммы',
					'range' => 'Вилка (от — до)',
					'exact' => 'Точная сумма',
				],
				'default_value' => 'negotiable',
				'return_format' => 'value',
				'allow_null' => 0,
			],
			[
				'key' => 'field_vacancy_salary_from',
				'label' => 'Сумма «от», ₽',
				'name' => 'salary_from',
				'type' => 'number',
				'min' => 0,
				'step' => 1000,
				'conditional_logic' => [
					[
						['field' => 'field_vacancy_salary_type', 'operator' => '!=', 'value' => 'negotiable'],
					],
				],
			],
			[
				'key' => 'field_vacancy_salary_to',
				'label' => 'Сумма «до», ₽',
				'name' => 'salary_to',
				'type' => 'number',
				'min' => 0,
				'step' => 1000,
				'conditional_logic' => [
					[
						['field' => 'field_vacancy_salary_type', 'operator' => '==', 'value' => 'range'],
					],
				],
			],
			[
				'key' => 'field_vacancy_employment_type',
				'label' => 'Тип занятости',
				'name' => 'employment_type',
				'type' => 'select',
				'choices' => [
					'full' => 'Полная занятость',
					'part' => 'Частичная занятость',
					'project' => 'Проектная работа',
					'internship' => 'Стажировка',
				],
				'default_value' => 'full',
				'allow_null' => 0,
			],
			[
				'key' => 'field_vacancy_experience',
				'label' => 'Требуемый опыт',
				'name' => 'experience',
				'type' => 'select',
				'choices' => [
					'none' => 'Без опыта',
					'1-3' => 'От 1 года',
					'3-6' => 'От 3 лет',
					'6+' => 'От 6 лет',
				],
				'default_value' => '1-3',
				'allow_null' => 0,
			],
			[
				'key' => 'field_vacancy_schedule',
				'label' => 'График работы',
				'name' => 'schedule',
				'type' => 'text',
				'placeholder' => '5/2 с 10:00 до 19:00',
				'instructions' => 'Короткой строкой — показывается в карточке и в блоке фактов.',
			],
			[
				'key' => 'field_vacancy_is_hot',
				'label' => 'Срочная вакансия',
				'name' => 'is_hot',
				'type' => 'true_false',
				'ui' => 1,
				'instructions' => 'Показывает бейдж «Срочно» и поднимает вакансию в начало списка.',
			],

			// ── Списки ──────────────────────────────────────────
			[
				'key' => 'field_vacancy_tab_lists',
				'label' => 'Обязанности и требования',
				'type' => 'tab',
				'placement' => 'top',
			],
			$list_field('field_vacancy_duties', 'duties', 'Обязанности', 'Добавить обязанность'),
			$list_field('field_vacancy_requirements', 'requirements', 'Требования', 'Добавить требование'),
			$list_field('field_vacancy_conditions', 'conditions', 'Условия', 'Добавить условие'),

			// ── Свободный блок ──────────────────────────────────
			[
				'key' => 'field_vacancy_tab_extra',
				'label' => 'Доп. описание',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_vacancy_extra_content',
				'label' => 'Дополнительный текст',
				'name' => 'extra_content',
				'type' => 'wysiwyg',
				'tabs' => 'all',
				'toolbar' => 'basic',
				'media_upload' => 0,
				'instructions' => 'О команде, продукте, нестандартные условия — всё, что не укладывается в списки выше.',
			],

			// ── Отклик ──────────────────────────────────────────
			[
				'key' => 'field_vacancy_tab_apply',
				'label' => 'Отклик',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_vacancy_hh_url',
				'label' => 'Ссылка на вакансию на hh.ru',
				'name' => 'hh_url',
				'type' => 'url',
				'instructions' => 'Если заполнено — рядом с формой появится кнопка «Откликнуться на hh.ru». Пусто — кнопки нет.',
			],
		],
	]);
});
