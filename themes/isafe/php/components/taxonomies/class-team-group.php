<?php
/**
 * Taxonomy Team Group.
 *
 * Splits Team Members into groups (Leadership Team, Field Team) so each can be
 * listed separately with a Query Loop filtered by this taxonomy.
 *
 * @package Mavero
 */

namespace Mavero\Components\Taxonomies;

use Mavero\Components\Component;
use Mavero\Components\Post_Types\Team;

/**
 * Team Group Taxonomy.
 */
class Team_Group implements Component {

	/**
	 * Taxonomy key.
	 *
	 * @var string
	 */
	const TAXONOMY = 'team_group';

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
	const SETUP_OPTION = 'mavero_team_group_setup_version';

	/**
	 * Default groups, seeded once; after that managed under Team Members → Teams.
	 *
	 * @var string[]
	 */
	const DEFAULT_TERMS = [
		'Leadership Team',
		'Field Team',
	];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! function_exists( 'register_extended_taxonomy' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'admin_init', [ $this, 'maybe_setup' ] );
	}

	/**
	 * Register the taxonomy.
	 *
	 * Publicly queryable so the Query Loop block offers it as a filter.
	 * Hierarchical only for the checkbox UI in the editor; URLs stay flat.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		register_extended_taxonomy(
			self::TAXONOMY,
			Team::POST_TYPE,
			[
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => false, // Team's admin_cols adds a sortable one.
				'hierarchical'      => true,
				'allow_hierarchy'   => false,
				'checked_ontop'     => true,
			],
			[
				'singular' => __( 'Team', 'mavero' ),
				'plural'   => __( 'Teams', 'mavero' ),
				'slug'     => 'teams',
			],
		);
	}

	/**
	 * One-off setup: seed the default groups and flush rewrite rules.
	 *
	 * @return void
	 */
	public function maybe_setup(): void {
		if ( self::SETUP_VERSION === get_option( self::SETUP_OPTION ) ) {
			return;
		}

		foreach ( self::DEFAULT_TERMS as $name ) {
			if ( ! term_exists( $name, self::TAXONOMY ) ) {
				wp_insert_term( $name, self::TAXONOMY );
			}
		}

		flush_rewrite_rules( false );
		update_option( self::SETUP_OPTION, self::SETUP_VERSION );
	}
}
