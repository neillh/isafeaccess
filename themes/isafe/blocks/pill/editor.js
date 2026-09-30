/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './editor.scss';
import metadata from './block.json';
import edit from './js/edit';

registerBlockType(
	metadata.name,
	{
		edit,
		save: () => <InnerBlocks.Content />,
	}
);
