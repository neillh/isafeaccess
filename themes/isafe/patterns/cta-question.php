<?php
/**
 * Title: Have a question? (form CTA)
 * Slug: isafe/cta-question
 * Categories: isafe, call-to-action
 * Keywords: contact, form, cta, question
 * Description: Green section with heading and copy on the left and a column for a WPForms form on the right.
 *
 * @package Mavero
 */

$isafe_city_url = wp_get_attachment_image_url( 479, 'full' );

// The "Contact Us" WPForms form, styled like the Elementor "Over Green" theme.
$isafe_form_block = \Mavero\Helpers\Block_Markup::wpforms_form( 'on-green' );
?>
<!-- wp:group {"align":"full","className":"isafe-cta-question is-style-section-green","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}<?php echo $isafe_city_url ? ',"background":{"backgroundImage":{"url":"' . esc_url( $isafe_city_url ) . '","id":479,"source":"file","title":"City-Scene-WhiteTransparent-800px"},"backgroundPosition":"50% 0%","backgroundSize":"cover","backgroundRepeat":"no-repeat"}' : ''; ?>},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull isafe-cta-question is-style-section-green" style="padding-top:60px;padding-bottom:60px">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"40%","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:heading {"level":2,"style":{"typography":{"textTransform":"uppercase"}},"fontSize":"xx-large"} -->
			<h2 class="wp-block-heading has-xx-large-font-size" style="text-transform:uppercase"><?php esc_html_e( 'Have a question?', 'mavero' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:separator {"className":"is-style-hatched"} -->
			<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
			<!-- /wp:separator -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Reach out to our team with any questions and we’ll respond to you as soon as we can.', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"60%","className":"isafe-cta-question__form"} -->
		<div class="wp-block-column isafe-cta-question__form" style="flex-basis:60%">
			<?php if ( $isafe_form_block ) : ?>
				<?php echo $isafe_form_block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- serialized block comment. ?>
			<?php else : ?>
			<!-- wp:paragraph {"className":"isafe-form-placeholder"} -->
			<p class="isafe-form-placeholder"><?php esc_html_e( 'WPForms form goes here — replace this paragraph with the WPForms block.', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->
			<?php endif; ?>
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
