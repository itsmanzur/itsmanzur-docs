<?php
/**
 * Plugin Name:       Nirdeshio
 * Description:       Product documentation, knowledge base, and help center for WordPress.
 * Version:           1.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            ItsDZ
 * Text Domain:       itsmanzur-docs
 * Domain Path:       /languages
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ItsDZ\Doczur
 */

defined( 'ABSPATH' ) || exit;

define( 'ITSDZ_VERSION', '1.2.0' );
define( 'ITSDZ_DB_VERSION', '1.2.0' );
define( 'ITSDZ_PLUGIN_FILE', __FILE__ );
define( 'ITSDZ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ITSDZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ITSDZ_PLUGIN_DIR . 'includes/functions.php';

// Must be registered before WooCommerce loads.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', ITSDZ_PLUGIN_FILE, true );
		}
	}
);

$itsdz_autoloader = ITSDZ_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! is_readable( $itsdz_autoloader ) ) {
	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Nirdeshio could not start because its Composer dependencies are missing. Run composer install in the plugin directory.', 'itsmanzur-docs' )
			);
		}
	);

	return;
}

require_once $itsdz_autoloader;

register_activation_hook( __FILE__, array( ItsDZ\Doczur\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( ItsDZ\Doczur\Core\Deactivator::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		ItsDZ\Doczur\Core\Plugin::instance()->register();
	}
);
