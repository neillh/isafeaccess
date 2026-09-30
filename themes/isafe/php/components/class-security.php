<?php
/**
 * Mavero\Components\Security class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for security features.
 *
 * Replaces WPExtended modules: disable-xml-rpc, user-enumeration,
 * block-usernames, and user-last-login.
 */
class Security implements Component {

	/**
	 * Usernames that are not allowed for registration.
	 *
	 * @var string[]
	 */
	private const BLOCKED_USERNAMES = [
		'admin',
		'administrator',
	];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		// Existing features.
		add_filter( 'allowed_redirect_hosts', [ $this, 'my_allowed_redirect_hosts' ] );
		add_filter( 'registration_errors', [ $this, 'block_ru_email_domains' ], 10, 3 );
		add_action( 'init', [ $this, 'custom_disable_wp_registration' ] );

		// Disable XML-RPC.
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', '__return_empty_array' );
		add_filter( 'wp_headers', [ $this, 'remove_x_pingback_header' ] );
		add_filter( 'pings_open', '__return_false', 9999 );
		add_filter( 'pre_update_option_enable_xmlrpc', '__return_zero' );
		add_filter( 'pre_option_enable_xmlrpc', '__return_zero' );
		add_action( 'wp_head', [ $this, 'remove_pingback_link' ], 9999 );
		add_action( 'xmlrpc_call', [ $this, 'block_xmlrpc_request' ] );

		// User enumeration protection.
		add_action( 'template_redirect', [ $this, 'block_user_enumeration' ] );

		// Block usernames.
		add_filter( 'registration_errors', [ $this, 'block_reserved_usernames' ], 10, 3 );
		add_action( 'user_profile_update_errors', [ $this, 'block_reserved_usernames_on_update' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'warn_existing_blocked_usernames' ] );

