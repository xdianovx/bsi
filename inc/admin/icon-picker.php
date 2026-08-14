<?php
/**
 * Выбор иконки в админке с превью.
 *
 * ACF-поля с классом-обёрткой `bsi-icon-picker` (тип radio, choices — slug'и
 * из bsi_ui_icons()) получают плитки с отрисованным SVG вместо простого списка.
 *
 * @package bsi
 */

declare(strict_types=1);

add_action('admin_enqueue_scripts', function (string $hook): void {
	if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
		return;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;

	if (!$screen || $screen->post_type !== 'insurance') {
		return;
	}

	require_once get_template_directory() . '/template-parts/ui/icon.php';

	if (!function_exists('bsi_ui_icons') || !function_exists('bsi_ui_icon_markup')) {
		return;
	}

	$icons = [];
	foreach (bsi_ui_icons() as $slug => $label) {
		$icons[$slug] = [
			'label' => $label,
			'svg' => bsi_ui_icon_markup($slug, 24),
		];
	}

	wp_register_script('bsi-icon-picker', '', ['jquery'], '1.0.0', true);
	wp_enqueue_script('bsi-icon-picker');
	wp_add_inline_script(
		'bsi-icon-picker',
		'window.bsiIconPicker = ' . wp_json_encode($icons) . ';' . bsi_icon_picker_js()
	);

	wp_register_style('bsi-icon-picker', false, [], '1.0.0');
	wp_enqueue_style('bsi-icon-picker');
	wp_add_inline_style('bsi-icon-picker', bsi_icon_picker_css());
});

/**
 * JS пикера: рисует SVG внутри каждого варианта.
 *
 * @return string
 */
function bsi_icon_picker_js(): string
{
	return <<<'JS'
(function () {
	var render = function (root) {
		var lists = (root || document).querySelectorAll('.bsi-icon-picker .acf-radio-list');

		Array.prototype.forEach.call(lists, function (list) {
			if (list.dataset.bsiIconPicker === 'done') {
				return;
			}
			list.dataset.bsiIconPicker = 'done';

			Array.prototype.forEach.call(list.querySelectorAll('li'), function (item) {
				var input = item.querySelector('input[type="radio"]');
				var label = item.querySelector('label');
				if (!input || !label) {
					return;
				}

				var icon = window.bsiIconPicker ? window.bsiIconPicker[input.value] : null;
				var preview = document.createElement('span');
				preview.className = 'bsi-icon-picker__preview';

				if (icon && icon.svg) {
					preview.innerHTML = icon.svg;
				} else {
					preview.textContent = '—';
					preview.classList.add('bsi-icon-picker__preview--empty');
				}

				label.insertBefore(preview, label.firstChild);
				item.classList.add('bsi-icon-picker__item');

				var sync = function () {
					item.classList.toggle('is-selected', input.checked);
				};

				input.addEventListener('change', function () {
					Array.prototype.forEach.call(list.querySelectorAll('li'), function (other) {
						other.classList.remove('is-selected');
					});
					sync();
				});

				sync();
			});
		});
	};

	document.addEventListener('DOMContentLoaded', function () {
		render(document);
	});

	if (window.acf && window.acf.addAction) {
		// Строки repeater'а добавляются динамически.
		window.acf.addAction('append', function ($el) {
			render($el && $el[0] ? $el[0] : document);
		});
		window.acf.addAction('ready', function () {
			render(document);
		});
	}
})();
JS;
}

/**
 * Стили пикера.
 *
 * @return string
 */
function bsi_icon_picker_css(): string
{
	return <<<'CSS'
.bsi-icon-picker .acf-radio-list {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
	gap: 6px;
	margin: 0;
	max-height: 260px;
	overflow-y: auto;
	padding: 4px;
	border: 1px solid #dcdcde;
	border-radius: 6px;
	background: #fff;
}
.bsi-icon-picker .acf-radio-list li {
	margin: 0;
}
.bsi-icon-picker .acf-radio-list li label {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 6px 8px;
	border: 1px solid transparent;
	border-radius: 6px;
	cursor: pointer;
	font-size: 12px;
	line-height: 1.3;
	color: #1d2327;
}
.bsi-icon-picker .acf-radio-list li label:hover {
	background: #f6f7f7;
}
.bsi-icon-picker .acf-radio-list li.is-selected label {
	border-color: #ee3145;
	background: #fdf2f3;
}
.bsi-icon-picker .acf-radio-list input[type="radio"] {
	margin: 0;
	flex-shrink: 0;
}
.bsi-icon-picker__preview {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 28px;
	height: 28px;
	flex-shrink: 0;
	color: #ee3145;
}
.bsi-icon-picker__preview svg {
	width: 22px;
	height: 22px;
}
.bsi-icon-picker__preview--empty {
	color: #a7aaad;
}
CSS;
}
