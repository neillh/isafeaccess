<?php
/**
 * Title: Text and image — image left
 * Slug: isafe/section-split-left
 * Categories: isafe
 * Keywords: split, media, text, image, columns
 * Description: Section heading, green subheadings and copy beside a hatched divider and photo.
 *
 * @package Mavero
 */

use Mavero\Helpers\Block_Markup as M;

$isafe_body  = M::heading( esc_html__( 'Subheading:', 'mavero' ), 3 );
$isafe_body .= M::paragraph( esc_html__( 'Supporting copy for this point. Keep it to a few sentences.', 'mavero' ) );
$isafe_body .= M::heading( esc_html__( 'Another subheading:', 'mavero' ), 3 );
$isafe_body .= M::paragraph( esc_html__( 'Supporting copy for this point. Keep it to a few sentences.', 'mavero' ) );

echo M::split( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	[
		'heading'    => __( 'Section heading', 'mavero' ),
		'body'       => $isafe_body,
		'buttons'    => [ [ 'text' => __( 'More Info', 'mavero' ), 'url' => home_url( '/contact-us/' ) ] ],
		'image_id'   => 154,
		'image_side' => 'left',
		'style'      => 'section-light',
	]
);
