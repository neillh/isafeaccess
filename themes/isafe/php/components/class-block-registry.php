<?php
/**
 * Mavero\Components\Blocks class
 *
 * @package Mavero
 */

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
namespace Mavero\Components;

use Mavero\Helpers\Utils;

/**
 * Class for registering custom block patterns.
 *
 * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/
 */
class Block_Registry implements Component {

	/**
	 * Block directories with this prefixed are registered only during development.
	 *
	 * @var string
	 */
	const TEMPLATE_DIRECTORY_PREFIX = '_';

	/**
	 * Block.json asset path keys that we'll rewrite to point to build directory.
	 *
	 * @var array
	 */
	const ASSET_PATH_KEYS = [
		'script',
		'viewScript',
		'viewScriptModule',
		'editorScript',
		'style',
		'viewStyle',
		'editorStyle',
	];

	/**
	 * Block source directory relative to the theme root.
	 *
	 * We register the build directory with WP and rewrite any
	 * render template paths back to the source directory since
	 * wp-scripts doesn't move any other PHP files to the
	 * build directory.
	 *
	 * @var string
	 */
	const SOURCE_DIRECTORY = 'blocks';

	/**
	 * Block distribution directory relative to the theme root.
	 *
	 * Contains block.json and all the built JS and CSS. All PHP
	 * is loaded from the source directory instead.
	 *
	 * @var string
	 */
	const BUILD_DIRECTORY = 'build/blocks';

	/**
	 * Register any needed hooks/filters.
	 */
	public function init() {
		// After post types and taxonomies (priority 10), but before core's
		// WP_Block_Supports::init (priority 22), which adds the block-support
		// attributes (align, style, className…) only to blocks registered by
		// then. Registered later, a dynamic block's server-side preview is
		// rejected by the REST block renderer ("align is not a valid property")
		// and the editor shows "Error loading block".
		add_action( 'init', [ $this, 'action_register_blocks' ], 20 );
		add_filter( 'block_type_metadata', [ $this, 'maybe_rewrite_block_asset_paths' ] );
		add_filter( 'block_categories_all', [ $this, 'action_add_block_category' ], 10 );

		/**
		 * As registering a block which is solely defined in the current themes parent,
		 * Have to add the `override_theme_file_path` filter to `theme_file_path` to fix wrong URL for block styles/scripts,
		 * this serves as a temporary workaround for https://github.com/WordPress/wordpress-develop/pull/3921
		 */
		add_filter( 'block_type_metadata', [ $this, 'filter_block_type_metadata' ] );
		add_filter( 'block_type_metadata_settings', [ $this, 'filter_block_type_metadata_settings' ], 10, 2 );
	}

	/**
	 * The filter function for overriding `get_theme_file_path()`
	 *
	 * @param string $path The file path.
	 * @param string $file The requested file to search for.
	 *
	 * @return string
	 */
	public function override_theme_file_path( $path, $file ): string {
		return get_template_directory() . '/' . $file;
	}

	/**
	 * Filter block metadata to fix block assets file path for child theme.
	 *
	 * @param array $metadata Metadata for registering a block type.
	 *
	 * @return array
	 */
	public function filter_block_type_metadata( $metadata ) {
		if ( array_key_exists( 'file', $metadata ) ) {

			// Normalize the block path.
			$block_path_norm = wp_normalize_path( $metadata['file'] );

			// Cache the $template_path_norm and $stylesheet_path_norm to avoid unnecessary additional calls.
			static $template_path_norm   = '';
			static $stylesheet_path_norm = '';

			if ( ! $template_path_norm || ! $stylesheet_path_norm ) {
				$template_path_norm   = wp_normalize_path( get_template_directory() );
				$stylesheet_path_norm = wp_normalize_path( get_stylesheet_directory() );
			}

			// If looking at a block which is only defined in the parent theme then add the filter.
			$is_template_block   = str_starts_with( $block_path_norm, $template_path_norm );
			$is_stylesheet_block = str_starts_with( $block_path_norm, $stylesheet_path_norm );

			static $theme_path_norm = '';

			if ( ( '' === $theme_path_norm ) || ( $is_template_block && ! $is_stylesheet_block ) ) {
				add_filter( 'theme_file_path', [ $this, 'override_theme_file_path' ], 10, 2 );
			}
		}

		return $metadata;
	}

