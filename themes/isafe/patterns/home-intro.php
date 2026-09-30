<?php
/**
 * Title: Home — Intro
 * Slug: isafe/home-intro
 * Categories: isafe, text
 * Keywords: intro, about, image, columns
 * Description: Two columns — lead statement, copy and button beside a hatched divider and photo.
 *
 * @package Mavero
 */

$isafe_intro_img_id  = 48;
$isafe_intro_img_url = wp_get_attachment_image_url( $isafe_intro_img_id, 'large' );
?>
<!-- wp:group {"align":"full","className":"isafe-intro","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull isafe-intro" style="padding-top:60px;padding-bottom:60px">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:paragraph {"className":"is-kicker"} -->
			<p class="is-kicker"><?php esc_html_e( 'Height Safety Specialists servicing NSW, greater Sydney region, East Coast and Canberra. iSafe Access offers installations, annual re-certification, training and maintenance in all aspects of height safety management and fall protection.', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'We offer the highest-quality, tailor-made solutions, giving you peace of mind that your teams, contractors and staff are at minimal risk when working at height.', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'As highly experienced height safety specialists, we have access to the largest suppliers and manufacturers in the industry. This allows us to ensure we remain educated, equipped and empowered to work with, advise, install and offer training, because your safety matters.', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/height-safety-services/' ) ); ?>"><?php esc_html_e( 'More Info', 'mavero' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:separator {"className":"is-style-hatched"} -->
			<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
			<!-- /wp:separator -->

			<!-- wp:image {"id":<?php echo (int) $isafe_intro_img_id; ?>,"sizeSlug":"large","linkDestination":"none"} -->
			<figure class="wp-block-image size-large"><img src="<?php echo esc_url( $isafe_intro_img_url ); ?>" alt="<?php esc_attr_e( 'Height safety technician climbing a caged access ladder', 'mavero' ); ?>" class="wp-image-<?php echo (int) $isafe_intro_img_id; ?>"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
