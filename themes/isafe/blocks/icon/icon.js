/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useIcon } from './use-icons';

/**
 * Icon
 *
 * @param {Object} props                IconProps
 * @param {string} props.name           name of the icon
 * @param {string} props.iconCollection name of the icon collection.
 *
 * @return {JSX.Element} React component.
 */
export const Icon = ( props ) => {
	const { name, iconCollection, ...rest } = props;
	const icon = useIcon( iconCollection, name );

	const className = classNames( rest?.className, [
		'icon',
	] );

	if ( ! icon || ( Array.isArray( icon ) && ! icon.length ) ) {
		return <Spinner />;
	}

	return <div dangerouslySetInnerHTML={ { __html: icon.source } } { ...{ ...rest, ...{ className } } } />;
};
