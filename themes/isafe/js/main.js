/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import initYoastFaqs from './modules/_yoast-faqs';
import initGroupSliders from './modules/_group-slider';

domReady(async () => {
	initYoastFaqs();
	initGroupSliders();
});
