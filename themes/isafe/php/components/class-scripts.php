<?php
/**
 * Mavero\Components\Scripts class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for managing stylesheets.
 */
class Scripts implements Component {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'action_enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'configure_core_scripts' ], 100 );
		add_action( 'wp_head', [ $this, 'remove_no_js_class_from_html' ], defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : ~PHP_INT_MAX ); // phpcs:ignore PHPCompatibility.Constants.NewConstants.php_int_minFound
		add_action( 'wp_footer', [ $this, 'fix_adsense_main_height' ] );
		add_action( 'init', [ $this, 'remove_emoji' ] );
	}

	/**
	 * Registers or enqueues stylesheets.
	 *
	 * Stylesheets that are global are enqueued. All other stylesheets are only registered, to be enqueued later.
	 *
	 * @throws \RuntimeException Throws if assets aren't built.
	 */
	public function action_enqueue_scripts() {
		$main_js_asset_path = xwp_theme_meta()->path( 'build', 'js', 'main.asset.php' );

		if ( ! file_exists( $main_js_asset_path ) ) {
			throw new \RuntimeException( 'Built JavaScript assets not found. Please run `npm run build`' );
		}

		$main_js_url   = xwp_theme_meta()->url( 'build', 'js', 'main.js' );
		$main_js_asset = require $main_js_asset_path; // phpcs:disable WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound

		wp_enqueue_script(
			'mavero-main',
			$main_js_url,
			$main_js_asset['dependencies'],
			$main_js_asset['version'],
			[
				'in_footer' => true,
				'strategy' => 'defer',
			]
		);
	}

	/**
	 * Registers or enqueues stylesheets.
	 *
	 * Stylesheets that are global are enqueued. All other stylesheets are only registered, to be enqueued later.
	 *
	 * @throws \RuntimeException Throws if assets aren't built.
	 */
	public function action_enqueue_admin_scripts() {
		$admin_js_asset_path = xwp_theme_meta()->path( 'assets', 'dist', 'admin.asset.php' );

		if ( ! file_exists( $admin_js_asset_path ) ) {
			throw new \RuntimeException( 'Built JavaScript assets not found. Please run `npm run build`' );
		}

		$admin_js_url   = xwp_theme_meta()->url( 'assets', 'dist', 'admin.js' );
		$admin_js_asset = require $admin_js_asset_path; // phpcs:disable WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound
		//$editor_js_url   = xwp_theme_meta()->url( 'assets/dist', 'js', 'editor.js' );

		wp_enqueue_script(
			'mavero-admin',
			$admin_js_url,
			[],
			[],
			[
				'in_footer' => true,
				'strategy' => 'defer',
			]
		);
	}

	/**
	 * Add custom attributes to core scripts.
	 *
	 * @return void
	 */
	public function configure_core_scripts() {
		wp_script_add_data( 'jetpack-photon', 'defer', true );
		wp_script_add_data( 'jquery-migrate', 'defer', true );
		wp_script_add_data( 'regenerator-runtime', 'nomodule', true );
		wp_script_add_data( 'regenerator-runtime', 'defer', true );
		wp_script_add_data( 'wp-embed', 'defer', true );
		wp_script_add_data( 'wp-polyfill-inert', 'defer', true );
		wp_script_add_data( 'wp-polyfill', 'nomodule', true );
		wp_script_add_data( 'wp-polyfill', 'defer', true );
	}

	/**
	 * Remove the no-js class from the <html> tag if JS support is detected.
	 *
	 * Do it in a way that doesn't block rendering.
	 *
	 * @see https://www.phpied.com/asynchronous-inline-scripts-via-data-urls/
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs
	 */
	public function remove_no_js_class_from_html() {
		$script_src = sprintf(
			'data:text/javascript,%s',
			rawurlencode( 'document.documentElement.classList.remove("no-js");' )
		);

		printf( '<script async src="%s"></script>', esc_url( $script_src, [ 'data' ] ) );
		echo PHP_EOL;
	}

	/**
	 * Prevent Google AdSense from setting height: auto !important on <main>.
	 *
	 * AdSense traverses up the DOM from ad slots and incorrectly sets
	 * height: auto !important on ancestor elements including <main>, causing CLS.
	 *
	 * @return void
	 */
	public function fix_adsense_main_height() {
		?>
		<script>
			(function () {
				var main = document.getElementById('wp--skip-link--target');
				if (!main) return;
				// Remove immediately in case AdSense already set it.
				main.style.removeProperty('height');
				// Intercept setProperty so AdSense cannot re-add height.
				var origSetProperty = main.style.setProperty.bind(main.style);
				main.style.setProperty = function (prop, value, priority) {
					if (prop === 'height') return;
					return origSetProperty(prop, value, priority);
				};
				// Fallback observer for setAttribute('style', ...) paths.
				new MutationObserver(function () {
					if (main.style.getPropertyValue('height')) {
						main.style.removeProperty('height');
					}
				}).observe(main, { attributes: true, attributeFilter: ['style'] });
			})();
		</script>
		<?php
	}

	/**
	 * Remove legacy emoji support.
	 *
	 * @return void
	 */
	public function remove_emoji() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}
}
