<?php
/**
 * Nirdeshio database migration coordinator.
 *
 * @package ItsDZ\Doczur\Core\Migrations
 */

namespace ItsDZ\Doczur\Core\Migrations;

use ItsDZ\Doczur\Core\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Runs versioned migrations only when required.
 */
final class Migrator implements Service {
	/**
	 * Database version option.
	 */
	const VERSION_OPTION = 'itsdz_db_version';

	/**
	 * Daily migration-check throttle.
	 */
	const CHECK_TRANSIENT = 'itsdz_migration_check';

	/**
	 * Plugin version the last migration check ran for.
	 */
	const PLUGIN_VERSION_OPTION = 'itsdz_plugin_version';

	/**
	 * Register the admin-only migration check.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'maybe_migrate' ) );
	}

	/**
	 * Run a throttled version check in wp-admin.
	 *
	 * @return void
	 */
	public function maybe_migrate() {
		// A plugin update (including auto-updates, which skip the activation hook)
		// must be checked right away instead of waiting for the daily throttle.
		if ( (string) get_option( self::PLUGIN_VERSION_OPTION, '' ) !== ITSDZ_VERSION ) {
			delete_transient( self::CHECK_TRANSIENT );
		}

		if ( get_transient( self::CHECK_TRANSIENT ) ) {
			return;
		}

		$this->run();

		if ( $this->is_current() ) {
			set_transient( self::CHECK_TRANSIENT, '1', DAY_IN_SECONDS );
			update_option( self::PLUGIN_VERSION_OPTION, ITSDZ_VERSION, false );
		}
	}

	/**
	 * Run all outstanding migrations.
	 *
	 * @return array<int, string>
	 */
	public function run() {
		$installed_version = (string) get_option( self::VERSION_OPTION, '0.0.0' );
		$changes           = array();

		if ( version_compare( $installed_version, Migration_1_0_0::VERSION, '<' ) || ! Migration_1_0_0::is_complete() ) {
			$migration = new Migration_1_0_0();
			$changes   = $migration->up();
		}

		if ( version_compare( $installed_version, Migration_1_1_0::VERSION, '<' ) || ! Migration_1_1_0::is_complete() ) {
			$changes = array_merge( $changes, ( new Migration_1_1_0() )->up() );
		}

		if ( version_compare( $installed_version, Migration_1_2_0::VERSION, '<' ) ) {
			$changes = array_merge( $changes, ( new Migration_1_2_0() )->up() );
		}

		$schema_complete = Migration_1_0_0::is_complete() && Migration_1_1_0::is_complete();

		if ( $schema_complete && version_compare( $installed_version, ITSDZ_DB_VERSION, '<' ) ) {
			update_option( self::VERSION_OPTION, ITSDZ_DB_VERSION, false );
		}

		return $changes;
	}

	/**
	 * Determine whether code and database schema versions match.
	 *
	 * @return bool
	 */
	private function is_current() {
		$installed_version = (string) get_option( self::VERSION_OPTION, '0.0.0' );

		return ! version_compare( $installed_version, ITSDZ_DB_VERSION, '<' ) && Migration_1_0_0::is_complete() && Migration_1_1_0::is_complete();
	}
}
