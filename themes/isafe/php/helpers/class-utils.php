<?php
/**
 * Holds common utility functions.
 *
 * @package Mavero
 */

namespace Mavero\Helpers;

/**
 * Class Utils
 */
class Utils {
	/**
	 * Check whether a class implements an interface. If not, throw exception.
	 *
	 * @param string|object $class_being_checked Class being checked.
	 * @param string        $target_interface    The interface to check against.
	 *
	 * @throws \RuntimeException Throws exception if condition passes.
	 */
	public static function throw_if_not_of_type( $class_being_checked, $target_interface ) {
		if ( is_object( $class_being_checked ) && ! ( $class_being_checked instanceof $target_interface ) ) {
			throw new \RuntimeException( esc_html( get_class( $class_being_checked ) . ' must implement ' . $target_interface ) );
		}

		if ( ! is_a( $class_being_checked, $target_interface, true ) ) {
			throw new \RuntimeException( esc_html( $class_being_checked . ' must implement ' . $target_interface ) );
		}
	}

	/**
	 * Reliably detect if the request is an API or Ajax request.
	 *
	 * @return bool
	 */
	public static function is_api_call() {
		$request = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_CALLBACK, [ 'options' => 'sanitize_url' ] );

		if ( ! $request ) {
			return wp_doing_ajax();
		}

