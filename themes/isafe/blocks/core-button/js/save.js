/**
 * WordPress dependencies
 */
/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import meta from '../block.json';

const { sizes } = meta;

/**
 * Add custom class related to `size` to core/button block.
 *
 * @param {Object} element
 * @param {Object} blockType
 * @param {Object} attributes
 */
const addButtonClass = ( element, blockType, attributes ) => {
	// skip if element is undefined
	if ( ! element ) {
		return;
	}

	// only apply to button blocks.
	if ( blockType.name !== 'core/button' ) {
		return element;
	}

	const existingClasses = element.props.className;

	const {
		props,
	} = element;

	const {
		size,
	} = attributes;

	const selectedSize = sizes.find( ( item ) => item.value === size );
	const sizeClass = selectedSize ? selectedSize.className : '';

	return { ...element, props: { ...props, className: classnames( existingClasses, sizeClass ) } };
};

addFilter(
	'blocks.getSaveElement',
	'express/add-button-class',
	addButtonClass
);
