/**
 * Returns an action object used in signalling that new icons have been added.
 *
 * @param {Object} iconCollection icon collection.
 *
 * @return {Object} Action object.
 */
export function registerIconCollection( iconCollection ) {
	return {
		type: 'REGISTER_ICON_COLLECTION',
		iconCollection,
	};
}

/**
 * Returns an action object used in signalling that new block icons have been added.
 *
 * @param {string} name Icon collection name.
 *
 * @return {Object} Action object.
 */
export function unregisterIconCollection( name ) {
	return {
		type: 'REMOVE_ICON_COLLECTION',
		name,
	};
}
