<?php
/**
 * Хелперы раздела «Страхование».
 *
 * @package bsi
 */

declare(strict_types=1);

if (!function_exists('bsi_insurance_page_id')) {
	/**
	 * ID страницы раздела «Страхование» (шаблон page-insurance.php).
	 *
	 * На ней лежат общие для раздела блоки: шаги оформления и частые вопросы.
	 *
	 * @return int 0, если страница не найдена.
	 */
	function bsi_insurance_page_id(): int
	{
		static $page_id = null;

		if ($page_id !== null) {
			return $page_id;
		}

		$pages = get_pages([
			'meta_key' => '_wp_page_template',
			'meta_value' => 'page-insurance.php',
			'number' => 1,
		]);

		$page_id = !empty($pages) ? (int) $pages[0]->ID : 0;

		return $page_id;
	}
}
