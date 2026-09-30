/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Icon save component.
 *
 * @param {Object} props
 * @param {Object} props.attributes
 * @param {string} props.attributes.iconSize
 * @param {string} props.attributes.source
 */
const Save = ( { attributes: {
	iconSize,
	source,
} } ) => {
	const style = {},
		wrapperStyle = {};
	style.height = `${ iconSize }px`;
	style.width = `${ iconSize }px`;
	const props = useBlockProps.save( {
		style: wrapperStyle,
	} );

	return (
		<div { ...props }>
			<div dangerouslySetInnerHTML={ { __html: source } } className={ 'xwp-co-icon' } style={ style } />
		</div>
	);
};

export default Save;
