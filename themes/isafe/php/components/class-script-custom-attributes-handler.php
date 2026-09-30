<?php
/**
 * Mavero\Components\Script_Custom_Attributes_Handler class.
 *
 * @package Mavero
 */

namespace Mavero\Components;

use WP_HTML_Tag_Processor;

/**
 * Class for managing script tag custom attributes.
 */
class Script_Custom_Attributes_Handler implements Component {

	/**
	 * Script custom attributes.
	 *
	 * @type array
	 */
	const ATTRIBUTES = [
		'async'    => true,
		'defer'    => true,
		'nomodule' => true,
		'type'     => 'module',
	];

	/**
	 * Add action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_filter( 'script_loader_tag', [ $this, 'filter_script_tag' ], PHP_INT_MAX, 2 );
	}

	/**
	 * Filter script tag and apply appropriate attributes based on script data.
	 *
	 * @param string $tag    The HTML tag.
	 * @param string $handle The script handle.
	 *
	 * @return string
	 */
	public function filter_script_tag( $tag, $handle ) {
		foreach ( self::ATTRIBUTES as $key => $value ) {
			if ( wp_scripts()->get_data( $handle, $key ) === $value ) {
				$attributes[ $key ] = $value;
			}
		}

		// Bail out early no attributes are set.
		if ( empty( $attributes ) ) {
			return $tag;
		}

		$processor = new WP_HTML_Tag_Processor( $tag );
		$processor->next_tag( 'script' );

		foreach ( $attributes as $key => $value ) {
			$processor->set_attribute( $key, $value );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Parse a script attributes string into an array.
	 *
	 * @param string $attrs_string Script attributes string.
	 *
	 * @return array Attributes and values.
	 */
	protected function parse_script_attrs( $attrs_string ) {
		$attrs            = wp_kses_hair( $attrs_string, wp_allowed_protocols() );
		$normalized_attrs = array_column( $attrs, 'value', 'name' );

		return $normalized_attrs;
	}

	/**
	 * Build a script tag based on the provided attributes array.
	 *
	 * @param array $attrs Tag attributes.
	 *
	 * @return string Script tag.
	 */
	protected function stringify_script_attrs( $attrs ) {
		$attrs_vals = [];

		foreach ( $attrs as $attr => $value ) {
			if ( empty( $value ) || $value === $attr || true === $value ) {
				$attrs_vals[] = $attr;
			} else {
				$attrs_vals[] = "$attr='$value'";
			}
		}

		return implode( ' ', $attrs_vals );
	}
}
