/**
 * WordPress dependencies
 */
import { buttons as icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import metadata from './block.json';
import ButtonEdit from './edit';
import save from './save';
import transforms from './transforms';

registerBlockType(
	metadata.name,
	{
		icon,
		edit: ButtonEdit,
		save,
		transforms,
	}
);
