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
	 * An add-on calls `add_filter( 'itsdz_is_pro_active', '__return_true' )`
	 * in its own bootstrap. This is informational only — for UI decisions
	 * like showing or hiding an add-on notice — never a gate on
	 * functionality Free itself provides.
	 *
	 * @return bool
	 */
	function itsdz_is_pro_active() {
		return (bool) apply_filters( 'itsdz_is_pro_active', false );
	}
}
