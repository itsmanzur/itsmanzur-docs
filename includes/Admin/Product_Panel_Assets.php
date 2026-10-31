<?php
/**
 * WooCommerce product-link sidebar panel for the article editor.
 *
 * @package ItsDZ\Doczur\Admin
 */

namespace ItsDZ\Doczur\Admin;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the product-link panel bundle on the article edit screen, independently
 * of the main DoczurPanel — a separate registerPlugin() call.
 */
final class Product_Panel_Assets implements Service {
	/**
	 * Script handle.
	 */
	const HANDLE = 'itsdz-product-panel';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the panel bundle on the itsdz_doc block editor screen only.
	 *
	 * Only reached when Bootstrap::add_services() already found WooCommerce
	 * active (that's the only reason this Service exists at all), so no
	 * further active/inactive flag needs to reach the React side — the
	 * panel simply isn't there when it shouldn't be.
	 *
	 * @return void
	 */
	public function enqueue() {
		$screen = get_current_screen();

		if ( ! $screen || Article_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$asset_file  = ITSDZ_PLUGIN_DIR . 'build/product-panel.asset.php';
		$script_file = ITSDZ_PLUGIN_DIR . 'build/product-panel.js';

		if ( ! is_readable( $asset_file ) || ! is_readable( $script_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			self::HANDLE,
			ITSDZ_PLUGIN_URL . 'build/product-panel.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( self::HANDLE, 'itsmanzur-docs', ITSDZ_PLUGIN_DIR . 'languages' );
	}
}
