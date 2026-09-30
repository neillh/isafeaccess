<?php
/**
 * Post Type Testimonial.
 *
 * Moved into the theme from the ISA Testimonial Carousel plugin. The post type,
 * taxonomy and meta keys keep the plugin's `isatc_` names so existing
 * testimonials carry straight over, and so the Mavero Schema plugin (which
 * reads `isatc_testimonial` directly for Review schema) keeps working.
 *
 * Rendered by the `mavero/testimonial-carousel` block.
 *
 * @package Mavero
 */

namespace Mavero\Components\Post_Types;

use Mavero\Components\Component;

/**
 * Testimonial Post Type.
 */
class Testimonial implements Component {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'isatc_testimonial';

	/**
	 * Category taxonomy key.
	 *
	 * @var string
	 */
	const TAXONOMY = 'isatc_category';

	/**
	 * Meta box nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'mavero_save_testimonial_details';

	/**
	 * Meta box nonce field name.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'mavero_testimonial_details_nonce';

	/**
	 * Text meta fields: key => label.
	 *
	 * @var array
	 */
	const TEXT_FIELDS = [
		'isatc_client_name'     => 'Client Name',
		'isatc_client_business' => 'Client Business Name',
		'isatc_business_url'    => 'Client Business URL',
	];

	/**
	 * Image (attachment ID) meta fields: key => label.
	 *
	 * @var array
	 */
	const IMAGE_FIELDS = [
		'isatc_client_photo_id' => 'Client Photo',
		'isatc_client_logo_id'  => 'Business Logo',
	];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function init(): void {
		// The plugin registers the same post type; leave it in charge until
		// it's deactivated rather than doubling up the meta boxes.
		if ( class_exists( 'ISATC_CPT' ) ) {
			return;
		}

		if ( ! function_exists( 'register_extended_post_type' ) ) {
			add_action( 'admin_notices', [ $this, 'missing_dependency_notice' ] );
			return;
		}

		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'add_meta_box' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta_box' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_head', [ $this, 'column_styles' ] );
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$admin_cols    = [
			'client_photo' => [
				'title'    => __( 'Photo', 'mavero' ),
				'function' => [ $this, 'render_photo_column' ],
			],
			'title',
			'client'       => [
				'title'    => __( 'Client', 'mavero' ),
				'meta_key' => 'isatc_client_name', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			],
			'business'     => [
				'title'    => __( 'Business', 'mavero' ),
				'meta_key' => 'isatc_client_business', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			],
			'rating'       => [
				'title'    => __( 'Rating', 'mavero' ),
				'function' => [ $this, 'render_rating_column' ],
			],
			'category'     => [
				'taxonomy' => self::TAXONOMY,
			],
			'menu_order'   => [
				'title'      => __( 'Order', 'mavero' ),
				'post_field' => 'menu_order',
				'default'    => 'ASC',
			],
		];
		$admin_cols    = apply_filters( 'mavero_admin_column_testimonial', $admin_cols );
		$admin_filters = apply_filters(
			'mavero_admin_filter_testimonial',
			[
				'category' => [
					'taxonomy' => self::TAXONOMY,
				],
			]
		);

