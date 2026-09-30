<?php
/**
 * Plugin Name:       Mavero Schema
 * Plugin URI:        https://isafeaccess.com.au
 * Description:       Builds a single, coherent Schema.org entity graph — Organization identity, services, people, contact points and reviews — merged into Yoast SEO's existing JSON-LD graph rather than emitted as a competing script tag.
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Mavero
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mavero-schema
 *
 * Design note
 * -----------
 * Every node this plugin produces is added to Yoast's own @graph through
 * `wpseo_schema_graph_pieces` (Yoast 28.x, src/generators/schema-generator.php:322)
 * and the per-piece `wpseo_schema_<identifier>` filters. Nothing is printed as a
 * standalone <script type="application/ld+json"> block. That is deliberate: a second
 * script tag produces a second, disconnected business entity, which is precisely the
 * defect this plugin exists to remove.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAVERO_SCHEMA_VERSION', '1.1.0' );
define( 'MAVERO_SCHEMA_FILE', __FILE__ );
define( 'MAVERO_SCHEMA_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAVERO_SCHEMA_URL', plugin_dir_url( __FILE__ ) );
define( 'MAVERO_SCHEMA_BASENAME', plugin_basename( __FILE__ ) );

require_once MAVERO_SCHEMA_DIR . 'includes/class-mavero-settings.php';
require_once MAVERO_SCHEMA_DIR . 'includes/class-mavero-admin.php';
require_once MAVERO_SCHEMA_DIR . 'includes/class-mavero-organization.php';
require_once MAVERO_SCHEMA_DIR . 'includes/class-mavero-webpage.php';
require_once MAVERO_SCHEMA_DIR . 'includes/class-mavero-pieces.php';
require_once MAVERO_SCHEMA_DIR . 'includes/class-mavero-reviews.php';

/**
 * Boot the plugin.
 *
 * Admin-side settings load unconditionally so the site can be configured before
 * (or without) Yoast. The schema output only wires up when Yoast is actually present.
 */
function mavero_schema_init() {
	Mavero_Settings::init();
	Mavero_Admin::init();

	if ( ! mavero_schema_yoast_active() ) {
		return;
	}

	Mavero_Organization::init();
	Mavero_WebPage::init();
	Mavero_Pieces::init();
	Mavero_Reviews::init();
}
add_action( 'plugins_loaded', 'mavero_schema_init', 20 );

/**
 * Whether Yoast SEO is active and exposes the schema API this plugin builds on.
 *
 * Checks for the abstract piece class specifically, not just WPSEO_VERSION: the
 * graph-piece API is what we extend, and it is what would break on a major rewrite.
 *
 * @return bool
 */
function mavero_schema_yoast_active() {
	return defined( 'WPSEO_VERSION' )
		&& class_exists( 'Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' );
}

/**
 * Convenience accessor for the settings array.
 *
 * @param string|null $section Optional top-level section key.
 * @return mixed
 */
function mavero_schema_get( $section = null ) {
	return Mavero_Settings::get( $section );
}

/**
 * The @id of the site's single business entity.
 *
 * This is Yoast's own Organization id. Everything this plugin emits points here —
 * there is deliberately no second business node.
 *
 * @return string
 */
function mavero_schema_org_id() {
	return trailingslashit( home_url( '/' ) ) . '#organization';
}

register_activation_hook(
	__FILE__,
	static function () {
		Mavero_Settings::install_defaults();
	}
);
