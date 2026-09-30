<?php
/**
 * Title: Home — Our height safety services
 * Slug: isafe/home-services-grid
 * Categories: isafe, services
 * Keywords: services, grid, columns
 * Description: Light grey section with a heading, kicker and a 2×2 grid of services.
 *
 * @package Mavero
 */

$isafe_services = [
	[
		'title' => __( 'Installations', 'mavero' ),
		'copy'  => [
			__( 'We specialise in the supply and installation of safe access, fall prevention and fall protection systems.', 'mavero' ),
			__( 'Every roof, structure and building has different requirements, and each comes with varying levels of risk. Our dedicated team has experience developing a variety of compliant height safety systems that allow easy access.', 'mavero' ),
		],
		'url'   => home_url( '/height-safety-installations/' ),
	],
	[
		'title' => __( 'Training', 'mavero' ),
		'copy'  => [
			__( 'Independent Safe Access offers on-site system induction training and accredited working at height training which gives clients an induction and introduction to how to use every system safely.', 'mavero' ),
			__( 'Our height safety specialists ensure you are set up for success and know how to use the system correctly, confidently and competently.', 'mavero' ),
		],
		'url'   => home_url( '/training-compliance/' ),
	],
	[
		'title' => __( 'Annual System Compliance / Recertification Inspections; Height Safety Audits & Registers', 'mavero' ),
		'copy'  => [
			__( 'Independent Safe Access offers a variety of services from height safety compliance, recertification, audits and asset registers.', 'mavero' ),
			__( 'We ensure that you remain compliant, meet Australian standards, and above all that your teams, contractors and outsourced specialists are able to safely carry out their work with reduced risk.', 'mavero' ),
		],
		'url'   => home_url( '/compliance-certification-inspections/' ),
	],
	[
		'title' => __( 'Personal Protective Equipment Sales & Inspections', 'mavero' ),
		'copy'  => [
			__( 'We offer tailored options to suit your PPE requirements. Everything from standard fall arrest harness equipment to the highest quality rope access equipment including Self Retracting Lifeline (SRL) carabiners and a wide range of hardware.', 'mavero' ),
			__( "In addition, Independent Safe Access' height safety specialists also offer PPE inspections, which are required by law, every 6 months.", 'mavero' ),
		],
		'url'   => home_url( '/shop/' ),
	],
];
?>
<!-- wp:group {"align":"full","className":"isafe-services-grid is-style-section-light","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull isafe-services-grid is-style-section-light" style="padding-top:60px;padding-bottom:60px">
	<!-- wp:heading {"style":{"typography":{"textTransform":"uppercase"}}} -->
	<h2 class="wp-block-heading" style="text-transform:uppercase"><?php esc_html_e( 'Our Height Safety Services', 'mavero' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"is-kicker"} -->
	<p class="is-kicker"><?php esc_html_e( 'We offer a portfolio of completely independent services', 'mavero' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:separator {"className":"is-style-hatched","style":{"dimensions":{}}} -->
	<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
	<!-- /wp:separator -->

	<?php foreach ( array_chunk( $isafe_services, 2 ) as $isafe_row ) : ?>
	<!-- wp:columns {"style":{"spacing":{"padding":{"top":"30px","bottom":"30px"},"blockGap":{"left":"80px"}}}} -->
	<div class="wp-block-columns" style="padding-top:30px;padding-bottom:30px">
		<?php foreach ( $isafe_row as $isafe_service ) : ?>
		<!-- wp:column {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":3,"style":{"typography":{"textTransform":"uppercase","fontWeight":"600"}},"fontSize":"medium"} -->
			<h3 class="wp-block-heading has-medium-font-size" style="font-weight:600;text-transform:uppercase"><?php echo esc_html( $isafe_service['title'] ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:separator {"className":"is-style-hatched"} -->
			<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
			<!-- /wp:separator -->

			<?php foreach ( $isafe_service['copy'] as $isafe_para ) : ?>
			<!-- wp:paragraph -->
			<p><?php echo esc_html( $isafe_para ); ?></p>
			<!-- /wp:paragraph -->
			<?php endforeach; ?>

			<!-- wp:buttons {"style":{"layout":{"selfStretch":"fit"},"spacing":{"margin":{"top":"auto"}}}} -->
			<div class="wp-block-buttons" style="margin-top:auto">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $isafe_service['url'] ); ?>"><?php esc_html_e( 'More Info', 'mavero' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->
	<?php endforeach; ?>
</div>
<!-- /wp:group -->
