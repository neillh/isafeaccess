<?php
/**
 * Page-level typing, and pointing a page at what it is actually about.
 *
 * Two hooks:
 *
 *  - `wpseo_schema_webpage_type` (src/context/meta-tags-context.php:544) sets the
 *    WebPage @type. It receives only the type, with no context argument, so the
 *    current page has to be resolved from the main query.
 *
 *  - `wpseo_schema_webpage` is the dynamic per-piece filter for Yoast's WebPage
 *    generator (identifier `webpage`), used here to attach `about`.
 *
 * On `about`: Yoast only sets this property on the front page — src/generators/schema/webpage.php
 * gates it behind `is_front_page()`. Every other page ships without it. So on a
 * mapped service page this adds the property rather than replacing one, pointing
 * the page at its own Service node instead of leaving it undeclared.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mavero_WebPage {

	public static function init() {
		add_filter( 'wpseo_schema_webpage_type', array( __CLASS__, 'page_type' ), 11 );
		add_filter( 'wpseo_schema_webpage', array( __CLASS__, 'attach_about' ), 11, 2 );
	}

	/**
	 * Apply a configured WebPage subtype to the page being rendered.
	 *
	 * @param string|array $type
	 * @return string|array
	 */
	public static function page_type( $type ) {
		$current = Mavero_Pieces::current_page_id();

		if ( $current <= 0 ) {
			return $type;
		}

		$configured = null;

		foreach ( (array) Mavero_Settings::get( 'page_types' ) as $row ) {
			if ( (int) $row['page_id'] === $current ) {
				$configured = $row['type'];
				break;
			}
		}

		// The services hub gets CollectionPage automatically when its ItemList is on.
		if ( null === $configured ) {
			$hub = Mavero_Settings::get( 'hub' );

			if ( ! empty( $hub['enabled'] ) && (int) $hub['page_id'] === $current ) {
				$configured = 'CollectionPage';
			}
		}

		if ( null === $configured ) {
			return $type;
		}

		$types = is_array( $type ) ? $type : array( $type );

		// WebPage stays as the base type; the subtype is added alongside it.
		if ( ! in_array( 'WebPage', $types, true ) ) {
			array_unshift( $types, 'WebPage' );
		}

		if ( ! in_array( $configured, $types, true ) ) {
			$types[] = $configured;
		}

		$types = array_values( array_filter( array_unique( $types ) ) );

		return ( count( $types ) === 1 ) ? $types[0] : $types;
	}

	/**
	 * Point a mapped service page at its own Service node.
	 *
	 * @param array $node    The WebPage node.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return array
	 */
	public static function attach_about( $node, $context = null ) {
		if ( ! is_array( $node ) ) {
			return $node;
		}

		$service = Mavero_Pieces::current_service();

		if ( ! $service || empty( $service['set_about'] ) ) {
			return $node;
		}

		$node['about'] = array( '@id' => Mavero_Pieces::service_id( (int) $service['page_id'] ) );

		return $node;
	}
}
