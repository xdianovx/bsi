<?php
/**
 * ACF-поля для CPT «Страхование».
 *
 * Каждая запись — мини-лендинг страхового продукта:
 * ключевые параметры → преимущества → покрытие → как оформить →
 * условия и правила (аккордеон) → документы → вопросы.
 *
 * @package bsi
 */

declare(strict_types=1);

add_action('acf/init', function () {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}

	require_once get_template_directory() . '/template-parts/ui/icon.php';

	$icon_choices = [];
	if (function_exists('bsi_ui_icons')) {
		foreach (bsi_ui_icons() as $slug => $label) {
			$icon_choices[$slug] = $label;
		}
	}

	/**
	 * Поле выбора иконки: radio с превью SVG (см. inc/admin/icon-picker.php).
	 */
	$icon_field = static function (string $key, string $instructions = ''): array {
		return [
			'key' => $key,
			'label' => 'Иконка',
			'name' => 'icon',
			'type' => 'radio',
			'allow_null' => 1,
			'other_choice' => 0,
			'layout' => 'vertical',
			'instructions' => $instructions,
			'choices' => [],
			'wrapper' => ['class' => 'bsi-icon-picker'],
		];
	};

	$fill_icons = static function (array $field) use ($icon_choices): array {
		$field['choices'] = $icon_choices;
		return $field;
	};

	acf_add_local_field_group([
		'key' => 'group_insurance_fields',
		'title' => 'Страховой продукт',
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'location' => [
			[
				[
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'insurance',
				],
			],
		],
		'fields' => [

			// ─── Ключевые параметры ───────────────────────────────────────
			[
				'key' => 'field_insurance_tab_info',
				'label' => 'Ключевые параметры',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_insurance_info',
				'label' => 'Ключевые параметры',
				'name' => 'insurance_info',
				'type' => 'repeater',
				'instructions' => 'Плитки в шапке: страховая сумма, цена «от», франшиза, возраст. Оптимально 3–4 штуки.',
				'layout' => 'block',
				'button_label' => 'Добавить параметр',
				'sub_fields' => [
					$fill_icons($icon_field('field_insurance_info_icon')),
					[
						'key' => 'field_insurance_info_key',
						'label' => 'Название',
						'name' => 'key',
						'type' => 'text',
						'placeholder' => 'Страховая сумма',
						'wrapper' => ['width' => '50'],
					],
					[
						'key' => 'field_insurance_info_value',
						'label' => 'Значение',
						'name' => 'value',
						'type' => 'text',
						'placeholder' => '40 000 у.е.',
						'wrapper' => ['width' => '50'],
					],
				],
			],
			[
				'key' => 'field_insurance_hero_note',
				'label' => 'Примечание под параметрами',
				'name' => 'insurance_hero_note',
				'type' => 'text',
				'instructions' => 'Например: «Оплата в рублях по курсу ЦБ РФ на день оформления полиса».',
			],

			// ─── Преимущества ─────────────────────────────────────────────
			[
				'key' => 'field_insurance_tab_benefits',
				'label' => 'Преимущества',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_insurance_benefits',
				'label' => 'Преимущества',
				'name' => 'insurance_benefits',
				'type' => 'repeater',
				'instructions' => 'Карточки с иконками. Оптимально 3–6 штук.',
				'layout' => 'block',
				'button_label' => 'Добавить преимущество',
				'sub_fields' => [
					$fill_icons($icon_field('field_insurance_benefit_icon')),
					[
						'key' => 'field_insurance_benefit_title',
						'label' => 'Заголовок',
						'name' => 'title',
						'type' => 'text',
					],
					[
						'key' => 'field_insurance_benefit_desc',
						'label' => 'Описание',
						'name' => 'description',
						'type' => 'textarea',
						'rows' => 3,
						'new_lines' => '',
					],
					[
						'key' => 'field_insurance_benefit_image',
						'label' => 'Своя картинка вместо иконки',
						'name' => 'image',
						'type' => 'image',
						'return_format' => 'array',
						'preview_size' => 'thumbnail',
						'instructions' => 'Необязательно. Если загружена — используется вместо иконки.',
					],
				],
			],

			// ─── Покрытие ─────────────────────────────────────────────────
			[
				'key' => 'field_insurance_tab_coverage',
				'label' => 'Что покрывает',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_insurance_coverage',
				'label' => 'Что покрывает полис',
				'name' => 'insurance_coverage',
				'type' => 'repeater',
				'layout' => 'table',
				'button_label' => 'Добавить пункт',
				'sub_fields' => [
					[
						'key' => 'field_insurance_coverage_title',
						'label' => 'Пункт',
						'name' => 'title',
						'type' => 'text',
						'placeholder' => 'Стоматологическая помощь',
					],
					[
						'key' => 'field_insurance_coverage_limit',
						'label' => 'Лимит',
						'name' => 'limit',
						'type' => 'text',
						'placeholder' => 'до 150 у.е.',
						'wrapper' => ['width' => '25'],
					],
				],
			],
			[
				'key' => 'field_insurance_exclusions',
				'label' => 'Что не покрывает',
				'name' => 'insurance_exclusions',
				'type' => 'repeater',
				'instructions' => 'Короткая выжимка. Полный перечень исключений — в блоке «Условия и правила».',
				'layout' => 'table',
				'button_label' => 'Добавить пункт',
				'sub_fields' => [
					[
						'key' => 'field_insurance_exclusion_title',
						'label' => 'Пункт',
						'name' => 'title',
						'type' => 'text',
					],
				],
			],

			// ─── Как оформить ─────────────────────────────────────────────
			[
				'key' => 'field_insurance_tab_conditions',
				'label' => 'Как оформить',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_insurance_conditions',
				'label' => 'Шаги оформления',
				'name' => 'insurance_conditions',
				'type' => 'repeater',
				'layout' => 'block',
				'button_label' => 'Добавить шаг',
				'sub_fields' => [
					[
						'key' => 'field_insurance_condition_order',
						'label' => 'Номер шага',
						'name' => 'order',
						'type' => 'number',
						'wrapper' => ['width' => '20'],
					],
					[
						'key' => 'field_insurance_condition_title',
						'label' => 'Заголовок',
						'name' => 'title',
						'type' => 'text',
						'wrapper' => ['width' => '80'],
					],
					$fill_icons($icon_field('field_insurance_condition_icon')),
					[
						'key' => 'field_insurance_condition_desc',
						'label' => 'Описание',
						'name' => 'description',
						'type' => 'textarea',
						'rows' => 3,
						'new_lines' => 'br',
					],
				],
			],

			// ─── Условия и правила ────────────────────────────────────────
			[
				'key' => 'field_insurance_tab_rules',
				'label' => 'Условия и правила',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_insurance_rules',
				'label' => 'Блоки правил',
				'name' => 'insurance_rules',
				'type' => 'repeater',
				'instructions' => 'Выводятся аккордеоном (свёрнуты). Сюда — выдержки из Правил страхования: страховой случай, исключения, действия при страховом случае, документы для выплаты.',
				'layout' => 'block',
				'button_label' => 'Добавить блок',
				'sub_fields' => [
					[
						'key' => 'field_insurance_rule_title',
						'label' => 'Заголовок блока',
						'name' => 'title',
						'type' => 'text',
						'placeholder' => 'Что не является страховым случаем',
					],
					[
						'key' => 'field_insurance_rule_ref',
						'label' => 'Ссылка на пункт Правил',
						'name' => 'ref',
						'type' => 'text',
						'placeholder' => 'п. 14.2 Правил',
						'wrapper' => ['width' => '30'],
					],
					[
						'key' => 'field_insurance_rule_content',
						'label' => 'Текст',
						'name' => 'content',
						'type' => 'wysiwyg',
						'tabs' => 'all',
						'media_upload' => 0,
						'delay' => 1,
					],
				],
			],
			[
				'key' => 'field_insurance_content_title',
				'label' => 'Заголовок для текста из редактора',
				'name' => 'insurance_content_title',
				'type' => 'text',
				'instructions' => 'Содержимое редактора выводится последним блоком аккордеона. По умолчанию — «Полный текст правил страхования».',
				'wrapper' => ['width' => '50'],
			],
			[
				'key' => 'field_insurance_rules_url',
				'label' => 'Ссылка на полные правила',
				'name' => 'insurance_rules_url',
				'type' => 'url',
				'wrapper' => ['width' => '50'],
			],

			// ─── Документы ────────────────────────────────────────────────
			[
				'key' => 'field_insurance_tab_docs',
				'label' => 'Документы',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_insurance_docs',
				'label' => 'Документы для скачивания',
				'name' => 'insurance_docs',
				'type' => 'repeater',
				'layout' => 'table',
				'button_label' => 'Добавить документ',
				'sub_fields' => [
					[
						'key' => 'field_insurance_doc_title',
						'label' => 'Название',
						'name' => 'title',
						'type' => 'text',
					],
					[
						'key' => 'field_insurance_doc_file',
						'label' => 'Файл',
						'name' => 'file',
						'type' => 'file',
						'return_format' => 'array',
						'wrapper' => ['width' => '40'],
					],
				],
			],

			// ─── Вопросы ──────────────────────────────────────────────────
			[
				'key' => 'field_insurance_tab_faq',
				'label' => 'Вопросы',
				'type' => 'tab',
				'placement' => 'top',
			],
			[
				'key' => 'field_insurance_faq',
				'label' => 'Частые вопросы',
				'name' => 'insurance_faq',
				'type' => 'repeater',
				'layout' => 'block',
				'button_label' => 'Добавить вопрос',
				'sub_fields' => [
					[
						'key' => 'field_insurance_faq_question',
						'label' => 'Вопрос',
						'name' => 'question',
						'type' => 'text',
					],
					[
						'key' => 'field_insurance_faq_answer',
						'label' => 'Ответ',
						'name' => 'answer',
						'type' => 'textarea',
						'rows' => 4,
						'new_lines' => 'br',
					],
				],
			],
		],
	]);
});
