<?php
/**
 * Title: Page hero
 * Slug: isafe/section-page-hero
 * Categories: isafe
 * Keywords: hero, cover, banner, title
 * Description: Full-width photo hero with H1, hatched divider and subtitle.
 *
 * @package Mavero
 */

use Mavero\Helpers\Block_Markup as M;

echo M::page_hero( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Block_Markup.
	[
		'image_id' => 888,
		'title'    => __( 'Page title goes here', 'mavero' ),
		'subtitle' => __( 'A short supporting subtitle', 'mavero' ),
	]
);