		// User last login tracking.
		add_action( 'wp_login', [ $this, 'record_last_login' ], 10, 2 );
		add_filter( 'manage_users_columns', [ $this, 'add_last_login_column' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'render_last_login_column' ], 10, 3 );
	}

	// -------------------------------------------------------------------------
	// Existing features
	// -------------------------------------------------------------------------

	public function my_allowed_redirect_hosts( $hosts ) {
		$my_hosts = array(
			'internationalartproject.org',
		);
		return array_merge( $hosts, $my_hosts );
	}

	public function block_ru_email_domains( $errors, $sanitized_user_login, $user_email ) {
		if ( preg_match( '/\.(ru|store)$/i', $user_email ) ) {
			$errors->add( 'email_error', __( '<strong>ERROR</strong>: Signups are disabled.', 'textdomain' ) );
		}
		return $errors;
	}

	public function custom_disable_wp_registration() {
		if ( strpos( $_SERVER['REQUEST_URI'], 'wp-login.php?action=register' ) !== false ) {
			wp_safe_redirect( home_url() );
			exit();
		}
	}

	// -------------------------------------------------------------------------
	// Disable XML-RPC
	// -------------------------------------------------------------------------

	/**
	 * Remove X-Pingback header from responses.
	 *
	 * @param array $headers Response headers.
	 * @return array
	 */
	public function remove_x_pingback_header( $headers ) {
		unset( $headers['X-Pingback'], $headers['x-pingback'] );
		return $headers;
	}

	/**
	 * Remove the pingback link from wp_head output.
	 */
	public function remove_pingback_link() {
		if ( has_action( 'wp_head', 'rsd_link' ) ) {
			remove_action( 'wp_head', 'rsd_link' );
		}
	}

	/**
	 * Block any XML-RPC method call with a 403.
	 */
	public function block_xmlrpc_request() {
		status_header( 403 );
		exit( 'XML-RPC is disabled.' );
	}

	// -------------------------------------------------------------------------
	// User Enumeration Protection
	// -------------------------------------------------------------------------

	/**
	 * Redirect author archive pages to prevent user enumeration.
	 */
	public function block_user_enumeration() {
		if ( is_author() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit();
		}
	}

	// -------------------------------------------------------------------------
	// Block Usernames
	// -------------------------------------------------------------------------

	/**
	 * Get the list of blocked usernames.
	 *
	 * @return string[]
	 */
	private function get_blocked_usernames() {
		return apply_filters( 'mavero_blocked_usernames', self::BLOCKED_USERNAMES );
	}

	/**
	 * Check if a username is blocked.
	 *
	 * @param string $username The username to check.
	 * @return bool
	 */
	private function is_username_blocked( $username ) {
		$username = strtolower( trim( $username ) );
		foreach ( $this->get_blocked_usernames() as $blocked ) {
			if ( strtolower( $blocked ) === $username ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Block reserved usernames during registration.
	 *
	 * @param \WP_Error $errors             Registration errors.
	 * @param string    $sanitized_user_login The sanitized username.
	 * @param string    $user_email          The user email.
	 * @return \WP_Error
	 */
	public function block_reserved_usernames( $errors, $sanitized_user_login, $user_email ) {
		if ( $this->is_username_blocked( $sanitized_user_login ) ) {
			$errors->add( 'blocked_username', __( '<strong>ERROR</strong>: This username is not allowed.', 'mavero' ) );
		}
		return $errors;
	}

	/**
	 * Block reserved usernames when creating/updating users in wp-admin.
	 *
	 * @param \WP_Error $errors The errors object.
	 * @param bool      $update Whether this is a user update.
	 * @param \stdClass $user   The user object being created/updated.
	 */
	public function block_reserved_usernames_on_update( $errors, $update, $user ) {
		if ( ! $update && isset( $user->user_login ) && $this->is_username_blocked( $user->user_login ) ) {
			$errors->add( 'blocked_username', __( '<strong>ERROR</strong>: This username is not allowed.', 'mavero' ) );
		}
	}

	/**
	 * Show admin notice if existing users have blocked usernames.
	 */
	public function warn_existing_blocked_usernames() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'users' !== $screen->id ) {
			return;
		}

		$problem_users = [];
		foreach ( $this->get_blocked_usernames() as $blocked ) {
			$user = get_user_by( 'login', $blocked );
			if ( $user ) {
				$problem_users[] = $user->user_login;
			}
		}

		if ( ! empty( $problem_users ) ) {
			$list = implode( ', ', array_map( 'esc_html', $problem_users ) );
			printf(
				'<div class="notice notice-warning"><p>%s: <strong>%s</strong></p></div>',
				esc_html__( 'The following usernames are insecure and should be changed', 'mavero' ),
				$list
			);
		}
	}

	// -------------------------------------------------------------------------
	// User Last Login
	// -------------------------------------------------------------------------

	private const LAST_LOGIN_META_KEY = 'mavero_last_login';

	/**
	 * Record the login timestamp.
	 *
	 * @param string   $user_login The username.
	 * @param \WP_User $user       The user object.
	 */
	public function record_last_login( $user_login, $user ) {
		update_user_meta( $user->ID, self::LAST_LOGIN_META_KEY, time() );
	}

	/**
	 * Add "Last Login" column to the users table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_last_login_column( $columns ) {
		$columns['mavero_last_login'] = __( 'Last Login', 'mavero' );
		return $columns;
	}

	/**
	 * Render the last login column value.
	 *
	 * @param string $output      Current column output.
	 * @param string $column_name Column name.
	 * @param int    $user_id     User ID.
	 * @return string
	 */
	public function render_last_login_column( $output, $column_name, $user_id ) {
		if ( 'mavero_last_login' !== $column_name ) {
			return $output;
		}

		$last_login = get_user_meta( $user_id, self::LAST_LOGIN_META_KEY, true );

		if ( empty( $last_login ) ) {
			// Fall back to WPExtended's meta key during migration.
			$last_login = get_user_meta( $user_id, 'wpext_user_last_login_status', true );
		}

		if ( empty( $last_login ) ) {
			return __( 'Never', 'mavero' );
		}

		$diff    = time() - (int) $last_login;
		$readable = human_time_diff( (int) $last_login, time() );

		return sprintf(
			/* translators: %s: human-readable time difference */
			__( '%s ago', 'mavero' ),
			$readable
		);
	}
}
