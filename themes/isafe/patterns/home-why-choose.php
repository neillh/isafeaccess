<?php
/**
 * Title: Home — Why choose iSafe Access
 * Slug: isafe/home-why-choose
 * Categories: isafe, about
 * Keywords: why, reasons, accordion, faq
 * Description: Photo with a green fade on the left; "8½ reasons" accordion on the right.
 *
 * @package Mavero
 */

$isafe_why_img_id  = 55;
$isafe_why_img_url = wp_get_attachment_image_url( $isafe_why_img_id, 'full' );

$isafe_list = static function ( array $items ) {
	$html = '<!-- wp:list --><ul class="wp-block-list">';
	foreach ( $items as $item ) {
		$html .= '<!-- wp:list-item --><li>' . esc_html( $item ) . '</li><!-- /wp:list-item -->';
	}
	return $html . '</ul><!-- /wp:list -->';
};

$isafe_para = static function ( $text ) {
	return '<!-- wp:paragraph --><p>' . esc_html( $text ) . '</p><!-- /wp:paragraph -->';
};

$isafe_reasons = [
	__( 'Over the past 21 years our team has engaged with a wide variety of industries', 'mavero' ) => $isafe_list(
		[
			__( 'Government facilities', 'mavero' ),
			__( 'Medical facilities', 'mavero' ),
			__( 'Education facilities', 'mavero' ),
			__( 'Transport providers: Rail, road and maritime', 'mavero' ),
			__( 'Housing industry: residential and commercial', 'mavero' ),
			__( 'Food and beverage industry', 'mavero' ),
			__( 'Sporting industry', 'mavero' ),
			__( 'Hospitality industries', 'mavero' ),
		]
	),
	__( 'We have worked on an array of projects', 'mavero' )                                            => $isafe_list(
		[
			__( 'Strata management projects', 'mavero' ),
			__( 'Retail and commercial projects', 'mavero' ),
			__( 'Roof maintenance and gutter cleaning projects', 'mavero' ),
			__( 'Residential projects', 'mavero' ),
			__( 'Shopping centres', 'mavero' ),
			__( 'Nursing homes', 'mavero' ),
			__( 'Warehouses', 'mavero' ),
			__( 'Factories', 'mavero' ),
			__( 'Cooling Towers', 'mavero' ),
			__( 'Air conditioner plant and equipment', 'mavero' ),
			__( 'Service Stations', 'mavero' ),
			__( 'Iconic Australian landmarks including bridges, skyscrapers and stadiums', 'mavero' ),
		]
	),
	__( 'Who we work with', 'mavero' )                                                                  => $isafe_list(
		[
			__( 'Strata managers', 'mavero' ),
			__( 'Building maintenance organisations', 'mavero' ),
			__( 'Building and construction businesses', 'mavero' ),
			__( 'Portable building manufacturers', 'mavero' ),
			__( 'Roofing companies', 'mavero' ),
			__( 'Home Owners', 'mavero' ),
		]
	),
	__( 'We offer a wide range of services', 'mavero' )                                                 => $isafe_list(
		[
			__( 'Supply of height safety systems', 'mavero' ),
			__( 'Installation of height safety systems', 'mavero' ),
			__( 'Fall arrest, prevention and protection systems', 'mavero' ),
			__( 'Recertification and compliance', 'mavero' ),
			__( 'Audits, assessments and accreditation', 'mavero' ),
			__( 'Product training on any equipment we supply and install', 'mavero' ),
			__( 'Creation of height safety asset registers', 'mavero' ),
		]
	),
	__( 'No job is too big or too small', 'mavero' )                                                    => $isafe_para( __( 'We offer height management systems from residential through to commercial projects.', 'mavero' ) ),
	__( 'Our team is knowledgeable, qualified and has access to all leading manufacturers resources', 'mavero' ) => $isafe_para( __( "The iSafe Access team have personally attended and have access to the leading manufacturer's courses, materials and resources. As a result, you get peace of mind that we are familiar with and understand the complexities of each system we install and train on, as well as inspect and recertify.", 'mavero' ) ),
	__( 'We meet all Australian standards and governing authority guidelines', 'mavero' )               => $isafe_para( __( 'We ensure that all Australian standards and local governing authority guidelines are met with all installations, inspections and training.', 'mavero' ) ),
	__( 'Fully insured', 'mavero' )                                                                     => $isafe_para( __( 'iSafe Access is fully insured with professional indemnity and public liability insurance.', 'mavero' ) ),
];
?>
<!-- wp:cover {"url":"<?php echo esc_url( $isafe_why_img_url ); ?>","id":<?php echo (int) $isafe_why_img_id; ?>,"dimRatio":80,"gradient":"green-fade","isDark":false,"align":"full","className":"isafe-why-choose","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"textColor":"contrast-2","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull is-light isafe-why-choose has-contrast-2-color has-text-color" style="padding-top:60px;padding-bottom:60px"><img class="wp-block-cover__image-background wp-image-<?php echo (int) $isafe_why_img_id; ?>" alt="" src="<?php echo esc_url( $isafe_why_img_url ); ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-80 has-background-dim wp-block-cover__gradient-background has-background-gradient has-green-fade-gradient-background"></span><div class="wp-block-cover__inner-container">
	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column"></div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
		<div class="wp-block-column">
			<!-- wp:heading {"style":{"typography":{"textTransform":"uppercase"}}} -->
			<h2 class="wp-block-heading" style="text-transform:uppercase"><?php esc_html_e( 'Why Choose iSafe Access', 'mavero' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"is-kicker"} -->
			<p class="is-kicker"><?php esc_html_e( '8½ reasons why you should partner with iSafe Access', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:separator {"className":"is-style-hatched"} -->
			<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
			<!-- /wp:separator -->

			<!-- wp:accordion {"iconPosition":"left"} -->
			<div role="group" class="wp-block-accordion">
				<?php foreach ( $isafe_reasons as $isafe_title => $isafe_panel ) : ?>
				<!-- wp:accordion-item -->
				<div class="wp-block-accordion-item">
					<!-- wp:accordion-heading {"iconPosition":"left"} -->
					<h3 class="wp-block-accordion-heading has-icon has-icon-left"><button type="button" class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span><span class="wp-block-accordion-heading__toggle-title"><?php echo esc_html( $isafe_title ); ?></span></button></h3>
					<!-- /wp:accordion-heading -->

					<!-- wp:accordion-panel -->
					<div role="region" class="wp-block-accordion-panel"><?php echo $isafe_panel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the builders above. ?></div>
					<!-- /wp:accordion-panel -->
				</div>
				<!-- /wp:accordion-item -->
				<?php endforeach; ?>
			</div>
			<!-- /wp:accordion -->

			<!-- wp:paragraph {"className":"is-kicker"} -->
			<p class="is-kicker"><?php esc_html_e( '8½ — We are a family run business', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Our qualified, passionate and dedicated team prides itself on our integrity, professionalism and our customer-centric attitude.', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div></div>
<!-- /wp:cover -->
