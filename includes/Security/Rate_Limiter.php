<?php
/**
 * Public REST request rate limiting.
 *
 * @package ItsDZ\Doczur\Security
 */

namespace ItsDZ\Doczur\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Applies fixed-window limits without storing raw IP addresses.
 */
final class Rate_Limiter {
	/**
	 * Consume one request from a rate-limit bucket.
	 *
	 * @param string $action Action identifier.
	 * @param int    $limit  Maximum requests per window.
	 * @param int    $window Window length in seconds.
	 * @return true|\WP_Error
	 */
	public function consume( $action, $limit, $window ) {
		$limit  = max( 1, absint( $limit ) );
		$window = max( 1, absint( $window ) );
		$key    = 'itsdz_rate_' . md5( sanitize_key( $action ) . '|' . $this->get_ip_hash() );
		$bucket = get_transient( $key );

		if ( ! is_array( $bucket ) || empty( $bucket['reset'] ) || time() >= (int) $bucket['reset'] ) {
			$bucket = array(
				'count' => 0,
				'reset' => time() + $window,
			);
		}

		if ( (int) $bucket['count'] >= $limit ) {
			return new \WP_Error(
				'itsdz_rate_limited',
				__( 'Too many requests. Please try again shortly.', 'itsmanzur-docs' ),
				array(
					'status'      => 429,
					'retry_after' => max( 1, (int) $bucket['reset'] - time() ),
				)
			);
		}

		++$bucket['count'];
		set_transient( $key, $bucket, $window );

		return true;
	}

	/**
	 * Return a site-specific irreversible client identifier.
	 *
	 * @return string
	 */
	public function get_ip_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

		/**
		 * Filter the client IP used for rate limiting and duplicate-vote checks.
		 *
		 * Defaults to REMOTE_ADDR. Behind a reverse proxy or CDN every visitor shares
		 * one address, so a site owner can return the real client IP here (for
		 * example from a header their proxy sets and they trust). Forwarded-for
		 * headers are never trusted automatically.
		 *
		 * @param string $ip Client IP address, or 'unknown'.
		 */
		$ip = (string) apply_filters( 'itsdz_client_ip', $ip );

		if ( 'unknown' !== $ip && ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = 'unknown';
		}

		return hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
	}
}
