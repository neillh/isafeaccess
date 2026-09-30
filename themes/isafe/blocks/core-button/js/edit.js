/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ButtonGroup, Button } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import meta from '../block.json';

const { sizes } = meta;

/**
 * Add custom `size` attribute to core/button block.
 */
const withButtonSizeControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, attributes, setAttributes } = props;

		if ( 'core/button' !== name ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<InspectorControls>
					<PanelBody
						title={ __( 'Button Size', 'express' ) }
						initialOpen={ true }
					>
						<ButtonGroup
							aria-label={ __( 'Button Size', 'express' ) }
						>
							{ sizes.map( ( size ) => (
								<Button
									key={ size.value }
									isPrimary={ size.value === attributes.size }
									onClick={ () => setAttributes( { size: size.value } ) }
									isSmall
								>
									{ size.label }
								</Button>
							) ) }
						</ButtonGroup>
					</PanelBody>
				</InspectorControls>

				<BlockEdit { ...props } />
			</>
		);
	};
}, 'withButtonSizeControls' );

addFilter(
	'editor.BlockEdit',
	'express/button-size-controls',
	withButtonSizeControls
);
