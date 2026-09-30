<?php
/**
 * Adds Service, Person and ItemList nodes to Yoast's graph.
 *
 * Yoast's Schema_Generator keys the pieces it is going to generate by identifier
 * (src/generators/schema-generator.php:108, `$pieces_to_generate[ $identifier ]`).
 * Two pieces sharing an identifier silently overwrite one another, so every piece
 * this plugin adds sets a distinct `$identifier`.
 *
 * A generator may return either a single node or a list of nodes; generate_graph()
 * detects the difference by looking for a top-level '@type' key (schema-generator.php:126).
 * The pieces here return lists.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the Service, Person and ItemList nodes for the current request.
 */
class Mavero_Pieces {

	public static function init() {
		add_filter( 'wpseo_schema_graph_pieces', array( __CLASS__, 'add_graph_pieces' ), 11, 2 );
	}

	/**
	 * Load the graph-piece class on demand.
	 *
	 * It cannot be loaded from the bootstrap: WordPress loads active plugins in
	 * the order held in the `active_plugins` option, and `mavero-schema` sorts
	 * before `wordpress-seo`, so Yoast's autoloader is not registered when this
	 * plugin's main file is parsed. Extending Yoast's abstract at that point
	 * fails silently and every piece disappears from the graph. By the time
	 * `wpseo_schema_graph_pieces` fires, the abstract exists.
	 *
	 * @return bool Whether Mavero_Graph_Piece is available.
	 */
	public static function ensure_piece_class() {
		if ( class_exists( 'Mavero_Graph_Piece', false ) ) {
			return true;
		}

		if ( ! class_exists( 'Yoast\\WP\\SEO\\Generators\\Schema\\Abstract_Schema_Piece' ) ) {
			return false;
		}

		require_once MAVERO_SCHEMA_DIR . 'includes/class-mavero-graph-piece.php';

		return class_exists( 'Mavero_Graph_Piece', false );
	}

	/**
	 * @param array $pieces  Yoast's piece generators.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return array
	 */
	public static function add_graph_pieces( $pieces, $context ) {
		if ( ! self::ensure_piece_class() ) {
			return $pieces;
		}

		$sets = array(
			'mavero_service'   => self::service_nodes(),
			'mavero_people'    => self::person_nodes(),
			'mavero_servicelist' => self::hub_nodes(),
		);

		foreach ( $sets as $identifier => $nodes ) {
			if ( ! empty( $nodes ) ) {
				$pieces[] = new Mavero_Graph_Piece( $context, $nodes, $identifier );
			}
		}

		return $pieces;
	}

	/* ---------------------------------------------------------------------
	 * Node builders
	 * ------------------------------------------------------------------ */

	/**
	 * The Service node for the page currently being rendered, if one is mapped.
	 *
	 * @return array
	 */
	public static function service_nodes() {
		$service = self::current_service();

		if ( ! $service ) {
			return array();
		}

		$page_id   = (int) $service['page_id'];
		$permalink = get_permalink( $page_id );

		$node = array(
			'@type'    => 'Service',
			'@id'      => self::service_id( $page_id ),
			'name'     => '' !== $service['name'] ? $service['name'] : get_the_title( $page_id ),
			'provider' => array( '@id' => mavero_schema_org_id() ),
		);

		if ( $permalink ) {
			$node['url'] = $permalink;
		}

		if ( ! empty( $service['service_type'] ) ) {
			$node['serviceType'] = $service['service_type'];
		}

		if ( ! empty( $service['description'] ) ) {
			$node['description'] = $service['description'];
		}

		// Per-service areas override the organisation-wide list; otherwise the
		// service inherits wherever the business says it operates.
		$areas = array();

		if ( ! empty( $service['areas'] ) ) {
			foreach ( Mavero_Settings::to_list( $service['areas'] ) as $name ) {
				$areas[] = array(
					'@type' => 'AdministrativeArea',
					'name'  => $name,
				);
			}
		} else {
			$areas = Mavero_Organization::area_served();
		}

		if ( ! empty( $areas ) ) {
			$node['areaServed'] = $areas;
		}

		/**
		 * Filter a single Service node before it enters the graph.
		 *
		 * @param array $node    The Service node.
		 * @param array $service The configured service row.
		 */
		return array( apply_filters( 'mavero_schema_service', $node, $service ) );
	}

	/**
	 * Person nodes for configured staff.
	 *
	 * @return array
	 */
	public static function person_nodes() {
		if ( ! self::people_on_current_page() ) {
			return array();
		}

		$people = Mavero_Settings::get( 'people' );
		$out    = array();

		foreach ( (array) $people as $person ) {
			if ( empty( $person['name'] ) ) {
				continue;
			}

			$node = array(
				'@type'    => 'Person',
				'@id'      => self::person_id( $person['name'] ),
				'name'     => $person['name'],
				'worksFor' => array( '@id' => mavero_schema_org_id() ),
			);

			if ( ! empty( $person['job_title'] ) ) {
				$node['jobTitle'] = $person['job_title'];
			}

			if ( ! empty( $person['description'] ) ) {
				$node['description'] = $person['description'];
			}

			if ( ! empty( $person['url'] ) ) {
				$node['url'] = $person['url'];
			}

			$sameas = Mavero_Settings::to_list( isset( $person['sameas'] ) ? $person['sameas'] : '' );

			if ( ! empty( $sameas ) ) {
				$node['sameAs'] = $sameas;
			}

			if ( ! empty( $person['image_id'] ) ) {
				$image = self::image_node( (int) $person['image_id'], $person['name'] );

				if ( $image ) {
					$node['image'] = $image;
				}
			}

			$out[] = apply_filters( 'mavero_schema_person', $node, $person );
		}

		return $out;
	}

