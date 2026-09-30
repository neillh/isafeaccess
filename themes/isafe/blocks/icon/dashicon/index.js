/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerIcons } from '../api';
import icons from './icons';
import { socialIcons } from './social-icons';

export const getDashiconIcons = () => {
	return {
		name: 'core/dashicon',
		label: __( 'Dashicon', 'express' ),
		icons,
	};
};

export const getSocialIcons = () => {
	return {
		name: 'xwp/social-icon',
		label: __( 'Social Icon', 'express' ),
		icons: socialIcons,
	};
};

export const registerDashicon = () => {
	registerIcons( getDashiconIcons() );
};

export const registerSocialIcons = () => {
	registerIcons( getSocialIcons() );
};
