<?php
/**
 * Settings screen.
 *
 * Tabbed, but all tabs post to the same option. Each tab declares which
 * top-level sections it is submitting via hidden `_sections[]` inputs, so
 * Mavero_Settings::sanitize() can merge one tab's data over the stored
 * configuration without wiping the tabs that were not on screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mavero_Admin {

	const SLUG = 'mavero-schema';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'plugin_action_links_' . MAVERO_SCHEMA_BASENAME, array( __CLASS__, 'action_links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	public static function menu() {
		add_options_page(
			__( 'Mavero Schema', 'mavero-schema' ),
			__( 'Mavero Schema', 'mavero-schema' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::SLUG );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'mavero-schema' ) . '</a>'
		);

		return $links;
	}

	public static function assets( $hook ) {
		if ( 'settings_page_' . self::SLUG !== $hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'mavero-schema-admin',
			MAVERO_SCHEMA_URL . 'assets/admin.css',
			array(),
			MAVERO_SCHEMA_VERSION
		);

		wp_enqueue_script(
			'mavero-schema-admin',
			MAVERO_SCHEMA_URL . 'assets/admin.js',
			array( 'jquery' ),
			MAVERO_SCHEMA_VERSION,
			true
		);
	}

	/**
	 * Warn when Yoast is missing — without it this plugin has nothing to attach to.
	 */
	public static function notices() {
		if ( ! current_user_can( 'manage_options' ) || mavero_schema_yoast_active() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || false === strpos( (string) $screen->id, self::SLUG ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Mavero Schema: Yoast SEO not detected.', 'mavero-schema' ) . '</strong> '
			. esc_html__( 'This plugin adds its nodes to Yoast\'s JSON-LD graph. Settings can be saved, but nothing will be output until Yoast SEO is active.', 'mavero-schema' )
			. '</p></div>';
	}

	private static function tabs() {
		return array(
			'organization' => __( 'Organization', 'mavero-schema' ),
			'contact'      => __( 'Contact & areas', 'mavero-schema' ),
			'social'       => __( 'Social profiles', 'mavero-schema' ),
			'people'       => __( 'People', 'mavero-schema' ),
			'services'     => __( 'Services', 'mavero-schema' ),
			'pagetypes'    => __( 'Page types', 'mavero-schema' ),
			'reviews'      => __( 'Reviews', 'mavero-schema' ),
			'status'       => __( 'Status', 'mavero-schema' ),
		);
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = self::tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'organization';

		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'organization';
		}

		$s = Mavero_Settings::get();
		?>
		<div class="wrap mavero-wrap">
			<h1><?php esc_html_e( 'Mavero Schema', 'mavero-schema' ); ?></h1>
			<p class="mavero-intro">
				<?php esc_html_e( 'Everything configured here is merged into Yoast SEO\'s existing JSON-LD graph. No separate script tag is ever printed, so the site keeps exactly one business entity.', 'mavero-schema' ); ?>
			</p>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::SLUG . '&tab=' . $key ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'status' === $tab ) : ?>
				<?php self::tab_status( $s ); ?>
			<?php else : ?>
				<form method="post" action="options.php" class="mavero-form">
					<?php settings_fields( Mavero_Settings::GROUP ); ?>
					<?php
					$method = 'tab_' . $tab;
					self::$method( $s );
					?>
					<?php submit_button(); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Tabs
	 * ------------------------------------------------------------------ */

	private static function tab_organization( $s ) {
		self::sections( array( 'org' ) );
		$org   = $s['org'];
		$types = Mavero_Settings::entity_types();
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'mavero-schema' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( self::n( 'org][enabled' ) ); ?>" value="1" <?php checked( $org['enabled'], 1 ); ?>>
						<?php esc_html_e( 'Enrich the Organization node', 'mavero-schema' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mavero-type"><?php esc_html_e( 'Entity type', 'mavero-schema' ); ?></label></th>
				<td>
					<select id="mavero-type" name="<?php echo esc_attr( self::n( 'org][type' ) ); ?>">
						<?php foreach ( $types as $value => $meta ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $org['type'], $value ); ?>>
								<?php echo esc_html( $meta['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php
					$effective = Mavero_Settings::effective_type();
					if ( $effective !== $org['type'] ) :
						?>
						<p class="mavero-warn">
							<?php
							printf(
								/* translators: 1: selected type, 2: type actually emitted */
								esc_html__( 'A postal address has not been entered, so %1$s is held back and %2$s is emitted instead. Google treats address as required for LocalBusiness and its subtypes; publishing one without it weakens the entity rather than strengthening it. Add an address on the Contact & areas tab to release this.', 'mavero-schema' ),
								'<code>' . esc_html( $org['type'] ) . '</code>',
								'<code>' . esc_html( $effective ) . '</code>'
							);
							?>
						</p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'Yoast free hardcodes "Organization" and ships no LocalBusiness generator. This setting overrides that through a filter.', 'mavero-schema' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<?php
			self::text_row( 'org][legal_name', __( 'Legal name', 'mavero-schema' ), $org['legal_name'], __( 'The registered entity name, if it differs from the trading name.', 'mavero-schema' ) );
			self::text_row( 'org][alternate_name', __( 'Alternate name', 'mavero-schema' ), $org['alternate_name'] );
			self::textarea_row( 'org][description', __( 'Description', 'mavero-schema' ), $org['description'] );
			self::text_row( 'org][slogan', __( 'Slogan', 'mavero-schema' ), $org['slogan'] );
			self::text_row( 'org][telephone', __( 'Telephone', 'mavero-schema' ), $org['telephone'] );
			self::text_row( 'org][email', __( 'Email', 'mavero-schema' ), $org['email'] );
			self::text_row( 'org][founding_date', __( 'Founding date', 'mavero-schema' ), $org['founding_date'], __( 'YYYY or YYYY-MM-DD. Anything else is discarded rather than emitted as an invalid date.', 'mavero-schema' ) );
			self::text_row( 'org][identifier', __( 'Identifier (ABN / ACN)', 'mavero-schema' ), $org['identifier'] );
			self::textarea_row( 'org][knows_about', __( 'Knows about', 'mavero-schema' ), $org['knows_about'], __( 'One topic per line. Areas of demonstrable expertise — standards, techniques, equipment.', 'mavero-schema' ) );
			?>
		</table>
		<?php
	}

	private static function tab_contact( $s ) {
		self::sections( array( 'address', 'areas', 'contacts' ) );
		$a = $s['address'];
		?>
		<h2><?php esc_html_e( 'Postal address', 'mavero-schema' ); ?></h2>
		<p class="description mavero-note">
			<?php esc_html_e( 'Optional — but entering a street and suburb here is what unlocks the LocalBusiness entity subtypes on the Organization tab.', 'mavero-schema' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<?php
			self::text_row( 'address][street', __( 'Street address', 'mavero-schema' ), $a['street'] );
			self::text_row( 'address][locality', __( 'Suburb / locality', 'mavero-schema' ), $a['locality'] );
			self::text_row( 'address][region', __( 'State / region', 'mavero-schema' ), $a['region'] );
			self::text_row( 'address][postcode', __( 'Postcode', 'mavero-schema' ), $a['postcode'] );
			self::text_row( 'address][country', __( 'Country code', 'mavero-schema' ), $a['country'], __( 'Two-letter code, e.g. AU.', 'mavero-schema' ) );
			?>
		</table>

		<h2><?php esc_html_e( 'Areas served', 'mavero-schema' ); ?></h2>
		<p class="description mavero-note">
			<?php esc_html_e( 'For a business that travels to the client rather than trading from a shopfront, this carries the geographic signal an address otherwise would.', 'mavero-schema' ); ?>
		</p>
		<?php
		self::repeater(
			'areas',
			$s['areas'],
			array(
				'type' => array(
					'label'   => __( 'Type', 'mavero-schema' ),
					'type'    => 'select',
					'options' => array(
						'Country'            => 'Country',
						'State'              => 'State',
						'City'               => 'City',
						'AdministrativeArea' => 'AdministrativeArea',
					),
				),
				'name' => array(
					'label' => __( 'Name', 'mavero-schema' ),
					'type'  => 'text',
				),
			)
		);
		?>

		<h2><?php esc_html_e( 'Contact points', 'mavero-schema' ); ?></h2>
		<?php
		self::repeater(
			'contacts',
			$s['contacts'],
			array(
				'type'      => array(
					'label'       => __( 'Contact type', 'mavero-schema' ),
					'type'        => 'text',
					'placeholder' => 'customer service',
				),
				'telephone' => array(
					'label' => __( 'Telephone', 'mavero-schema' ),
					'type'  => 'text',
				),
				'email'     => array(
					'label' => __( 'Email', 'mavero-schema' ),
					'type'  => 'text',
				),
				'area'      => array(
					'label'       => __( 'Area served', 'mavero-schema' ),
					'type'        => 'text',
					'placeholder' => 'AU',
				),
				'language'  => array(
					'label'       => __( 'Language', 'mavero-schema' ),
					'type'        => 'text',
					'placeholder' => 'en',
				),
			)
		);
	}

	private static function tab_social( $s ) {
		self::sections( array( 'social' ) );
		$social = $s['social'];
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Mode', 'mavero-schema' ); ?></th>
				<td>
					<label><input type="radio" name="<?php echo esc_attr( self::n( 'social][mode' ) ); ?>" value="merge" <?php checked( $social['mode'], 'merge' ); ?>>
						<?php esc_html_e( 'Add to the profiles Yoast already has', 'mavero-schema' ); ?></label><br>
					<label><input type="radio" name="<?php echo esc_attr( self::n( 'social][mode' ) ); ?>" value="replace" <?php checked( $social['mode'], 'replace' ); ?>>
						<?php esc_html_e( 'Replace Yoast\'s list entirely', 'mavero-schema' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mavero-social"><?php esc_html_e( 'Profile URLs', 'mavero-schema' ); ?></label></th>
				<td>
					<textarea id="mavero-social" class="large-text code" rows="6" name="<?php echo esc_attr( self::n( 'social][urls' ) ); ?>"><?php echo esc_textarea( $social['urls'] ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'One URL per line. Company profiles only — a named individual\'s personal profile belongs on their entry under People, not on the business.', 'mavero-schema' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_people( $s ) {
		self::sections( array( 'people', 'people_scope', 'people_page' ) );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Output on', 'mavero-schema' ); ?></th>
				<td>
					<?php $scope = $s['people_scope']; ?>
					<label><input type="radio" name="<?php echo esc_attr( self::n( 'people_scope' ) ); ?>" value="page" <?php checked( $scope, 'page' ); ?>>
						<?php esc_html_e( 'One page only (recommended)', 'mavero-schema' ); ?></label><br>
					<label><input type="radio" name="<?php echo esc_attr( self::n( 'people_scope' ) ); ?>" value="all" <?php checked( $scope, 'all' ); ?>>
						<?php esc_html_e( 'Every page', 'mavero-schema' ); ?></label><br>
					<label><input type="radio" name="<?php echo esc_attr( self::n( 'people_scope' ) ); ?>" value="none" <?php checked( $scope, 'none' ); ?>>
						<?php esc_html_e( 'Off', 'mavero-schema' ); ?></label>
					<p class="description">
						<?php esc_html_e( 'The Organization\'s employee references are emitted only where the Person nodes themselves appear, so the graph never points at an @id that is missing from the page.', 'mavero-schema' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Page', 'mavero-schema' ); ?></th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => self::n( 'people_page' ),
							'selected'          => (int) $s['people_page'],
							'show_option_none'  => __( '— Select —', 'mavero-schema' ),
							'option_none_value' => 0,
						)
					);
					?>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Team', 'mavero-schema' ); ?></h2>
		<?php
		self::repeater(
			'people',
			$s['people'],
			array(
				'name'        => array(
					'label' => __( 'Name', 'mavero-schema' ),
					'type'  => 'text',
				),
				'job_title'   => array(
					'label' => __( 'Job title', 'mavero-schema' ),
					'type'  => 'text',
				),
				'description' => array(
					'label' => __( 'Description', 'mavero-schema' ),
					'type'  => 'textarea',
				),
				'url'         => array(
					'label' => __( 'URL', 'mavero-schema' ),
					'type'  => 'text',
				),
				'sameas'      => array(
					'label'       => __( 'Profile URLs', 'mavero-schema' ),
					'type'        => 'textarea',
					'placeholder' => "https://www.linkedin.com/in/…",
				),
				'image_id'    => array(
					'label' => __( 'Photo', 'mavero-schema' ),
					'type'  => 'media',
				),
			)
		);
	}

	private static function tab_services( $s ) {
		self::sections( array( 'services', 'hub' ) );
		$hub = $s['hub'];
		?>
		<h2><?php esc_html_e( 'Services', 'mavero-schema' ); ?></h2>
		<p class="description mavero-note">
			<?php esc_html_e( 'Each mapped page gains a Service node whose provider is the one Organization entity. Put the specifics the page already states — standards, intervals, load figures, equipment — into the description; that is what makes the node worth having.', 'mavero-schema' ); ?>
		</p>
		<?php
		self::repeater(
			'services',
			$s['services'],
			array(
				'page_id'      => array(
					'label' => __( 'Page', 'mavero-schema' ),
					'type'  => 'page',
				),
				'name'         => array(
					'label'       => __( 'Service name', 'mavero-schema' ),
					'type'        => 'text',
					'placeholder' => __( 'Defaults to the page title', 'mavero-schema' ),
				),
				'service_type' => array(
					'label' => __( 'Service type', 'mavero-schema' ),
					'type'  => 'text',
				),
				'description'  => array(
					'label' => __( 'Description', 'mavero-schema' ),
					'type'  => 'textarea',
				),
				'areas'        => array(
					'label'       => __( 'Areas served', 'mavero-schema' ),
					'type'        => 'text',
					'placeholder' => __( 'Comma separated; blank inherits', 'mavero-schema' ),
				),
				'set_about'    => array(
					'label' => __( 'Set page about', 'mavero-schema' ),
					'type'  => 'checkbox',
				),
			)
		);
		?>

		<h2><?php esc_html_e( 'Services hub page', 'mavero-schema' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'mavero-schema' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( self::n( 'hub][enabled' ) ); ?>" value="1" <?php checked( $hub['enabled'], 1 ); ?>>
						<?php esc_html_e( 'Emit an ItemList of the services above, and type the page as CollectionPage', 'mavero-schema' ); ?></label>
					<p class="description"><?php esc_html_e( 'The list is built from names and URLs rather than @id references, because those Service nodes live on their own pages — a reference to an @id absent from this page\'s graph would dangle.', 'mavero-schema' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Page', 'mavero-schema' ); ?></th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => self::n( 'hub][page_id' ),
							'selected'          => (int) $hub['page_id'],
							'show_option_none'  => __( '— Select —', 'mavero-schema' ),
							'option_none_value' => 0,
						)
					);
					?>
				</td>
			</tr>
			<?php self::text_row( 'hub][heading', __( 'List name', 'mavero-schema' ), $hub['heading'] ); ?>
		</table>
		<?php
	}

	private static function tab_pagetypes( $s ) {
		self::sections( array( 'page_types' ) );
		?>
		<h2><?php esc_html_e( 'Page types', 'mavero-schema' ); ?></h2>
		<p class="description mavero-note">
			<?php esc_html_e( 'Adds a subtype alongside WebPage. FAQPage is listed for completeness only — Google restricts FAQ rich results to authoritative government and health sites, so it will not earn a rich result here.', 'mavero-schema' ); ?>
		</p>
		<?php
		self::repeater(
			'page_types',
			$s['page_types'],
			array(
				'page_id' => array(
					'label' => __( 'Page', 'mavero-schema' ),
					'type'  => 'page',
				),
				'type'    => array(
					'label'   => __( 'Type', 'mavero-schema' ),
					'type'    => 'select',
					'options' => Mavero_Settings::page_types(),
				),
			)
		);
	}

	private static function tab_reviews( $s ) {
		self::sections( array( 'reviews' ) );
		$r     = $s['reviews'];
		$count = Mavero_Reviews::count();
		?>
		<div class="mavero-callout">
			<h3><?php esc_html_e( 'Before switching this on', 'mavero-schema' ); ?></h3>
			<p><?php esc_html_e( 'Reviews a business publishes about itself, on its own site, are not eligible for Google review rich results. They will not produce stars, and an AggregateRating built over them is a guidelines problem rather than a missed opportunity — this plugin never generates one.', 'mavero-schema' ); ?></p>
			<p><?php esc_html_e( 'The markup does still carry meaning for AI and LLM retrieval. That is a reasonable thing to want; it is just worth choosing deliberately, and keeping to the page where testimonials actually live.', 'mavero-schema' ); ?></p>
		</div>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Re-emit reviews', 'mavero-schema' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( self::n( 'reviews][takeover' ) ); ?>" value="1" <?php checked( $r['takeover'], 1 ); ?>>
						<?php
						/* translators: %d: number of published testimonials */
						printf( esc_html__( 'Add testimonial reviews to Yoast\'s graph, attached to the one Organization entity (%d published)', 'mavero-schema' ), (int) $count );
						?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Company authors', 'mavero-schema' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( self::n( 'reviews][allow_org_author' ) ); ?>" value="1" <?php checked( $r['allow_org_author'], 1 ); ?>>
						<?php esc_html_e( 'Accept a company name when no individual is credited', 'mavero-schema' ); ?></label>
					<p class="description"><?php esc_html_e( 'Testimonials credited only to a company, with no person named, are output with an Organization author — a valid author type — rather than dropped.', 'mavero-schema' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Limit', 'mavero-schema' ); ?></th>
				<td>
					<input type="number" min="0" class="small-text" name="<?php echo esc_attr( self::n( 'reviews][max' ) ); ?>" value="<?php echo esc_attr( $r['max'] ); ?>">
					<p class="description"><?php esc_html_e( '0 for all.', 'mavero-schema' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Pages', 'mavero-schema' ); ?></th>
				<td>
					<?php
					$pages    = get_pages( array( 'sort_column' => 'menu_order,post_title' ) );
					$selected = array_map( 'intval', (array) $r['pages'] );

					if ( empty( $pages ) ) {
						echo '<p class="description">' . esc_html__( 'No pages found.', 'mavero-schema' ) . '</p>';
					} else {
						echo '<div class="mavero-checklist">';
						foreach ( $pages as $page ) {
							printf(
								'<label><input type="checkbox" name="%s[]" value="%d" %s> %s</label>',
								esc_attr( self::n( 'reviews][pages' ) ),
								(int) $page->ID,
								checked( in_array( (int) $page->ID, $selected, true ), true, false ),
								esc_html( $page->post_title )
							);
						}
						echo '</div>';
					}
					?>
					<p class="description"><?php esc_html_e( 'Keep this to the page where testimonials are actually shown. A thank-you or checkout page carrying review markup is noise.', 'mavero-schema' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_status( $s ) {
		$org_id    = mavero_schema_org_id();
		$effective = Mavero_Settings::effective_type();
		$rows      = array(
			__( 'Yoast SEO', 'mavero-schema' )         => mavero_schema_yoast_active()
				? sprintf( 'Active — %s', defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : '?' )
				: __( 'Not detected', 'mavero-schema' ),
			__( 'Business @id', 'mavero-schema' )      => $org_id,
			__( 'Entity type emitted', 'mavero-schema' ) => $effective,
			__( 'Postal address', 'mavero-schema' )    => Mavero_Settings::has_address()
				? __( 'Configured — LocalBusiness subtypes available', 'mavero-schema' )
				: __( 'Not set — subtypes held back', 'mavero-schema' ),
			__( 'Areas served', 'mavero-schema' )      => count( (array) $s['areas'] ),
			__( 'Contact points', 'mavero-schema' )    => count( (array) $s['contacts'] ),
			__( 'People', 'mavero-schema' )            => count( (array) $s['people'] ),
			__( 'Services', 'mavero-schema' )          => count( (array) $s['services'] ),
			__( 'Testimonials', 'mavero-schema' )      => post_type_exists( Mavero_Reviews::POST_TYPE )
				/* translators: %d: number of published testimonials */
				? sprintf( __( '%d published', 'mavero-schema' ), Mavero_Reviews::count() )
				: __( 'Post type not registered — is the iSafe theme active?', 'mavero-schema' ),
		);
		?>
		<h2><?php esc_html_e( 'Status', 'mavero-schema' ); ?></h2>
		<table class="widefat striped mavero-status">
			<tbody>
			<?php foreach ( $rows as $label => $value ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td><code><?php echo esc_html( (string) $value ); ?></code></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description" style="margin-top:12px">
			<?php esc_html_e( 'To verify the output, view the source of a front-end page and read the single JSON-LD block Yoast prints in the head, or paste the URL into Google\'s Rich Results Test.', 'mavero-schema' ); ?>
		</p>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Field helpers
	 * ------------------------------------------------------------------ */

	/**
	 * Full input name for a settings path.
	 *
	 * @param string $path e.g. "org][telephone"
	 * @return string
	 */
	private static function n( $path ) {
		return Mavero_Settings::OPTION . '[' . $path . ']';
	}

	/**
	 * Declare which top-level sections this form submits.
	 *
	 * @param string[] $keys
	 */
	private static function sections( array $keys ) {
		foreach ( $keys as $key ) {
			printf(
				'<input type="hidden" name="%s[_sections][]" value="%s">',
				esc_attr( Mavero_Settings::OPTION ),
				esc_attr( $key )
			);
		}
	}

	private static function text_row( $path, $label, $value, $desc = '' ) {
		$id = 'mavero-' . sanitize_key( str_replace( array( '][', '[', ']' ), '-', $path ) );
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="text" id="<?php echo esc_attr( $id ); ?>" class="regular-text"
					name="<?php echo esc_attr( self::n( $path ) ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php if ( $desc ) : ?>
					<p class="description"><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private static function textarea_row( $path, $label, $value, $desc = '' ) {
		$id = 'mavero-' . sanitize_key( str_replace( array( '][', '[', ']' ), '-', $path ) );
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<textarea id="<?php echo esc_attr( $id ); ?>" class="large-text" rows="4"
					name="<?php echo esc_attr( self::n( $path ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php if ( $desc ) : ?>
					<p class="description"><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a repeatable row set.
	 *
	 * Rows are indexed numerically. A hidden template row carries the literal
	 * index token `__i__`, which the JS swaps for a real index when adding a row;
	 * Mavero_Settings::rows() drops that key defensively in case it is ever posted.
	 *
	 * @param string $section Top-level settings key.
	 * @param array  $rows    Stored rows.
	 * @param array  $fields  Field definitions.
	 */
	private static function repeater( $section, $rows, array $fields ) {
		$rows = is_array( $rows ) ? array_values( $rows ) : array();
		?>
		<div class="mavero-repeater" data-section="<?php echo esc_attr( $section ); ?>">
			<div class="mavero-rows">
				<?php
				foreach ( $rows as $i => $row ) {
					self::repeater_row( $section, $fields, $row, (string) $i );
				}
				?>
			</div>

			<script type="text/html" class="mavero-tpl">
				<?php self::repeater_row( $section, $fields, array(), '__i__' ); ?>
			</script>

			<p>
				<button type="button" class="button mavero-add" data-next="<?php echo esc_attr( count( $rows ) ); ?>">
					<?php esc_html_e( 'Add row', 'mavero-schema' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	private static function repeater_row( $section, array $fields, $row, $index ) {
		?>
		<div class="mavero-row">
			<div class="mavero-row-fields">
				<?php foreach ( $fields as $key => $field ) : ?>
					<?php
					$name  = Mavero_Settings::OPTION . '[' . $section . '][' . $index . '][' . $key . ']';
					$value = isset( $row[ $key ] ) ? $row[ $key ] : '';
					$ph    = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
					?>
					<label class="mavero-field mavero-field-<?php echo esc_attr( $field['type'] ); ?>">
						<span class="mavero-field-label"><?php echo esc_html( $field['label'] ); ?></span>
						<?php if ( 'textarea' === $field['type'] ) : ?>
							<textarea rows="3" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>"><?php echo esc_textarea( $value ); ?></textarea>

						<?php elseif ( 'select' === $field['type'] ) : ?>
							<select name="<?php echo esc_attr( $name ); ?>">
								<?php foreach ( $field['options'] as $ov => $ol ) : ?>
									<option value="<?php echo esc_attr( $ov ); ?>" <?php selected( $value, $ov ); ?>><?php echo esc_html( $ol ); ?></option>
								<?php endforeach; ?>
							</select>

						<?php elseif ( 'page' === $field['type'] ) : ?>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => $name,
									'selected'          => (int) $value,
									'show_option_none'  => __( '— Select —', 'mavero-schema' ),
									'option_none_value' => 0,
								)
							);
							?>

						<?php elseif ( 'checkbox' === $field['type'] ) : ?>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0">
							<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $value, 1 ); ?>>

						<?php elseif ( 'media' === $field['type'] ) : ?>
							<span class="mavero-media">
								<input type="hidden" class="mavero-media-id" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (int) $value ); ?>">
								<span class="mavero-media-preview">
									<?php
									if ( $value ) {
										echo wp_get_attachment_image( (int) $value, array( 60, 60 ) );
									}
									?>
								</span>
								<button type="button" class="button-link mavero-media-pick"><?php esc_html_e( 'Choose', 'mavero-schema' ); ?></button>
								<button type="button" class="button-link mavero-media-clear"><?php esc_html_e( 'Clear', 'mavero-schema' ); ?></button>
							</span>

						<?php else : ?>
							<input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>">
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button-link mavero-remove" aria-label="<?php esc_attr_e( 'Remove row', 'mavero-schema' ); ?>">&times;</button>
		</div>
		<?php
	}
}
