<?php
/**
 * Article ↔ WooCommerce product link storage.
 *
 * @package ItsDZ\Doczur\Integrations\WooCommerce
 */

namespace ItsDZ\Doczur\Integrations\WooCommerce;

use ItsDZ\Doczur\Frontend\Documentation;
use ItsDZ\Doczur\Utils\Cache;
use ItsDZ\Doczur\Core\Migrations\Migration_1_1_0;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the itsdz_product_links table.
 *
 * The table itself always exists once the plugin is activated (see
 * Migration_1_1_0/Bootstrap::activate()) regardless of WooCommerce's active
 * state — only this class's callers are gated behind a WooCommerce-active
 * check, kept at the Integrations\WooCommerce\Bootstrap/REST-controller layer.
 */
final class Product_Linker {
	/**
	 * Get the WooCommerce product IDs linked to an article.
	 *
	 * @param int $article_id Article post ID.
	 * @return int[]
	 */
	public function get_links_for_article( $article_id ) {
		global $wpdb;

		$table = Migration_1_1_0::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT wc_product_id FROM %i WHERE article_id = %d ORDER BY display_order',
				$table,
				absint( $article_id )
			)
		);

		return array_map( 'absint', $ids );
	}

	/**
	 * Replace the full set of products linked to an article.
	 *
	 * A simple replace (delete then insert) rather than incremental
	 * add/remove — the picker UI always submits the complete desired set,
	 * so there is no partial-update case to support.
	 *
	 * @param int   $article_id     Article post ID.
	 * @param int[] $wc_product_ids Product IDs, in display order.
	 * @return void
	 */
	public function set_links( $article_id, array $wc_product_ids ) {
		global $wpdb;

		$table      = Migration_1_1_0::table_name();
		$article_id = absint( $article_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'article_id' => $article_id ), array( '%d' ) );

		foreach ( array_values( array_unique( array_map( 'absint', $wc_product_ids ) ) ) as $order => $wc_product_id ) {
			if ( ! $wc_product_id ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table,
				array(
					'article_id'    => $article_id,
					'wc_product_id' => $wc_product_id,
					'display_order' => $order,
					'created_at'    => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%d', '%s' )
			);
		}

		Cache::bump_generation( 'product-tab-by-article', $article_id );
		foreach ( $wc_product_ids as $wc_product_id ) {
			Cache::bump_generation( 'product-tab', absint( $wc_product_id ) );
		}
	}

	/**
	 * Get the published articles linked to a WooCommerce product.
	 *
	 * Dual-layer privacy check, matching the free plugin's established
	 * pattern (Indexer + Search_Service, Documentation::get_article_kb() +
	 * Template_Loader::guard_article()): the link table can never be
	 * trusted alone, since an article or its parent project can be
	 * unpublished after the link row was created and left behind.
	 *
	 * @param int $wc_product_id WooCommerce product ID.
	 * @return \WP_Post[]
	 */
	public function get_articles_for_product( $wc_product_id ) {
		global $wpdb;

		$table = Migration_1_1_0::table_name();

		// First layer: only ever query for rows against this product; the
		// article's own status is not trustworthy from this table alone.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$article_ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT article_id FROM %i WHERE wc_product_id = %d ORDER BY display_order',
				$table,
				absint( $wc_product_id )
			)
		);

		$articles = array();

		foreach ( $article_ids as $article_id ) {
			$article = get_post( $article_id );
			$kb      = $article ? Documentation::get_article_kb( $article->ID ) : null;

			// Second layer: re-check status at render time, in case the
			// article or its project was unpublished since the link was made.
			if ( self::is_article_publicly_visible( $article, $kb ) ) {
				$articles[] = $article;
			}
		}

		return $articles;
	}

	/**
	 * Pure status check shared by the two privacy layers above.
	 *
	 * Kept as a standalone, dependency-free method so it can be unit
	 * tested against plain stub objects without touching the database.
	 *
	 * @param \WP_Post|null $article Candidate article, or null if it no longer exists.
	 * @param \WP_Post|null $kb      The article's parent project, or null if orphaned/unpublished.
	 * @return bool
	 */
	public static function is_article_publicly_visible( $article, $kb ) {
		if ( ! $article || 'publish' !== $article->post_status ) {
			return false;
		}

		return $kb && 'publish' === $kb->post_status;
	}

	/**
	 * Remove every link row for a product, e.g. once it has been deleted.
	 *
	 * @param int $wc_product_id WooCommerce product ID.
	 * @return void
	 */
	public function delete_for_product( $wc_product_id ) {
		global $wpdb;

		$table = Migration_1_1_0::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'wc_product_id' => absint( $wc_product_id ) ), array( '%d' ) );

		Cache::bump_generation( 'product-tab', absint( $wc_product_id ) );
	}

	/**
	 * Remove every link row for an article, e.g. once it has been deleted.
	 *
	 * @param int $article_id Article post ID.
	 * @return void
	 */
	public function delete_for_article( $article_id ) {
		global $wpdb;

		$table = Migration_1_1_0::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'article_id' => absint( $article_id ) ), array( '%d' ) );
	}
}
