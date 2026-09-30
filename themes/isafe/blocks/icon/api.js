/**
 * WordPress dependencies
 */
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from './store';

/**
 * Register icon collection.
 *
 * @param {Object} options Options object.
 */
export function registerIcons( options ) {
	dispatch( store ).registerIconCollection( options );
}

/**
 * Unregister icon collection using collection name.
 *
 * @param {string} name
 */
export function unregisterIcons( name ) {
	dispatch( store ).unregisterIconCollection( name );
}