	/**
	 * Remove the filter added to block metadata to fix block assets file path for child theme.
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata Metadata provided for registering a block type.
	 *
	 * @return array
	 */
	public function filter_block_type_metadata_settings( $settings, $metadata ) {
		remove_filter( 'theme_file_path', [ $this, 'override_theme_file_path' ] );

		return $settings;
	}

	/**
	 * Register all blocks in the source directory to account
	 * for wp-scripts not moving all PHP scripts during the build.
	 *
	 * All block asset paths are rewriten to point to the build
	 * directory dynamically.
	 */
	public function action_register_blocks() {
		// Lookup by block.json since we have other non-block directories in the same folder.
		$block_json_paths = glob( xwp_theme_meta()->path( self::SOURCE_DIRECTORY, '*', 'block.json' ) );

		$block_directories = array_map( 'dirname', $block_json_paths );

		// Register block templates only during development.
		if ( ! Utils::is_debug() ) {
			$block_directories = array_filter(
				$block_directories,
				function ( $block_directory ) {
					return ! str_starts_with( basename( $block_directory ), self::TEMPLATE_DIRECTORY_PREFIX );
				}
			);
		}

		foreach ( $block_directories as $directory ) {
			// Allow blocks to run any custom logic before registration (to allow hooking into the registration).
			$block_init_path = xwp_theme_meta()->path( 'blocks', basename( $directory ), 'init.php' );
			if ( is_readable( $block_init_path ) ) {
				require_once $block_init_path;
			}
			$block = register_block_type( $directory );

			// Allow blocks to run any custom logic during registration.
			$block_functions_path = sprintf( '%s/functions.php', $directory );

			if ( is_a( $block, \WP_Block_Type::class ) && is_readable( $block_functions_path ) ) {
				require_once $block_functions_path;
			}
		}
	}

	/**
	 * Rewrite PHP template path if it's specified.
	 *
	 * The wp-scripts doesn't move all PHP files to the build directory
	 * during the build like it does with CSS and JS files so we keep
	 * all PHP assets under the original block source directory.
	 *
	 * @param array $metadata Registered block metadata.
	 *
	 * @return array Modified metadata.
	 */
	public function maybe_rewrite_block_asset_paths( array $metadata ): array {
		if ( ! empty( $metadata['file'] ) && $this->is_block_in_theme( $metadata['file'] ) ) {
			$block_path = dirname( $metadata['file'] );

			foreach ( self::ASSET_PATH_KEYS as $asset_type ) {
				if ( ! empty( $metadata[ $asset_type ] ) ) {
					$metadata[ $asset_type ] = $this->rewrite_assets_paths( $metadata[ $asset_type ], $block_path );
				}
			}
		}

		return $metadata;
	}

	/**
	 * If the full path of a block asset belongs to a block in this theme.
	 *
	 * @param string $block_path Absolute path to any block asset.
	 *
	 * @return boolean
	 */
	public function is_block_in_theme( $block_path ) {
		return ! empty( xwp_theme_meta()->path_from_root( $block_path ) );
	}

	/**
	 * Rewrite assets paths to point to the built assets.
	 *
	 * @param string|string[] $assets     Single or array of asset paths or handles.
	 * @param string          $block_path Path to block directory.
	 *
	 * @return string[] Rewritten assets paths.
	 */
	protected function rewrite_assets_paths( $assets, string $block_path ): array {
		if ( is_string( $assets ) ) {
			$assets = [ $assets ];
		}

		// Resolve block path relative to the theme root directory.
		$path_to_root = xwp_theme_meta()->path_to_root( $block_path );

		if ( ! $path_to_root ) {
			return $assets;
		}

		foreach ( $assets as $key => $asset ) {
			$filename = remove_block_asset_path_prefix( $asset );

			// Rewrite only paths (not handle IDs).
			if ( $filename !== $asset ) {
				$assets[ $key ] = sprintf(
					'file:%s/%s/%s/%s',
					$path_to_root,
					self::BUILD_DIRECTORY,
					basename( $block_path ),
					$filename
				);
			}
		}

		return $assets;
	}

	/**
	 * Adding a new (custom) block category.
	 *
	 * @param array $block_categories Array of categories for block types.
	 *
	 * @return array List of available block categories.
	 */
	public function action_add_block_category( array $block_categories ): array {
		return array_merge(
			$block_categories,
			[
				[
					'slug'  => 'mavero-blocks',
					'title' => __( 'Mavero Blocks', 'mavero' ),
				],
			]
		);
	}
}
