<?php
/**
 * WooCommerce integration entry point.
 *
 * @package ItsDZ\Doczur\Integrations\WooCommerce
 */

namespace ItsDZ\Doczur\Integrations\WooCommerce;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Utils\Cache;
use ItsDZ\Doczur\Integrations\WooCommerce\REST\Product_Link_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Wires up the product tab, the picker's REST routes, and cache invalidation.
 *
 * Only instantiated by Core\Plugin::get_services() when
 * function_exists('WC') at boot; register() repeats that check so a
 * WooCommerce deactivation mid-request can never reach a wc_*() call.
 */
final class Bootstrap implements Service {
	/**
	 * Product-link data access, shared by the tab renderer and REST routes.
	 *
	 * @var Product_Linker
	 */
	private $linker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->linker = new Product_Linker();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		( new Product_Tab( $this->linker ) )->register();
		( new Product_Link_Controller( $this->linker ) )->register();

		// The link table itself only changes through Product_Linker (which
		// already bumps the cache directly), but an already-linked
		// article's own title/status can change independently of its
		// links — bump every product it's linked to whenever it saves.
		add_action( 'save_post_' . Article_Post_Type::POST_TYPE, array( $this, 'bump_cache_for_article' ) );

		// Orphan-row prevention: a deleted product or article should never
		// leave a stale link row behind.
		add_action( 'before_delete_post', array( $this, 'cleanup_on_delete' ) );
	}

	/**
	 * Invalidate the product-tab cache for everything an article links to.
	 *
	 * @param int $article_id Article post ID.
	 * @return void
	 */
	public function bump_cache_for_article( $article_id ) {
		foreach ( $this->linker->get_links_for_article( $article_id ) as $wc_product_id ) {
			Cache::bump_generation( 'product-tab', $wc_product_id );
		}
	}

	/**
	 * Clean up link rows when either side of the relationship is deleted.
	 *
	 * @param int $post_id Deleted post ID.
	 * @return void
	 */
	public function cleanup_on_delete( $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( 'product' === $post_type ) {
			$this->linker->delete_for_product( $post_id );
		} elseif ( Article_Post_Type::POST_TYPE === $post_type ) {
			$this->linker->delete_for_article( $post_id );
		}
	}
}
