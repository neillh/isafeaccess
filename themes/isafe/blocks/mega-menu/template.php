<?php
/**
 * Mega Menu block template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content — inner blocks, already rendered.
 * @var WP_Block $block      Block instance.
 *
 * @package Mavero
 */

$label = isset( $attributes['label'] ) ? trim( wp_strip_all_tags( $attributes['label'] ) ) : '';

// Nothing to hang the dropdown off — don't render an empty nav item.
if ( '' === $label ) {
	return;
}

$panel_width = ( isset( $attributes['panelWidth'] ) && 'wide' === $attributes['panelWidth'] ) ? 'wide' : 'full';
$panel_id    = wp_unique_id( 'mavero-mega-menu-panel-' );

// When a link is set the label becomes an anchor and the arrow becomes a
// separate disclosure button, so the parent stays navigable on every device.
$url         = isset( $attributes['url'] ) ? trim( $attributes['url'] ) : '';
$is_linked   = '' !== $url;
$new_tab     = ! empty( $attributes['opensInNewTab'] );
$rel         = isset( $attributes['rel'] ) ? trim( $attributes['rel'] ) : '';
$rel_parts   = array_filter( preg_split( '/\s+/', $rel ) );
if ( $new_tab ) {
	$rel_parts = array_merge( $rel_parts, [ 'noreferrer', 'noopener' ] );
}
$rel_parts = array_unique( $rel_parts );

/* translators: %s: Mega menu label. */
$toggle_label = sprintf( __( 'Open %s submenu', 'mavero' ), $label );

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class'                  => sprintf(
			'wp-block-mavero-mega-menu--%s%s',
			$panel_width,
			$is_linked ? ' is-linked' : ''
		),
		'data-wp-interactive'    => 'mavero/mega-menu',
		'data-wp-class--is-open' => 'context.isOpen',
		'data-wp-on--mouseenter' => 'actions.handleMouseEnter',
		'data-wp-on--mouseleave' => 'actions.handleMouseLeave',
		'data-wp-on--focusout'   => 'actions.handleFocusout',
		'data-wp-on--keydown'    => 'actions.handleKeydown',
	]
);

$icon = '<span class="wp-block-mavero-mega-menu__icon" aria-hidden="true">'
	. '<svg viewBox="0 0 12 8" width="12" height="8" focusable="false" aria-hidden="true">'
	. '<path d="M1.5 1.5 6 6l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />'
	. '</svg></span>';
?>
<li
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>
	<?php echo wp_interactivity_data_wp_context( [ 'isOpen' => false ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the Interactivity API. ?>
>
	<span class="wp-block-mavero-mega-menu__trigger">
		<?php if ( $is_linked ) : ?>
			<a
				class="wp-block-mavero-mega-menu__link"
				href="<?php echo esc_url( $url ); ?>"
				<?php echo $new_tab ? ' target="_blank"' : ''; ?>
				<?php echo $rel_parts ? ' rel="' . esc_attr( implode( ' ', $rel_parts ) ) . '"' : ''; ?>
			>
				<span class="wp-block-mavero-mega-menu__label"><?php echo esc_html( $label ); ?></span>
			</a>
			<button
				type="button"
				class="wp-block-mavero-mega-menu__toggle wp-block-mavero-mega-menu__toggle--icon"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
				aria-label="<?php echo esc_attr( $toggle_label ); ?>"
				data-wp-bind--aria-expanded="context.isOpen"
				data-wp-on--click="actions.toggle"
			>
				<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?>
			</button>
		<?php else : ?>
			<button
				type="button"
				class="wp-block-mavero-mega-menu__toggle"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
				data-wp-bind--aria-expanded="context.isOpen"
				data-wp-on--click="actions.toggle"
			>
				<span class="wp-block-mavero-mega-menu__label"><?php echo esc_html( $label ); ?></span>
				<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?>
			</button>
		<?php endif; ?>
	</span>

	<div
		id="<?php echo esc_attr( $panel_id ); ?>"
		class="wp-block-mavero-mega-menu__panel"
	>
		<div class="wp-block-mavero-mega-menu__panel-inner">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered inner blocks. ?>
		</div>
	</div>
</li>
