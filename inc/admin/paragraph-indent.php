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
 */
function bsi_setup_editor_styles(): void
{
	add_theme_support('editor-styles');
	add_editor_style('assets/css/editor-style.css');
}
add_action('after_setup_theme', 'bsi_setup_editor_styles');
