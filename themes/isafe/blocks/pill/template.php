<?php
/**
 * Pill block template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content (inner blocks).
 * @var WP_Block $block      Block instance.
 *
 * @package Mavero
 */

$wrapper_attributes = get_block_wrapper_attributes();
$text               = ! empty( $attributes['text'] ) ? wp_kses_post( $attributes['text'] ) : '';
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php if ( $text ) : ?>
		<span class="wp-block-mavero-pill__text"><?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, already sanitized above ?></span>
	<?php endif; ?>
</div>
