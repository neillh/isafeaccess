<?php
/**
 * Mavero\Components\Editor class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for integrating with the block editor.
 *
 * @link https://wordpress.org/gutenberg/handbook/extensibility/theme-support/
 */
class Editor implements Component {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'after_setup_theme', [ $this, 'action_add_editor_support' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_script_enqueue' ] );
		add_action( 'admin_footer', [ $this, 'remove_min_value_inline_script' ] );
	}


	/**
	 * Adds support for various editor features.
	 */
	public function action_add_editor_support() {
		// Add support for editor styles.
		add_theme_support( 'editor-styles' );

		// Add support for default block styles.
		//add_theme_support( 'wp-block-styles' );

		// Add support for wide-aligned images.
		add_theme_support( 'align-wide' );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @throws \RuntimeException Throws if script asset is not found.
	 */
	public function editor_script_enqueue() {
		$script_asset_path = xwp_theme_meta()->path( 'build', 'js', 'editor.asset.php' );

		if ( ! file_exists( $script_asset_path ) ) {
			throw new \RuntimeException( 'Built JavaScript assets not found. Please run `npm run build`' );
		}

		$script_url   = xwp_theme_meta()->url( 'build', 'js', 'editor.js' );
		$script_asset = require $script_asset_path; // phpcs:disable WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound

		wp_enqueue_script(
			'xwp-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		$editor_data = apply_filters( 'mavero_editor_data', [] );

		if ( ! empty( $editor_data ) ) {
			wp_add_inline_script( 'xwp-editor', 'const xwpEditorObject = ' . wp_json_encode( $editor_data ), 'before' );
		}
	}

	/**
	 * Remove min value inline script.
	 *
	 * @echo javascript to allow minus values in margin field
	 */
	public function remove_min_value_inline_script() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'edit' === $screen->parent_base ) {
			echo '<script type="text/javascript">
				document.addEventListener("DOMContentLoaded", function() {
					const observer = new MutationObserver(function(mutations) {
						mutations.forEach(function(mutation) {
							if (mutation.addedNodes.length) {
								mutation.addedNodes.forEach(function(node) {
									if (node.querySelectorAll) {
										node.querySelectorAll(".spacing-sizes-control input[min=\'0\']").forEach(function(input) {
											input.removeAttribute("min");
										});
									}
								});
							}
						});
					});

					observer.observe(document.body, {
						childList: true,
						subtree: true
					});
				});
			</script>';
		}
	}
}
