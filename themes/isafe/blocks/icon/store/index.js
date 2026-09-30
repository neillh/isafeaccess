/**
 * WordPress dependencies
 */
import { createReduxStore } from '@wordpress/data';
/**
 * Internal dependencies
 */
import * as selectors from './selector';
import * as actions from './actions';
import reducer from './reducer';

const STORE_NAME = 'xwp/icons';

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );
