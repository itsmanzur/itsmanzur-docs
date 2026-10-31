<?php
/**
 * Bootstrap for the WordPress integration test suite.
 *
 * Unlike tests/bootstrap.php — which stubs WordPress functions so the unit
 * suite can run in isolation — this boots a real WordPress instance against a
 * dedicated test database, so post types, taxonomies, REST routes and the
 * search index all behave exactly as they do in production.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

$itsdz_plugin_dir = dirname( __DIR__, 2 );
$itsdz_autoloader = $itsdz_plugin_dir . '/vendor/autoload.php';

if ( ! is_readable( $itsdz_autoloader ) ) {
	throw new RuntimeException( 'Composer dependencies are missing. Run composer install.' );
}

require_once $itsdz_autoloader;

/*
 * Tell wp-phpunit which configuration file to load. An environment variable is
 * the only interface the library offers for this, and it must be set before
 * its bootstrap runs.
 */
// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Required by wp-phpunit; test-only bootstrap.
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . $itsdz_plugin_dir . '/tests/wp-tests-config.php' );

$itsdz_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $itsdz_tests_dir ) {
	$itsdz_tests_dir = $itsdz_plugin_dir . '/vendor/wp-phpunit/wp-phpunit';
}

if ( ! is_readable( $itsdz_tests_dir . '/includes/functions.php' ) ) {
	throw new RuntimeException( 'The WordPress test library was not found. Run composer install.' );
}

require_once $itsdz_tests_dir . '/includes/functions.php';

/*
 * A real WooCommerce install is not loaded here — see wc-stubs.php. Must happen before the
 * plugin loads, since Plugin::get_services() decides whether to register the WooCommerce
 * integration by checking function_exists('WC') once, at boot.
 */
require_once __DIR__ . '/wc-stubs.php';

/**
 * Load Nirdeshio before WordPress finishes booting so its hooks are in place.
 */
tests_add_filter(
	'muplugins_loaded',
	static function () use ( $itsdz_plugin_dir ) {
		require $itsdz_plugin_dir . '/itsmanzur-docs.php';
	}
);

/**
 * Run the activation routine once, so custom tables and capabilities exist.
 */
tests_add_filter(
	'setup_theme',
	static function () {
		ItsDZ\Doczur\Core\Activator::activate();
	}
);

require $itsdz_tests_dir . '/includes/bootstrap.php';
