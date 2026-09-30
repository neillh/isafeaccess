/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	ButtonGroup,
	Button,
	PanelBody,
	RangeControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ICON_SIZES } from './options';
import { Icon } from './icon';
import IconCollectionControl from './icon-collection-control';

/**
 * Icon edit component.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {string}   props.attributes.icon
 * @param {number}   props.attributes.iconSize
 * @param {Function} props.setAttributes
 */
const IconEdit = ( {
	attributes: {
		icon,
		iconSize,
		iconCollection,
	},
	setAttributes,
} ) => {
	const style = {};
	const isCustom = ! ICON_SIZES.some( ( _size ) => _size.value === iconSize );
	style.height = `${ iconSize }px`;
	style.width = `${ iconSize }px`;
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<Icon iconCollection={ iconCollection } name={ icon } style={ style } />
			</div>
			<InspectorControls>
				<IconCollectionControl
					icon={ icon }
					iconCollection={ iconCollection }
					onChange={ ( _icon ) => {
						setAttributes( {
							icon: _icon.name,
							source: _icon.source,
							iconCollection: _icon.iconCollection,
						} );
					} }
				/>
				<PanelBody title={ __( 'Size', 'express' ) } initialOpen={ true }>
					<ButtonGroup>
						{ ICON_SIZES.map( ( iconSizeObject ) => (
							<Button
								className={ `xwp-co-blocks-icons__button-size-${ iconSizeObject.value }` }
								key={ iconSizeObject.value }
								variant={
									iconSizeObject.value === iconSize || ( isCustom && 25 === iconSizeObject.value ) ? 'primary' : undefined
								}
								onClick={ () => setAttributes( { iconSize: iconSizeObject.value } ) }
							>
								{ iconSizeObject.label }
							</Button>
						) ) }
					</ButtonGroup>

					<RangeControl
						className={ 'xwp-co-blocks-icons__custom-range-control' }
						label={ __( 'Pick custom size (in px)', 'express' ) }
						value={ iconSize }
						onChange={ ( size ) => setAttributes( { iconSize: size } ) }
						min={ 10 }
						initialPosition={ iconSize }
						max={ 500 }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};

export default IconEdit;
