<?php

function bsi_body_classes( $classes ) {
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'bsi_body_classes' );

function bsi_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'bsi_pingback_header' );

function bsi_prepare_offer_item( $row ) {
	$post_obj = $row['post'] ?? null;
	if ( ! $post_obj instanceof WP_Post ) {
		return null;
	}

	$post_id = $post_obj->ID;
	$post_type = $post_obj->post_type;

	$badges = $row['badges'] ?? [];
	$tags = [];
	if ( is_array( $badges ) ) {
		foreach ( $badges as $t ) {
			if ( ! empty( $t->name ) ) {
				$tags[] = $t->name;
			}
		}
	}

	$title = get_the_title( $post_id );
	$url = ( $row['link_override'] ?? '' ) ?: get_permalink( $post_id );

	$image = '';
	$thumb = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( $thumb ) {
		$image = $thumb;
	}

	$type_obj = get_post_type_object( $post_type );
	$type = $type_obj && ! empty( $type_obj->labels->singular_name ) ? $type_obj->labels->singular_name : '';

	$rating = 0;
	if ( $post_type === 'hotel' && function_exists( 'get_field' ) ) {
		$rating_val = get_field( 'rating', $post_id );
		if ( is_numeric( $rating_val ) ) {
			$rating = (int) $rating_val;
		}
	}

	$location_title = $row['location_override'] ?? '';
	$price = $row['price'] ?? '';
	
	if ( ! $price && $post_type === 'tour' && function_exists( 'get_field' ) ) {
		$tour_price = get_field( 'price_from', $post_id );
		if ( $tour_price ) {
			if ( is_numeric( $tour_price ) ) {
				$price = number_format( (float) $tour_price, 0, '.', ' ' );
			} else {
				$price = (string) $tour_price;
			}
		}
	}

	$flag_url = '';
	$country_id = 0;
	$country_title = '';

	if ( $post_type === 'hotel' && function_exists( 'get_field' ) ) {
		$country_val = get_field( 'hotel_country', $post_id );
		if ( $country_val instanceof WP_Post ) {
			$country_id = (int) $country_val->ID;
		} elseif ( is_array( $country_val ) ) {
			$country_id = (int) reset( $country_val );
		} else {
			$country_id = (int) $country_val;
		}
	} elseif ( $post_type === 'tour' && function_exists( 'get_field' ) ) {
		$country_id = function_exists( 'bsi_get_tour_primary_country_id' ) ? bsi_get_tour_primary_country_id( (int) $post_id ) : 0;
	}

	if ( $country_id ) {
		$country_title = get_the_title( $country_id );
		if ( function_exists( 'offer_get_country_flag_url' ) ) {
			$flag_url = offer_get_country_flag_url( $country_id );
		}
	}

	$location_extra = '';
	if ( ! $location_title && $country_title ) {
		$location_title = $country_title;
	}

	return [
		'url'            => $url,
		'image'          => $image,
		'type'           => $type,
		'tags'           => $tags,
		'title'          => $title,
		'rating'         => $rating,
		'flag'           => $flag_url,
		'location_title' => $location_title,
		'location_extra' => $location_extra,
		'price'          => $price,
		'post_id'        => $post_id,
	];
}

/**
 * Логотип в шапке — часть первого экрана и кандидат в LCP.
 * Smush переводит все изображения на data-src и подставляет их только
 * после выполнения JS; класс no-lazyload оставляет логотип обычным
 * изображением, которое браузер грузит сразу.
 */
add_filter('get_custom_logo', function ($html) {
	if (!is_string($html) || $html === '' || strpos($html, '<img') === false) {
		return $html;
	}

	if (strpos($html, 'no-lazyload') !== false) {
		return $html;
	}

	return preg_replace(
		'/<img\s+([^>]*?)class="([^"]*)"/',
		'<img $1class="$2 no-lazyload" fetchpriority="high" decoding="async"',
		$html,
		1
	);
});

