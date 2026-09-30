<?php
/**
 * Title: 404 content
 * Slug: isafe/hidden-404
 * Inserter: no
 *
 * @package Mavero
 */

?>
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading"><?php esc_html_e( 'Page not found', 'mavero' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-hatched"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'The page you were looking for may have moved. Try a search, or head back to the home page.', 'mavero' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"<?php esc_attr_e( 'Search', 'mavero' ); ?>","showLabel":false,"buttonText":"<?php esc_attr_e( 'Search', 'mavero' ); ?>"} /-->
