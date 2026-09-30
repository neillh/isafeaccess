<?php
/**
 * Removes the plugin's single option on uninstall.
 *
 * Nothing else is touched: this plugin creates no post types, no terms and no
 * post meta. Everything it emits is derived from settings and from data owned by the
 * theme and other plugins, so uninstalling it removes markup, never content.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mavero_schema_settings' );
