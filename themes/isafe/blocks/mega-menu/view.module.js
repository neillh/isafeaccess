/**
 * Mega Menu block view module.
 *
 * Built as an ES module (see the `*.module.js` entry glob in webpack.config.js)
 * so it can import the Interactivity API the same way core's Navigation block does.
 *
 * Pointer devices open the panel on hover with a short intent delay so diagonal
 * travel towards the panel doesn't dismiss it. Touch devices, and any menu inside
 * the open mobile overlay, toggle on click instead.
 */

/**
 * WordPress dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const OPEN_DELAY = 150;
const CLOSE_DELAY = 300;

/**
 * Whether the device is a true pointer device that can hover.
 *
 * @return {boolean} True when hover is a reliable interaction.
 */
const canHover = () =>
	window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;

/**
 * Whether this menu is currently inside the expanded mobile overlay.
 *
 * In the overlay the panel renders as an accordion, so hover must not apply.
 *
 * @param {HTMLElement} ref The block's root element.
 * @return {boolean} True when rendered inside an open overlay.
 */
const isInOpenOverlay = ( ref ) =>
	!! ref.closest(
		'.wp-block-navigation__responsive-container.is-menu-open'
	);

/**
 * Cancel any pending open/close timer for this menu.
 *
 * @param {Object} context The block's Interactivity context.
 */
const clearTimer = ( context ) => {
	if ( context.timerId ) {
		clearTimeout( context.timerId );
		context.timerId = 0;
	}
};

store( 'mavero/mega-menu', {
	actions: {
		handleMouseEnter() {
			const { ref } = getElement();

			if ( ! canHover() || isInOpenOverlay( ref ) ) {
				return;
			}

			const context = getContext();
			clearTimer( context );

			context.timerId = setTimeout( () => {
				context.timerId = 0;
				context.isOpen = true;
			}, OPEN_DELAY );
		},

		handleMouseLeave() {
			const { ref } = getElement();

			if ( ! canHover() || isInOpenOverlay( ref ) ) {
				return;
			}

			const context = getContext();
			clearTimer( context );

			context.timerId = setTimeout( () => {
				context.timerId = 0;
				context.isOpen = false;
			}, CLOSE_DELAY );
		},

		toggle( event ) {
			// The toggle is a button; stop the click reaching the nav's own handlers.
			event.preventDefault();
			event.stopPropagation();

			const context = getContext();
			clearTimer( context );
			context.isOpen = ! context.isOpen;
		},

		handleKeydown( event ) {
			if ( 'Escape' !== event.key ) {
				return;
			}

			const context = getContext();

			if ( ! context.isOpen ) {
				return;
			}

			event.preventDefault();
			clearTimer( context );
			context.isOpen = false;

			// Return focus to the trigger rather than losing it inside a hidden panel.
			const { ref } = getElement();
			ref.querySelector( '.wp-block-mavero-mega-menu__toggle' )?.focus();
		},

		handleFocusout( event ) {
			const { ref } = getElement();

			// `relatedTarget` is null when focus leaves the document entirely.
			if ( event.relatedTarget && ref.contains( event.relatedTarget ) ) {
				return;
			}

			const context = getContext();
			clearTimer( context );
			context.isOpen = false;
		},
	},
} );
