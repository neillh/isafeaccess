<?php
/**
 * Mavero\Components\Foundation class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for adding basic theme support, most of which is mandatory to be implemented by all themes.
 *
 * Exposes template tags:
 * * `xwp_theme()->get_version()`
 * * `xwp_theme()->get_asset_version( string $filepath )`
 */
class Foundation implements Component, Templater {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'after_setup_theme', [ $this, 'action_essential_theme_support' ] );
		add_action( 'wp_head', [ $this, 'action_add_pingback_header' ] );
		add_action( 'wp_head', [ $this, 'action_remove_duplicate_title_tag' ], 0 );
		add_action( 'loop_start', [ $this, 'dequeue_jetpack_social_links' ] );
		add_filter( 'body_class', [ $this, 'filter_body_classes_add_hfeed' ] );
		add_filter( 'embed_defaults', [ $this, 'filter_embed_dimensions' ] );
		add_filter( 'theme_scandir_exclusions', [ $this, 'filter_scandir_exclusions_for_optional_templates' ] );
		add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
	}

	/**
	 * Gets template tags to expose as methods on the Template_Tags class instance, accessible through `xwp_theme()`.
	 *
	 * @return array Associative array of $method_name => $callback_info pairs. Each $callback_info must either be
	 *               a callable or an array with key 'callable'. This approach is used to reserve the possibility of
	 *               adding support for further arguments in the future.
	 */
	public function get_template_tags() {
		return [
			'get_version'       => [ $this, 'get_version' ],
			'get_asset_version' => [ $this, 'get_asset_version' ],
		];
	}

	/**
	 * Adds theme support for essential features.
	 */
	public function action_essential_theme_support() {
		// Add default RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		// Ensure WordPress manages the document title.
		add_theme_support( 'title-tag' );

		// Ensure WordPress theme features render in HTML5 markup.
		add_theme_support(
			'html5',
			[
				'gallery',
				'caption',
				'style',
				'script',
			]
		);

		// Add support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		// Add support for responsive embedded content.
		add_theme_support( 'responsive-embeds' );

		// Only load styles for used blocks.
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );

		// Limit inlining small assets to 50kb.
		add_filter(
			'styles_inline_size_limit',
			function () {
				return 50000; // Size in bytes.
			}
		);
	}

	/**
	 * Adds a pingback url auto-discovery header for singularly identifiable articles.
	 */
	public function action_add_pingback_header() {
		if ( is_singular() && pings_open() ) {
			echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '">';
		}
	}

	/**
	 * Adds a 'hfeed' class to the array of body classes for non-singular pages.
	 *
	 * @param array $classes Classes for the body element.
	 * @return array Filtered body classes.
	 */
	public function filter_body_classes_add_hfeed( array $classes ) {
		if ( ! is_singular() ) {
			$classes[] = 'hfeed';
		}

		return $classes;
	}

	/**
	 * Sets the embed width in pixels, based on the theme's design and stylesheet.
	 *
	 * @param array $dimensions An array of embed width and height values in pixels (in that order).
	 * @return array Filtered dimensions array.
	 */
	public function filter_embed_dimensions( array $dimensions ) {
		$dimensions['width'] = 720;
		return $dimensions;
	}

	/**
	 * Excludes any directory named 'optional' from being scanned for theme template files.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/theme_scandir_exclusions/
	 *
	 * @param array $exclusions the default directories to exclude.
	 * @return array Filtered exclusions.
	 */
	public function filter_scandir_exclusions_for_optional_templates( array $exclusions ) {
		return array_merge( $exclusions, [ 'optional' ] );
	}

	/**
	 * Gets the theme version.
	 *
	 * @return string Theme version number.
	 */
	public function get_version() {
		static $theme_version = null;

		if ( null === $theme_version ) {
			$theme_version = wp_get_theme( get_template() )->get( 'Version' );
		}

		return $theme_version;
	}

	/**
	 * Gets the version for a given asset.
	 *
	 * Returns filemtime when WP_DEBUG is true, otherwise the theme version.
	 *
	 * @param string $filepath Asset file path.
	 * @return string Asset version number.
	 */
	public function get_asset_version( $filepath ) {
		$asset_file = $filepath . '.asset.php';
		if ( file_exists( $asset_file ) ) {
			$asset_meta = include $asset_file;

			if ( isset( $asset_meta['version'] ) ) {
				return $asset_meta['version'];
			}
		}

		if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE !== 'production' ) ) {
			return (string) filemtime( $filepath );
		}

		return $this->get_version();
	}

	/**
	 * Remove filters for jetpack social links.
	 *
	 * @return void
	 */
	public function dequeue_jetpack_social_links() {
		remove_filter( 'the_content', 'sharing_display', 19 );
		remove_filter( 'the_excerpt', 'sharing_display', 19 );
		if ( class_exists( 'Jetpack_Likes' ) ) {
			remove_filter( 'the_content', [ Jetpack_Likes::init(), 'post_likes' ], 30, 1 );
		}
	}

	/**
	 * Stops core printing a second <title> when Yoast SEO prints its own.
	 *
	 * Yoast unhooks core's title output on `init`, but locate_block_template() re-adds
	 * `_block_template_render_title_tag` later on `template_include`, so block themes get both.
	 *
	 * @return void
	 */
	public function action_remove_duplicate_title_tag() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			remove_action( 'wp_head', '_block_template_render_title_tag', 1 );
		}
	}
}