/**
 * Подставляет alt изображениям из медиатеки, у которых он не заполнен.
 *
 * Пустой alt допустим только для декоративных картинок; у контентных
 * он нужен и поиску по картинкам, и скринридерам. Берём подпись,
 * затем заголовок вложения, затем заголовок записи.
 */
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment) {
	if (!empty($attr['alt']) && trim((string) $attr['alt']) !== '') {
		return $attr;
	}

	if (!($attachment instanceof WP_Post)) {
		return $attr;
	}

	$candidates = [
		(string) $attachment->post_excerpt,
		(string) $attachment->post_title,
	];

	if ($attachment->post_parent) {
		$candidates[] = (string) get_the_title($attachment->post_parent);
	}

	foreach ($candidates as $candidate) {
		$candidate = trim(wp_strip_all_tags($candidate));

		// Имена вроде IMG_20260311_155358 смысла не несут.
		if ($candidate === '' || preg_match('/^(img|dsc|photo|image|screenshot)[-_\s]?\d+/i', $candidate)) {
			continue;
		}

		$attr['alt'] = $candidate;
		break;
	}

	return $attr;
}, 10, 2);

/**
 * Сбрасывает кеш правил перезаписи при их изменении.
 *
 * WordPress хранит правила в опции rewrite_rules и не перечитывает их
 * после выката кода. Новое правило (например, пагинация каталога туров)
 * без сброса просто не работает, а зайти в «Настройки → Постоянные
 * ссылки» после каждого деплоя никто не помнит.
 *
 * Значение константы меняем вручную вместе с правилами — сброс
 * выполнится один раз на всех окружениях.
 */
add_action('init', function (): void {
	$version = '2026-08-11-country-deposits';

	if (get_option('bsi_rewrite_rules_version') === $version) {
		return;
	}

	flush_rewrite_rules(false);
	update_option('bsi_rewrite_rules_version', $version, false);
}, 99);

/**
 * Убирает «Заголовок 1» из выпадающего списка форматов редактора.
 *
 * H1 на странице должен быть один и выводиться шаблоном. Когда редактор
 * позволяет вставить ещё один в текст, на странице оказывается два-три
 * заголовка первого уровня — поисковики считают это ошибкой структуры.
 * Оставляем H2-H6: они для подзаголовков и нужны.
 */
add_filter('tiny_mce_before_init', function (array $settings): array {
	$settings['block_formats'] = 'Абзац=p;Заголовок 2=h2;Заголовок 3=h3;'
		. 'Заголовок 4=h4;Заголовок 5=h5;Заголовок 6=h6;Цитата=blockquote;'
		. 'Преформатированный=pre';

	return $settings;
});

/**
 * Понижает H1 из контента до H2 при выводе.
 *
 * Страницы, где заголовок первого уровня уже вставлен в текст, чинить
 * вручную не нужно: шаблон отдаёт свой H1, а лишние из контента
 * становятся подзаголовками.
 */
add_filter('the_content', function ($content) {
	if (!is_string($content) || stripos($content, '<h1') === false) {
		return $content;
	}

	$content = preg_replace('/<h1(\s[^>]*)?>/i', '<h2$1>', $content);

	return preg_replace('/<\/h1>/i', '</h2>', $content);
}, 25);

/**
 * Дописывает mailto: ссылкам, где адрес указан без схемы.
 *
 * В контенте встречаются ссылки вида <a href="e.sikora@bsigroup.ru">.
 * Браузер считает такой href относительным путём, поэтому клик ведёт
 * на /kontakty/e.sikora%40bsigroup.ru и отдаёт 404 — в обходе таких
 * адресов набралось четыре штуки.
 */
add_filter('the_content', function ($content) {
	if (!is_string($content) || strpos($content, '@') === false) {
		return $content;
	}

	return preg_replace_callback(
		'/href=(["\'])(?!mailto:|tel:|https?:|ftp:|\/|#|\?)([^"\'\s]+@[^"\'\s]+\.[a-z]{2,})\1/i',
		static function (array $m): string {
			return 'href=' . $m[1] . 'mailto:' . $m[2] . $m[1];
		},
		$content
	);
}, 20);
