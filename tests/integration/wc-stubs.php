<?php
/**
 * Minimal WooCommerce stand-ins for the integration suite.
 *
 * Real WooCommerce is not loaded here (see bootstrap.php for why) — this
 * defines only the surface Nirdeshio's WooCommerce integration actually touches: the
 * `WooCommerce` marker class, the WC() accessor, wc_get_product(), and
 * WC_Data_Store::load('product')->search_products() — the real WooCommerce
 * free-text product search API (not wc_get_products(['s' => ...]), which
 * silently ignores 's' entirely; that bug shipped once already because
 * this stub used to implement wc_get_products() search "correctly" itself,
 * masking that the real function doesn't support it at all).
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch -- deliberately one small file: a marker class, a product stub class, and the handful of functions that only make sense alongside them, none of it autoloaded/PSR-4 mapped.

if ( ! class_exists( 'WooCommerce' ) ) {
	/**
	 * Marker class matching WooCommerce's own global class name.
	 */
	class WooCommerce {}
}

if ( ! function_exists( 'WC' ) ) {
	/**
	 * Stand-in for WC() — Integrations\WooCommerce\Bootstrap::register()
	 * checks function_exists('WC') as its own defensive second guard,
	 * independent of the function_exists() check Plugin::get_services()
	 * already makes.
	 *
	 * @return WooCommerce
	 */
	function WC() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- matches WooCommerce's own real function name.
		return new WooCommerce();
	}
}

if ( ! class_exists( 'Itsdz_Test_WC_Product' ) ) {
	/**
	 * Stand-in for WC_Product, exposing only the two methods Doczur calls.
	 */
	class Itsdz_Test_WC_Product {
		/**
		 * In-memory product registry, keyed by ID. Tests populate this
		 * directly and must clear it in tear_down().
		 *
		 * @var array<int, Itsdz_Test_WC_Product>
		 */
		public static $products = array();

		/**
		 * Product ID.
		 *
		 * @var int
		 */
		private $id;

		/**
		 * Product name.
		 *
		 * @var string
		 */
		private $name;

		/**
		 * Constructor.
		 *
		 * @param int    $id   Product ID.
		 * @param string $name Product name.
		 */
		public function __construct( $id, $name ) {
			$this->id   = $id;
			$this->name = $name;
		}

		/**
		 * Get the product ID.
		 *
		 * @return int
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get the product name.
		 *
		 * @return string
		 */
		public function get_name() {
			return $this->name;
		}
	}
}

if ( ! function_exists( 'wc_get_product' ) ) {
	/**
	 * Stand-in for wc_get_product().
	 *
	 * @param int $product_id Product ID.
	 * @return Itsdz_Test_WC_Product|false
	 */
	function wc_get_product( $product_id ) {
		return Itsdz_Test_WC_Product::$products[ (int) $product_id ] ?? false;
	}
}

if ( ! class_exists( 'Itsdz_Test_WC_Product_Data_Store' ) ) {
	/**
	 * Stand-in for the product data store, exposing only search_products() —
	 * the method WC_Data_Store::load('product') actually returns in real
	 * WooCommerce, matching the same signature.
	 */
	class Itsdz_Test_WC_Product_Data_Store {
		/**
		 * Return product IDs whose name contains $term (case-insensitive).
		 *
		 * @param string $term  Search term.
		 * @param string $type  Unused; real WooCommerce filters by product type.
		 * @param bool   $include_variations Unused here.
		 * @param bool   $all_statuses       Unused here.
		 * @param int    $limit Maximum results.
		 * @return int[]
		 */
		public function search_products( $term, $type = '', $include_variations = false, $all_statuses = false, $limit = null ) {
			unset( $type, $include_variations, $all_statuses );

			$term  = strtolower( $term );
			$limit = $limit ? (int) $limit : 20;
			$ids   = array();

			foreach ( Itsdz_Test_WC_Product::$products as $product ) {
				if ( str_contains( strtolower( $product->get_name() ), $term ) ) {
					$ids[] = $product->get_id();
				}

				if ( count( $ids ) >= $limit ) {
					break;
				}
			}

			return $ids;
		}
	}
}

if ( ! class_exists( 'WC_Data_Store' ) ) {
	/**
	 * Stand-in for WC_Data_Store::load(), which this code only ever calls
	 * with 'product'.
	 */
	class WC_Data_Store {
		/**
		 * Load a data store.
		 *
		 * @param string $store_name Data store name.
		 * @return Itsdz_Test_WC_Product_Data_Store
		 */
		public static function load( $store_name ) {
			unset( $store_name );

			return new Itsdz_Test_WC_Product_Data_Store();
		}
	}
}

// phpcs:enable Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch
