<?php
/**
 * Testimonial Carousel block template.
 *
 * Renders testimonials from the theme's Testimonial post type
 * (Mavero\Components\Post_Types\Testimonial) as a Group "Slider" track, so
 * js/modules/_group-slider.js supplies the arrows and looping.
 *
 * Review schema for these testimonials comes from the Mavero Schema plugin,
 * which reads the post type directly.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 *
 * @package Mavero
 */

use Mavero\Components\Post_Types\Testimonial;

$post_type = Testimonial::POST_TYPE;

if ( ! post_type_exists( $post_type ) ) {
	return;
}

$is_editor_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;

$order_args = [
	'menu_order' => [ 'orderby' => 'menu_order', 'order' => 'ASC' ],
	'date_desc'  => [ 'orderby' => 'date', 'order' => 'DESC' ],
	'date_asc'   => [ 'orderby' => 'date', 'order' => 'ASC' ],
];
$order      = $order_args[ $attributes['order'] ?? 'menu_order' ] ?? $order_args['menu_order'];

$testimonials = get_posts(
	[
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, (int) ( $attributes['count'] ?? 12 ) ),
		'no_found_rows'  => true,
	] + $order
);

if ( empty( $testimonials ) ) {
	if ( $is_editor_preview ) {
		printf(
			'<p>%s</p>',
			esc_html__( 'No testimonials found. Add some under Testimonials in the WordPress admin.', 'mavero' )
		);
	}
	return;
}

$show_rating = ! empty( $attributes['showRating'] );
$show_photo  = ! empty( $attributes['showPhoto'] );
$show_logo   = ! empty( $attributes['showLogo'] );

$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wp-block-group is-style-slider wp-block-mavero-testimonial-carousel__track">
		<?php
		foreach ( $testimonials as $testimonial ) :
			$client_name     = get_post_meta( $testimonial->ID, 'isatc_client_name', true );
			$client_business = get_post_meta( $testimonial->ID, 'isatc_client_business', true );
			$business_url    = get_post_meta( $testimonial->ID, 'isatc_business_url', true );
			$rating          = min( 5, max( 0, (int) get_post_meta( $testimonial->ID, 'isatc_rating', true ) ) );
			$photo_id        = (int) get_post_meta( $testimonial->ID, 'isatc_client_photo_id', true );
			$logo_id         = (int) get_post_meta( $testimonial->ID, 'isatc_client_logo_id', true );
			$quote           = Testimonial::get_plain_content( $testimonial );
			?>
			<figure class="wp-block-mavero-testimonial-carousel__card">
				<?php if ( $show_rating && $rating > 0 ) : ?>
					<div class="wp-block-mavero-testimonial-carousel__rating" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: rating out of 5 */ __( '%d out of 5 stars', 'mavero' ), $rating ) ); ?>">
						<span aria-hidden="true"><?php echo esc_html( str_repeat( '★', $rating ) ); ?></span><span class="is-empty" aria-hidden="true"><?php echo esc_html( str_repeat( '★', 5 - $rating ) ); ?></span>
					</div>
				<?php endif; ?>

				<blockquote class="wp-block-mavero-testimonial-carousel__quote">
					<?php echo wp_kses_post( wpautop( $quote ) ); ?>
				</blockquote>

				<figcaption class="wp-block-mavero-testimonial-carousel__meta">
					<?php if ( $show_photo && $photo_id ) : ?>
						<?php echo wp_get_attachment_image( $photo_id, 'thumbnail', false, [ 'class' => 'wp-block-mavero-testimonial-carousel__photo', 'alt' => '' ] ); ?>
					<?php endif; ?>

					<span class="wp-block-mavero-testimonial-carousel__meta-text">
						<?php if ( $client_name ) : ?>
							<cite class="wp-block-mavero-testimonial-carousel__name"><?php echo esc_html( $client_name ); ?></cite>
						<?php endif; ?>

						<?php if ( $client_business ) : ?>
							<span class="wp-block-mavero-testimonial-carousel__business">
								<?php if ( $business_url ) : ?>
									<a href="<?php echo esc_url( $business_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $client_business ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $client_business ); ?>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</span>

					<?php if ( $show_logo && $logo_id ) : ?>
						<?php echo wp_get_attachment_image( $logo_id, 'medium', false, [ 'class' => 'wp-block-mavero-testimonial-carousel__logo', 'alt' => esc_attr( $client_business ) ] ); ?>
					<?php endif; ?>
				</figcaption>
			</figure>
		<?php endforeach; ?>
	</div>
</div>
