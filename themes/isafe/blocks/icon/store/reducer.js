/**
 * Reducer managing the block style variations.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
export default function reducer( state = { iconCollection: {} }, action ) {
	switch ( action.type ) {
		case 'REGISTER_ICON_COLLECTION':
			return {
				...state,
				iconCollection: {
					...state.iconCollection,
					[ action.iconCollection.name ]: action.iconCollection,
				},
			};
		case 'REMOVE_ICON_COLLECTION':
			if ( state.iconCollection.hasOwnProperty( action.name ) ) {
				const newState = { ...state };
				delete newState.iconCollection[ action.name ];
				return newState;
			}

			return state;
		default:
			return state;
	}
}
