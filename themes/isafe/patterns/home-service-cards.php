<?php
/**
 * Title: Home — Service cards
 * Slug: isafe/home-service-cards
 * Categories: isafe, featured
 * Keywords: cards, services, columns
 * Description: Three coloured service cards (light, green, dark) that overlap the hero above.
 *
 * @package Mavero
 */

$isafe_cards = [
	[
		'style'  => 'section-light',
		'title'  => __( 'Personal Protective Equipment (PPE)', 'mavero' ),
		'items'  => [ __( 'Rope, Harnesses & Lanyards', 'mavero' ), __( 'Rescue Kits', 'mavero' ), __( 'Personal PPE', 'mavero' ), __( 'Retractable Devices', 'mavero' ) ],
		'button' => __( 'Coming Soon', 'mavero' ),
		'url'    => '',
	],
	[
		'style'  => 'section-green',
		'title'  => __( 'Equipment Installation', 'mavero' ),
		'items'  => [ __( 'Anchor Points', 'mavero' ), __( 'Ladders', 'mavero' ), __( 'Static Lines', 'mavero' ), __( 'Walkways, Stairs, & Guardrails', 'mavero' ) ],
		'button' => __( 'More Info', 'mavero' ),
		'url'    => home_url( '/height-safety-installations/' ),
	],
	[
		'style'  => 'section-dark',
		'title'  => __( 'Compliance, Certification, & Training', 'mavero' ),
		'items'  => [ __( 'Audits & Reports', 'mavero' ), __( 'Certification & Recertification', 'mavero' ), __( 'Working at Heights Training', 'mavero' ), __( 'Onsite System Induction Training', 'mavero' ) ],
		'button' => __( 'More Info', 'mavero' ),
		'url'    => home_url( '/compliance-certification-inspections/' ),
	],
];
?>
<!-- wp:group {"align":"full","className":"isafe-service-cards","style":{"spacing":{"margin":{"top":"-90px"},"padding":{"top":"0","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull isafe-service-cards" style="margin-top:-90px;padding-top:0;padding-bottom:var(--wp--preset--spacing--30)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0","left":"0"}}}} -->
	<div class="wp-block-columns">
		<?php foreach ( $isafe_cards as $isafe_card ) : ?>
		<!-- wp:column {"className":"is-style-<?php echo esc_attr( $isafe_card['style'] ); ?>","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|30"}}} -->
		<div class="wp-block-column is-style-<?php echo esc_attr( $isafe_card['style'] ); ?>" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"style":{"typography":{"textTransform":"uppercase"}}} -->
			<h2 class="wp-block-heading" style="text-transform:uppercase"><?php echo esc_html( $isafe_card['title'] ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:separator {"className":"is-style-hatched"} -->
			<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
			<!-- /wp:separator -->

			<!-- wp:list {"className":"is-style-chevrons","fontSize":"small"} -->
			<ul class="wp-block-list is-style-chevrons has-small-font-size">
				<?php foreach ( $isafe_card['items'] as $isafe_item ) : ?>
				<!-- wp:list-item -->
				<li><?php echo esc_html( $isafe_item ); ?></li>
				<!-- /wp:list-item -->
				<?php endforeach; ?>
			</ul>
			<!-- /wp:list -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button"<?php echo $isafe_card['url'] ? ' href="' . esc_url( $isafe_card['url'] ) . '"' : ''; ?>><?php echo esc_html( $isafe_card['button'] ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
