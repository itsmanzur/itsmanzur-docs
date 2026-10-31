<?php
/**
 * Documentation version taxonomy.
 *
 * @package ItsDZ\Doczur\Taxonomies
 */

namespace ItsDZ\Doczur\Taxonomies;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the version taxonomy used to tag articles with a product version.
 */
final class Version_Taxonomy implements Service {
	/**
	 * Taxonomy key.
	 */
	const TAXONOMY = 'itsdz_version';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 5 );
	}

	/**
	 * Register the version taxonomy; it has no admin menu of its own.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			array( Article_Post_Type::POST_TYPE ),
			array(
				'labels'             => array(
					'name'          => _x( 'Versions', 'taxonomy general name', 'itsmanzur-docs' ),
					'singular_name' => _x( 'Version', 'taxonomy singular name', 'itsmanzur-docs' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'hierarchical'       => true,
				'show_ui'            => false,
				'show_in_rest'       => true,
				'capabilities'       => Capabilities::taxonomy_map(),
				'rewrite'            => false,
			)
		);
	}
}
