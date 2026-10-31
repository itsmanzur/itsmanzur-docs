<?php
/**
 * Nirdeshio uninstall handler.
 *
 * Data is intentionally preserved unless the site owner explicitly opts in to
 * deletion. The cleanup implementation will be introduced with the data model.
 *
 * @package ItsDZ\Doczur
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$itsdz_delete_data = (bool) get_option( 'itsdz_delete_data_on_uninstall', false );

if ( ! $itsdz_delete_data ) {
	return;
}

delete_option( 'itsdz_delete_data_on_uninstall' );
delete_option( 'itsdz_db_version' );
delete_option( 'itsdz_capabilities_version' );
delete_option( 'itsdz_kb_slug_base' );
delete_option( 'itsdz_rewrite_version' );
delete_option( 'itsdz_rewrite_flush' );
delete_option( 'itsdz_onboarding' );
delete_metadata( 'user', 0, 'itsdz_onboarding_notice_dismisses', '', true );
delete_transient( 'itsdz_migration_check' );

global $wpdb;

$itsdz_tables = array(
	$wpdb->prefix . 'itsdz_search_index',
	$wpdb->prefix . 'itsdz_search_log',
	$wpdb->prefix . 'itsdz_feedback',
	$wpdb->prefix . 'itsdz_views',
	$wpdb->prefix . 'itsdz_product_links',
);

foreach ( $itsdz_tables as $itsdz_table ) {
	// Table names are generated internally and escaped as identifiers.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $itsdz_table ) );
}

$itsdz_roles = wp_roles();

foreach ( $itsdz_roles->role_objects as $itsdz_role ) {
	$itsdz_role->remove_cap( 'itsdz_manage_docs' );
}
