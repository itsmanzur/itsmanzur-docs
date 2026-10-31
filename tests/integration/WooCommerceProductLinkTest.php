<?php
/**
 * WooCommerce product-link tests: migration, privacy, and REST routes.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use Itsdz_Test_WC_Product;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;
use ItsDZ\Doczur\Integrations\WooCommerce\Product_Linker;
use ItsDZ\Doczur\Core\Migrations\Migration_1_1_0;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Drives the product-link feature end to end.
 *
 * WooCommerce itself is stubbed (see tests/integration/wc-stubs.php)
 * rather than loaded for real — this code only ever touches the
 * `WooCommerce` marker class and wc_get_product()/wc_get_products().
 */
final class WooCommerceProductLinkTest extends WP_UnitTestCase {
	/**
	 * Data-access instance under test.
	 *
	 * @var Product_Linker
	 */
	private $linker;

	/**
	 * Published project used by most tests.
	 *
	 * @var int
	 */
	private $kb_id = 0;

	/**
	 * Set up an admin user, a project, and a clean product registry.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		Itsdz_Test_WC_Product::$products = array();
		$this->linker                    = new Product_Linker();

		Capabilities::install();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->kb_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		do_action( 'rest_api_init' );
	}

	/**
	 * Clear the stub product registry between tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		Itsdz_Test_WC_Product::$products = array();
		parent::tear_down();
	}

	/**
	 * Create an article attached to the fixture project.
	 *
	 * @param array<string, mixed> $args Post factory overrides.
	 * @return int
	 */
	private function make_article( $args = array() ) {
		$article_id = self::factory()->post->create(
			array_merge(
				array(
					'post_status' => 'publish',
					'post_type'   => Article_Post_Type::POST_TYPE,
				),
				$args
			)
		);
		update_post_meta( $article_id, '_itsdz_kb_id', $this->kb_id );

		return $article_id;
	}

	/**
	 * Register a stub WooCommerce product.
	 *
	 * @param int    $id   Product ID.
	 * @param string $name Product name.
	 * @return int
	 */
	private function make_product( $id, $name = 'Widget' ) {
		Itsdz_Test_WC_Product::$products[ $id ] = new Itsdz_Test_WC_Product( $id, $name );

		return $id;
	}

	/**
	 * The table always exists, and re-running the migration is a no-op.
	 *
	 * @return void
	 */
	public function test_migration_creates_table_and_is_idempotent() {
		$this->assertTrue( Migration_1_1_0::is_complete() );

		( new Migration_1_1_0() )->up();

		$this->assertTrue( Migration_1_1_0::is_complete() );
	}

	/**
	 * A draft article is never returned, even when directly linked.
	 *
	 * @return void
	 */
	public function test_get_articles_for_product_excludes_draft_articles() {
		$product_id = $this->make_product( 501 );
		$article_id = $this->make_article( array( 'post_status' => 'draft' ) );

		$this->linker->set_links( $article_id, array( $product_id ) );

		$this->assertSame( array(), $this->linker->get_articles_for_product( $product_id ) );
	}

