<?php
/**
 * Mavero\Components\Styles class
 *
 * @package Mavero
 */

namespace Mavero\Components;

use WP_Block;
use Mavero\Helpers\Utils;

/**
 * Class for managing stylesheets.
 *
 * Exposes template tags:
 * * `xwp_theme()->print_styles()`
 */
class Styles implements Component, Templater {

	/**
	 * Associative array of CSS files, as $handle => $data pairs.
	 * $data must be an array with keys 'file' (file path relative to 'assets/css' directory), and optionally 'global'
	 * (whether the file should immediately be enqueued instead of just being registered) and 'preload_callback'
	 * (callback function determining whether the file should be preloaded for the current request).
	 *
	 * Do not access this property directly, instead use the `get_css_files()` method.
	 *
	 * @var array
	 */
	protected $css_files;

	/**
	 * Holds the original core block render callback.
	 *
	 * @var array
	 */
	protected $callbacks = [];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'remove_styles' ], 99 );
		add_action( 'wp_enqueue_scripts', [ $this, 'action_enqueue_styles' ], 999999 );
		add_action( 'admin_enqueue_scripts', [ $this, 'action_enqueue_admin_styles' ], 999999 );
		add_action( 'login_enqueue_scripts', [ $this, 'action_enqueue_login_styles' ], 999999 );
		add_action( 'wp_head', [ $this, 'action_preload_styles' ], 5 );
		add_action( 'wp_head', [ $this, 'action_preload_fonts' ], 5 );
		add_action( 'wp_head', [ $this, 'action_preconnect_sources' ], 5 );
		add_filter( 'style_loader_tag', [ $this, 'defer_stylesheet' ], 10, 4 );
		add_filter( 'style_loader_src', [ $this, 'remove_google_fonts' ] );
		add_action( 'wp_body_open', [ $this, 'init_inline_styles' ] );
		add_action( 'admin_init', [ $this, 'add_editor_style_support' ] );
		add_action( 'after_setup_theme', [ $this, 'action_remove_duotone' ] );

		// Ensure not used via API.
		if ( ! Utils::is_api_call() ) {
			//add_filter( 'block_type_metadata_settings', [ $this, 'filter_block_type_metadata' ], 10, 2 );
		}
	}

	/**
	 * Dequeues plugin stylesheets the theme doesn't need.
	 *
	 * @return void
	 */
	public function remove_styles() {
		wp_dequeue_style( 'jetpack-social-menu' );
	}

	/**
	 * Override core block render that enqueue support styles within the render. i.e. core/gallery.
	 *
	 * @param array $settings The block settings.
	 * @param array $metadata The original block metadata.
	 *
	 * @return array
	 */
	public function filter_block_type_metadata( $settings, $metadata ) {
		if ( false !== strpos( $metadata['name'], 'core/' ) && ! empty( $settings['render_callback'] ) ) {
			$this->callbacks[ $metadata['name'] ] = $settings['render_callback'];
			$settings['render_callback']          = [ $this, 'render_core_block' ];
		}

		return $settings;
	}

	/**
	 * Render the block using the original call back and check the footer for changed styles.
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content    The block content.
	 * @param WP_Block $block      The block object.
	 *
	 * @return string
	 */
	public function render_core_block( $attributes, $content, $block ) {
		global $wp_filter;

		if ( ! array_key_exists( 'wp_footer', $wp_filter ) ) {
			return $content;
		}

		// Duplicate the wp_footer hook.
		$temp_holder = $wp_filter['wp_footer'];

		// Remove the old wp_footer.
		unset( $wp_filter['wp_footer'] );

		// Render the content with the original callback.
		$content = call_user_func( $this->callbacks[ $block->name ], $attributes, render_block( $block->parsed_block ), $block );


		// If the original callback enqueued an action to wp_footer, it will now exist.
		if ( ! empty( $wp_filter['wp_footer'] ) ) {

			// Do the action to output the styles/scripts.
			do_action( 'wp_footer' );

			// Reset it again.
			unset( $wp_filter['wp_footer'] );
		}

		// Replace the previous wp_footer hooks.
		foreach ( $temp_holder->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $hook ) {
				add_action( 'wp_footer', $hook['function'], $priority, $hook['accepted_args'] );
			}
		}

		return $content;
	}

	/**
	 * Init the style capture and handler.
	 *
	 * @return void
	 */
	public function init_inline_styles() {
		// Bail if we're not using default styles.
		if ( ! current_theme_supports( 'wp-block-styles' ) ) {
			return;
		}

		// Remove the wp_render_layout_support_flag from render_block, because we want to control it.
		remove_filter( 'render_block', 'wp_render_layout_support_flag' );
		// Replace with our own handler.
		add_filter( 'render_block', [ $this, 'layout_render' ], 10, 2 );

		// Ensure globals are here at the top of the page.
		wp_enqueue_global_styles();
		$this->render_inline_styles();
	}

	/**
	 * Render inline styles that have been enqueued since the last run.
	 *
	 * @return void
	 */
	public function render_inline_styles() {
		$styles = wp_styles();

		// Send the current queue to all_deps. This will populate the `to_do` for new items.
		$styles->all_deps( $styles->queue );
		if ( ! empty( $styles->to_do ) ) {

			// Copy the queue to a temp var.
			$temp = $styles->queue;

			// Set the queue as the to_do list.
			array_map( [ $styles, 'dequeue' ], $temp );
			array_map( [ $styles, 'enqueue' ], $styles->to_do );

			/*
			 * This will use the current queue and add inline scripts for each that fit.
			 * calling it repeatedly would append the same inline CSS on each run, so we only give it the new items.
			 */
			wp_maybe_inline_styles();

			// Replace the original queue.
			array_map( [ $styles, 'dequeue' ], $styles->to_do );
			array_map( [ $styles, 'enqueue' ], $temp );

			// Do items that are new.
			$styles->do_items();
		}
	}

	/**
	 * Handler the render styles.
	 *
	 * @param string $block_content Rendered block content.
	 * @param array  $block         Block object.
	 *
	 * @return string Filtered block content.
	 */
	public function layout_render( $block_content, $block ) {
		static $counter = 0;

		// Call the layout support.
		$block_content = wp_render_layout_support_flag( $block_content, $block );
		$store         = \WP_Style_Engine::get_store( 'block-supports' );
		$rules         = $store->get_all_rules();

		// Render inline and remove rule from footer.
		if ( ! empty( $rules ) ) {
			$styles = wp_style_engine_get_stylesheet_from_context( 'block-supports' );
			if ( ! empty( $styles ) ) {
				++$counter;
				$style_tag_id = 'block-support-' . $counter;
				wp_register_style( $style_tag_id, false, [], true, true );
				wp_add_inline_style( $style_tag_id, $styles );
				wp_enqueue_style( $style_tag_id );
			}
			array_map( [ $store, 'remove_rule' ], array_keys( $rules ) );
		}

		// Render any new styles enqueued, will most likely be this block we're rendering.
		$this->render_inline_styles();

		return $block_content;
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
			'print_styles' => [ $this, 'print_styles' ],
		];
	}

	/**
	 * Registers or enqueues stylesheets.
	 *
	 * Stylesheets that are global are enqueued. All other stylesheets are only registered, to be enqueued later.
	 */
	public function action_enqueue_styles() {
		$css_uri = trailingslashit( xwp_theme_meta()->url( 'build', 'css' ) );
		$css_dir = trailingslashit( xwp_theme_meta()->path( 'build', 'css' ) );

		$preloading_styles_enabled = $this->preloading_styles_enabled();

		$css_files = $this->get_css_files();
		foreach ( $css_files as $handle => $data ) {
			$src     = $css_uri . $data['file'];
			$version = filemtime( $css_dir . $data['file'] );

			/*
			 * Enqueue global stylesheets immediately and register the other ones for later use
			 * (unless preloading stylesheets is disabled, in which case stylesheets should be immediately
			 * enqueued based on whether they are necessary for the page content).
			 */
			if (
				$data['global']
				|| (
					! $preloading_styles_enabled && is_callable( $data['preload_callback'] )
					&& call_user_func( $data['preload_callback'] )
				)
			) {
				wp_enqueue_style( $handle, $src, [], $version, $data['media'] );
			} else {
				wp_register_style( $handle, $src, [], $version, $data['media'] );
			}

			wp_style_add_data( $handle, 'precache', true );
		}
	}

	/**
	 * Preloads in-body stylesheets depending on what templates are being used.
	 *
	 * Only stylesheets that have a 'preload_callback' provided will be considered. If that callback evaluates to true
	 * for the current request, the stylesheet will be preloaded.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
	 */
	public function action_preload_styles() {

		// If preloading styles is disabled, return early.
		if ( ! $this->preloading_styles_enabled() ) {
			return;
		}

		$wp_styles = wp_styles();

		$css_files = $this->get_css_files();
		foreach ( $css_files as $handle => $data ) {

			// Skip if stylesheet not registered.
			if ( ! isset( $wp_styles->registered[ $handle ] ) ) {
				continue;
			}

			// Skip if no preload callback provided.
			if ( ! is_callable( $data['preload_callback'] ) ) {
				continue;
			}

			// Skip if preloading is not necessary for this request.
			if ( ! call_user_func( $data['preload_callback'] ) ) {
				continue;
			}

			$preload_uri = $wp_styles->registered[ $handle ]->src . '?ver=' . $wp_styles->registered[ $handle ]->ver;

			echo '<link rel="preload" id="' . esc_attr( $handle ) . '-preload" href="' . esc_url( $preload_uri ) . '" as="style">';
			echo "\n";
		}
	}

	/**
	 * Preconnects to third-party origins needed early in the page load.
	 *
	 * Everything above the fold is served from the site's own origin, so nothing is preconnected
	 * by default — an unused preconnect is flagged by Lighthouse and wastes a connection. Jetpack's
	 * Image CDN adds its own i0.wp.com preconnect when enabled. Add other origins via the filter
	 * only if they serve render-critical assets.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel/preconnect
	 */
	public function action_preconnect_sources() {
		$critical_preconnect_urls = apply_filters( 'mavero_critical_preconnect_urls', [] );

		foreach ( $critical_preconnect_urls as $critical_preconnect_url ) {
			echo '<link rel="preconnect" href="' . esc_url( $critical_preconnect_url ) . '" crossorigin>';
		}
	}

	/**
	 * Preloads critical font files.
	 *
	 * Only the upright Archivo variable font is preloaded: it covers every weight used above the
	 * fold. The italic file is left to load on demand. The URL must match the theme.json @font-face
	 * src exactly (no ?ver), or the browser downloads the font twice.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
	 */
	public function action_preload_fonts() {

		// Supports remote URIs & local paths (relative to the theme root).
		$critical_font_paths = [ 'assets/font/Archivo-latin-VariableFont_wght.woff2' ];

		if ( is_child_theme() ) {
			$critical_font_paths = [];
		}

		$critical_font_paths = apply_filters( 'mavero_critical_font_paths', $critical_font_paths );

		foreach ( $critical_font_paths as $critical_font_path ) {
			$preload_uri = $critical_font_path;

			// Support for local paths.
			$host = wp_parse_url( $critical_font_path, PHP_URL_HOST );
			if ( null === $host ) {
				$preload_uri = trailingslashit( get_template_directory_uri() ) . $critical_font_path;
			}

			echo '<link rel="preload" href="' . esc_url( $preload_uri ) . '" as="font" type="font/woff2" crossorigin="anonymous">';
		}
	}

	/**
	 * Prints stylesheet link tags directly.
	 *
	 * This should be used for stylesheets that aren't global and thus should only be loaded if the HTML markup
	 * they are responsible for is actually present. Template parts should use this method when the related markup
	 * requires a specific stylesheet to be loaded. If preloading stylesheets is disabled, this method will not do
	 * anything.
	 *
	 * If the `<link>` tag for a given stylesheet has already been printed, it will be skipped.
	 *
	 * @param string ...$handles One or more stylesheet handles.
	 */
	public function print_styles( ...$handles ) {
		// If preloading styles is disabled (and thus they have already been enqueued), return early.
		if ( ! $this->preloading_styles_enabled() ) {
			return;
		}

		$css_files = $this->get_css_files();
		$handles   = array_filter(
			$handles,
			function ( $handle ) use ( $css_files ) {
				$is_valid = isset( $css_files[ $handle ] ) && ! $css_files[ $handle ]['global'];
				if ( ! $is_valid ) {
					_doing_it_wrong(
						__CLASS__ . '::print_styles()',
						/* translators: %s: stylesheet handle */
						esc_html( sprintf( __( 'Invalid theme stylesheet handle: %s', 'mavero' ), $handle ) ),
						'Mavero 1.0'
					);
				}

				return $is_valid;
			}
		);

		if ( empty( $handles ) ) {
			return;
		}

		wp_print_styles( $handles );
	}

	/**
	 * Determines whether to preload stylesheets and inject their link tags directly within the page content.
	 *
	 * Using this technique generally improves performance, however may not be preferred under certain circumstances.
	 * By default, this method returns true. The {@see 'mavero_preloading_styles_enabled'} filter can be
	 * used to tweak the return value.
	 *
	 * @return bool True if preloading stylesheets and injecting them is enabled, false otherwise.
	 */
	protected function preloading_styles_enabled() {
		/**
		 * Filters whether to preload stylesheets and inject their link tags within the page content.
		 *
		 * @param bool $preloading_styles_enabled Whether preloading stylesheets and injecting them is enabled.
		 */
		return apply_filters( 'mavero_preloading_styles_enabled', true );
	}

	/**
	 * Gets all CSS files.
	 *
	 * @return array Associative array of $handle => $data pairs.
	 */
	protected function get_css_files() {
		if ( is_array( $this->css_files ) ) {
			return $this->css_files;
		}

		$css_files = [
			'mavero-global' => [
				'file' => 'style.css',
				'global' => true,
				'preload_callback' => '__return_true',
			],
		];

		/**
		 * Filters default CSS files.
		 *
		 * @param array $css_files Associative array of CSS files, as $handle => $data pairs.
		 *                         $data must be an array with keys 'file' (file path relative to 'css'
		 *                         directory), and optionally 'global' (whether the file should immediately be
		 *                         enqueued instead of just being registered) and 'preload_callback' (callback)
		 *                         function determining whether the file should be preloaded for the current request).
		 */
		$css_files = apply_filters( 'mavero_css_files', $css_files );

		$this->css_files = [];
		foreach ( $css_files as $handle => $data ) {
			if ( is_string( $data ) ) {
				$data = [ 'file' => $data ];
			}

			if ( empty( $data['file'] ) ) {
				continue;
			}

			$this->css_files[ $handle ] = array_merge(
				[
					'global' => false,
					'preload_callback' => null,
					'media' => 'all',
				],
				$data
			);
		}

		return $this->css_files;
	}

	/**
	 * Defers non-critical stylesheets (plugins, or handles suffixed `|defer`).
	 *
	 * Swaps media to "print" and restores the original media on load, so the file downloads
	 * without blocking render. Theme and core block stylesheets stay render-blocking: core
	 * block CSS only loads for blocks on the page (should_load_separate_core_block_assets),
	 * and deferring above-the-fold blocks like the header navigation and hero cover lays
	 * them out unstyled first, which caused most of the site's CLS.
	 *
	 * @param string $tag    Entire <link> tag.
	 * @param string $handle The link registration handle.
	 * @param string $href   The link's href attribute.
	 * @param string $media  The link's media attribute.
	 *
	 * @return string Updated link tag.
	 */
	public function defer_stylesheet( $tag, $handle, $href, $media ) {
		$should_be_deferred = str_contains( $handle, '|defer' );
		$is_critical_style  = str_contains( $tag, '/themes/' ) || str_starts_with( $handle, 'wp-block-' );

		if ( ! $should_be_deferred && ( $is_critical_style || is_admin_bar_showing() ) ) {
			return $tag;
		}

		if ( str_contains( $tag, "media='print'" ) ) {
			return $tag;
		}

		$new_tag  = '<noscript>' . str_replace( " id='", " id='fallback-", $tag ) . '</noscript>';
		$new_tag .= str_replace(
			" media='{$media}'",
			" media='print' onload='this.media=" . wp_json_encode( $media ) . "; this.onload=null;'",
			$tag
		);

		return $new_tag;
	}

	/**
	 * Enqueues block editor stylesheet.
	 *
	 * This function assumes there will be only one CSS file
	 * enqueued for the editor.
	 */
	public function add_editor_style_support() {
		add_editor_style( 'build/css/editor.css' );
	}

	/**
	 * Enqueues admin assets.
	 *
	 * @return void
	 */
	public function action_enqueue_admin_styles() {
		$css_dir = trailingslashit( xwp_theme_meta()->path( 'build', 'css' ) );
		$src     = trailingslashit( xwp_theme_meta()->url( 'build', 'css' ) ) . 'admin.css';
		$version = xwp_theme()->get_asset_version( $css_dir . 'admin.css' );

		wp_register_style(
			'mavero-admin',
			$src,
			[],
			$version
		);

		wp_enqueue_style( 'mavero-admin' );
	}

	/**
	 * Enqueues login screen assets.
	 *
	 * @return void
	 */
	public function action_enqueue_login_styles() {
		$css_dir = trailingslashit( xwp_theme_meta()->path( 'build', 'css' ) );
		$src     = trailingslashit( xwp_theme_meta()->url( 'build', 'css' ) ) . 'custom-login.css';
		$version = xwp_theme()->get_asset_version( $css_dir . 'custom-login.css' );

		wp_register_style(
			'custom-login',
			$src,
			[],
			$version
		);

		wp_enqueue_style( 'custom-login' );
	}

	/**
	 * Removes core duotone SVGs.
	 *
	 * This function disables Gutenberg's Duotone SVG inlining.
	 *
	 * @return void
	 */
	public function action_remove_duotone() {
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );
	}

	/**
	 * Removes Google Fonts stylesheets enqueued by plugins.
	 *
	 * Archivo is self-hosted via theme.json, so any Google Fonts request is a wasted
	 * third-party connection. Returning false from style_loader_src prevents the stylesheet
	 * from being printed.
	 *
	 * @param string $src The stylesheet URL.
	 *
	 * @return string|false
	 */
	public function remove_google_fonts( $src ) {
		if ( str_contains( $src, 'fonts.googleapis.com' ) ) {
			return false;
		}

		return $src;
	}
}