	/**
	 * An ItemList of the configured services, emitted on the services hub page.
	 *
	 * Deliberately built from names and URLs rather than @id references to the
	 * Service nodes: those nodes live on their own pages, and a reference to an
	 * @id that appears nowhere in this page's graph is a dangling pointer.
	 *
	 * @return array
	 */
	public static function hub_nodes() {
		$hub = Mavero_Settings::get( 'hub' );

		if ( empty( $hub['enabled'] ) || empty( $hub['page_id'] ) ) {
			return array();
		}

		if ( (int) $hub['page_id'] !== self::current_page_id() ) {
			return array();
		}

		$services = Mavero_Settings::get( 'services' );
		$items    = array();
		$position = 1;

		foreach ( (array) $services as $service ) {
			$page_id = (int) $service['page_id'];
			$url     = get_permalink( $page_id );

			if ( ! $url ) {
				continue;
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => '' !== $service['name'] ? $service['name'] : get_the_title( $page_id ),
				'url'      => $url,
			);

			++$position;
		}

		if ( empty( $items ) ) {
			return array();
		}

		$permalink = get_permalink( (int) $hub['page_id'] );

		$node = array(
			'@type'           => 'ItemList',
			'@id'             => ( $permalink ? $permalink : home_url( '/' ) ) . '#services',
			'itemListElement' => $items,
		);

		if ( ! empty( $hub['heading'] ) ) {
			$node['name'] = $hub['heading'];
		}

		return array( $node );
	}

	/* ---------------------------------------------------------------------
	 * Identity and context helpers
	 * ------------------------------------------------------------------ */

	/**
	 * @param string $name
	 * @return string
	 */
	public static function person_id( $name ) {
		return trailingslashit( home_url( '/' ) ) . '#/schema/person/' . sanitize_title( $name );
	}

	/**
	 * @param int $page_id
	 * @return string
	 */
	public static function service_id( $page_id ) {
		$permalink = get_permalink( (int) $page_id );

		if ( ! $permalink ) {
			$permalink = trailingslashit( home_url( '/' ) );
		}

		return $permalink . '#service';
	}

	/**
	 * The post ID of the page currently being rendered.
	 *
	 * Yoast builds its graph during wp_head, so the main query is resolved by the
	 * time any of this runs.
	 *
	 * @return int
	 */
	public static function current_page_id() {
		if ( is_admin() ) {
			return 0;
		}

		if ( ! is_singular() ) {
			return 0;
		}

		return (int) get_queried_object_id();
	}

	/**
	 * The configured service row matching the current page, if any.
	 *
	 * @return array|null
	 */
	public static function current_service() {
		$current = self::current_page_id();

		if ( $current <= 0 ) {
			return null;
		}

		foreach ( (array) Mavero_Settings::get( 'services' ) as $service ) {
			if ( (int) $service['page_id'] === $current ) {
				return $service;
			}
		}

		return null;
	}

	/**
	 * Whether Person nodes should be emitted on the page being rendered.
	 *
	 * Default scope is a single page (normally About), which keeps the Person
	 * nodes and the Organization's `employee` references in the same graph.
	 *
	 * @return bool
	 */
	public static function people_on_current_page() {
		$scope  = Mavero_Settings::get( 'people_scope' );
		$people = Mavero_Settings::get( 'people' );

		if ( empty( $people ) || 'none' === $scope ) {
			return false;
		}

		if ( 'all' === $scope ) {
			return ! is_admin();
		}

		$page = (int) Mavero_Settings::get( 'people_page' );

		return $page > 0 && $page === self::current_page_id();
	}

	/**
	 * Build an ImageObject from an attachment.
	 *
	 * @param int    $id
	 * @param string $caption
	 * @return array|null
	 */
	public static function image_node( $id, $caption = '' ) {
		$src = wp_get_attachment_image_src( $id, 'full' );

		if ( ! $src ) {
			return null;
		}

		$node = array(
			'@type'      => 'ImageObject',
			'url'        => $src[0],
			'contentUrl' => $src[0],
		);

		// Yoast reads an SVG's viewBox and reports fractional dimensions; only
		// emit width/height when they are real pixel values.
		if ( ! empty( $src[1] ) && ! empty( $src[2] ) ) {
			$node['width']  = (int) $src[1];
			$node['height'] = (int) $src[2];
		}

		if ( '' !== $caption ) {
			$node['caption'] = $caption;
		}

		return $node;
	}
}
