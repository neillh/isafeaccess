<?php
/**
 * Mavero\Components\Block_Styles class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for managing block stylesheets.
 */
class Block_Styles implements Component {

	/**
	 * List of style handles outside the "allowed list"
	 *
	 * WordPress will enqueue these even if we're not whitelisting them
	 *
	 * @var array
	 */
	private $styles_to_deregister = [
		'wp-block-library',
		'wp-block-library-theme',
		'wp-block-image-theme',
		'wp-block-separator-theme',
	];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	/**
	 * Button style variations to register on core/button.
	 * Styles are provided by css/blocks/_buttons.scss.
	 *
	 * @var array
	 */
	private $button_styles = [
		// Core already registers "Fill" (default) and "Outline" on core/button.
		[
			'name'  => 'link',
			'label' => 'Link',
		],
	];

	/**
	 * List style variations to register on core/list.
	 *
	 * @var array
	 */
	private $list_styles = [
		[
			'name'  => 'numbered',
			'label' => 'Numbered',
		],
		[
			'name'  => 'checklist',
			'label' => 'Checklist',
		],
		[
			'name'  => 'columns',
			'label' => 'Checklist — Two Columns',
		],
		[
			'name'  => 'chevrons',
			'label' => 'Chevrons',
		],
	];

	/**
	 * Separator style variations to register on core/separator.
	 *
	 * @var array
	 */
	private $separator_styles = [
		[
			'name'  => 'hatched',
			'label' => 'Hatched',
		],
	];

	/**
	 * Group style variations to register on core/group.
	 *
	 * @var array
	 */
	private $group_styles = [
		[
			'name'  => 'slider',
			'label' => 'Slider',
		],
	];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'action_deregister_block_styles' ] );
		add_action( 'init', [ $this, 'action_register_button_styles' ] );
		add_action( 'init', [ $this, 'action_register_list_styles' ] );
		add_action( 'init', [ $this, 'action_register_group_styles' ] );
		add_action( 'init', [ $this, 'action_register_separator_styles' ] );
	}

	/**
	 * Get core (non-block) styles to deregister.
	 *
	 * @return array
	 */
	public function get_static_styles_to_deregister() {
		return $this->styles_to_deregister;
	}

	/**
	 * Executed by the action `wp_enqueue_scripts` to deregister block styles.
	 */
	public function action_deregister_block_styles() {
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		// Deregister static items.
		foreach ( $this->styles_to_deregister as $handle ) {
			wp_deregister_style( $handle );
		}

		// Core registers other handles with `wp-block-library` as a dependency —
		// notably `block-style-variation-styles`, which carries the section style
		// CSS (styles/sections/*.json). A missing dependency stops those printing,
		// so keep an empty placeholder handle in its place.
		wp_register_style( 'wp-block-library', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

		// Deregister dynamic items.
		foreach ( $registered_blocks as $key => $_ ) {
			$handle = $this->create_handle( $key );
			//wp_deregister_style( $handle );
		}
	}

	/**
	 * Register extra button style variations on core/button and
	 * woocommerce/product-button (Fill and Outline come from core).
	 */
	public function action_register_button_styles() {
		$blocks = [ 'core/button', 'woocommerce/product-button' ];
		foreach ( $blocks as $block ) {
			foreach ( $this->button_styles as $style ) {
				register_block_style( $block, $style );
			}
		}
	}

	/**
	 * Register list style variations on core/list.
	 *
	 * Styles are provided by css/blocks/_list.scss.
	 */
	public function action_register_list_styles() {
		foreach ( $this->list_styles as $style ) {
			register_block_style( 'core/list', $style );
		}
	}

	/**
	 * Register style variations on core/group.
	 *
	 * Styles are provided by css/blocks/_group-slider.scss.
	 */
	public function action_register_group_styles() {
		foreach ( $this->group_styles as $style ) {
			register_block_style( 'core/group', $style );
		}
	}

	/**
	 * Register style variations on core/separator.
	 *
	 * Styles are provided by css/components/_separator.scss.
	 */
	public function action_register_separator_styles() {
		foreach ( $this->separator_styles as $style ) {
			register_block_style( 'core/separator', $style );
		}
	}

	/**
	 * Turn block's name into a handle readable by wp_deregister_style
	 *
	 * @param string $block_name Block's name.
	 *
	 * @return string Readable handle.
	 */
	public function create_handle( $block_name ) {
		if ( strpos( $block_name, 'core/' ) !== 0 ) {
			return '';
		}

		return str_replace( 'core/', 'wp-block-', $block_name );
	}
}
