<?php
/**
 * REST routes for managing article ↔ WooCommerce product links.
 *
 * @package ItsDZ\Doczur\Integrations\WooCommerce\REST
 */

namespace ItsDZ\Doczur\Integrations\WooCommerce\REST;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\REST\REST_Controller;
use ItsDZ\Doczur\Integrations\WooCommerce\Product_Linker;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-only management routes for the picker in the block editor sidebar.
 *
 * Registered under Free's own `itsdz/v1` namespace (via the shared
 * REST_Controller base class) rather than a separate `itsdz/v1` namespace of its own — one
 * unified API surface for the whole product, regardless of which plugin
 * happens to serve a given route. The public-facing product tab never goes
 * through REST at all — see Product_Tab.
 */
final class Product_Link_Controller extends REST_Controller {
	/**
	 * Product-link data access.
	 *
	 * @var Product_Linker
	 */
	private $linker;

	/**
	 * Constructor.
	 *
	 * @param Product_Linker $linker Product-link data access.
	 */
	public function __construct( Product_Linker $linker ) {
		$this->linker = $linker;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/product-links',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_links' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'article_id' => array(
							'required'          => true,
							// A bare 'is_numeric' fatals: WordPress calls
							// validate_callback with three arguments, but
							// is_numeric() only accepts one.
							'validate_callback' => static function ( $value ) {
								return is_numeric( $value );
							},
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_links' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/products/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_products' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Get the products currently linked to an article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_links( $request ) {
		$article = $this->get_post( $request->get_param( 'article_id' ), Article_Post_Type::POST_TYPE );

		if ( is_wp_error( $article ) ) {
			return $article;
		}

		$products = array();

		foreach ( $this->linker->get_links_for_article( $article->ID ) as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$products[] = array(
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
				);
			}
		}

		return rest_ensure_response( $products );
	}

	/**
	 * Replace the full set of products linked to an article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function set_links( $request ) {
		$article = $this->get_post( $request->get_param( 'article_id' ), Article_Post_Type::POST_TYPE );

		if ( is_wp_error( $article ) ) {
			return $article;
		}

		$raw_ids = $request->get_param( 'wc_product_ids' );

		if ( ! is_array( $raw_ids ) ) {
			return new \WP_Error(
				'itsdz_invalid_products',
				__( 'wc_product_ids must be an array of product IDs.', 'itsmanzur-docs' ),
				array( 'status' => 400 )
			);
		}

		$valid_ids = array();

		foreach ( $raw_ids as $raw_id ) {
			$product_id = absint( $raw_id );

			if ( ! $product_id ) {
				continue;
			}

			if ( ! wc_get_product( $product_id ) ) {
				return new \WP_Error(
					'itsdz_invalid_product',
					/* translators: %d: WooCommerce product ID. */
					sprintf( __( 'Product %d does not exist.', 'itsmanzur-docs' ), $product_id ),
					array( 'status' => 400 )
				);
			}

			$valid_ids[] = $product_id;
		}

		$this->linker->set_links( $article->ID, $valid_ids );

		return rest_ensure_response( array( 'wc_product_ids' => $valid_ids ) );
	}

	/**
	 * Search WooCommerce products by name, for the picker's type-ahead.
	 *
	 * `wc_get_products( [ 's' => $query ] )` is not a real search API —
	 * `'s'` is not one of WC_Product_Query's recognized query vars, so it
	 * gets silently absorbed and ignored, returning every product
	 * regardless of the query text. The actual, documented free-text
	 * search is on the product data store, the same one WooCommerce's own
	 * admin product-picker (WC_AJAX::json_search_products()) uses.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function search_products( $request ) {
		$query = trim( (string) $request->get_param( 'q' ) );

		if ( '' === $query ) {
			return rest_ensure_response( array() );
		}

		$product_ids = \WC_Data_Store::load( 'product' )->search_products( $query, '', false, false, 20 );

		$products = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$products[] = array(
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
				);
			}
		}

		return rest_ensure_response( $products );
	}
}
