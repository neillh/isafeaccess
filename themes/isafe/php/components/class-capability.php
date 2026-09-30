<?php
/**
 * Mavero\Components\Capability class.
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Capability.
 */
class Capability implements Component {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_filter( 'map_meta_cap', [ $this, 'map_meta_cap' ], 10, 4 );
		add_filter( 'map_meta_cap', [ $this, 'editor_html_cap' ], 10, 4 );
	}

	/**
	 * Map meta cap.
	 *
	 * @param array  $caps    The capabilities.
	 * @param string $cap     The capability.
	 * @param int    $user_id The user ID.
	 * @param array  $args    The arguments.
	 *
	 * @return array
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( 'edit_post' === $cap && isset( $args[0] ) && $args[0] === (int) get_option( 'page_on_front' ) ) {
			$caps = [ 'manage_options' ];
		}


		return $caps;
	}

	/**
	 * Add unfiltered html capability to Editors.
	 *
	 * @param array  $caps    The capabilities.
	 * @param string $cap     The capability.
	 * @param int    $user_id The user ID.
	 * @param array  $args    The arguments.
	 *
	 * @return array
	 */
	public function editor_html_cap( $caps, $cap, $user_id, $args ) {
		if ( 'unfiltered_html' === $cap && ( user_can( $user_id, 'delete_others_pages' ) || user_can( $user_id, 'activate_plugins' ) ) ) {
			$caps = [ 'unfiltered_html' ];
		}

		return $caps;
	}
}
