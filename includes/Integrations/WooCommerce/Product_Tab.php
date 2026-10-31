<?php
/**
 * "Documentation" tab on the WooCommerce single product page.
 *
 * @package ItsDZ\Doczur\Integrations\WooCommerce
 */

namespace ItsDZ\Doczur\Integrations\WooCommerce;

use ItsDZ\Doczur\Utils\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Adds and renders the product-page documentation tab.
 *
 * Rendered directly in PHP from Product_Linker rather than through a REST
 * route — the product page is public-facing, so there is no reason to add
 * an HTTP endpoint surface for data that only ever needs a server-side
 * render.
 */
final class Product_Tab {
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
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_product_tabs', array( $this, 'maybe_add_tab' ) );
	}

	/**
	 * Add the Documentation tab, but only when it has content.
	 *
	 * An empty tab is never shown.
	 *
	 * @param array<string, mixed> $tabs Registered WooCommerce product tabs.
	 * @return array<string, mixed>
	 */
	public function maybe_add_tab( $tabs ) {
		$product_id = get_the_ID();

		if ( ! $product_id || empty( $this->get_articles( $product_id ) ) ) {
			return $tabs;
		}

		$tabs['itsdz_documentation'] = array(
			'title'    => __( 'Documentation', 'itsmanzur-docs' ),
			'priority' => 25,
			'callback' => array( $this, 'render_tab' ),
		);

		return $tabs;
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render_tab() {
		$product_id = get_the_ID();
		$articles   = $product_id ? $this->get_articles( $product_id ) : array();

		if ( ! $articles ) {
			return;
		}
		?>
		<div class="itsdz-product-docs">
			<ul class="itsdz-product-docs-list">
				<?php foreach ( $articles as $article ) : ?>
					<li>
						<a href="<?php echo esc_url( $article['url'] ); ?>">
							<?php echo esc_html( $article['title'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Get the cached, render-ready article list for a product.
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return array<int, array{id: int, title: string, url: string}>
	 */
	private function get_articles( $product_id ) {
		$key = 'product-tab:' . $product_id . ':' . Cache::generation( 'product-tab', $product_id );

		$cached = Cache::get( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$articles = array_map(
			static function ( $article ) {
				return array(
					'id'    => $article->ID,
					'title' => get_the_title( $article ),
					'url'   => get_permalink( $article ),
				);
			},
			$this->linker->get_articles_for_product( $product_id )
		);

		Cache::set( $key, $articles, HOUR_IN_SECONDS );

		return $articles;
	}
}
