<?php

/**
 * Единая пагинация каталогов и архивов.
 *
 * Разметка одна на весь сайт: <nav class="ui-pagination"> + ссылки .page-numbers
 * от paginate_links(). Стили — scss/ui/pagination.scss, пример — ui.html.
 * Стрелки «Назад»/«Вперёд» рисуются шевронами Lucide, а не символами ← →.
 *
 * Использование:
 *   bsi_pagination(['total' => $q->max_num_pages, 'current' => $paged]);
 *   bsi_pagination([...], ['class' => 'js-news-pagination', 'attrs' => ['data-tours-pagination' => '']]);
 *
 * Для AJAX-ответов, где обёртка уже есть на странице, — bsi_pagination_links().
 */

if (!function_exists('bsi_pagination_arrow')) {
	/**
	 * Подпись кнопки «Назад»/«Вперёд» с шевроном Lucide.
	 *
	 * @param string $direction prev|next
	 */
	function bsi_pagination_arrow(string $direction): string
	{
		$icon = '';
		if (function_exists('bsi_lucide_icon')) {
			$icon = bsi_lucide_icon($direction === 'prev' ? 'chevron-left' : 'chevron-right', [
				'width' => '18',
				'height' => '18',
				'stroke' => 'currentColor',
				'stroke-width' => '2',
				'class' => 'ui-pagination__icon',
			]);
		}

		$label = '<span class="ui-pagination__label">' . ($direction === 'prev' ? 'Назад' : 'Вперёд') . '</span>';

		return $direction === 'prev' ? $icon . $label : $label . $icon;
	}
}

if (!function_exists('bsi_pagination_links')) {
	/**
	 * HTML ссылок пагинации без обёртки — для вставки в готовый контейнер (AJAX).
	 *
	 * @param array $args Аргументы paginate_links(); prev_text/next_text/mid_size уже заданы.
	 */
	function bsi_pagination_links(array $args = []): string
	{
		$defaults = [
			'prev_text' => bsi_pagination_arrow('prev'),
			'next_text' => bsi_pagination_arrow('next'),
			'mid_size' => 2,
		];

		$links = paginate_links(array_merge($defaults, $args));

		return is_string($links) ? $links : '';
	}
}

if (!function_exists('bsi_pagination')) {
	/**
	 * Печатает <nav class="ui-pagination"> со ссылками. Пустая — ничего не выводит.
	 *
	 * @param array $args    Аргументы paginate_links().
	 * @param array $wrapper class — доп. классы обёртки, attrs — доп. атрибуты (data-*),
	 *                       always — печатать пустую обёртку и на одной странице
	 *                       (AJAX-каталоги ищут контейнер один раз при инициализации).
	 */
	function bsi_pagination(array $args = [], array $wrapper = []): void
	{
		$total = isset($args['total']) ? (int) $args['total'] : 0;
		$always = !empty($wrapper['always']);

		$links = $total > 1 ? bsi_pagination_links($args) : '';
		if ($links === '' && !$always) {
			return;
		}

		$class = 'ui-pagination';
		if (!empty($wrapper['class'])) {
			$class .= ' ' . trim((string) $wrapper['class']);
		}

		$attr_str = '';
		if (!empty($wrapper['attrs']) && is_array($wrapper['attrs'])) {
			foreach ($wrapper['attrs'] as $key => $value) {
				$attr_str .= ' ' . esc_attr((string) $key);
				if ($value !== '' && $value !== null) {
					$attr_str .= '="' . esc_attr((string) $value) . '"';
				}
			}
		}

		echo '<nav class="' . esc_attr($class) . '"' . $attr_str . '>' . $links . '</nav>';
	}
}
