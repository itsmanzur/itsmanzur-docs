<?php
/**
 * Adds the WooCommerce product-link table.
 *
 * @package ItsDZ\Doczur\Core\Migrations
 */

namespace ItsDZ\Doczur\Core\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the product-links table.
 *
 * Run by the Migrator on activation and on upgrade; dbDelta makes it safe to
 * re-run.
 */
final class Migration_1_1_0 {
	/**
	 * Migration version.
	 */
	const VERSION = '1.1.0';

	/**
	 * Run the migration through WordPress dbDelta.
	 *
	 * @return array<int, string>
	 */
	public function up() {
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		return dbDelta( self::schema_query() );
	}

	/**
	 * Build the SQL statement consumed by dbDelta.
	 *
	 * @return string
	 */
	public static function schema_query() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::table_name();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			article_id bigint(20) unsigned NOT NULL,
			wc_product_id bigint(20) unsigned NOT NULL,
			display_order smallint(5) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY article_product (article_id, wc_product_id),
			KEY wc_product_id (wc_product_id),
			KEY article_id (article_id)
		) {$charset_collate};";
	}

	/**
	 * Return the prefixed table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'itsdz_product_links';
	}

	/**
	 * Check that the table exists.
	 *
	 * @return bool
	 */
	public static function is_complete() {
		global $wpdb;

		$table_name = self::table_name();

		// A live schema check must bypass cache so failed migrations can self-repair.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found_table = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		return $table_name === $found_table;
	}
}