		return 1 === strpos( $request, rest_get_url_prefix() ) || wp_doing_ajax();
	}

	/**
	 * Is WP debug mode enabled.
	 *
	 * @return boolean
	 */
	public static function is_debug() {
		return ( defined( 'WP_DEBUG' ) && WP_DEBUG );
	}

	/**
	 * Is WP script debug mode enabled.
	 *
	 * @return boolean
	 */
	public static function is_script_debug() {
		return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
	}

	/**
	 * This is a sophisticated extended version of wp_remote_request(). It is designed to more gracefully handle failures.
	 *
	 * Note that like wp_remote_request(), this function does not cache.
	 *
	 * @param string $url            URL to request.
	 * @param string $fallback_value Optional. Set a fallback value to be returned if the external request fails.
	 * @param int    $threshold      Optional. The number of fails required before subsequent requests automatically return the fallback value. Defaults to 3, with a maximum of 10.
	 * @param int    $timeout        Optional. Number of seconds before the request times out. Valid values 1-5; defaults to 1.
	 * @param int    $retry          Optional. Number of seconds before resetting the fail counter and the number of seconds to delay making new requests after the fail threshold is reached. Defaults to 20, with a minimum of 10.
	 * @param array  $args           Optional. Set other arguments to be passed to wp_remote_request().
	 *
	 * @return string|\WP_Error|array Array of results. If fail counter is met, returns the $fallback_value, otherwise return WP_Error.
	 * @see wp_remote_request()
	 */
	public static function safe_wp_remote_request( $url, $fallback_value = '', $threshold = 3, $timeout = 1, $retry = 20, $args = [] ) {
		global $blog_id;

		$default_args = [ 'method' => 'GET' ];
		$parsed_args  = wp_parse_args( $args, $default_args );

		$cache_group = "$blog_id:safe_wp_remote_request";
		$cache_key   = 'disable_remote_request_' . md5( wp_parse_url( $url, PHP_URL_HOST ) . '_' . $parsed_args['method'] );

		// valid url.
		if ( empty( $url ) || ! wp_parse_url( $url ) ) {
			return ( $fallback_value ) ? $fallback_value : new \WP_Error( 'invalid_url', $url );
		}

		// Ensure positive values.
		$timeout   = abs( $timeout );
		$retry     = abs( $retry );
		$threshold = abs( $threshold );

		// Default max timeout is 5s.
		// For POST requests for through WP-CLI, this needs to be event higher to makes things work like elastic search.
		// For POST requests for admins, this needs to be a bit higher due to Elasticsearch and other things.
		$timeout         = (int) $timeout;
		$is_post_request = 0 === strcasecmp( 'POST', $parsed_args['method'] );

		if ( defined( 'WP_CLI' ) && WP_CLI && $is_post_request ) {
			if ( 30 < $timeout ) {
				_doing_it_wrong( __FUNCTION__, 'Remote POST request timeouts are capped at 30 seconds in WP-CLI for performance and stability reasons.', null );
				$timeout = 30;
			}
		} elseif ( \is_admin() && $is_post_request ) {
			if ( 15 < $timeout ) {
				_doing_it_wrong( __FUNCTION__, 'Remote POST request timeouts are capped at 15 seconds for admin requests for performance and stability reasons.', null );
				$timeout = 15;
			}
		} elseif ( $timeout > 5 ) {
				_doing_it_wrong( __FUNCTION__, 'Remote request timeouts are capped at 5 seconds for performance and stability reasons.', null );
				$timeout = 5;
		}

		// retry time < 10 seconds will default to 10 seconds.
		$retry = ( (int) $retry < 10 ) ? 10 : (int) $retry;
		// more than 10 faulty hits seem to be to much.
		$threshold = ( (int) $threshold > 10 ) ? 10 : (int) $threshold;

		$option = wp_cache_get( $cache_key, $cache_group );

		// check if the timeout was hit and obey the option and return the fallback value.
		if ( false !== $option && time() - $option['time'] < $retry ) {
			if ( $option['hits'] >= $threshold ) {
				if ( ! defined( 'DISABLE_REMOTE_REQUEST_ERROR_REPORTING' ) || ! DISABLE_REMOTE_REQUEST_ERROR_REPORTING ) {
					trigger_error( esc_html( "safe_wp_remote_request: Blog ID {$blog_id}: Requesting $url with method {$parsed_args[ 'method' ]} has been throttled after {$option['hits']} attempts. Not reattempting until after $retry seconds" ), E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				}

				return ( $fallback_value ) ? $fallback_value : new \WP_Error( 'remote_request_disabled', 'Remote requests disabled: ' . maybe_serialize( $option ) );
			}
		}

		$start    = microtime( true );
		$response = wp_remote_request( $url, array_merge( $parsed_args, [ 'timeout' => $timeout ] ) );
		$end      = microtime( true );

		$elapsed = ( $end - $start ) > $timeout;
		if ( true === $elapsed ) {
			if ( false !== $option && $option['hits'] < $threshold ) {
				wp_cache_set(
					$cache_key,
					[
						'time' => floor( $end ),
						'hits' => $option['hits'] + 1,
					],
					$cache_group,
					$retry // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
				);
			} elseif ( false !== $option && $option['hits'] == $threshold ) {
				wp_cache_set(
					$cache_key,
					[
						'time' => floor( $end ),
						'hits' => $threshold,
					],
					$cache_group,
					$retry // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
				);
			} else {
				wp_cache_set(
					$cache_key,
					[
						'time' => floor( $end ),
						'hits' => 1,
					],
					$cache_group,
					$retry // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
				);
			}
		} elseif ( false !== $option && $option['hits'] > 0 && time() - $option['time'] < $retry ) {
				wp_cache_set(
					$cache_key,
					[
						'time' => $option['time'],
						'hits' => $option['hits'] - 1,
					],
					$cache_group,
					$retry // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
				);
		} else {
			wp_cache_delete( $cache_key, $cache_group );
		}

		if ( is_wp_error( $response ) ) {
			if ( ! defined( 'DISABLE_REMOTE_REQUEST_ERROR_REPORTING' ) || ! DISABLE_REMOTE_REQUEST_ERROR_REPORTING ) {
				trigger_error( esc_html( "safe_wp_remote_request: Blog ID {$blog_id}: Requesting $url with method {$parsed_args[ 'method' ]} and a timeout of $timeout failed. Result: " . maybe_serialize( $response ) ), E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			}
			do_action( 'remote_request_error', $url, $response );

			return ( $fallback_value ) ? $fallback_value : $response;
		}

		return $response;
	}

	/**
	 * This is a convenience method for safe_wp_remote_request() and behaves the same
	 *
	 * Note that like wp_remote_get(), this function does not cache.
	 *
	 * @param string $url            URL to retrieve.
	 * @param string $fallback_value The value to return if the request fails.
	 * @param int    $threshold      The number of times the request can fail before it is disabled.
	 * @param int    $timeout        The timeout in seconds.
	 * @param int    $retry          The number of seconds to wait before retrying.
	 * @param array  $args           Optional. Request arguments. See wp_remote_get() for information on accepted arguments.
	 *
	 * @return array|string|\WP_Error
	 * @see wp_remote_get()
	 * @see safe_wp_remote_request()
	 */
	public static function safe_wp_remote_get( $url, $fallback_value = '', $threshold = 3, $timeout = 1, $retry = 20, $args = [] ) {
		// Same defaults as WP_HTTP::get() https://developer.wordpress.org/reference/classes/wp_http/get/.
		$default_args = [ 'method' => 'GET' ];
		$parsed_args  = wp_parse_args( $args, $default_args );

		return self::safe_wp_remote_request( $url, $fallback_value, $threshold, $timeout, $retry, $parsed_args );
	}

	public static function get_cached_term_name( int $post_id, string $taxonomy ): string {
		$term = self::get_cached_term( $post_id, $taxonomy );
		return $term ? $term->name : '';
	}

	public static function get_cached_term_slug( int $post_id, string $taxonomy ): string {
			$term = self::get_cached_term( $post_id, $taxonomy );
			return $term ? $term->slug : '';
	}

	public static function get_cached_term( int $post_id, string $taxonomy ): ?\WP_Term {
		$cache_key   = $taxonomy . '_term_' . $post_id;
		$cache_group = 'taxonomy_term_cache';

		$term = wp_cache_get( $cache_key, $cache_group );

		if ( false === $term ) {
			$terms = get_the_terms( $post_id, $taxonomy );
			$term  = $terms[0] ?? null;
			wp_cache_set( $cache_key, $term, $cache_group );
		}

		return $term;
	}

	public static function clear_cached_term( int $post_id, string $taxonomy ): void {
		$cache_key   = $taxonomy . '_term_' . $post_id;
		$cache_group = 'taxonomy_term_cache';

		wp_cache_delete( $cache_key, $cache_group );
	}

	/**
	 * Check if Jetpack's Image Accelerator module is active.
	 *
	 * @return bool True if Jetpack's Image Accelerator is active, false otherwise.
	 */
	public static function is_jetpack_image_accelerator_active() {
		// Check if Jetpack is active.
		if ( class_exists( 'Jetpack' ) ) {
			// Get list of active Jetpack modules.
			$active_modules = \Jetpack::get_active_modules();

			// Check if Image Accelerator module is active.
			if ( in_array( 'photon', $active_modules, true ) ) {
				return true; // Image Accelerator is active.
			}
		}

		return false; // Image Accelerator is not active.
	}
}
