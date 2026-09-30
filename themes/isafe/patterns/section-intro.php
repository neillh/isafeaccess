<?php
/**
 * Title: Intro band
 * Slug: isafe/section-intro
 * Categories: isafe
 * Keywords: intro, lead, text
 * Description: Bold lead paragraph followed by a long hatched divider.
 *
 * @package Mavero
 */

use Mavero\Helpers\Block_Markup as M;

echo M::intro( M::paragraph( '<strong>' . esc_html__( 'Lead paragraph introducing the page — what we do and who it is for.', 'mavero' ) . '</strong>' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
