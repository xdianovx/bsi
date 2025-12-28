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
		$country_val = get_field( 'tour_country', $post_id );
		if ( $country_val instanceof WP_Post ) {
			$country_id = (int) $country_val->ID;
		} elseif ( is_array( $country_val ) ) {
			$country_id = (int) reset( $country_val );
		} else {
			$country_id = (int) $country_val;
		}
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
