/**
 * Block type editor script definition.
 * It will only be enqueued in the context of the editor.
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';
import { register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import Save from './save';
import { store } from './store';

// Stylesheets.
import './editor.scss';
import { registerDashicon, registerSocialIcons } from './dashicon';

registerBlockType(
	metadata.name,
	{
		edit: Edit,
		save: Save,
	}
);

domReady( () => {
	register( store );
	registerDashicon();
	registerSocialIcons();
} );

