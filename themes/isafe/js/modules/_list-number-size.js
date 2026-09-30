/**
 * Number size control for the core/list "Numbered" block style.
 *
 * Adds a `numberSize` attribute to core/list and exposes it as a font size
 * picker in the block sidebar. The chosen value is written to the block as the
 * `--list-number-size` custom property, which css/blocks/_list.scss reads.
 *
 * The style itself is registered in PHP — see Mavero\Components\Block_Styles.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { cloneElement } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import { PanelBody, FontSizePicker } from '@wordpress/components';

const BLOCK_NAME = 'core/list';
const STYLE_CLASS = 'is-style-numbered';
const CSS_VAR = '--list-number-size';

/**
 * Whether the block is currently using the "Numbered" style variation.
 *
 * Block styles are stored in the block's `className` attribute.
 *
 * @param {Object} attributes Block attributes.
 * @return {boolean} True when the numbered style is active.
 */
const isNumbered = ( attributes ) =>
	( attributes?.className || '' ).split( ' ' ).includes( STYLE_CLASS );

/**
 * Add the `numberSize` attribute to core/list.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} New settings.
 */
const addNumberSizeAttribute = ( settings, name ) => {
	if ( BLOCK_NAME !== name ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			numberSize: {
				type: 'string',
			},
		},
	};
};

addFilter(
	'blocks.registerBlockType',
	'mavero/list-number-size-attribute',
	addNumberSizeAttribute
);

/**
 * Sidebar panel holding the number size picker.
 *
 * Kept as its own component so `useSettings` is never called conditionally.
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.value    Current size.
 * @param {Function} props.onChange Size change handler.
 * @return {Element} The panel.
 */
const NumberSizePanel = ( { value, onChange } ) => {
	const [ fontSizes ] = useSettings( 'typography.fontSizes' );

	return (
		<InspectorControls group="styles">
			<PanelBody
				title={ __( 'Numbered list', 'mavero' ) }
				initialOpen={ true }
			>
				<FontSizePicker
					__nextHasNoMarginBottom
					label={ __( 'Number size', 'mavero' ) }
					value={ value }
					fontSizes={ fontSizes }
					withReset={ true }
					onChange={ onChange }
				/>
			</PanelBody>
		</InspectorControls>
	);
};

/**
 * Render the number size control for numbered lists.
 */
const withNumberSizeControl = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, attributes, setAttributes } = props;

		if ( BLOCK_NAME !== name || ! isNumbered( attributes ) ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<BlockEdit { ...props } />

				<NumberSizePanel
					value={ attributes.numberSize }
					onChange={ ( numberSize ) =>
						setAttributes( { numberSize } )
					}
				/>
			</>
		);
	};
}, 'withNumberSizeControl' );

addFilter(
	'editor.BlockEdit',
	'mavero/list-number-size-control',
	withNumberSizeControl
);

/**
 * Apply the size in the editor canvas so it matches the frontend.
 */
const withNumberSizeStyle = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {
		const { name, attributes, wrapperProps } = props;

		if ( BLOCK_NAME !== name || ! attributes?.numberSize ) {
			return <BlockListBlock { ...props } />;
		}

		return (
			<BlockListBlock
				{ ...props }
				wrapperProps={ {
					...wrapperProps,
					style: {
						...wrapperProps?.style,
						[ CSS_VAR ]: attributes.numberSize,
					},
				} }
			/>
		);
	};
}, 'withNumberSizeStyle' );

addFilter(
	'editor.BlockListBlock',
	'mavero/list-number-size-editor-style',
	withNumberSizeStyle
);

/**
 * Write the custom property onto the saved markup.
 *
 * Returns the element untouched when no size is set, so existing lists
 * serialize identically and do not trip block validation.
 *
 * @param {Object} element    Block save element.
 * @param {Object} blockType  Block type.
 * @param {Object} attributes Block attributes.
 * @return {Object} The save element.
 */
const addNumberSizeStyle = ( element, blockType, attributes ) => {
	if ( ! element || BLOCK_NAME !== blockType.name || ! attributes?.numberSize ) {
		return element;
	}

	return cloneElement( element, {
		style: {
			...element.props.style,
			[ CSS_VAR ]: attributes.numberSize,
		},
	} );
};

addFilter(
	'blocks.getSaveElement',
	'mavero/list-number-size-save',
	addNumberSizeStyle
);
