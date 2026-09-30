/**
 * Returns all icons collections
 *
 * @param {Object} state Data state.
 *
 * @return {?Array} Icon collections.
 */
export function getIconCollections( state ) {
	const { iconCollection } = state;
	return Object.values( iconCollection );
}

/**
 * Returns an Icon collection by its name
 *
 * @param {Object} state Data state.
 * @param {string} name  Name of the Icon collection.
 *
 * @return {?Object} Icon collection.
 */
export function getIconCollection( state, name ) {
	const { iconCollection } = state;
	return iconCollection[ name ] ?? [];
}

/**
 * Returns an icon of an icon collection by its name
 *
 * @param {Object} state Data state.
 * @param {string} name  Name of the Icon collection.
 *
 * @return {?Array} List of Icons.
 */
export function getIcons( state, name ) {
	const { iconCollection } = state;
	return iconCollection?.hasOwnProperty( name ) ? iconCollection[ name ]?.icons ?? [] : [];
}

/**
 * Returns an icon of an icon collection by its name
 *
 * @param {Object} state    Data state.
 * @param {string} name     Name of the Icon collection.
 * @param {string} iconName Name of the iconName.
 *
 * @return {?Object} Icon.
 */
export function getIcon( state, name, iconName ) {
	const { iconCollection } = state;
	return iconCollection?.hasOwnProperty( name )
		? iconCollection[ name ]?.icons?.find( ( item ) => item.name === iconName ) ?? []
		: undefined;
}
