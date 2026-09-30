/**
 * WordPress dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import meta from '../block.json';

const { sizes } = meta;

/**
 * Add custom `size` class to core/button block.
 */
const withButtonSizeClass = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {
		const { name, attributes } = props;

		if ( 'core/button' !== name ) {
			return <BlockListBlock { ...props } />;
		}

		const { size } = attributes;
		const selectedSize = sizes.find( ( item ) => item.value === size );
		const sizeClass = selectedSize ? selectedSize.className : '';

		return <BlockListBlock { ...props } className={ sizeClass } />;
	};
}, 'withButtonSizeClass' );

addFilter(
	'editor.BlockListBlock',
	'fmbc/button-size-class',
	withButtonSizeClass
);
