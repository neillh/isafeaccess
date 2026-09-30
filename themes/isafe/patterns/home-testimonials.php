<?php
/**
 * Title: Home — Certified client testimonials
 * Slug: isafe/home-testimonials
 * Categories: isafe, testimonials
 * Keywords: testimonials, reviews, carousel
 * Description: Heading, kicker and hatched divider above the testimonial carousel.
 *
 * @package Mavero
 */

?>
<!-- wp:group {"align":"full","className":"isafe-testimonials","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull isafe-testimonials" style="padding-top:60px;padding-bottom:60px">
	<!-- wp:heading {"style":{"typography":{"textTransform":"uppercase"}}} -->
	<h2 class="wp-block-heading" style="text-transform:uppercase"><?php esc_html_e( 'Certified Client Testimonials', 'mavero' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"is-kicker"} -->
	<p class="is-kicker"><?php esc_html_e( 'What we say about our service is one thing, but what our clients say is what really counts!', 'mavero' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:separator {"className":"is-style-hatched"} -->
	<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
	<!-- /wp:separator -->

	<!-- wp:mavero/testimonial-carousel {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} /-->
</div>
<!-- /wp:group -->
