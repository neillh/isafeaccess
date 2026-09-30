/**
 * We have modified the core/button block to provide additional control.
 */

/**
 * WordPress dependencies
 */
import { registerBlockStyle } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './js/block-list';
import './js/save';
import './js/attributes';
import './js/edit';

export function coreButton() {
	const { styles } = metadata;

	styles.forEach( ( style ) => {
		registerBlockStyle( 'core/button', style );
	} );
}
