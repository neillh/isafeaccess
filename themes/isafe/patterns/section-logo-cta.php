<?php
/**
 * Title: Logo CTA
 * Slug: isafe/section-logo-cta
 * Categories: isafe
 * Keywords: cta, contact, logo, call to action
 * Description: Brand logo and divider beside a heading, green subheading, copy and button.
 *
 * @package Mavero
 */

use Mavero\Helpers\Block_Markup as M;

echo M::logo_cta( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	[
		'heading'    => __( 'Contact Us Today', 'mavero' ),
		'subheading' => __( 'Ready to enhance the safety of your work environment?', 'mavero' ),
		'body'       => M::paragraph( esc_html__( 'Contact iSafe Access now to discuss your height safety needs and discover how we can help protect your workforce.', 'mavero' ) ),
		'buttons'    => [ [ 'text' => __( 'Contact Us', 'mavero' ), 'url' => home_url( '/contact-us/' ) ] ],
	]
);
