<?php
/**
 * Красная строка (отступ первой строки) в редакторе.
 *
 * На сайте активен плагин Classic Editor, поэтому основной путь — кнопка-иконка
 * в панели TinyMCE (js/admin/tinymce-indent.js). Стили блоков Gutenberg
 * зарегистрированы про запас — сработают, если классический редактор отключат.
 *
 * Класс на абзаце: is-style-indent.
 * Стили: scss/components/editor-styles.scss (фронт), assets/css/editor-style.css (редактор).
 *
 * @package bsi
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Регистрирует стили блоков для красной строки (Gutenberg).
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
 * Classic Editor: подключает TinyMCE-плагин с кнопкой «Красная строка».
 *
 * @param array $plugins Внешние плагины TinyMCE.
 * @return array
 */
function bsi_indent_mce_plugin($plugins)
{
	if (!is_array($plugins)) {
		return $plugins;
	}

	$path = get_template_directory() . '/js/admin/tinymce-indent.js';

	$plugins['bsi_indent'] = add_query_arg(
		'ver',
		bsi_asset_version($path),
		get_template_directory_uri() . '/js/admin/tinymce-indent.js'
	);

	return $plugins;
}
add_filter('mce_external_plugins', 'bsi_indent_mce_plugin');

/**
 * Classic Editor: ставит кнопку в панель после выравниваний.
 *
 * Приоритет 999 — чтобы отработать после Advanced Editor Tools (TinyMCE Advanced),
 * который пересобирает панель из своих настроек.
 *
 * @param array $buttons Кнопки первого ряда.
 * @return array
 */
function bsi_indent_mce_buttons($buttons)
{
	if (!is_array($buttons) || in_array('bsi_indent', $buttons, true)) {
		return $buttons;
	}

	$anchor = array_search('alignright', $buttons, true);
	$position = false === $anchor ? count($buttons) : $anchor + 1;

	array_splice($buttons, $position, 0, 'bsi_indent');

	return $buttons;
}
add_filter('mce_buttons', 'bsi_indent_mce_buttons', 9999);

/**
 * Иконка кнопки: SVG вместо шрифтовой иконки TinyMCE.
 */
function bsi_indent_mce_icon_css(): void
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;

	if ($screen && !in_array($screen->base, array('post', 'page', 'widgets', 'term', 'edit-tags'), true)) {
		return;
	}

	// Три строки текста, у первой — отступ слева.
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="%23555" stroke-width="1.8" stroke-linecap="round"><path d="M7 4h10M3 9h14M3 14h14"/></svg>';
	?>
	<style>
		.mce-i-bsi-indent {
			background: url('data:image/svg+xml;utf8,<?php echo $svg; ?>') no-repeat center;
			background-size: 18px 18px;
		}

		.mce-i-bsi-indent::before {
			content: '';
		}

		.mce-active .mce-i-bsi-indent {
			opacity: 1;
		}
	</style>
	<?php
}
add_action('admin_head', 'bsi_indent_mce_icon_css');
