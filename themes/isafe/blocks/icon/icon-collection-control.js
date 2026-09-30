/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';
/**
 * Internal dependencies
 */
import { useState } from '@wordpress/element';
import { IconPicker } from './icon-picker';
import useCollectionNames from './use-collection-names';

const IconCollectionControl = ( { icon, iconCollection, onChange } ) => {
	const collectionNames = useCollectionNames();
	const [ iconCollectionDropdown, setIconCollectionDropdown ] = useState(
		(
			Array.isArray( collectionNames ) &&
			collectionNames.some( ( _collectionNames ) => _collectionNames.name === iconCollection ) )
			? iconCollection : null );
	return (
		<PanelBody title={ __( 'Icon', 'express' ) } initialOpen={ true }>
			{ collectionNames &&
				<SelectControl
					label={ __( 'Icon Type', 'express' ) }
					value={ iconCollectionDropdown }
					options={ collectionNames.map(
						( { name, label } ) => ( {
							label,
							value: name,
						}
						) ) }
					onChange={ ( _iconCollection ) => {
						setIconCollectionDropdown( _iconCollection );
					} }
				/>
			}
			<IconPicker
				value={ {
					name: icon,
					iconCollection,
				} }
				iconCollection={ iconCollectionDropdown }
				onChange={ onChange }
			/>
		</PanelBody>
	);
};

export default IconCollectionControl;
