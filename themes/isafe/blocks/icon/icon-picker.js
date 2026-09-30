/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	CheckboxControl,
	BaseControl,
	NavigableMenu,
	VisuallyHidden,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalScrollable as Scrollable,
	SearchControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useState, memo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useIcons } from './use-icons';
import { useFilteredList } from './use-filtered-list';
import { Icon } from './icon';

/**
 * IconPicker
 *
 * @param {Object} props
 * @return {*} React Element
 */
export const IconPicker = ( props ) => {
	const { value, onChange, iconCollection, label, ...rest } = props;

	const icons = useIcons( iconCollection );

	const instanceId = useInstanceId( IconPicker );
	const id = `icon-picker-${ instanceId }`;

	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ filteredIcons ] = useFilteredList( icons, searchTerm );

	const hasIcons = !! filteredIcons.length;

	return (
		<BaseControl label={ label } id={ id } className="component-icon-picker" { ...rest }>
			<SearchControl value={ searchTerm } onChange={ setSearchTerm } id={ id } />
			{ hasIcons ? (
				<Scrollable style={ { maxHeight: 200 } }>
					<IconGrid icons={ filteredIcons } selectedIcon={ value } onChange={ onChange } />
				</Scrollable>
			) : (
				<p>{ __( 'No icons were found…', 'express' ) }</p>
			) }
		</BaseControl>
	);
};

const IconComponent = memo( ( props ) => {
	const { name, iconCollection, isChecked, label } = props;
	return <>
		<Icon
			key={ name }
			name={ name }
			iconCollection={ iconCollection }
			className={ isChecked ? 'icon--active' : '' }
		/>
		<VisuallyHidden>{ label }</VisuallyHidden>
	</>;
} );

const IconGrid = ( props ) => {
	const { icons, selectedIcon, onChange } = props;

	return (
		<NavigableMenu orientation="vertical" className="component-icon-picker__list">
			{ icons.map( ( icon ) => {
				const isChecked =
					selectedIcon?.name === icon.name && selectedIcon?.iconCollection === icon.iconCollection;
				return (
					<CheckboxControl
						key={ icon.name }
						label={ <IconComponent
							key={ icon.name }
							name={ icon.name }
							iconCollection={ icon.iconCollection }
							isChecked={ isChecked }
							label={ icon.label }
						/> }
						checked={ isChecked }
						onChange={ () => onChange( icon ) }
						className="component-icon-picker__checkbox-control"
					/>
				);
			} ) }
		</NavigableMenu>
	);
};
