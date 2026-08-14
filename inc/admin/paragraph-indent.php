<?php
/**
 * Красная строка (отступ первой строки) для блоков редактора.
 *
 * Регистрирует стили блоков, которые появляются в панели «Стили»:
 * - «Красная строка» — отступ первой строки;
 * - «Красная строка + по ширине» — отступ + выключка по ширине.
 *
 * Для блока «Группа» стиль применяется ко всем абзацам внутри,
 * что удобно для длинных документов (оферты, политики, правила).
 *
 * @package bsi
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Регистрирует стили блоков для красной строки.
 */
function bsi_register_indent_block_styles(): void
{
	$styles = array(
		array(
			'name'  => 'indent',
			'label' => __('Красная строка', 'bsi'),
		),
		array(
			'name'  => 'indent-justify',
			'label' => __('Красная строка + по ширине', 'bsi'),
		),
	);

	$blocks = array('core/paragraph', 'core/group');

	foreach ($blocks as $block) {
		foreach ($styles as $style) {
			register_block_style($block, $style);
		}
	}
}
add_action('init', 'bsi_register_indent_block_styles');

/**
 * Подключает стили темы к редактору, чтобы красная строка была видна прямо в админке.
 * Работает и для Gutenberg, и для классического редактора (mce_css).
 */
function bsi_setup_editor_styles(): void
{
	add_theme_support('editor-styles');
	add_editor_style('assets/css/editor-style.css');
}
add_action('after_setup_theme', 'bsi_setup_editor_styles');

/**
 * Classic Editor: кнопка «Форматы» (styleselect) в панели TinyMCE.
 *
 * На сайте активен плагин Classic Editor, поэтому стили блоков Gutenberg
 * не показываются — красная строка добавляется через выпадающий список форматов.
 * Приоритет 999 — чтобы отработать после Advanced Editor Tools (TinyMCE Advanced),
 * который пересобирает панель из своих настроек.
 *
 * @param array $buttons Кнопки первого ряда.
 * @return array
 */
function bsi_indent_mce_buttons($buttons)
{
	if (!is_array($buttons)) {
		return $buttons;
	}

	if (!in_array('styleselect', $buttons, true)) {
		array_splice($buttons, 1, 0, 'styleselect');
	}

	return $buttons;
}
add_filter('mce_buttons', 'bsi_indent_mce_buttons', 999);

/**
 * Classic Editor: пункты «Красная строка» в списке форматов.
 *
 * @param array $settings Настройки инициализации TinyMCE.
 * @return array
 */
function bsi_indent_mce_style_formats($settings)
{
	if (!is_array($settings)) {
		return $settings;
	}

	$existing = array();

	if (!empty($settings['style_formats'])) {
		$decoded = json_decode($settings['style_formats'], true);

		if (is_array($decoded)) {
			$existing = $decoded;
		}
	}

	$formats = array(
		array(
			'title'   => 'Красная строка',
			'block'   => 'p',
			'classes' => 'is-style-indent',
			'wrapper' => false,
		),
		array(
			'title'   => 'Красная строка + по ширине',
			'block'   => 'p',
			'classes' => 'is-style-indent-justify',
			'wrapper' => false,
		),
	);

	$settings['style_formats'] = wp_json_encode(array_merge($existing, $formats));

	return $settings;
}
add_filter('tiny_mce_before_init', 'bsi_indent_mce_style_formats', 999);
