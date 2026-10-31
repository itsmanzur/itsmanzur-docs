<?php
/**
 * Re-runs the product-link table creation for sites upgrading from 1.1.x.
 *
 * @package ItsDZ\Doczur\Core\Migrations
 */

namespace ItsDZ\Doczur\Core\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Sites on 1.1.x already store database version 1.1.0, so the 1.1.0 migration
 * would never be triggered by a version comparison. This migration moves them
 * to 1.2.0 and makes sure the product-link table exists.
 */
final class Migration_1_2_0 {
	/**
	 * Migration version.
	 */
	const VERSION = '1.2.0';

	/**
	 * Create the product-link table if it is missing.
	 *
	 * @return array<int, string>
	 */
	public function up() {
		return ( new Migration_1_1_0() )->up();
	}
}
