<?php
/**
 * Custom WP login component.
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Customize WP Login Page
 */
class Custom_login implements Component {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'login_header', [ $this, 'custom_login_wrapper_start' ] );
		add_action( 'login_footer', [ $this, 'custom_login_wrapper_end' ] );
		add_filter( 'login_headerurl', [ $this, 'my_custom_login_url' ], 10, 2 );

	}

	/**
	 * Login wrapper
	 */
	public function custom_login_wrapper_start() {
		echo '<div class="md-login-wrapper">';
		echo '<div class="login-hero"></div>';
		echo '<div class="login-form">';
	}

	/**
	 * Login wrapper
	 */
	public function custom_login_wrapper_end() {
		echo '</div>';
		echo '</div>';
	}

	/**
	 * login logo url
	 */
	public function my_custom_login_url() {
		return site_url();
	}
}
