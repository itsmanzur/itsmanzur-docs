<?php
/**
 * Product-link visibility rule tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\Integrations\WooCommerce\Product_Linker;
use PHPUnit\Framework\TestCase;

/**
 * Proves the dual-layer privacy rule in isolation, against plain stub
 * objects. The actual database-driven lookup in
 * Product_Linker::get_articles_for_product() is covered separately, by
 * WooCommerceProductLinkTest.php (integration).
 */
final class ProductLinkerTest extends TestCase {
	/**
	 * Build an article-shaped stub object.
	 *
	 * @param string $status Post status.
	 * @return object
	 */
	private function article( $status ) {
		return (object) array( 'post_status' => $status );
	}

	/**
	 * Build a KB-shaped stub object.
	 *
	 * @param string $status Post status.
	 * @return object
	 */
	private function kb( $status ) {
		return (object) array( 'post_status' => $status );
	}

	/**
	 * A published article in a published project is visible.
	 *
	 * @return void
	 */
	public function test_published_article_in_published_kb_is_visible() {
		$this->assertTrue(
			Product_Linker::is_article_publicly_visible( $this->article( 'publish' ), $this->kb( 'publish' ) )
		);
	}

	/**
	 * A draft article is never visible, regardless of its project.
	 *
	 * @return void
	 */
	public function test_draft_article_is_not_visible() {
		$this->assertFalse(
			Product_Linker::is_article_publicly_visible( $this->article( 'draft' ), $this->kb( 'publish' ) )
		);
	}

	/**
	 * A published article whose project is not published is not visible.
	 *
	 * @return void
	 */
	public function test_published_article_in_unpublished_kb_is_not_visible() {
		$this->assertFalse(
			Product_Linker::is_article_publicly_visible( $this->article( 'publish' ), $this->kb( 'draft' ) )
		);
	}

	/**
	 * A deleted article (get_post() returned null) is never visible.
	 *
	 * @return void
	 */
	public function test_missing_article_is_not_visible() {
		$this->assertFalse(
			Product_Linker::is_article_publicly_visible( null, $this->kb( 'publish' ) )
		);
	}

	/**
	 * An orphaned article — its KB no longer resolves — is not visible.
	 *
	 * @return void
	 */
	public function test_orphaned_article_is_not_visible() {
		$this->assertFalse(
			Product_Linker::is_article_publicly_visible( $this->article( 'publish' ), null )
		);
	}
}
