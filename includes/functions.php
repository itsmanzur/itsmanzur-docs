<?php
/**
 * Global helper functions — deliberately outside the ItsDZ\Doczur namespace
 * so an add-on plugin can call them without depending on it.
 *
 * @package ItsDZ\Doczur
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'itsdz_is_pro_active' ) ) {
	/**
	 * Whether an add-on has identified itself as active.
	 *
	 * A generic check for third-party extensions: an add-on can call
	 * `add_filter( 'itsdz_is_pro_active', '__return_true' )` in its own bootstrap.
	 * Nothing in Nirdeshio uses it and it never gates a feature. The name is
	 * kept so existing extensions keep working.
	 *
	 * @return bool
	 */
	function itsdz_is_pro_active() {
		return (bool) apply_filters( 'itsdz_is_pro_active', false );
	}
}
