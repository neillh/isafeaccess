<?php
/**
 * Title: Partner logos
 * Slug: isafe/section-partner-logos
 * Categories: isafe
 * Keywords: logos, partners, manufacturers, slider
 * Description: Heading, kicker and divider above a looping logo slider.
 *
 * @package Mavero
 */

use Mavero\Helpers\Block_Markup as M;

echo M::section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	M::section_heading(
		__( 'Collaborative Partnerships with Leading Manufacturers', 'mavero' ),
		__( 'We are proud to partner with some of the industry’s top manufacturers, which allows us to offer you the best products and solutions available.', 'mavero' )
	) . M::logo_slider( [ 193, 194, 195, 196, 197 ] ),
	'isafe-partner-logos'
);
