<?php
/**
 * Админка событийного тура: адаптивная сетка ACF-полей и пипетка цвета сектора со схемы зала.
 *
 * Сетка: ширина метабокса меряется container query — на узком экране поля шириной 25%
 * встают по два, уже 440px все поля во всю ширину.
 * Пипетка: кнопка у поля «Цвет на схеме» открывает схему зала, клик по ней ставит цвет.
 *
 * @package bsi
 */

declare(strict_types=1);

add_action('admin_enqueue_scripts', function (string $hook): void {
	if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
		return;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;

	if (!$screen || $screen->post_type !== 'event') {
		return;
	}

	$path = get_template_directory() . '/js/admin/event-sector-color.js';

	wp_enqueue_script(
		'bsi-event-sector-color',
		get_template_directory_uri() . '/js/admin/event-sector-color.js',
		['jquery', 'acf-input'],
		file_exists($path) ? (string) filemtime($path) : null,
		true
	);

	wp_add_inline_style('acf-input', bsi_event_admin_css());
});

function bsi_event_admin_css(): string
{
	return <<<'CSS'
.post-type-event .acf-postbox > .inside { container-type: inline-size; }

.post-type-event .acf-field[data-key="field_event_sector_date_prices"] .acf-repeater.-empty > .acf-table { display: none; }
.post-type-event .acf-field[data-key="field_event_sector_date_prices"] .acf-actions { text-align: left; }
.post-type-event .acf-field[data-key="field_event_sector_date_prices"] .acf-button.button-primary {
	float: none; background: #f6f7f7; border-color: #2271b1; color: #2271b1;
}
.post-type-event .acf-field[data-key="field_event_venue_scheme"] .image-wrap img { max-height: 320px; width: auto; }

.post-type-event .acf-field[data-key="field_event_sector_color"] .acf-input { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.post-type-event .acf-field[data-key="field_event_sector_color"] .wp-picker-container { margin: 0; }
.bsi-pipette-btn.button { display: inline-flex; align-items: center; gap: 4px; }
.bsi-pipette-btn .dashicons { font-size: 16px; width: 16px; height: 16px; }

@container (max-width: 760px) {
	.post-type-event .acf-fields > .acf-field[data-width="25"] { width: 50% !important; }
	.post-type-event .acf-fields > .acf-field[data-width="50"] { width: 100% !important; border-left: 0 !important; }
}
@container (max-width: 440px) {
	.post-type-event .acf-fields > .acf-field[data-width] { width: 100% !important; float: none; border-left: 0 !important; }
	.post-type-event .acf-repeater.-table { overflow-x: auto; }
	.post-type-event .acf-repeater.-table > .acf-table { min-width: 400px; }
}

.bsi-pipette { position: fixed; inset: 0; z-index: 100000; display: flex; flex-direction: column; background: rgba(0, 0, 0, .8); }
.bsi-pipette__bar { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fff; font-size: 14px; }
.bsi-pipette__title { flex: 1; }
.bsi-pipette__swatch { width: 28px; height: 28px; border-radius: 4px; background: #fff; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .2); }
.bsi-pipette__hex { min-width: 64px; font-family: monospace; }
.bsi-pipette__stage { flex: 1; display: flex; align-items: center; justify-content: center; overflow: auto; padding: 16px; }
.bsi-pipette__canvas { max-width: 100%; max-height: 100%; cursor: crosshair; background: #fff; }
CSS;
}
