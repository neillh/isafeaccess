/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { store } from './store';

function transformIcons( iconCollection ) {
	return iconCollection?.icons?.map( ( icon ) => ( { ...icon, iconCollection: iconCollection.name } ) );
}

const useIcons = ( iconCollection = false ) => {
	const [ icons, setIcons ] = useState( [] );
	const rawIcons = useSelect(
		( select ) => {
			const { getIconCollection, getIconCollections } = select( store );

			if ( iconCollection ) {
				return getIconCollection( iconCollection );
			}

			return getIconCollections();
		},
		[ iconCollection ],
	);

	useEffect( () => {
		if ( iconCollection ) {
			setIcons( transformIcons( rawIcons ) );
			return;
		}

		if ( ! rawIcons ) {
			setIcons( [] );
			return;
		}

		setIcons(
			Object.values( rawIcons ).reduce(
				( rawIconsTemp, iconCol ) => [ ...rawIconsTemp, ...transformIcons( iconCol ) ],
				[],
			),
		);
	}, [ rawIcons, iconCollection ] );

	return icons;
};

const useIcon = ( iconCollection, name ) => {
	const [ icon, setIcon ] = useState( null );
	const rawIcon = useSelect(
		( select ) => {
			return select( store ).getIcon( iconCollection, name );
		},
		[ iconCollection, name ],
	);

	useEffect( () => {
		setIcon( rawIcon );
	}, [ rawIcon ] );

	return icon;
};

export { useIcons, useIcon };
