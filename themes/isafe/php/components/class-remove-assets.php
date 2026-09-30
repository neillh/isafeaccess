<?php
/**
 * Mavero\Components\Remove Assets class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Manages frontend asset dequeuing via an admin UI.
 * Assets are auto-discovered as pages are visited on the frontend,
 * then rules are configured per page type in WP Admin → Asset Manager.
 */
class Remove_Assets implements Component {

	const SEEN_OPTION  = 'mm_seen_assets';
	const RULES_OPTION = 'mm_asset_rules';

	const PAGE_TYPES = [
		'front_page' => 'Home',
		'shop'       => 'Shop',
		'product'    => 'Product',
		'post'       => 'Post',
		'page'       => 'Page',
		'category'   => 'Category',
		'cart'       => 'Cart',
		'checkout'   => 'Checkout',
		'account'    => 'My Account',
	];

	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'capture_and_dequeue' ], 999 );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_post_mm_asset_save', [ $this, 'handle_save' ] );
		add_action( 'admin_notices', [ $this, 'show_notices' ] );

		// Seed default rules on first run.
		if ( false === get_option( self::RULES_OPTION ) ) {
			update_option( self::RULES_OPTION, $this->default_rules(), false );
		}
	}

	// -------------------------------------------------------------------------
	// Frontend: capture + dequeue
	// -------------------------------------------------------------------------

	public function capture_and_dequeue() {
		if ( is_admin() ) {
			return;
		}

		global $wp_scripts, $wp_styles;

		$page_type = $this->get_page_type();

		if ( $page_type !== 'other' ) {
			$this->capture_assets(
				$wp_scripts->queue ?? [],
				$wp_styles->queue ?? [],
				$page_type
			);
		}

		$rules = get_option( self::RULES_OPTION, [] );
		$this->apply_rules( $rules, $page_type );
	}

	private function get_page_type() {
		if ( is_front_page() ) {
			return 'front_page';
		}
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return 'shop';
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'product';
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return 'checkout';
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return 'account';
		}
		if ( is_singular( 'post' ) ) {
			return 'post';
		}
		if ( is_category() ) {
			return 'category';
		}
		if ( is_page() ) {
			return 'page';
		}
		return 'other';
	}

	private function capture_assets( array $script_handles, array $style_handles, string $page_type ) {
		global $wp_scripts, $wp_styles;

		$seen    = get_option( self::SEEN_OPTION, [ 'scripts' => [], 'styles' => [] ] );
		$changed = false;

		foreach ( $script_handles as $handle ) {
			$src = $wp_scripts->registered[ $handle ]->src ?? '';
			if ( ! isset( $seen['scripts'][ $handle ] ) ) {
				$seen['scripts'][ $handle ] = [ 'src' => $src, 'pages' => [] ];
				$changed = true;
			}
			if ( ! in_array( $page_type, $seen['scripts'][ $handle ]['pages'], true ) ) {
				$seen['scripts'][ $handle ]['pages'][] = $page_type;
				$changed = true;
			}
			if ( empty( $seen['scripts'][ $handle ]['src'] ) && $src ) {
				$seen['scripts'][ $handle ]['src'] = $src;
				$changed = true;
			}
		}

		foreach ( $style_handles as $handle ) {
			$src = $wp_styles->registered[ $handle ]->src ?? '';
			if ( ! isset( $seen['styles'][ $handle ] ) ) {
				$seen['styles'][ $handle ] = [ 'src' => $src, 'pages' => [] ];
				$changed = true;
			}
			if ( ! in_array( $page_type, $seen['styles'][ $handle ]['pages'], true ) ) {
				$seen['styles'][ $handle ]['pages'][] = $page_type;
				$changed = true;
			}
			if ( empty( $seen['styles'][ $handle ]['src'] ) && $src ) {
				$seen['styles'][ $handle ]['src'] = $src;
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( self::SEEN_OPTION, $seen, false );
		}
	}

	private function apply_rules( array $rules, string $page_type ) {
		foreach ( $rules['scripts'] ?? [] as $handle => $pages ) {
			if ( in_array( 'all', $pages, true ) || in_array( $page_type, $pages, true ) ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
		foreach ( $rules['styles'] ?? [] as $handle => $pages ) {
			if ( in_array( 'all', $pages, true ) || in_array( $page_type, $pages, true ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Admin: menu + page
	// -------------------------------------------------------------------------

	public function register_admin_page() {
		add_menu_page(
			'Asset Manager',
			'Asset Manager',
			'manage_options',
			'mm-asset-manager',
			[ $this, 'render_admin_page' ],
			'dashicons-performance',
			80
		);
	}

	public function show_notices() {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_mm-asset-manager' !== $screen->id ) {
			return;
		}

		$notice = get_transient( 'mm_asset_notice_' . get_current_user_id() );
		if ( $notice ) {
			delete_transient( 'mm_asset_notice_' . get_current_user_id() );
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		check_admin_referer( 'mm_asset_save', 'mm_asset_nonce' );

		$action = sanitize_key( $_POST['mm_action'] ?? '' );

		if ( 'save_rules' === $action ) {
			$seen           = get_option( self::SEEN_OPTION, [ 'scripts' => [], 'styles' => [] ] );
			$page_type_keys = array_keys( self::PAGE_TYPES );
			$rules          = [ 'scripts' => [], 'styles' => [] ];

			foreach ( array_keys( $seen['scripts'] ) as $handle ) {
				$pages = [];
				// Check "all pages" toggle first.
				if ( ! empty( $_POST['disable_script_all'][ $handle ] ) ) {
					$pages = [ 'all' ];
				} else {
					foreach ( $page_type_keys as $pt ) {
						if ( ! empty( $_POST['disable_script'][ $handle ][ $pt ] ) ) {
							$pages[] = $pt;
						}
					}
				}
				if ( ! empty( $pages ) ) {
					$rules['scripts'][ sanitize_key( $handle ) ] = $pages;
				}
			}

			foreach ( array_keys( $seen['styles'] ) as $handle ) {
				$pages = [];
				if ( ! empty( $_POST['disable_style_all'][ $handle ] ) ) {
					$pages = [ 'all' ];
				} else {
					foreach ( $page_type_keys as $pt ) {
						if ( ! empty( $_POST['disable_style'][ $handle ][ $pt ] ) ) {
							$pages[] = $pt;
						}
					}
				}
				if ( ! empty( $pages ) ) {
					$rules['styles'][ sanitize_key( $handle ) ] = $pages;
				}
			}

			update_option( self::RULES_OPTION, $rules, false );
			$this->set_notice( 'Asset rules saved.', 'success' );
		}

		if ( 'clear_seen' === $action ) {
			delete_option( self::SEEN_OPTION );
			$this->set_notice( 'Seen assets cleared. Visit your site pages to rebuild the list.', 'info' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mm-asset-manager' ) );
		exit;
	}

	public function render_admin_page() {
		$seen       = get_option( self::SEEN_OPTION, [ 'scripts' => [], 'styles' => [] ] );
		$rules      = get_option( self::RULES_OPTION, [ 'scripts' => [], 'styles' => [] ] );
		$page_types = self::PAGE_TYPES;

		$scripts = $seen['scripts'] ?? [];
		$styles  = $seen['styles'] ?? [];
		ksort( $scripts );
		ksort( $styles );
		?>
		<div class="wrap">
			<h1>Asset Manager</h1>
			<p>
				Assets are auto-discovered as pages are visited on the frontend.
				Tick the page types you want each asset <strong>disabled</strong> on, then save.
				<strong>All</strong> disables the asset on every page.
			</p>

			<?php if ( empty( $scripts ) && empty( $styles ) ): ?>
				<div class="notice notice-info"><p>No assets discovered yet — visit your site's frontend pages (home, shop, product, etc.) while logged out or in an incognito window, then reload this page.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'mm_asset_save', 'mm_asset_nonce' ); ?>
				<input type="hidden" name="action" value="mm_asset_save">
				<input type="hidden" name="mm_action" value="save_rules">

				<?php if ( ! empty( $scripts ) ): ?>
					<h2>Scripts <span style="font-weight:normal;font-size:13px;color:#666">(<?php echo count( $scripts ); ?>)</span></h2>
					<?php $this->render_table( $scripts, $rules['scripts'] ?? [], $page_types, 'script' ); ?>
				<?php endif; ?>

				<?php if ( ! empty( $styles ) ): ?>
					<h2 style="margin-top:30px">Styles <span style="font-weight:normal;font-size:13px;color:#666">(<?php echo count( $styles ); ?>)</span></h2>
					<?php $this->render_table( $styles, $rules['styles'] ?? [], $page_types, 'style' ); ?>
				<?php endif; ?>

				<?php if ( ! empty( $scripts ) || ! empty( $styles ) ): ?>
					<?php submit_button( 'Save Rules' ); ?>
				<?php endif; ?>
			</form>

			<hr>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'mm_asset_save', 'mm_asset_nonce' ); ?>
				<input type="hidden" name="action" value="mm_asset_save">
				<input type="hidden" name="mm_action" value="clear_seen">
				<?php submit_button( 'Clear Discovered Assets', 'secondary delete', 'submit', false ); ?>
				<p class="description">Removes the discovered asset list. Rules are preserved. Assets will be re-discovered as pages are visited.</p>
			</form>
		</div>

		<style>
			#mm-asset-manager-table th, #mm-asset-manager-table td { vertical-align: middle; }
			#mm-asset-manager-table .col-handle { width: 220px; }
			#mm-asset-manager-table .col-src { font-size: 11px; color: #666; word-break: break-all; }
			#mm-asset-manager-table .col-seen { width: 130px; font-size: 11px; color: #888; }
			#mm-asset-manager-table .col-toggle { width: 68px; text-align: center; }
			#mm-asset-manager-table .col-all { width: 50px; text-align: center; }
			#mm-asset-manager-table tr.is-disabled td { background: #fff8f0; }
			#mm-asset-manager-table input[type=checkbox] { margin: 0; }
		</style>
		<?php
	}

	private function render_table( array $assets, array $rules, array $page_types, string $type ) {
		$prefix = "disable_{$type}";
		?>
		<table class="wp-list-table widefat fixed striped" id="mm-asset-manager-table">
			<thead>
				<tr>
					<th class="col-handle">Handle</th>
					<th class="col-src">Source</th>
					<th class="col-seen">Seen on</th>
					<th class="col-all">All</th>
					<?php foreach ( $page_types as $label ): ?>
						<th class="col-toggle"><?php echo esc_html( $label ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $assets as $handle => $info ):
					$rule_pages  = $rules[ $handle ] ?? [];
					$is_all      = in_array( 'all', $rule_pages, true );
					$seen_labels = array_map( fn( $p ) => $page_types[ $p ] ?? $p, $info['pages'] ?? [] );
					$row_class   = ( ! empty( $rule_pages ) ) ? 'is-disabled' : '';
				?>
				<tr class="<?php echo esc_attr( $row_class ); ?>">
					<td class="col-handle"><code><?php echo esc_html( $handle ); ?></code></td>
					<td class="col-src"><?php echo esc_html( $this->display_src( $info['src'] ?? '' ) ); ?></td>
					<td class="col-seen"><?php echo esc_html( implode( ', ', $seen_labels ) ); ?></td>
					<td class="col-all">
						<input type="checkbox"
							name="<?php echo esc_attr( "{$prefix}_all[{$handle}]" ); ?>"
							value="1"
							<?php checked( $is_all ); ?>>
					</td>
					<?php foreach ( $page_types as $pt => $label ): ?>
						<td class="col-toggle">
							<input type="checkbox"
								name="<?php echo esc_attr( "{$prefix}[{$handle}][{$pt}]" ); ?>"
								value="1"
								<?php checked( $is_all || in_array( $pt, $rule_pages, true ) ); ?>>
						</td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function display_src( string $src ): string {
		$src = str_replace( site_url(), '', $src );
		return mb_strlen( $src ) > 90 ? mb_substr( $src, 0, 90 ) . '…' : $src;
	}

	private function set_notice( string $message, string $type = 'success' ) {
		set_transient(
			'mm_asset_notice_' . get_current_user_id(),
			[ 'message' => $message, 'type' => $type ],
			60
		);
	}

	/**
	 * Migrate the previous hardcoded rules into the new format on first run.
	 */
	private function default_rules(): array {
		return [
			'scripts' => [
				'wcap_mailchimp_capture'    => [ 'front_page', 'shop', 'product', 'post', 'page' ],
				'wcpf-plugin-vendor-script' => [ 'front_page', 'product' ],
				'wcpf-plugin-script'        => [ 'front_page', 'product' ],
				'jquery-ui-datepicker'      => [ 'front_page', 'post', 'shop', 'product' ],
				'iw-no-right-click'         => [ 'all' ],
			],
			'styles'  => [
				'wcb-customizer-google-font-body-open-sans' => [ 'all' ],
				'afterpay_css'                              => [ 'front_page' ],
				'wcpf-plugin-style'                         => [ 'front_page', 'product', 'post' ],
				'twentig-blocks'                            => [ 'all' ],
				'wc-gift-cards-blocks-integration'          => [ 'front_page', 'shop', 'post' ],
				'wc-gc-css'                                 => [ 'front_page', 'shop', 'post' ],
				'wc-gc-blocks-style'                        => [ 'front_page', 'shop', 'post' ],
			],
		];
	}
}
