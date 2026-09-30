<?php
/**
 * Title: Footer
 * Slug: isafe/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package Mavero
 */

?>
<!-- wp:group {"align":"full","className":"site-footer is-style-section-dark","style":{"spacing":{"padding":{"top":"40px","bottom":"40px"}},"elements":{"link":{"color":{"text":"var:preset|color|text-on-dark"},":hover":{"color":{"text":"var:preset|color|accent"}}}}},"layout":{"type":"constrained","contentSize":"1140px"}} -->
<div class="wp-block-group alignfull site-footer is-style-section-dark has-link-color" style="padding-top:40px;padding-bottom:40px">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50","top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"33%"} -->
		<div class="wp-block-column" style="flex-basis:33%">
			<!-- wp:image {"width":"240px","sizeSlug":"full","linkDestination":"custom","className":"site-footer__logo","style":{"spacing":{"margin":{"top":"13px"}}}} -->
			<figure class="wp-block-image size-full is-resized site-footer__logo" style="margin-top:13px"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/isafe-access-logo-tag-white.svg' ) ); ?>" alt="<?php esc_attr_e( 'iSafe Access — Height Safety Specialist', 'mavero' ); ?>" style="width:240px;height:auto"/></a></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"25%"} -->
		<div class="wp-block-column" style="flex-basis:25%">
			<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0.75rem"}},"fontSize":"small"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":""} -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":2,"style":{"typography":{"textTransform":"uppercase","lineHeight":"24px"}}} -->
			<h2 class="wp-block-heading" style="line-height:24px;text-transform:uppercase"><a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact Us', 'mavero' ); ?></a></h2>
			<!-- /wp:heading -->

			<!-- wp:separator {"className":"is-style-hatched"} -->
			<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
			<!-- /wp:separator -->

			<!-- wp:paragraph -->
			<p><strong><?php esc_html_e( 'Phone:', 'mavero' ); ?></strong> <a href="tel:1300147233">1300 147 233</a><br><strong><?php esc_html_e( 'Email:', 'mavero' ); ?></strong> <a href="mailto:admin@isafeaccess.com.au">admin@isafeaccess.com.au</a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><strong><?php esc_html_e( 'Servicing:', 'mavero' ); ?></strong> <?php esc_html_e( 'NSW, greater Sydney region, East Coast and Canberra', 'mavero' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:social-links {"iconColor":"contrast","iconColorValue":"#24272A","iconBackgroundColor":"text-on-dark","iconBackgroundColorValue":"#F1F1F1","openInNewTab":true,"className":"is-style-default","style":{"spacing":{"blockGap":{"left":"10px"}}}} -->
			<ul class="wp-block-social-links has-icon-color has-icon-background-color is-style-default">
				<!-- wp:social-link {"url":"https://www.facebook.com/profile.php?id=100062993331268","service":"facebook"} /-->
				<!-- wp:social-link {"url":"https://www.instagram.com/i_safeaccess/","service":"instagram"} /-->
				<!-- wp:social-link {"url":"https://www.linkedin.com/in/ross-howe-b5198817a/","service":"linkedin"} /-->
			</ul>
			<!-- /wp:social-links -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
