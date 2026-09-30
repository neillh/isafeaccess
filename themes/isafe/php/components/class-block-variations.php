<?php
/**
 * Mavero\Components\Block_Overrides class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for implementing block overrides.
 */
class Block_Overrides implements Component {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_filter( 'register_block_type_args', [ $this, 'core_html_extra_keywords' ], 10, 2 );

		// Add support for custom class name for shortcode block.
		add_filter( 'register_block_type_args', [ $this, 'shortcode_block_supports' ], 10, 2 );
		add_filter( 'render_block', [ $this, 'shortcode_block_render' ], 10, 2 );
	}

	/**
	 * Adjust render callback to add custom class for the shortcode block.
	 *
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block including name and attributes.
	 *
	 * @return string
	 */
	public function shortcode_block_render( $block_content, $block ) {
		if ( $block['blockName'] === 'core/shortcode' ) {
			$custom_class = $block['attrs']['className'] ?? '';

			$block_content = sprintf(
				'<div class="wp-block-shortcode %s">%s</div>',
				esc_attr( trim( $custom_class ) ),
				$block_content
			);
		}

		return $block_content;
	}

	/**
	 * Add support for custom class name for shortcode block.
	 *
	 * @param array  $args       Array of block type arguments.
	 * @param string $block_type Block type name including namespace.
	 *
	 * @return array
	 */
	public function shortcode_block_supports( $args, $block_type ) {
		if ( $block_type === 'core/shortcode' ) {
			$args['supports']['customClassName'] = true;
		}

		return $args;
	}

	/**
	 * Add extra keywords to the core/html block.
	 *
	 * @param array  $args       Array of block type arguments.
	 * @param string $block_type Block type name including namespace.
	 *
	 * @return array
	 */
	public function core_html_extra_keywords( $args, $block_type ) {
		if ( $block_type === 'core/html' ) {
			$args['keywords'][] = 'iframe';
		}

		return $args;
	}
}
