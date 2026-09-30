<?php
/**
 * Mavero\Components\Block_Patterns class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for implementing block patterns.
 *
 * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/
 */
class Block_Patterns implements Component {

	/**
	 * Collection of custom block pattern category meta.
	 *
	 * @var array
	 */
	protected $categories = [];

	/**
	 * Bootstrap the class.
	 *
	 * @return void
	 */
	public function __construct() {
		/**
		 * Set the default pattern categories enabled by this theme.
		 *
		 * @see https://developer.wordpress.org/reference/classes/wp_block_pattern_categories_registry/register/
		 */
		$this->categories = [
			'isafe' => [
				'label' => __( 'iSafe Access', 'mavero' ),
			],
		];

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->categories['testing'] = [
				'label' => __( 'Testing', 'mavero' ),
			];
		}
	}

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_categories' ], 9 );

		/**
		 * Disable fetching remote patterns.
		 *
		 * @see https://developer.wordpress.org/reference/hooks/should_load_remote_block_patterns/
		 */
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
	}

	/**
	 * Registers block patterns categories.
	 *
	 * @return void
	 */
	public function register_categories() {
		$categories = apply_filters( 'mavero_block_pattern_categories', $this->categories );

		foreach ( $categories as $name => $properties ) {
			register_block_pattern_category( $name, $properties );
		}
	}
}