	/**
	 * An article whose project is not published is never returned.
	 *
	 * @return void
	 */
	public function test_get_articles_for_product_excludes_unpublished_kb() {
		$draft_kb_id = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);
		$article_id  = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => Article_Post_Type::POST_TYPE,
			)
		);
		update_post_meta( $article_id, '_itsdz_kb_id', $draft_kb_id );

		$product_id = $this->make_product( 502 );
		$this->linker->set_links( $article_id, array( $product_id ) );

		$this->assertSame( array(), $this->linker->get_articles_for_product( $product_id ) );
	}

	/**
	 * Published, correctly-parented articles are returned in display order.
	 *
	 * @return void
	 */
	public function test_get_articles_for_product_returns_published_linked_articles() {
		$product_id = $this->make_product( 503 );
		$first      = $this->make_article( array( 'post_title' => 'First' ) );
		$second     = $this->make_article( array( 'post_title' => 'Second' ) );

		$this->linker->set_links( $first, array( $product_id ) );
		$this->linker->set_links( $second, array( $product_id ) );

		$articles = $this->linker->get_articles_for_product( $product_id );

		$this->assertCount( 2, $articles );
		$this->assertSame( array( $first, $second ), wp_list_pluck( $articles, 'ID' ) );
	}

	/**
	 * Deleting a product removes its link rows (orphan prevention).
	 *
	 * @return void
	 */
	public function test_deleting_a_product_cleans_up_its_links() {
		// A real post row, not just a stub registry entry: WordPress core's
		// own before_delete_post listeners assume get_post() resolves, so
		// deleting a product that doesn't actually exist as a post would
		// fatal on WP's own side before this cleanup ever runs.
		$product_id = self::factory()->post->create( array( 'post_type' => 'product' ) );
		$this->make_product( $product_id );
		$article_id = $this->make_article();
		$this->linker->set_links( $article_id, array( $product_id ) );

		$this->assertSame( array( $product_id ), $this->linker->get_links_for_article( $article_id ) );

		wp_delete_post( $product_id, true );

		$this->assertSame( array(), $this->linker->get_links_for_article( $article_id ) );
	}

	/**
	 * Deleting an article removes its link rows too.
	 *
	 * @return void
	 */
	public function test_deleting_an_article_cleans_up_its_links() {
		$product_id = $this->make_product( 505 );
		$article_id = $this->make_article();
		$this->linker->set_links( $article_id, array( $product_id ) );

		wp_delete_post( $article_id, true );

		$this->assertSame( array(), $this->linker->get_links_for_article( $article_id ) );
	}

	/**
	 * Dispatch a GET to the product-links route.
	 *
	 * @param int $article_id Article ID.
	 * @return \WP_REST_Response
	 */
	private function get_links_route( $article_id ) {
		$request = new WP_REST_Request( 'GET', '/itsdz/v1/product-links' );
		$request->set_param( 'article_id', $article_id );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Dispatch a POST to the product-links route.
	 *
	 * @param int   $article_id     Article ID.
	 * @param int[] $wc_product_ids Product IDs.
	 * @return \WP_REST_Response
	 */
	private function post_links_route( $article_id, array $wc_product_ids ) {
		$request = new WP_REST_Request( 'POST', '/itsdz/v1/product-links' );
		$request->set_param( 'article_id', $article_id );
		$request->set_param( 'wc_product_ids', $wc_product_ids );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * GET /product-links is rejected without the management capability.
	 *
	 * @return void
	 */
	public function test_rest_get_product_links_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$article_id = $this->make_article();
		$response   = $this->get_links_route( $article_id );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * GET /product-links returns the currently linked products.
	 *
	 * @return void
	 */
	public function test_rest_get_product_links_returns_linked_products() {
		$product_id = $this->make_product( 601, 'Pro License' );
		$article_id = $this->make_article();
		$this->linker->set_links( $article_id, array( $product_id ) );

		$response = $this->get_links_route( $article_id );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				array(
					'id'   => $product_id,
					'name' => 'Pro License',
				),
			),
			$data
		);
	}

	/**
	 * POST /product-links is rejected without the management capability.
	 *
	 * @return void
	 */
	public function test_rest_post_product_links_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$article_id = $this->make_article();
		$response   = $this->post_links_route( $article_id, array() );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * A non-existent product ID is rejected with 400, and nothing is saved.
	 *
	 * @return void
	 */
	public function test_rest_post_product_links_rejects_nonexistent_product() {
		$article_id = $this->make_article();

		$response = $this->post_links_route( $article_id, array( 999999 ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array(), $this->linker->get_links_for_article( $article_id ) );
	}

	/**
	 * A non-existent article ID is rejected with 404.
	 *
	 * @return void
	 */
	public function test_rest_post_product_links_rejects_nonexistent_article() {
		$response = $this->post_links_route( 999999, array() );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * POST replaces the full set rather than appending to it.
	 *
	 * @return void
	 */
	public function test_rest_post_product_links_replaces_full_set() {
		$article_id = $this->make_article();
		$first      = $this->make_product( 602 );
		$second     = $this->make_product( 603 );

		$this->post_links_route( $article_id, array( $first ) );
		$response = $this->post_links_route( $article_id, array( $second ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $second ), $this->linker->get_links_for_article( $article_id ) );
	}

	/**
	 * GET /products/search is rejected without the management capability.
	 *
	 * @return void
	 */
	public function test_rest_products_search_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$request = new WP_REST_Request( 'GET', '/itsdz/v1/products/search' );
		$request->set_param( 'q', 'widget' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * GET /products/search returns name matches only.
	 *
	 * @return void
	 */
	public function test_rest_products_search_returns_matches() {
		$this->make_product( 701, 'Blue Widget' );
		$this->make_product( 702, 'Red Gadget' );

		$request = new WP_REST_Request( 'GET', '/itsdz/v1/products/search' );
		$request->set_param( 'q', 'widget' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $data );
		$this->assertSame( 'Blue Widget', $data[0]['name'] );
	}

}