		register_extended_post_type(
			self::POST_TYPE,
			[
				'admin_filters'       => $admin_filters,
				'admin_cols'          => $admin_cols,
				'public'              => false,
				'show_ui'             => true,
				'show_in_rest'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'query_var'           => false,
				'rewrite'             => false,
				'menu_icon'           => 'dashicons-testimonial',
				'menu_position'       => 25,
				'enter_title_here'    => __( 'Testimonial title (admin only)', 'mavero' ),
				'supports'            => [ 'title', 'editor', 'page-attributes' ],
				// The testimonial is a short quote, not page content: the classic
				// editor keeps block-comment markup out of post_content.
				'block_editor'        => false,
			],
			[
				'singular' => __( 'Testimonial', 'mavero' ),
				'plural'   => __( 'Testimonials', 'mavero' ),
				'slug'     => 'testimonial',
			],
		);
	}

	/**
	 * Register the category taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		register_extended_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			[
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'hierarchical' => true,
				'query_var'    => false,
				'rewrite'      => false,
				'labels'       => [
					'menu_name' => __( 'Categories', 'mavero' ),
				],
			],
			[
				'singular' => __( 'Testimonial Category', 'mavero' ),
				'plural'   => __( 'Testimonial Categories', 'mavero' ),
				'slug'     => 'testimonial-category',
			],
		);
	}

	/**
	 * Register post meta, exposed to REST.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$fields = [
			'isatc_client_name'     => [ 'string', 'sanitize_text_field' ],
			'isatc_client_business' => [ 'string', 'sanitize_text_field' ],
			'isatc_business_url'    => [ 'string', 'esc_url_raw' ],
			'isatc_rating'          => [ 'integer', [ __CLASS__, 'sanitize_rating' ] ],
			'isatc_client_photo_id' => [ 'integer', 'absint' ],
			'isatc_client_logo_id'  => [ 'integer', 'absint' ],
		];

		foreach ( $fields as $key => [ $type, $sanitize ] ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				[
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => static fn( $allowed, $meta_key, $post_id ) => current_user_can( 'edit_post', $post_id ),
				]
			);
		}
	}

	/**
	 * Clamp a rating to 0–5 (0 = no rating given).
	 *
	 * @param mixed $value Raw rating.
	 *
	 * @return int
	 */
	public static function sanitize_rating( $value ): int {
		return min( 5, max( 0, absint( $value ) ) );
	}

	/**
	 * Testimonial text with any Gutenberg block-comment delimiters removed,
	 * for testimonials authored before the block editor was disabled.
	 *
	 * @param \WP_Post $post Testimonial post.
	 *
	 * @return string
	 */
	public static function get_plain_content( \WP_Post $post ): string {
		return (string) preg_replace( '/<!--\s*\/?wp:\S+.*?-->/s', '', $post->post_content );
	}

	/**
	 * Add the Testimonial Details meta box.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'mavero_testimonial_details',
			__( 'Testimonial Details', 'mavero' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the Testimonial Details meta box.
	 *
	 * @param \WP_Post $post Current post.
	 *
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$rating = (int) get_post_meta( $post->ID, 'isatc_rating', true );
		?>
		<p class="description"><?php esc_html_e( 'The main editor above is used for the testimonial text itself.', 'mavero' ); ?></p>
		<table class="form-table">
			<tbody>
				<?php foreach ( self::TEXT_FIELDS as $key => $label ) : ?>
					<tr>
						<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<input
								type="<?php echo 'isatc_business_url' === $key ? 'url' : 'text'; ?>"
								id="<?php echo esc_attr( $key ); ?>"
								name="<?php echo esc_attr( $key ); ?>"
								class="regular-text"
								value="<?php echo esc_attr( get_post_meta( $post->ID, $key, true ) ); ?>"
							/>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th><label for="isatc_rating"><?php esc_html_e( 'Rating', 'mavero' ); ?></label></th>
					<td>
						<select id="isatc_rating" name="isatc_rating">
							<option value="0" <?php selected( $rating, 0 ); ?>><?php esc_html_e( 'No rating', 'mavero' ); ?></option>
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $rating, $i ); ?>>
									<?php echo esc_html( str_repeat( '★', $i ) . str_repeat( '☆', 5 - $i ) . " ({$i}/5)" ); ?>
								</option>
							<?php endfor; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Leave as "No rating" if the client did not give one — a rating is never invented for structured data.', 'mavero' ); ?></p>
					</td>
				</tr>
				<?php foreach ( self::IMAGE_FIELDS as $key => $label ) : ?>
					<?php
					$image_id  = (int) get_post_meta( $post->ID, $key, true );
					$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
					?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td>
							<div class="mavero-image-field">
								<input type="hidden" name="<?php echo esc_attr( $key ); ?>" class="mavero-image-field__input" value="<?php echo esc_attr( $image_id ?: '' ); ?>" />
								<img class="mavero-image-field__preview" src="<?php echo esc_url( $image_url ); ?>" alt="" <?php echo $image_url ? '' : 'hidden'; ?> />
								<button type="button" class="button mavero-image-field__select"><?php esc_html_e( 'Select Image', 'mavero' ); ?></button>
								<button type="button" class="button mavero-image-field__remove" <?php echo $image_url ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'mavero' ); ?></button>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save the Testimonial Details meta box.
	 *
	 * Meta is registered with sanitize callbacks, so update_post_meta()
	 * sanitises each value on the way in.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function save_meta_box( int $post_id ): void {
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$keys = array_merge( array_keys( self::TEXT_FIELDS ), [ 'isatc_rating' ], array_keys( self::IMAGE_FIELDS ) );

		foreach ( $keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised by the registered meta sanitize_callback.
			}
		}
	}

	/**
	 * Media picker for the image fields on the testimonial edit screen.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_add_inline_script(
			'media-editor',
			<<<'JS'
			document.addEventListener( 'click', ( event ) => {
				const button = event.target.closest( '.mavero-image-field__select, .mavero-image-field__remove' );
				if ( ! button ) {
					return;
				}
				event.preventDefault();

				const field = button.closest( '.mavero-image-field' );
				const input = field.querySelector( '.mavero-image-field__input' );
				const preview = field.querySelector( '.mavero-image-field__preview' );
				const remove = field.querySelector( '.mavero-image-field__remove' );

				if ( button === remove ) {
					input.value = '';
					preview.hidden = remove.hidden = true;
					return;
				}

				const frame = wp.media( { title: button.textContent, multiple: false, library: { type: 'image' } } );
				frame.on( 'select', () => {
					const image = frame.state().get( 'selection' ).first().toJSON();
					input.value = image.id;
					preview.src = image.sizes?.thumbnail?.url || image.url;
					preview.hidden = remove.hidden = false;
				} );
				frame.open();
			} );
			JS
		);
	}

	/**
	 * Render the client photo or a grey placeholder.
	 *
	 * @param \WP_Post $post Row post.
	 *
	 * @return void
	 */
	public function render_photo_column( \WP_Post $post ) {
		$photo_id = (int) get_post_meta( $post->ID, 'isatc_client_photo_id', true );
		$photo    = $photo_id ? wp_get_attachment_image( $photo_id, [ 60, 60 ] ) : '';

		echo $photo ?: '<div style="width:60px;height:60px;background:#ccc;"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the star rating column.
	 *
	 * @param \WP_Post $post Row post.
	 *
	 * @return void
	 */
	public function render_rating_column( \WP_Post $post ) {
		$rating = (int) get_post_meta( $post->ID, 'isatc_rating', true );

		echo $rating ? esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ) : '—';
	}

	/**
	 * Add column and meta box styles.
	 *
	 * @return void
	 */
	public function column_styles() {
		$screen = get_current_screen();

		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		echo '<style>
			.column-client_photo { width: 70px; text-align: center; }
			.column-client_photo img { max-width: 60px; height: auto; }
			.widefat .column-rating { width: 100px; white-space: nowrap; color: #f0ad00; }
			.mavero-image-field__preview { display: block; max-width: 120px; height: auto; margin-bottom: 8px; padding: 4px; border: 1px solid #dcdcde; border-radius: 4px; background: #fff; }
			.mavero-image-field__preview[hidden], .mavero-image-field .button[hidden] { display: none; }
		</style>';
	}

	/**
	 * Warn when Composer dependencies haven't been installed.
	 *
	 * @return void
	 */
	public function missing_dependency_notice() {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Testimonials are unavailable: run `composer install --no-dev` in the theme directory to install johnbillion/extended-cpts.', 'mavero' )
		);
	}
}
