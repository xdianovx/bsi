<?php
/**
 * Инлайновая иконка Lucide.
 *
 * Файлы — официальные SVG из репозитория lucide-icons/lucide, лежат в
 * img/icons/lucide/<slug>.svg. Рендерится инлайном, чтобы цвет наследовался
 * через currentColor.
 *
 * get_template_part('template-parts/ui/icon', null, ['name' => 'shield-check', 'size' => 24]);
 *
 * @package bsi
 */

declare(strict_types=1);

if (!function_exists('bsi_ui_icons')) {
	/**
	 * Белый список иконок: slug (= имя файла Lucide) => подпись для админки.
	 *
	 * @return array<string, string>
	 */
	function bsi_ui_icons(): array
	{
		return [
			'shield-check' => 'Щит с галочкой',
			'badge-check' => 'Значок с галочкой',
			'circle-check' => 'Галочка в круге',
			'ban' => 'Запрет',
			'heart-pulse' => 'Сердце с пульсом',
			'stethoscope' => 'Стетоскоп',
			'briefcase-medical' => 'Медицинский кейс',
			'cross' => 'Медицинский крест',
			'hospital' => 'Больница',
			'baby' => 'Ребёнок',
			'life-buoy' => 'Спасательный круг',
			'plane' => 'Самолёт',
			'luggage' => 'Багаж',
			'car' => 'Автомобиль',
			'globe' => 'Глобус',
			'map-pin' => 'Точка на карте',
			'mountain-snow' => 'Горы / спорт',
			'umbrella' => 'Зонт',
			'wallet' => 'Кошелёк',
			'credit-card' => 'Карта оплаты',
			'receipt-text' => 'Счёт / чек',
			'calendar-x' => 'Отмена поездки',
			'clock' => 'Часы',
			'file-text' => 'Документ',
			'scale' => 'Юридическая помощь',
			'handshake' => 'Рукопожатие',
			'users' => 'Люди',
			'phone-call' => 'Телефон',
		];
	}
}

if (!function_exists('bsi_ui_icon_svg')) {
	/**
	 * Возвращает содержимое SVG-иконки (внутренности тега <svg>).
	 *
	 * @param string $name Slug иконки из белого списка.
	 * @return string Пустая строка, если иконки нет.
	 */
	function bsi_ui_icon_svg(string $name): string
	{
		static $cache = [];

		if (isset($cache[$name])) {
			return $cache[$name];
		}

		if (!isset(bsi_ui_icons()[$name])) {
			return '';
		}

		$path = get_template_directory() . '/img/icons/lucide/' . $name . '.svg';

		if (!is_readable($path)) {
			return '';
		}

		$svg = (string) file_get_contents($path);

		// Оставляем только внутренности <svg>, атрибуты задаём сами.
		if (preg_match('~<svg[^>]*>(.*)</svg>~is', $svg, $matches)) {
			$cache[$name] = trim($matches[1]);
			return $cache[$name];
		}

		return '';
	}
}

if (!function_exists('bsi_ui_icon_markup')) {
	/**
	 * Готовая разметка иконки.
	 *
	 * @param string $name  Slug иконки.
	 * @param int    $size  Размер в пикселях.
	 * @param string $class Дополнительные классы.
	 * @return string
	 */
	function bsi_ui_icon_markup(string $name, int $size = 24, string $class = ''): string
	{
		$inner = bsi_ui_icon_svg($name);

		if ($inner === '') {
			return '';
		}

		return sprintf(
			'<svg class="%s" xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
			esc_attr(trim('bsi-icon ' . $class)),
			$size,
			$size,
			$inner
		);
	}
}

if (!isset($args)) {
	return;
}

$icon_name = isset($args['name']) ? (string) $args['name'] : '';
$icon_class = isset($args['class']) ? (string) $args['class'] : '';
$icon_size = isset($args['size']) ? (int) $args['size'] : 24;

// Разметка собирается из локальных SVG-файлов Lucide, пользовательских данных нет.
echo bsi_ui_icon_markup($icon_name, $icon_size, $icon_class); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
