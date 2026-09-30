<?php
/**
 * Mavero\Components\Disable_Share_Buttons class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class to support disable share buttons.
 */
class Disable_Share_Buttons implements Component, Templater {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_meta' ] );
		add_filter( 'mavero_editor_data', [ $this, 'add_editor_data' ] );
	}

	/**
	 * Supported post types.
	 *
	 * @return mixed|null
	 */
	public function supported_post_type() {
		$post_types = get_post_types( [ 'public' => true ] );

		unset( $post_types['attachment'], $post_types['guest-author'] );

		return apply_filters( 'mavero_disable_share_supported_post_types', array_values( $post_types ) );
	}

	/**
	 * Register meta for all cpt.
	 */
	public function register_meta() {
		$post_types = $this->supported_post_type();

		foreach ( $post_types as $post_type ) {
			add_post_type_support( $post_type, 'custom-fields' );

			register_meta(
				$post_type,
				'disable_share_buttons',
				[
					'show_in_rest' => true,
					'single'       => true,
					'type'         => 'boolean',
				]
			);
		}
	}

	/**
	 * Adds editor data.
	 *
	 * @param array $editor_data The editor data.
	 *
	 * @return array
	 */
	public function add_editor_data( array $editor_data ): array {
		$config = [
			'DisableShare' => [
				'supportedPostTypes' => $this->supported_post_type(),
			],
		];

		return array_merge( $editor_data, $config );
	}

	/**
	 * Get template tags.
	 *
	 * @return array[]
	 */
	public function get_template_tags() {
		return [
			'is_share_buttons_disabled' => [ $this, 'is_share_buttons_disabled' ],
		];
	}

	/**
	 * Check if share buttons are disabled.
	 *
	 * @return mixed
	 */
	public function is_share_buttons_disabled() {
		return get_post_meta( get_the_ID(), 'disable_share_buttons', true );
	}
}
