<?php
/**
 * Enriches Yoast's existing Organization node in place.
 *
 * Yoast free builds this node in src/generators/schema/organization.php with a
 * hardcoded `'@type' => 'Organization'` and only name, url, logo, image and
 * sameAs. It exposes no dedicated organization filter, but Schema_Generator
 * applies a dynamic `wpseo_schema_<identifier>` filter to every piece
 * (src/generators/schema-generator.php:145), and the Organization generator's
 * identifier is `organization`. That is the hook used here.
 *
 * Nothing in this class creates a new business node. There is one business
 * entity on this site and it keeps Yoast's `#organization` @id.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mavero_Organization {

	public static function init() {
		add_filter( 'wpseo_schema_organization', array( __CLASS__, 'enrich' ), 11, 2 );
		add_filter( 'wpseo_schema_organization_social_profiles', array( __CLASS__, 'social_profiles' ), 11 );
	}

	/**
	 * @param array $node    The Organization node Yoast built.
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return array
	 */
	public static function enrich( $node, $context = null ) {
		if ( ! is_array( $node ) ) {
			return $node;
		}

		$org = Mavero_Settings::get( 'org' );

		if ( empty( $org['enabled'] ) ) {
			return $node;
		}

		// Entity type. effective_type() refuses to return a LocalBusiness subtype
		// while no postal address is configured — a LocalBusiness without an
		// address is the exact defect this plugin was built to remove, and it
		// would be worse on the primary entity than on a stray one.
		$type = Mavero_Settings::effective_type();

		if ( 'Organization' !== $type ) {
			$node['@type'] = array( 'Organization', $type );
		}

		$map = array(
			'legalName'     => 'legal_name',
			'alternateName' => 'alternate_name',
			'description'   => 'description',
			'slogan'        => 'slogan',
			'telephone'     => 'telephone',
			'email'         => 'email',
			'foundingDate'  => 'founding_date',
		);

		foreach ( $map as $prop => $key ) {
			if ( ! empty( $org[ $key ] ) ) {
				$node[ $prop ] = $org[ $key ];
			}
		}

		// Yoast may already have set alternateName from its own settings; ours wins
		// only when explicitly configured, which the loop above already handles.

		if ( ! empty( $org['identifier'] ) ) {
			$node['identifier'] = $org['identifier'];
		}

		$knows = Mavero_Settings::to_list( isset( $org['knows_about'] ) ? $org['knows_about'] : '' );

		if ( ! empty( $knows ) ) {
			$node['knowsAbout'] = $knows;
		}

		$address = self::address_node();

		if ( $address ) {
			$node['address'] = $address;
		}

		$areas = self::area_served();

		if ( ! empty( $areas ) ) {
			$node['areaServed'] = $areas;
		}

		$contacts = self::contact_points();

		if ( ! empty( $contacts ) ) {
			$node['contactPoint'] = $contacts;
		}

		$employees = self::employee_refs( $context );

		if ( ! empty( $employees ) ) {
			$node['employee'] = $employees;
		}

		/**
		 * Filter the finished Organization node.
		 *
		 * @param array $node    The enriched Organization node.
		 * @param mixed $context Yoast Meta_Tags_Context.
		 */
		return apply_filters( 'mavero_schema_organization', $node, $context );
	}

	/**
	 * Replace or extend Yoast's sameAs list.
	 *
	 * Yoast passes its own configured profiles through here before deduping them
	 * into `sameAs` (organization.php:82).
	 *
	 * @param array $profiles
	 * @return array
	 */
	public static function social_profiles( $profiles ) {
		$social = Mavero_Settings::get( 'social' );
		$urls   = Mavero_Settings::to_list( isset( $social['urls'] ) ? $social['urls'] : '' );

		if ( empty( $urls ) ) {
			return $profiles;
		}

		if ( 'replace' === $social['mode'] ) {
			return $urls;
		}

		$profiles = is_array( $profiles ) ? $profiles : array();

		return array_values( array_unique( array_merge( array_values( $profiles ), $urls ) ) );
	}

	/**
	 * Build a PostalAddress node, or null when nothing usable is configured.
	 *
	 * @return array|null
	 */
	public static function address_node() {
		if ( ! Mavero_Settings::has_address() ) {
			return null;
		}

		$a    = Mavero_Settings::get( 'address' );
		$node = array( '@type' => 'PostalAddress' );

		$map = array(
			'streetAddress'   => 'street',
			'addressLocality' => 'locality',
			'addressRegion'   => 'region',
			'postalCode'      => 'postcode',
			'addressCountry'  => 'country',
		);

		foreach ( $map as $prop => $key ) {
			if ( ! empty( $a[ $key ] ) ) {
				$node[ $prop ] = $a[ $key ];
			}
		}

		return $node;
	}

	/**
	 * areaServed as a list of typed place nodes.
	 *
	 * For a business with no shopfront that travels to the client, this is the
	 * property that carries the geographic signal an address otherwise would.
	 *
	 * @return array
	 */
	public static function area_served() {
		$areas = Mavero_Settings::get( 'areas' );
		$out   = array();

		foreach ( (array) $areas as $area ) {
			if ( empty( $area['name'] ) ) {
				continue;
			}

			$out[] = array(
				'@type' => empty( $area['type'] ) ? 'State' : $area['type'],
				'name'  => $area['name'],
			);
		}

		return $out;
	}

	/**
	 * @return array
	 */
	public static function contact_points() {
		$contacts = Mavero_Settings::get( 'contacts' );
		$out      = array();

		foreach ( (array) $contacts as $c ) {
			$node = array( '@type' => 'ContactPoint' );

			if ( ! empty( $c['type'] ) ) {
				$node['contactType'] = $c['type'];
			}

			if ( ! empty( $c['telephone'] ) ) {
				$node['telephone'] = $c['telephone'];
			}

			if ( ! empty( $c['email'] ) ) {
				$node['email'] = $c['email'];
			}

			if ( ! empty( $c['area'] ) ) {
				$areas             = Mavero_Settings::to_list( $c['area'] );
				$node['areaServed'] = ( count( $areas ) === 1 ) ? $areas[0] : $areas;
			}

			if ( ! empty( $c['language'] ) ) {
				$langs                    = Mavero_Settings::to_list( $c['language'] );
				$node['availableLanguage'] = ( count( $langs ) === 1 ) ? $langs[0] : $langs;
			}

			// @type alone carries no information.
			if ( count( $node ) > 1 ) {
				$out[] = $node;
			}
		}

		return $out;
	}

	/**
	 * `employee` references, but only on pages where the Person nodes themselves
	 * are emitted — otherwise the Organization would point at @ids that appear
	 * nowhere in the graph.
	 *
	 * @param mixed $context Yoast Meta_Tags_Context.
	 * @return array
	 */
	private static function employee_refs( $context ) {
		if ( ! Mavero_Pieces::people_on_current_page() ) {
			return array();
		}

		$people = Mavero_Settings::get( 'people' );
		$out    = array();

		foreach ( (array) $people as $person ) {
			if ( empty( $person['name'] ) ) {
				continue;
			}

			$out[] = array( '@id' => Mavero_Pieces::person_id( $person['name'] ) );
		}

		return $out;
	}
}
