<?php
/**
 * Team LinkedIn block template.
 *
 * Renders nothing when the team member has no LinkedIn URL.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 *
 * @package Mavero
 */

use Mavero\Components\Post_Types\Team;

$post_id  = $block->context['postId'] ?? get_the_ID();
$linkedin = $post_id ? get_post_meta( $post_id, Team::META_LINKEDIN, true ) : '';

if ( ! $linkedin ) {
	return;
}

$new_tab = ! empty( $attributes['openInNewTab'] );
$label   = sprintf(
	/* translators: %s: Team member name. */
	__( '%s on LinkedIn', 'mavero' ),
	get_the_title( $post_id )
);

if ( $new_tab ) {
	/* translators: Accessibility text appended to links that open in a new tab. */
	$label .= ' ' . __( '(opens in a new tab)', 'mavero' );
}

$wrapper_attributes = get_block_wrapper_attributes();
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<a
		class="wp-block-mavero-team-linkedin__link"
		href="<?php echo esc_url( $linkedin ); ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
		<?php if ( $new_tab ) : ?>
			target="_blank" rel="noopener noreferrer"
		<?php endif; ?>
	>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
			<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.125 2.062 2.062 0 0 1 0 4.125zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
		</svg>
	</a>
</div>
