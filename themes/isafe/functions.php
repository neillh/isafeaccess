<?php
/**
 * Mavero functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Mavero
 */

use Mavero\Theme;
use Mavero\Theme_Meta;
use Mavero\Helpers\Autoload;

require_once __DIR__ . '/php/helpers/class-autoload.php';

// Composer dependencies (johnbillion/extended-cpts for the post types). Run
// `composer install --no-dev` in the theme directory when deploying.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Autoloader serves for `Mavero` namespace and autoload all files under the php directory.
$autoload = new Autoload();
$autoload->add( 'Mavero', sprintf( '%s/php', __DIR__ ) );

/**
 * Set the theme version here.
 */
$theme_version = '1.0';

/**
 * Retrieves the theme meta data.
 *
 * @return Theme_Meta
 */
function xwp_theme_meta() {
	global $theme_version;
	static $theme_meta = null;

	if ( ! $theme_meta ) {
		$theme_meta = new Theme_Meta( $theme_version, __DIR__ );
	}

	return $theme_meta;
}

/**
 * Retrieves an instance of the theme.
 *
 * @return Theme
 */
function xwp_theme() {
	static $theme = null;

	if ( ! $theme ) {
		$theme = new Theme();
		$theme->init();
	}

	return $theme;
}

xwp_theme();

if ( ! function_exists( 'wp_body_open' ) ) {
	/**
	 * Shim for sites older than 5.2.
	 *
	 * @link https://core.trac.wordpress.org/ticket/12563
	 */
	function wp_body_open() {
		do_action( 'wp_body_open' );
	}
}

unset( $theme_version );


// Allow SVG uploads in WordPress
function enable_svg_upload( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'enable_svg_upload' );

add_filter( 'wpseo_og_locale', 'custom_change_yoast_locale' );
function custom_change_yoast_locale( $locale ) {
    return 'en_AU';
}
