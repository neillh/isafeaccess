/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Edit component for the Testimonial Carousel block.
 *
 * The slider behaviour (arrows, looping) is added on the front end by
 * js/modules/_group-slider.js, so the editor preview shows the cards in a
 * plain scrolling row.
 *
 * @param {Object}   props               Block properties.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { count, order, showRating, showPhoto, showLogo } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Testimonials', 'mavero' ) }>
					<RangeControl
						label={ __( 'Number of testimonials', 'mavero' ) }
						value={ count }
						onChange={ ( value ) => setAttributes( { count: value } ) }
						min={ 1 }
						max={ 24 }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Order', 'mavero' ) }
						value={ order }
						options={ [
							{
								label: __( 'Manual (menu order)', 'mavero' ),
								value: 'menu_order',
							},
							{
								label: __( 'Newest first', 'mavero' ),
								value: 'date_desc',
							},
							{
								label: __( 'Oldest first', 'mavero' ),
								value: 'date_asc',
							},
						] }
						onChange={ ( value ) => setAttributes( { order: value } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={ __( 'Display', 'mavero' ) }>
					<ToggleControl
						label={ __( 'Show star rating', 'mavero' ) }
						checked={ showRating }
						onChange={ ( value ) =>
							setAttributes( { showRating: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show client photo', 'mavero' ) }
						checked={ showPhoto }
						onChange={ ( value ) =>
							setAttributes( { showPhoto: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show client logo', 'mavero' ) }
						checked={ showLogo }
						onChange={ ( value ) =>
							setAttributes( { showLogo: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				{ /* Supports (align, spacing…) are already applied by useBlockProps. */ }
				<ServerSideRender
					block="mavero/testimonial-carousel"
					attributes={ attributes }
					skipBlockSupportAttributes
				/>
			</div>
		</>
	);
}
