<?php
/**
 * Taxonomy Industry.
 *
 * Groups Projects by the industry they were delivered for. Public, so each
 * industry gets its own archive at /industries/{term}/ listing its projects.
 *
 * @package Mavero
 */

namespace Mavero\Components\Taxonomies;

use Mavero\Components\Component;
use Mavero\Components\Post_Types\Project;

/**
 * Industry Taxonomy.
 */
class Industry implements Component {

	/**
	 * Taxonomy key.
	 *
	 * @var string
	 */
	const TAXONOMY = 'industry';

	/**
	 * Bump to re-run the one-off setup (seed terms + flush rewrites).
	 *
	 * @var string
	 */
	const SETUP_VERSION = '1';

	/**
	 * Option storing the setup version that last ran.
	 *
	 * @var string
	 */
	const SETUP_OPTION = 'mavero_industry_setup_version';

	/**
	 * Default industries: name => description.
	 *
	 * Seeded once; after that the client manages them under Projects → Industries.
	 *
	 * @var array
	 */
	const DEFAULT_TERMS = [
		'Commercial Office Buildings'        => 'High-rise commercial properties, office complexes, and business parks.',
		'Industrial Manufacturing'           => 'Manufacturing facilities, processing plants, production environments, and industrial infrastructure.',
		'Mechanical Services & HVAC'         => 'Rooftop plant equipment, air conditioning systems, condensers, and ventilation infrastructure.',
		'Construction & Major Projects'      => 'New construction, fit-outs, refurbishments, and large-scale infrastructure developments.',
		'Education Facilities'               => 'Schools, universities, and educational institutions requiring compliant roof access and height safety systems.',
		'Government & Public Sector'         => 'Federal, state, and local government assets requiring inspection, certification, and compliance services.',
		'Research & Scientific Facilities'   => '',
		'Water & Utilities Infrastructure'   => '',
		'Transport & Rail Infrastructure'    => '',
		'Residential & Strata Communities'   => '',
		'Retail & Shopping Centres'          => '',
		'Clubs, Hospitality & Entertainment' => '',
		'Energy & Renewables'                => '',
		'Property & Facilities Management'   => '',
	];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! function_exists( 'register_extended_taxonomy' ) ) {
			return; // Project shows the missing-dependency notice.
		}

		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'admin_init', [ $this, 'maybe_setup' ] );
	}

	/**
	 * Register the taxonomy.
	 *
	 * Hierarchical only for the checkbox UI in the editor (a fixed list to tick,
	 * rather than free-typed tags); URLs stay flat.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		register_extended_taxonomy(
			self::TAXONOMY,
			Project::POST_TYPE,
			[
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => false, // Project's admin_cols adds a sortable one.
				'hierarchical'      => true,
				'exclusive'         => false,
				'allow_hierarchy'   => false,
				'checked_ontop'     => true,
			],
			[
				'singular' => __( 'Industry', 'mavero' ),
				'plural'   => __( 'Industries', 'mavero' ),
				'slug'     => 'industries',
			],
		);
	}

	/**
	 * One-off setup: seed the default industries and flush rewrite rules so
	 * /projects/ and /industries/{term}/ resolve without re-saving permalinks.
	 *
	 * @return void
	 */
	public function maybe_setup(): void {
		if ( self::SETUP_VERSION === get_option( self::SETUP_OPTION ) ) {
			return;
		}

		foreach ( self::DEFAULT_TERMS as $name => $description ) {
			if ( ! term_exists( $name, self::TAXONOMY ) ) {
				wp_insert_term( $name, self::TAXONOMY, [ 'description' => $description ] );
			}
		}

		flush_rewrite_rules( false );
		update_option( self::SETUP_OPTION, self::SETUP_VERSION );
	}
}
