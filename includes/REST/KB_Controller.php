<?php
/**
 * Documentation project REST controller.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Utils\Cache;
use ItsDZ\Doczur\Utils\Content_Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin CRUD for documentation projects.
 */
final class KB_Controller extends REST_Controller {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'kb';

	/**
	 * Register hooks: routes plus the overview cache invalidator.
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		add_action( 'save_post_' . Article_Post_Type::POST_TYPE, array( $this, 'bump_overview_cache' ), 10, 2 );

		// save_post fires mid wp_insert_post() / wp_update_post(), which is
		// too early to see _itsdz_kb_id when it is attached the way
		// Article_Controller does it: insert first, then a separate
		// update_post_meta() call. Article_Controller::create_item() is
		// exactly that shape, so without this second hook a brand-new
		// article would never invalidate the KB it was just filed under.
		add_action( 'added_post_meta', array( $this, 'bump_overview_cache_on_meta_change' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'bump_overview_cache_on_meta_change' ), 10, 4 );
	}

	/**
	 * Invalidate the saved article's project overview cache.
	 *
	 * Handles everything that changes an article without touching its KB
	 * assignment (status, title, content) — for the KB assignment itself,
	 * see bump_overview_cache_on_meta_change().
	 *
	 * @param int      $post_id Article ID (unused; $post carries it too).
	 * @param \WP_Post $post    Article object.
	 * @return void
	 */
	public function bump_overview_cache( $post_id, $post ) {
		unset( $post_id );

		$kb_id = absint( get_post_meta( $post->ID, '_itsdz_kb_id', true ) );

		if ( $kb_id ) {
			Cache::bump_generation( 'kb-overview', $kb_id );
		}
	}

	/**
	 * Invalidate the overview cache when something the overview reads
	 * changes without a full save_post — either an article's KB assignment,
	 * or its review date (both are written via a direct update_post_meta()
	 * call from Article_Controller, which never fires save_post).
	 *
	 * Added/updated_post_meta fire for every post type's every meta key, not
	 * just these, so the key is checked before doing anything.
	 *
	 * @param int    $meta_id    Meta row ID (unused).
	 * @param int    $post_id    Post ID (not necessarily an article).
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value New value.
	 * @return void
	 */
	public function bump_overview_cache_on_meta_change( $meta_id, $post_id, $meta_key, $meta_value ) {
		unset( $meta_id );

		if ( '_itsdz_kb_id' === $meta_key ) {
			$kb_id = absint( $meta_value );
		} elseif ( '_itsdz_last_reviewed' === $meta_key ) {
			$kb_id = absint( get_post_meta( $post_id, '_itsdz_kb_id', true ) );
		} else {
			return;
		}

		if ( $kb_id ) {
			Cache::bump_generation( 'kb-overview', $kb_id );
		}
	}

	/**
	 * Register project routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->write_args( true ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->write_args( false ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/overview',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_overview' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					// A bare 'is_numeric' fatals here: WordPress calls
					// validate_callback with three arguments (value, request,
					// param key), but is_numeric() only accepts one.
					'id' => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value );
						},
					),
				),
			)
		);
	}

	/**
	 * List projects.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$posts = get_posts(
			array(
				'orderby'        => 'date',
				'order'          => 'DESC',
				'posts_per_page' => 100,
				'post_status'    => array( 'draft', 'publish' ),
				'post_type'      => KB_Post_Type::POST_TYPE,
			)
		);

		return rest_ensure_response( array_map( array( $this, 'prepare_item' ), $posts ) );
	}

	/**
	 * Get one project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$post = $this->get_post( $request['id'], KB_Post_Type::POST_TYPE );

		return is_wp_error( $post ) ? $post : rest_ensure_response( $this->prepare_item( $post ) );
	}

	/**
	 * Create a documentation project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		/**
		 * Cap the number of documentation projects a site may create.
		 *
		 * 0 (the default) means no cap. Nirdeshio never limits projects itself. It
		 * exists so a site owner (or another plugin) can impose a limit of
		 * their own if they want one; nothing in Nirdeshio itself ever sets
		 * this away from unlimited.
		 *
		 * @param int $maximum Maximum number of projects, 0 for unlimited.
		 */
		$maximum = (int) apply_filters( 'itsdz_max_documentation_projects', 0 );

