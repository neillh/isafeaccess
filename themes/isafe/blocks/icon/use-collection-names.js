/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { store } from './store';

/**
 * Get collection names.
 */
const useCollectionNames = () => {
	return useSelect( ( select ) => {
		const { getIconCollections } = select( store );

		return getIconCollections();
	}, [] );
};

export default useCollectionNames;
