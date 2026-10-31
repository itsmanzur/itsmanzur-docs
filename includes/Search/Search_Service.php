<?php
/**
 * FULLTEXT documentation search.
 *
 * @package ItsDZ\Doczur\Search
 */

namespace ItsDZ\Doczur\Search;

use ItsDZ\Doczur\Core\Migrations\Migration_1_0_0;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Utils\Cache;
use ItsDZ\Doczur\Utils\Content_Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Executes cached, publication-safe searches.
 */
final class Search_Service {
	/**
	 * Search one public documentation project.
	 *
	 * @param int    $kb_id Project ID.
	 * @param string $query Raw search query.
	 * @param int    $limit Result limit.
	 * @return array<int, array<string, mixed>>|\WP_Error
	 */
	public function search( $kb_id, $query, $limit = 10 ) {
		global $wpdb;

		$kb_id = absint( $kb_id );
		$kb    = Content_Validator::get_kb( $kb_id, true );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		$query = $this->normalize_query( $query );

		if ( mb_strlen( $query ) < 2 ) {
			return new \WP_Error(
				'itsdz_search_query_too_short',
				__( 'Enter at least two characters to search.', 'itsmanzur-docs' ),
				array( 'status' => 400 )
			);
		}

		$limit      = min( 20, max( 1, absint( $limit ) ) );
		$generation = Cache::generation( 'search', $kb_id );
		$cache_key  = 'search:' . $kb_id . ':' . $generation . ':' . md5( strtolower( $query ) . '|' . $limit );
		$cached     = Cache::get( $cache_key );

		if ( is_array( $cached ) ) {
			$cached_rows = array();

			foreach ( $cached as $cached_result ) {
				$cached_rows[] = (object) array(
					'article_id' => $cached_result['id'],
					'relevance'  => $cached_result['relevance'],
				);
			}

			$public_cached = $this->hydrate_public_results( $cached_rows, $kb_id );

			if ( count( $public_cached ) !== count( $cached ) ) {
				Cache::set( $cache_key, $public_cached, 5 * MINUTE_IN_SECONDS );
			}

			return $public_cached;
		}

		$table = Migration_1_0_0::table_names()['search_index'];
		$sql   = $wpdb->prepare(
			'SELECT article_id, MATCH(title, content_plain, keywords) AGAINST (%s IN NATURAL LANGUAGE MODE) AS relevance
			FROM %i
			WHERE kb_id = %d AND MATCH(title, content_plain, keywords) AGAINST (%s IN NATURAL LANGUAGE MODE)
			ORDER BY (relevance * weight) DESC, article_id DESC
			LIMIT %d',
			$query,
			$table,
			$kb_id,
			$query,
			$limit
		);

		// Publication state is checked again below so a stale index can never leak.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows    = $wpdb->get_results( $sql );
		$results = $this->hydrate_public_results( $rows, $kb_id );

		Cache::set( $cache_key, $results, 5 * MINUTE_IN_SECONDS );

		return $results;
	}

	/**
	 * Hydrate rows while enforcing the final public-content boundary.
	 *
	 * This check is intentionally independent of index state so a stale or
	 * manually corrupted index can never expose draft/private content.
	 *
	 * @param array<int, object> $rows  Search index rows.
	 * @param int                $kb_id Public project ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function hydrate_public_results( $rows, $kb_id ) {
		$results = array();

		foreach ( $rows as $row ) {
			$article = get_post( (int) $row->article_id );

			if ( ! $article || Article_Post_Type::POST_TYPE !== $article->post_type || 'publish' !== $article->post_status || absint( get_post_meta( $article->ID, '_itsdz_kb_id', true ) ) !== $kb_id ) {
				continue;
			}

			$results[] = array(
				'id'        => $article->ID,
				'title'     => get_the_title( $article ),
				'url'       => get_permalink( $article ),
				// Decoded: the modal sets this as plain text, so a raw &hellip; or &amp; would show as typed.
				'excerpt'   => wp_trim_words( html_entity_decode( wp_strip_all_tags( $article->post_content ), ENT_QUOTES, 'UTF-8' ), 30, '…' ),
				'relevance' => round( (float) $row->relevance, 4 ),
			);
		}

		return $results;
	}

	/**
	 * Normalize and bound a public search query.
	 *
	 * @param mixed $query Raw query.
	 * @return string
	 */
	private function normalize_query( $query ) {
		if ( ! is_scalar( $query ) ) {
			return '';
		}

		$query = preg_replace( '/\s+/u', ' ', sanitize_text_field( $query ) );

		return mb_substr( trim( $query ), 0, 100 );
	}
}
