<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package bsi
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function bsi_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'bsi_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function bsi_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'bsi_pingback_header' );

/**
 * Подготавливает элемент подборки для вывода
 *
 * @param array $row Массив данных элемента из ACF
 * @return array|null Подготовленный массив данных карточки или null если элемент невалиден
 */
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

	$location_title = $row['location_override'] ?? '';
	$price = $row['price'] ?? '';

	$resort_name = '';
	if ( ! $location_title ) {
		$resort_terms = get_the_terms( $post_id, 'resort' );
		if ( ! empty( $resort_terms ) && ! is_wp_error( $resort_terms ) ) {
			$resort_name = $resort_terms[0]->name;
		}
		$location_title = $resort_name;
	}

	$flag_url = '';
	$country_id = 0;

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
		$country_val = get_field( 'tour_country', $post_id );
		if ( $country_val instanceof WP_Post ) {
			$country_id = (int) $country_val->ID;
		} elseif ( is_array( $country_val ) ) {
			$country_id = (int) reset( $country_val );
		} else {
			$country_id = (int) $country_val;
		}
	}

	if ( $country_id && function_exists( 'offer_get_country_flag_url' ) ) {
		$flag_url = offer_get_country_flag_url( $country_id );
	}

	return [
		'url'            => $url,
		'image'          => $image,
		'type'           => $type,
		'tags'           => $tags,
		'title'          => $title,
		'flag'           => $flag_url,
		'location_title' => $location_title,
		'price'          => $price,
		'post_id'        => $post_id,
	];
}
