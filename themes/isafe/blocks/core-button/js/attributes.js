/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Store `size` attribute in the block's meta.
 *
 * @param {*} settings
 * @param {*} name
 * @return {*} New settings.
 */
const addButtonBlockSizes = ( settings, name ) => {
	if ( 'core/button' !== name ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			size: {
				default: 'medium',
				type: 'string',
			},
		},
	};
};

addFilter(
	'blocks.registerBlockType',
	'express/button-block-sizes',
	addButtonBlockSizes
);
