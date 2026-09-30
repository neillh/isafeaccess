/**
 * WordPress dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 *
 * @param {Array}   list       list of items to filter
 * @param {string}  searchTerm search term string
 * @param {string?} property   name of the prop
 * @return {Array} filtered list
 */
export function useFilteredList( list = [], searchTerm = '', property = 'name' ) {
	const [ filteredList, setFilteredList ] = useState( list );

	const filterList = useCallback(
		( searchT ) => {
			return list.filter( ( item ) => item[ property ].includes( searchT ) );
		},
		[ list, property ],
	);

	useEffect( () => {
		const hasListItems = !! list?.length;
		const hasSearchTerm = searchTerm !== '';
		const canFilter = hasSearchTerm && hasListItems;
		const newFilteredList = canFilter ? filterList( searchTerm ) : list;
		setFilteredList( newFilteredList );
	}, [ searchTerm, filterList, list ] );

	return [ filteredList ];
}
