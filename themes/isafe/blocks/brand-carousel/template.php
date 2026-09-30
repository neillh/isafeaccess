<?php
/**
 * Brand Carousel block template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package Mavero
 */

$selection_mode  = isset( $attributes['selectionMode'] ) ? $attributes['selectionMode'] : 'all';
$selected_brands = isset( $attributes['selectedBrands'] ) ? array_map( 'intval', (array) $attributes['selectedBrands'] ) : [];
$title           = isset( $attributes['title'] ) ? $attributes['title'] : __( 'Our Brands', 'mavero' );

// Fetch brands from the product_brand taxonomy.
$query_args = [
	'taxonomy' => 'product_brand',
	'hide_empty' => true,
];

if ( 'manual' === $selection_mode && ! empty( $selected_brands ) ) {
	$query_args['include']    = $selected_brands;
	$query_args['hide_empty'] = false;
	$query_args['orderby']    = 'include';
}

$terms = get_terms( $query_args );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}

// Build slide data.
$slides = [];
foreach ( $terms as $term ) {
	if ( ! ( $term instanceof WP_Term ) ) {
		continue;
	}

	$term_url     = get_term_link( $term, 'product_brand' );
	$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
	$image_url    = '';
	$image_alt    = esc_attr( $term->name );
	$brand_title  = __( 'Shop ', 'mavero' ) . $term->name;

	if ( $thumbnail_id ) {
		$image_src = wp_get_attachment_image_src( $thumbnail_id, 'medium' );
		if ( $image_src ) {
			$image_url = $image_src[0];
		}
	}

	$slides[] = [
		'name' => $term->name,
		'image_url' => $image_url,
		'image_alt' => $image_alt,
		'term_url' => ! is_wp_error( $term_url ) ? $term_url : '',
		'title' => $brand_title,
	];
}

if ( empty( $slides ) ) {
	return;
}

$align              = ! empty( $attributes['align'] ) ? 'align' . $attributes['align'] : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array_filter( array( 'class' => $align ) )
);
$slide_count        = count( $slides );
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $title ) : ?>
		<h2 class="wp-block-mavero-brand-carousel__title"><?php echo wp_kses_post( $title ); ?></h2>
	<?php endif; ?>

	<div class="wp-block-mavero-brand-carousel__container">
		<button class="wp-block-mavero-brand-carousel__arrow wp-block-mavero-brand-carousel__arrow--prev"
			aria-label="<?php esc_attr_e( 'Previous brands', 'mavero' ); ?>">
			<span aria-hidden="true">&#8249;</span>
		</button>

		<div class="wp-block-mavero-brand-carousel__track" role="list">
			<?php foreach ( $slides as $slide ) : ?>
				<div class="wp-block-mavero-brand-carousel__slide" role="listitem">
					<?php if ( $slide['image_url'] ) : ?>
						<a href="<?php echo esc_url( $slide['term_url'] ); ?>" title="<?php echo esc_attr( $slide['title'] ); ?>">
							<img src="<?php echo esc_url( $slide['image_url'] ); ?>"
								alt="<?php echo $slide['image_alt']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped with esc_attr() above ?>"
								loading="lazy" draggable="false" />
						</a>
					<?php else : ?>
						<span class="wp-block-mavero-brand-carousel__name">
							<a href="<?php echo esc_url( $slide['term_url'] ); ?>" title="<?php echo esc_attr( $slide['title'] ); ?>">
								<?php echo esc_html( $slide['name'] ); ?>
							</a>
						</span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<button class="wp-block-mavero-brand-carousel__arrow wp-block-mavero-brand-carousel__arrow--next"
			aria-label="<?php esc_attr_e( 'Next brands', 'mavero' ); ?>">
			<span aria-hidden="true">&#8250;</span>
		</button>
	</div>

	<?php if ( $slide_count > 1 ) : ?>
		<div class="wp-block-mavero-brand-carousel__dots" role="tablist"
			aria-label="<?php esc_attr_e( 'Brand slides', 'mavero' ); ?>"></div>
	<?php endif; ?>
</div>
