<?php
/**
 * Settings model: defaults, storage, retrieval and sanitisation.
 *
 * Everything lives in one option (an array) rather than a spread of discrete
 * options, so the whole schema configuration can be exported, diffed and moved
 * between environments as a single unit.
 *
 * The settings screen is tabbed, but a tab only ever submits its own sections.
 * `sanitize()` therefore merges the submitted sections over what is already
 * stored, using a hidden list of the sections the form actually posted, so that
 * saving one tab never wipes another — and clearing every row of a repeater on a
 * submitted tab still correctly saves an empty list.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mavero_Settings {

	const OPTION = 'mavero_schema_settings';
	const GROUP  = 'mavero_schema_group';

	/**
	 * @var array|null Runtime cache of the merged settings.
	 */
	private static $cache = null;

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * The full default shape. Also serves as the schema for sanitisation —
	 * anything not described here is discarded on save.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'org'          => array(
				'enabled'        => 1,
				'type'           => 'Organization',
				'legal_name'     => '',
				'alternate_name' => '',
				'description'    => '',
				'slogan'         => '',
				'telephone'      => '',
				'email'          => '',
				'founding_date'  => '',
				'identifier'     => '',
				'knows_about'    => '',
			),
			'address'      => array(
				'street'   => '',
				'locality' => '',
				'region'   => '',
				'postcode' => '',
				'country'  => '',
			),
			'areas'        => array(),
			'contacts'     => array(),
			'social'       => array(
				'mode' => 'merge',
				'urls' => '',
			),
			'people'       => array(),
			'people_scope' => 'page',
			'people_page'  => 0,
			'services'     => array(),
			'hub'          => array(
				'enabled' => 0,
				'page_id' => 0,
				'heading' => '',
			),
			'page_types'   => array(),
			'reviews'      => array(
				'takeover'         => 0,
				'allow_org_author' => 1,
				'pages'            => array(),
				'max'              => 0,
			),
		);
	}

	/**
	 * Write defaults on activation without clobbering an existing configuration.
	 */
	public static function install_defaults() {
		$stored = get_option( self::OPTION, null );

		if ( null === $stored ) {
			add_option( self::OPTION, self::defaults() );
		}
	}

	/**
	 * Retrieve the settings, or one top-level section of them.
	 *
	 * @param string|null $section Top-level key, or null for everything.
	 * @return mixed
	 */
	public static function get( $section = null ) {
		if ( null === self::$cache ) {
			$stored = self::stored();

			// Shallow-merge per section so a newly added default key appears for
			// sites that saved their settings under an earlier version.
			$merged = self::defaults();
			foreach ( $merged as $key => $default ) {
				if ( ! array_key_exists( $key, $stored ) ) {
					continue;
				}

				$merged[ $key ] = ( is_array( $default ) && self::is_assoc( $default ) && is_array( $stored[ $key ] ) )
					? array_merge( $default, $stored[ $key ] )
					: $stored[ $key ];
			}

			self::$cache = $merged;
		}

		if ( null === $section ) {
			return self::$cache;
		}

		return isset( self::$cache[ $section ] ) ? self::$cache[ $section ] : null;
	}

	/**
	 * The stored option as an array, repairing it first if it has been damaged.
	 *
	 * A serialized array records every string's byte length. If anything
	 * rewrites the row outside WordPress — a search-replace, a DB pull that
	 * turns \n into \r\n — one wrong length makes the whole array unreadable,
	 * get_option() returns false, and the settings screen silently shows
	 * blank defaults. Saving that screen would then overwrite the real data.
	 * So when the value is unreadable but a row exists, recount the string
	 * lengths and, if that yields an array, write it back cleanly.
	 *
	 * @return array
	 */
	private static function stored() {
		$stored = get_option( self::OPTION, array() );

		if ( is_array( $stored ) ) {
			return $stored;
		}

		global $wpdb;

		$raw = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::OPTION )
		);

		if ( ! is_string( $raw ) || ! is_serialized( $raw ) ) {
			return array();
		}

		$fixed = self::repair_serialized( $raw );
		$value = ( false === $fixed ) ? false : @unserialize( $fixed, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.PHP.DiscouragedPHPFunctions

		if ( ! is_array( $value ) ) {
			return array();
		}

		// Bypass sanitize(): it would discard every section this request didn't submit.
		$hooked = remove_filter( 'sanitize_option_' . self::OPTION, array( __CLASS__, 'sanitize' ) );
		update_option( self::OPTION, $value );

		if ( $hooked ) {
			add_filter( 'sanitize_option_' . self::OPTION, array( __CLASS__, 'sanitize' ) );
		}

		return $value;
	}

	/**
	 * Recount the byte length of every string in a serialized value.
	 *
	 * Where a declared length does not land on the string's closing `";`, the
	 * real end is taken to be the next `";` that is followed by another token
	 * or a closing brace.
	 *
	 * @param string $raw Serialized data.
	 * @return string|false Repaired data, or false if a string end can't be found.
	 */
	private static function repair_serialized( $raw ) {
		$out = '';
		$pos = 0;

		while ( preg_match( '/s:(\d+):"/', $raw, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
			$start = $m[0][1] + strlen( $m[0][0] );
			$end   = $start + (int) $m[1][0];

			if ( '";' !== substr( $raw, $end, 2 ) ) {
				if ( ! preg_match( '/";(?=[sibdaO]:|N;|\}|$)/', $raw, $e, PREG_OFFSET_CAPTURE, $start ) ) {
					return false;
				}

				$end = $e[0][1];
			}

			$out .= substr( $raw, $pos, $m[0][1] - $pos )
				. 's:' . ( $end - $start ) . ':"' . substr( $raw, $start, $end - $start ) . '";';
			$pos  = $end + 2;
		}

		return $out . substr( $raw, $pos );
	}

	/**
	 * Clear the runtime cache. Used after a save within the same request.
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Sanitise a submitted settings payload and merge it over what is stored.
	 *
	 * @param mixed $input Raw $_POST value for the option.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$stored = array_merge( self::defaults(), self::stored() );
		$input  = is_array( $input ) ? $input : array();

		// Which sections did this form actually submit? Without this, a repeater
		// emptied to zero rows would be indistinguishable from a tab that never
		// posted that section at all, and the old rows would silently return.
		$submitted = array();
		if ( isset( $input['_sections'] ) && is_array( $input['_sections'] ) ) {
			$submitted = array_map( 'sanitize_key', $input['_sections'] );
		}
		unset( $input['_sections'] );

		$out = $stored;

		foreach ( array_keys( self::defaults() ) as $section ) {
			if ( ! in_array( $section, $submitted, true ) ) {
				continue;
			}

			$value        = isset( $input[ $section ] ) ? $input[ $section ] : array();
			$method       = 'sanitize_' . $section;
			$out[ $section ] = method_exists( __CLASS__, $method )
				? self::$method( $value )
				: self::sanitize_scalar( $value );
		}

		self::flush();

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Per-section sanitisers
	 * ------------------------------------------------------------------ */

	private static function sanitize_org( $v ) {
		$v = is_array( $v ) ? $v : array();

		return array(
			'enabled'        => empty( $v['enabled'] ) ? 0 : 1,
			'type'           => self::pick( $v, 'type', array_keys( self::entity_types() ), 'Organization' ),
			'legal_name'     => self::text( $v, 'legal_name' ),
			'alternate_name' => self::text( $v, 'alternate_name' ),
			'description'    => self::textarea( $v, 'description' ),
			'slogan'         => self::text( $v, 'slogan' ),
			'telephone'      => self::text( $v, 'telephone' ),
			'email'          => isset( $v['email'] ) ? sanitize_email( $v['email'] ) : '',
			'founding_date'  => self::date( $v, 'founding_date' ),
			'identifier'     => self::text( $v, 'identifier' ),
			'knows_about'    => self::textarea( $v, 'knows_about' ),
		);
	}

	private static function sanitize_address( $v ) {
		$v = is_array( $v ) ? $v : array();

		return array(
			'street'   => self::text( $v, 'street' ),
			'locality' => self::text( $v, 'locality' ),
			'region'   => self::text( $v, 'region' ),
			'postcode' => self::text( $v, 'postcode' ),
			'country'  => self::text( $v, 'country' ),
		);
	}

	private static function sanitize_areas( $v ) {
		$out = array();

		foreach ( self::rows( $v ) as $row ) {
			$name = self::text( $row, 'name' );

			if ( '' === $name ) {
				continue;
			}

			$out[] = array(
				'type' => self::pick( $row, 'type', array( 'Country', 'State', 'City', 'AdministrativeArea' ), 'State' ),
				'name' => $name,
			);
		}

		return $out;
	}

	private static function sanitize_contacts( $v ) {
		$out = array();

		foreach ( self::rows( $v ) as $row ) {
			$phone = self::text( $row, 'telephone' );
			$email = isset( $row['email'] ) ? sanitize_email( $row['email'] ) : '';

			// A ContactPoint with neither a phone nor an email says nothing.
			if ( '' === $phone && '' === $email ) {
				continue;
			}

			$out[] = array(
				'type'      => self::text( $row, 'type' ),
				'telephone' => $phone,
				'email'     => $email,
				'area'      => self::text( $row, 'area' ),
				'language'  => self::text( $row, 'language' ),
			);
		}

		return $out;
	}

	private static function sanitize_social( $v ) {
		$v = is_array( $v ) ? $v : array();

		return array(
			'mode' => self::pick( $v, 'mode', array( 'merge', 'replace' ), 'merge' ),
			'urls' => self::url_list( isset( $v['urls'] ) ? $v['urls'] : '' ),
		);
	}

	private static function sanitize_people( $v ) {
		$out = array();

		foreach ( self::rows( $v ) as $row ) {
			$name = self::text( $row, 'name' );

			if ( '' === $name ) {
				continue;
			}

			$out[] = array(
				'name'        => $name,
				'job_title'   => self::text( $row, 'job_title' ),
				'description' => self::textarea( $row, 'description' ),
				'url'         => self::url( $row, 'url' ),
				'sameas'      => self::url_list( isset( $row['sameas'] ) ? $row['sameas'] : '' ),
				'image_id'    => self::int( $row, 'image_id' ),
			);
		}

		return $out;
	}

	private static function sanitize_services( $v ) {
		$out = array();

		foreach ( self::rows( $v ) as $row ) {
			$page = self::int( $row, 'page_id' );

			if ( $page <= 0 ) {
				continue;
			}

			$out[] = array(
				'page_id'      => $page,
				'name'         => self::text( $row, 'name' ),
				'service_type' => self::text( $row, 'service_type' ),
				'description'  => self::textarea( $row, 'description' ),
				'areas'        => self::text( $row, 'areas' ),
				'set_about'    => empty( $row['set_about'] ) ? 0 : 1,
			);
		}

		return $out;
	}

	private static function sanitize_hub( $v ) {
		$v = is_array( $v ) ? $v : array();

		return array(
			'enabled' => empty( $v['enabled'] ) ? 0 : 1,
			'page_id' => self::int( $v, 'page_id' ),
			'heading' => self::text( $v, 'heading' ),
		);
	}

	private static function sanitize_page_types( $v ) {
		$out = array();

		foreach ( self::rows( $v ) as $row ) {
			$page = self::int( $row, 'page_id' );

			if ( $page <= 0 ) {
				continue;
			}

			$out[] = array(
				'page_id' => $page,
				'type'    => self::pick( $row, 'type', array_keys( self::page_types() ), 'ContactPage' ),
			);
		}

		return $out;
	}

	private static function sanitize_reviews( $v ) {
		$v = is_array( $v ) ? $v : array();

		$pages = array();
		if ( isset( $v['pages'] ) && is_array( $v['pages'] ) ) {
			foreach ( $v['pages'] as $id ) {
				$id = (int) $id;

				if ( $id > 0 ) {
					$pages[] = $id;
				}
			}
		}

		return array(
			'takeover'         => empty( $v['takeover'] ) ? 0 : 1,
			'allow_org_author' => empty( $v['allow_org_author'] ) ? 0 : 1,
			'pages'            => array_values( array_unique( $pages ) ),
			'max'              => max( 0, self::int( $v, 'max' ) ),
		);
	}

	private static function sanitize_scalar( $v ) {
		if ( is_array( $v ) ) {
			return array_map( array( __CLASS__, 'sanitize_scalar' ), $v );
		}

		return sanitize_text_field( (string) $v );
	}

	/* ---------------------------------------------------------------------
	 * Vocabularies
	 * ------------------------------------------------------------------ */

	/**
	 * Entity types offered for the primary business node.
	 *
	 * Yoast free hardcodes 'Organization' (src/generators/schema/organization.php)
	 * and ships no LocalBusiness generator, so anything below other than
	 * Organization is only reachable by filtering — which is what this plugin does.
	 *
	 * Every type flagged `needs_address` is a LocalBusiness subtype. Google treats
	 * `address` as required for those, so the admin screen refuses to apply them
	 * until a postal address has been entered.
	 *
	 * @return array
	 */
	public static function entity_types() {
		return array(
			'Organization'              => array(
				'label'        => 'Organization — safe default, no address required',
				'needs_address' => false,
			),
			'ProfessionalService'       => array(
				'label'        => 'ProfessionalService — LocalBusiness subtype',
				'needs_address' => true,
			),
			'HomeAndConstructionBusiness' => array(
				'label'        => 'HomeAndConstructionBusiness — LocalBusiness subtype',
				'needs_address' => true,
			),
			'GeneralContractor'         => array(
				'label'        => 'GeneralContractor — LocalBusiness subtype',
				'needs_address' => true,
			),
			'LocalBusiness'             => array(
				'label'        => 'LocalBusiness — generic, prefer a subtype',
				'needs_address' => true,
			),
		);
	}

	public static function page_types() {
		return array(
			'ContactPage'    => 'ContactPage',
			'AboutPage'      => 'AboutPage',
			'CollectionPage' => 'CollectionPage',
			'FAQPage'        => 'FAQPage (restricted by Google — see notes)',
			'ProfilePage'    => 'ProfilePage',
		);
	}

	/**
	 * Whether a usable postal address has been configured.
	 *
	 * Street plus locality is the minimum that makes a PostalAddress meaningful;
	 * a lone country code does not.
	 *
	 * @return bool
	 */
	public static function has_address() {
		$a = self::get( 'address' );

		return ! empty( $a['street'] ) && ! empty( $a['locality'] );
	}

	/**
	 * The entity type that will actually be emitted, after the address guard.
	 *
	 * @return string
	 */
	public static function effective_type() {
		$org   = self::get( 'org' );
		$types = self::entity_types();
		$type  = isset( $org['type'] ) ? $org['type'] : 'Organization';

		if ( ! isset( $types[ $type ] ) ) {
			return 'Organization';
		}

		if ( $types[ $type ]['needs_address'] && ! self::has_address() ) {
			return 'Organization';
		}

		return $type;
	}

	/* ---------------------------------------------------------------------
	 * Small helpers
	 * ------------------------------------------------------------------ */

	private static function rows( $v ) {
		if ( ! is_array( $v ) ) {
			return array();
		}

		// The repeater posts a __i__ template row that must never be saved.
		unset( $v['__i__'] );

		return $v;
	}

	private static function text( $row, $key ) {
		return isset( $row[ $key ] ) ? sanitize_text_field( (string) $row[ $key ] ) : '';
	}

	private static function textarea( $row, $key ) {
		return isset( $row[ $key ] ) ? sanitize_textarea_field( (string) $row[ $key ] ) : '';
	}

	private static function int( $row, $key ) {
		return isset( $row[ $key ] ) ? (int) $row[ $key ] : 0;
	}

	private static function url( $row, $key ) {
		return isset( $row[ $key ] ) ? esc_url_raw( trim( (string) $row[ $key ] ) ) : '';
	}

	private static function date( $row, $key ) {
		$v = isset( $row[ $key ] ) ? trim( (string) $row[ $key ] ) : '';

		// Accept a full date or a bare year; anything else is dropped rather than
		// emitted as an invalid ISO 8601 value.
		if ( preg_match( '/^\d{4}(-\d{2}(-\d{2})?)?$/', $v ) ) {
			return $v;
		}

		return '';
	}

	private static function pick( $row, $key, $allowed, $fallback ) {
		$v = isset( $row[ $key ] ) ? (string) $row[ $key ] : '';

		return in_array( $v, $allowed, true ) ? $v : $fallback;
	}

	/**
	 * Normalise a newline-separated list of URLs, dropping anything unusable.
	 *
	 * @param string $raw
	 * @return string Newline-separated, cleaned.
	 */
	private static function url_list( $raw ) {
		$lines = preg_split( '/[\r\n]+/', (string) $raw );
		$out   = array();

		foreach ( (array) $lines as $line ) {
			$line = esc_url_raw( trim( $line ) );

			if ( '' !== $line ) {
				$out[] = $line;
			}
		}

		return implode( "\n", array_unique( $out ) );
	}

	/**
	 * Split a newline- or comma-separated string into a clean list.
	 *
	 * @param string $raw
	 * @return string[]
	 */
	public static function to_list( $raw ) {
		$parts = preg_split( '/[\r\n]+|,/', (string) $raw );
		$out   = array();

		foreach ( (array) $parts as $part ) {
			$part = trim( $part );

			if ( '' !== $part ) {
				$out[] = $part;
			}
		}

		return array_values( array_unique( $out ) );
	}

	private static function is_assoc( $arr ) {
		if ( array() === $arr ) {
			return true;
		}

		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}
}
