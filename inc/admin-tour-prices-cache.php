<?php
/**
 * Админка: отдельный пункт меню — сброс кэша цен туров (Samotour, карточки).
 *
 * @package BSI
 */

declare(strict_types=1);

const BSI_TOUR_PRICES_CACHE_PAGE = 'bsi-tour-prices-cache';

add_action(
	'admin_menu',
	static function (): void {
		add_menu_page(
			'Кэш цен туров',
			'Кэш цен туров',
			'manage_options',
			BSI_TOUR_PRICES_CACHE_PAGE,
			'bsi_render_tour_prices_cache_page',
			'dashicons-tickets-alt',
			33
		);
	}
);

/**
 * @return void
 */
function bsi_render_tour_prices_cache_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$cleared = isset( $_GET['bsi_cache_cleared'] ) ? (int) $_GET['bsi_cache_cleared'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p class="description">
			<?php
			esc_html_e(
				'Серверный кэш минимальных цен на карточках туров (Samotour). Срок жизни — до 3 часов. С настройками валют не связан.',
				'bsi'
			);
			?>
		</p>
		<?php if ( null !== $cleared ) : ?>
		<div class="notice notice-success is-dismissible"><p>
			<?php
			echo esc_html(
				$cleared > 0
					// translators: %d: number of deleted cache rows.
					? sprintf( __( 'Очищено записей: %d.', 'bsi' ), $cleared )
					: __( 'Кэш пуст (записей в transient не было).', 'bsi' )
			);
			?>
		</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'bsi_clear_tour_prices_cache', '_bsi_tour_prices_nonce' ); ?>
			<input type="hidden" name="action" value="bsi_clear_tour_prices_cache" />
			<?php
			submit_button(
				__( 'Сбросить кэш цен туров', 'bsi' ),
				'primary',
				'submit',
				false,
				array( 'id' => 'bsi-clear-tour-prices-cache' )
			);
			?>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_bsi_clear_tour_prices_cache', 'bsi_handle_clear_tour_prices_cache' );

/**
 * @return void
 */
function bsi_handle_clear_tour_prices_cache(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied', 'bsi' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'bsi_clear_tour_prices_cache', '_bsi_tour_prices_nonce' );

	require_once get_template_directory() . '/inc/services/PriceLoaderService.php';

	$deleted = PriceLoaderService::clearTourPricesCache( null );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'              => BSI_TOUR_PRICES_CACHE_PAGE,
				'bsi_cache_cleared' => (int) $deleted,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
