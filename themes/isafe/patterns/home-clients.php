<?php
/**
 * Title: Home — Clients who trust us
 * Slug: isafe/home-clients
 * Categories: isafe, gallery
 * Keywords: logos, clients, carousel, slider
 * Description: Centred heading, looping client logo slider and a link to testimonials.
 *
 * @package Mavero
 */

// Client logos, in the order the Elementor image carousel showed them.
$isafe_logo_ids = [ 929, 925, 927, 917, 923, 922, 924, 918, 920, 921, 926, 928, 919 ];
?>
<!-- wp:group {"align":"full","className":"isafe-clients","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained","wideSize":"1220px"}} -->
<div class="wp-block-group alignfull isafe-clients" style="padding-top:60px;padding-bottom:60px">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Clients Who Trust Us', 'mavero' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"is-style-slider is-logo-slider","layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group is-style-slider is-logo-slider">
		<?php
		foreach ( $isafe_logo_ids as $isafe_logo_id ) :
			$isafe_logo_url = wp_get_attachment_image_url( $isafe_logo_id, 'full' );
			if ( ! $isafe_logo_url ) {
				continue;
			}
			$isafe_logo_alt = get_the_title( $isafe_logo_id );
			?>
		<!-- wp:image {"id":<?php echo (int) $isafe_logo_id; ?>,"sizeSlug":"full","linkDestination":"none"} -->
		<figure class="wp-block-image size-full"><img src="<?php echo esc_url( $isafe_logo_url ); ?>" alt="<?php echo esc_attr( str_replace( '-', ' ', $isafe_logo_alt ) ); ?>" class="wp-image-<?php echo (int) $isafe_logo_id; ?>"/></figure>
		<!-- /wp:image -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/testimonials/' ) ); ?>"><?php esc_html_e( 'Read Client Testimonials', 'mavero' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
