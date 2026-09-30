<?php
/**
 * Title: Home — Hero
 * Slug: isafe/home-hero
 * Categories: isafe, banner
 * Keywords: hero, cover, banner
 * Description: Full-width photo hero with H1, hatched divider and subheading.
 *
 * @package Mavero
 */

$isafe_hero_id  = 577;
$isafe_hero_url = wp_get_attachment_image_url( $isafe_hero_id, 'full' );
$isafe_hero_alt = __( 'iSafe Access height safety technician working on a roof', 'mavero' );
?>
<!-- wp:cover {"url":"<?php echo esc_url( $isafe_hero_url ); ?>","id":<?php echo (int) $isafe_hero_id; ?>,"alt":<?php echo wp_json_encode( $isafe_hero_alt ); ?>,"dimRatio":30,"gradient":"hero-overlay","minHeight":80,"minHeightUnit":"vh","isDark":true,"align":"full","className":"isafe-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"calc(90px + var(--wp--preset--spacing--50))"}}},"textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull isafe-hero has-base-color has-text-color" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:calc(90px + var(--wp--preset--spacing--50));min-height:80vh"><img class="wp-block-cover__image-background wp-image-<?php echo (int) $isafe_hero_id; ?>" alt="<?php echo esc_attr( $isafe_hero_alt ); ?>" src="<?php echo esc_url( $isafe_hero_url ); ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-30 has-background-dim wp-block-cover__gradient-background has-background-gradient has-hero-overlay-gradient-background"></span><div class="wp-block-cover__inner-container">
	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","contentSize":"400px","justifyContent":"left"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":1,"textColor":"base"} -->
		<h1 class="wp-block-heading has-base-color has-text-color"><?php esc_html_e( 'Height Safety Specialists Sydney & NSW', 'mavero' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:separator {"className":"is-style-hatched","style":{"layout":{"selfStretch":"fit"}}} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
		<!-- /wp:separator -->

		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","lineHeight":"1.2"}},"fontSize":"x-large"} -->
		<p class="has-x-large-font-size" style="font-weight:800;line-height:1.2"><?php esc_html_e( 'Comprehensive Fall Prevention & Height Safety Management', 'mavero' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