		if ( $maximum > 0 ) {
			$existing = get_posts(
				array(
					'fields'         => 'ids',
					'posts_per_page' => $maximum,
					'post_status'    => array( 'draft', 'publish', 'pending', 'private' ),
					'post_type'      => KB_Post_Type::POST_TYPE,
				)
			);

			if ( count( $existing ) >= $maximum ) {
				return new \WP_Error(
					'itsdz_kb_limit_reached',
					sprintf(
						/* translators: %d: maximum number of documentation projects allowed. */
						_n(
							'A maximum of %d documentation project is allowed.',
							'A maximum of %d documentation projects are allowed.',
							$maximum,
							'itsmanzur-docs'
						),
						$maximum
					),
					array( 'status' => 403 )
				);
			}
		}

		$post_id = wp_insert_post( $this->post_data( $request ), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->update_meta( $post_id, $request->get_param( 'meta' ) );

		$response = rest_ensure_response( $this->prepare_item( get_post( $post_id ) ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update one project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$post = $this->get_post( $request['id'], KB_Post_Type::POST_TYPE );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data       = $this->post_data( $request, $post );
		$data['ID'] = $post->ID;
		$result     = wp_update_post( $data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->update_meta( $post->ID, $request->get_param( 'meta' ) );

		return rest_ensure_response( $this->prepare_item( get_post( $post->ID ) ) );
	}

	/**
	 * Trash or permanently delete a project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$post = $this->get_post( $request['id'], KB_Post_Type::POST_TYPE );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$force   = rest_sanitize_boolean( $request->get_param( 'force' ) );
		$deleted = $force ? wp_delete_post( $post->ID, true ) : wp_trash_post( $post->ID );

		if ( ! $deleted ) {
			return new \WP_Error( 'itsdz_delete_failed', __( 'The documentation project could not be deleted.', 'itsmanzur-docs' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $post->ID,
			)
		);
	}

	/**
	 * Build the at-a-glance overview for a project's Documentation screen.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_overview( $request ) {
		$kb = Content_Validator::get_kb( $request['id'] );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		// Resolved here, before the cache key is built, so a site that
		// changes this filter's value sees it take effect immediately
		// instead of waiting out the cache TTL.
		$stale_days = (int) apply_filters( 'itsdz_stale_review_days', 90 );

		$key    = 'kb-overview:' . $kb->ID . ':' . $stale_days . ':' . Cache::generation( 'kb-overview', $kb->ID );
		$cached = Cache::get( $key );

		if ( is_array( $cached ) ) {
			return rest_ensure_response( $cached );
		}

		$overview = $this->build_overview( $kb->ID, $stale_days );

		Cache::set( $key, $overview, 5 * MINUTE_IN_SECONDS );

		return rest_ensure_response( $overview );
	}

	/**
	 * Query and shape one project's overview data.
	 *
	 * A single query pulls every article and groups/counts them in PHP —
	 * simpler than several targeted meta/tax queries, and fast enough for
	 * the article counts a documentation project realistically has. Revisit
	 * only if a KB with hundreds of articles turns out to need it.
	 *
	 * @param int $kb_id      Project ID.
	 * @param int $stale_days Days after which an unreviewed article is stale.
	 * @return array<string, mixed>
	 */
	private function build_overview( $kb_id, $stale_days ) {
		$threshold = gmdate( 'Y-m-d', strtotime( "-{$stale_days} days" ) );

		$articles = get_posts(
			array(
				'meta_key'       => '_itsdz_kb_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $kb_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'posts_per_page' => -1,
				'post_status'    => array( 'draft', 'publish' ),
				'post_type'      => Article_Post_Type::POST_TYPE,
			)
		);

		$counts = array(
			'total'        => 0,
			'published'    => 0,
			'draft'        => 0,
			'needs_review' => 0,
		);

		$attention = array();

		foreach ( $articles as $article ) {
			++$counts['total'];
			++$counts[ 'publish' === $article->post_status ? 'published' : 'draft' ];

			$reason = $this->attention_reason( $article, $threshold );

			if ( ! $reason ) {
				continue;
			}

			++$counts['needs_review'];

			$attention[] = array(
				'id'            => $article->ID,
				'title'         => get_the_title( $article ),
				'reason'        => $reason,
				'last_reviewed' => (string) get_post_meta( $article->ID, '_itsdz_last_reviewed', true ),
				'edit_url'      => $this->edit_url( $article->ID ),
			);
		}

		$recent = array_map(
			function ( $article ) {
				return array(
					'id'           => $article->ID,
					'title'        => get_the_title( $article ),
					'status'       => $article->post_status,
					'modified_gmt' => mysql_to_rfc3339( $article->post_modified_gmt ),
					'edit_url'     => $this->edit_url( $article->ID ),
				);
			},
			array_slice( $articles, 0, 5 )
		);

		return array(
			'counts'    => $counts,
			'attention' => $attention,
			'recent'    => $recent,
			// A generic extension point: Any add-on
			// hooks this to add its own data — analytics today, whatever
			// else later — without Free ever needing to know the shape, or
			// even that "analytics" is a concept that exists.
			'extra'     => apply_filters( 'itsdz_kb_overview_extra', array(), $kb_id ),
		);
	}

	/**
	 * Decide why one article needs a look, if it does.
	 *
	 * @param \WP_Post $article   Article object.
	 * @param string   $threshold ISO date; a review older than this is stale.
	 * @return string|null One of 'draft', 'never_reviewed', 'stale_review', or null.
	 */
	private function attention_reason( $article, $threshold ) {
		if ( 'draft' === $article->post_status ) {
			return 'draft';
		}

		$last_reviewed = get_post_meta( $article->ID, '_itsdz_last_reviewed', true );

		if ( '' === $last_reviewed ) {
			return 'never_reviewed';
		}

		if ( $last_reviewed < $threshold ) {
			return 'stale_review';
		}

		return null;
	}

	/**
	 * Build the native block editor URL for an article.
	 *
	 * @param int $post_id Article ID.
	 * @return string
	 */
	private function edit_url( $post_id ) {
		return "post.php?post={$post_id}&action=edit";
	}

	/**
	 * Format a project for REST output.
	 *
	 * @param \WP_Post $post Project object.
	 * @return array<string, mixed>
	 */
	public function prepare_item( $post ) {
		return array(
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'content' => $post->post_content,
			'slug'    => $post->post_name,
			'status'  => $post->post_status,
			'url'     => get_permalink( $post ),
			'meta'    => $this->get_meta( $post->ID ),
		);
	}

	/**
	 * Build safe post data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param \WP_Post|null    $existing Existing project.
	 * @return array<string, mixed>
	 */
	private function post_data( $request, $existing = null ) {
		return array(
			'post_content' => null !== $request->get_param( 'content' ) ? wp_kses_post( $request->get_param( 'content' ) ) : ( $existing ? $existing->post_content : '' ),
			'post_status'  => $this->sanitize_status( $request->get_param( 'status' ), $existing ? $existing->post_status : 'draft' ),
			'post_title'   => null !== $request->get_param( 'title' ) ? sanitize_text_field( $request->get_param( 'title' ) ) : ( $existing ? $existing->post_title : '' ),
			'post_type'    => KB_Post_Type::POST_TYPE,
		);
	}

	/**
	 * Update allowlisted project meta.
	 *
	 * @param int   $post_id Project ID.
	 * @param mixed $meta    Raw meta object.
	 * @return void
	 */
	private function update_meta( $post_id, $meta ) {
		if ( ! is_array( $meta ) ) {
			return;
		}

		foreach ( array_keys( $this->get_meta( $post_id ) ) as $key ) {
			if ( array_key_exists( $key, $meta ) ) {
				update_post_meta( $post_id, $key, $meta[ $key ] );
			}
		}

		if ( array_key_exists( '_itsdz_kb_slug_base', $meta ) ) {
			KB_Post_Type::persist_rewrite_slug( $meta['_itsdz_kb_slug_base'] );
		}
	}

	/**
	 * Read project meta.
	 *
	 * @param int $post_id Project ID.
	 * @return array<string, mixed>
	 */
	private function get_meta( $post_id ) {
		$keys = array(
			'_itsdz_kb_logo',
			'_itsdz_kb_brand_color',
			'_itsdz_kb_theme_mode',
			'_itsdz_kb_template',
			'_itsdz_kb_doc_type',
			'_itsdz_kb_slug_base',
			'_itsdz_kb_layout_mode',
			'_itsdz_kb_nav_style',
			'_itsdz_kb_show_toc',
			'_itsdz_kb_show_feedback',
			'_itsdz_kb_show_related',
			'_itsdz_kb_show_print',
			'_itsdz_kb_custom_css',
			'_itsdz_kb_header_links',
			'_itsdz_kb_active_version',
		);
		$meta = array();

		foreach ( $keys as $key ) {
			$meta[ $key ] = get_post_meta( $post_id, $key, true );
		}

		return $meta;
	}

	/**
	 * Route arguments for project writes.
	 *
	 * @param bool $title_required Whether title is required.
	 * @return array<string, array<string, mixed>>
	 */
	private function write_args( $title_required ) {
		return array(
			'title'   => array(
				'required'          => $title_required,
				'sanitize_callback' => 'sanitize_text_field',
				'type'              => 'string',
			),
			'content' => array( 'type' => 'string' ),
			'status'  => array(
				'enum' => array( 'draft', 'publish' ),
				'type' => 'string',
			),
			'meta'    => array( 'type' => 'object' ),
		);
	}
}
