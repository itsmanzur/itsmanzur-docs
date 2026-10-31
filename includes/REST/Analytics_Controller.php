<?php
/**
 * Analytics REST controller.
 *
 * Endpoints exposed here:
 *   GET /itsdz/v1/analytics/views?article_id=N   — total views for one article
 *   GET /itsdz/v1/analytics/views?kb_id=N        — view totals for all articles in a KB
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\Analytics\View_Tracker;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Provides read-only view count data to authenticated admin consumers.
 */
final class Analytics_Controller extends REST_Controller {

	/**
	 * REST resource base.
	 *
	 * @var string
	 */
	protected $rest_base = 'analytics';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		// GET /itsdz/v1/analytics/views.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/views',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_views' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->get_views_args(),
				),
				'schema' => array( $this, 'get_views_schema' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Endpoint: GET /analytics/views
	// -------------------------------------------------------------------------

	/**
	 * Return view count data for an article or a whole KB.
	 *
	 * Exactly one of `article_id` or `kb_id` must be supplied.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_views( \WP_REST_Request $request ) {
		$article_id = absint( $request->get_param( 'article_id' ) );
		$kb_id      = absint( $request->get_param( 'kb_id' ) );

		if ( ! $article_id && ! $kb_id ) {
			return new \WP_Error(
				'itsdz_analytics_missing_param',
				__( 'Provide either article_id or kb_id.', 'itsmanzur-docs' ),
				array( 'status' => 400 )
			);
		}

		// Single-article view count.
		if ( $article_id ) {
			$article = $this->get_post( $article_id, Article_Post_Type::POST_TYPE );

			if ( is_wp_error( $article ) ) {
				return $article;
			}

			$total = View_Tracker::get_total( $article_id );

			return rest_ensure_response(
				array(
					'article_id' => $article_id,
					'total'      => $total,
				)
			);
		}

		// KB-wide view totals.
		$kb = $this->get_post( $kb_id, KB_Post_Type::POST_TYPE );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		$totals = View_Tracker::get_kb_totals( $kb_id );

		return rest_ensure_response(
			array(
				'kb_id'    => $kb_id,
				'articles' => $totals,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Args & Schema
	// -------------------------------------------------------------------------

	/**
	 * Declare accepted query parameters.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_views_args() {
		return array(
			'article_id' => array(
				'description'       => __( 'Article post ID to fetch views for.', 'itsmanzur-docs' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'kb_id'      => array(
				'description'       => __( 'Knowledge base ID to fetch aggregated views for.', 'itsmanzur-docs' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * JSON Schema for the view count response.
	 *
	 * @return array<string, mixed>
	 */
	public function get_views_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'itsdz-analytics-views',
			'type'       => 'object',
			'properties' => array(
				'article_id' => array(
					'description' => __( 'Article post ID (single-article response only).', 'itsmanzur-docs' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total'      => array(
					'description' => __( 'Total recorded views for the article.', 'itsmanzur-docs' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'kb_id'      => array(
					'description' => __( 'Knowledge base ID (KB response only).', 'itsmanzur-docs' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'articles'   => array(
					'description'          => __( 'Map of article_id to total view count.', 'itsmanzur-docs' ),
					'type'                 => 'object',
					'context'              => array( 'view' ),
					'additionalProperties' => array( 'type' => 'integer' ),
					'readonly'             => true,
				),
			),
		);
	}
}
