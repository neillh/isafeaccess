/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	Spinner,
	Button,
} from '@wordpress/components';
import { chevronUp, chevronDown, close } from '@wordpress/icons';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

/**
 * Edit component for the Brand Carousel block.
 *
 * @param {Object} props Block properties.
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { selectionMode, selectedBrands, title } = attributes;
	const [ brands, setBrands ] = useState( null );

	useEffect( () => {
		apiFetch( {
			path: '/wp/v2/product_brand?per_page=100&hide_empty=true&orderby=name&order=asc',
		} )
			.then( setBrands )
			.catch( () => setBrands( [] ) );
	}, [] );

	const blockProps = useBlockProps();

	const addBrand = ( id ) => {
		setAttributes( { selectedBrands: [ ...selectedBrands, id ] } );
	};

	const removeBrand = ( id ) => {
		setAttributes( {
			selectedBrands: selectedBrands.filter( ( b ) => b !== id ),
		} );
	};

	const moveItem = ( index, direction ) => {
		const next = [ ...selectedBrands ];
		const target = index + direction;
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		setAttributes( { selectedBrands: next } );
	};

	const unselected = brands?.filter(
		( b ) => ! selectedBrands.includes( b.id )
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'mavero' ) }>
					<TextControl
						label={ __( 'Heading', 'mavero' ) }
						value={ title }
						onChange={ ( val ) => setAttributes( { title: val } ) }
					/>
					<SelectControl
						label={ __( 'Brands to Display', 'mavero' ) }
						value={ selectionMode }
						options={ [
							{
								label: __( 'All Active Brands', 'mavero' ),
								value: 'all',
							},
							{
								label: __( 'Manual Selection', 'mavero' ),
								value: 'manual',
							},
						] }
						onChange={ ( val ) =>
							setAttributes( { selectionMode: val } )
						}
					/>
				</PanelBody>

				{ selectionMode === 'manual' && (
					<>
						<PanelBody
							title={ __( 'Selected Brands', 'mavero' ) }
						>
							{ brands === null && <Spinner /> }
							{ brands !== null &&
								selectedBrands.length === 0 && (
									<p style={ { margin: 0, fontSize: '13px', color: '#757575' } }>
										{ __(
											'No brands selected. Add brands below.',
											'mavero'
										) }
									</p>
								) }
							{ brands !== null &&
								selectedBrands.map( ( id, index ) => {
									const brand = brands.find(
										( b ) => b.id === id
									);
									if ( ! brand ) return null;
									return (
										<div
											key={ id }
											style={ {
												display: 'flex',
												alignItems: 'center',
												gap: '2px',
												marginBottom: '4px',
											} }
										>
											<span
												style={ {
													flex: 1,
													fontSize: '13px',
													overflow: 'hidden',
													textOverflow: 'ellipsis',
													whiteSpace: 'nowrap',
												} }
											>
												{ brand.name }
											</span>
											<Button
												size="small"
												icon={ chevronUp }
												label={ __(
													'Move up',
													'mavero'
												) }
												disabled={ index === 0 }
												onClick={ () =>
													moveItem( index, -1 )
												}
											/>
											<Button
												size="small"
												icon={ chevronDown }
												label={ __(
													'Move down',
													'mavero'
												) }
												disabled={
													index ===
													selectedBrands.length - 1
												}
												onClick={ () =>
													moveItem( index, 1 )
												}
											/>
											<Button
												size="small"
												icon={ close }
												label={ __(
													'Remove',
													'mavero'
												) }
												isDestructive
												onClick={ () =>
													removeBrand( id )
												}
											/>
										</div>
									);
								} ) }
						</PanelBody>

						<PanelBody
							title={ __( 'Add Brands', 'mavero' ) }
							initialOpen={ false }
						>
							{ brands === null && <Spinner /> }
							{ brands !== null &&
								unselected.length === 0 && (
									<p style={ { margin: 0, fontSize: '13px', color: '#757575' } }>
										{ __(
											'All brands have been added.',
											'mavero'
										) }
									</p>
								) }
							{ brands !== null &&
								unselected.map( ( brand ) => (
									<div
										key={ brand.id }
										style={ { marginBottom: '4px' } }
									>
										<Button
											size="small"
											variant="secondary"
											onClick={ () =>
												addBrand( brand.id )
											}
											style={ { width: '100%', justifyContent: 'flex-start' } }
										>
											{ brand.name }
										</Button>
									</div>
								) ) }
						</PanelBody>
					</>
				) }
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block="mavero/brand-carousel"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
