<?php
/**
 * Nirdeshio uninstall handler.
 *
 * Data is preserved unless the site owner explicitly opts in to deletion
 * (Settings → "Delete all data on uninstall"). When they do, this removes
 * everything the plugin created: knowledge bases and articles with their meta,
 * taxonomy terms, custom tables, options, transients, scheduled events and
 * the capability. On multisite it runs for every site that opted in.
 *
 * @package ItsDZ\Doczur
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove Nirdeshio data from the current site, if its owner opted in.
 *
 * @return void
 */
function itsdz_uninstall_site() {
	global $wpdb;

	if ( ! (bool) get_option( 'itsdz_delete_data_on_uninstall', false ) ) {
		return;
	}

	// Posts (and their meta). Post types are not registered during uninstall, which is fine for a query by name.
	foreach ( array( 'itsdz_doc', 'itsdz_kb' ) as $itsdz_post_type ) {
		do {
			$itsdz_ids = get_posts(
				array(
					'post_type'              => $itsdz_post_type,
					'post_status'            => 'any',
					'posts_per_page'         => 100,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'suppress_filters'       => true,
				)
			);

			foreach ( $itsdz_ids as $itsdz_id ) {
				wp_delete_post( $itsdz_id, true );
			}
		} while ( $itsdz_ids );
	}

	// Terms and term meta. wp_delete_term() needs the taxonomy registered, so register them for this request.
	foreach ( array( 'itsdz_section', 'itsdz_tag', 'itsdz_version', 'itsdz_glossary' ) as $itsdz_taxonomy ) {
		if ( ! taxonomy_exists( $itsdz_taxonomy ) ) {
			register_taxonomy( $itsdz_taxonomy, 'post', array( 'public' => false ) );
		}

		$itsdz_term_ids = get_terms(
			array(
				'taxonomy'   => $itsdz_taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( is_wp_error( $itsdz_term_ids ) ) {
			continue;
		}

		foreach ( $itsdz_term_ids as $itsdz_term_id ) {
			wp_delete_term( (int) $itsdz_term_id, $itsdz_taxonomy );
		}
	}

	// Named options.
	foreach ( array(
		'itsdz_delete_data_on_uninstall',
		'itsdz_db_version',
		'itsdz_plugin_version',
		'itsdz_capabilities_version',
		'itsdz_kb_slug_base',
		'itsdz_rewrite_version',
		'itsdz_rewrite_flush',
		'itsdz_onboarding',
		'itsdz_show_powered_by',
		'itsdz_llms_txt_enabled',
	) as $itsdz_option ) {
		delete_option( $itsdz_option );
	}

	// Named transients, so an external object cache is cleared too.
	foreach ( array( 'itsdz_migration_check', 'itsdz_view_buffer' ) as $itsdz_transient ) {
		delete_transient( $itsdz_transient );
	}

	// Cache generation counters and every transient the plugin writes (cache, rate limit, feedback, view buffer).
	foreach ( array( 'itsdz_cache_gen_', '_transient_itsdz_', '_transient_timeout_itsdz_' ) as $itsdz_prefix ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE option_name LIKE %s', $wpdb->options, $wpdb->esc_like( $itsdz_prefix ) . '%' ) );
	}

	delete_metadata( 'user', 0, 'itsdz_onboarding_notice_dismisses', '', true );

	// Scheduled events.
	wp_clear_scheduled_hook( 'itsdz_flush_views' );
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'itsdz_flush_views_as', array(), 'itsdz' );
	}

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
}

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $itsdz_site_id ) {
		switch_to_blog( (int) $itsdz_site_id );
		itsdz_uninstall_site();
		restore_current_blog();
	}
} else {
	itsdz_uninstall_site();
}
