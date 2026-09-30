/**
 * Frontend behaviour for the "Slider" style on core/group.
 *
 * The CSS (css/blocks/_group-slider.scss) turns the group into a
 * scroll-snapping flex track — 1 slide per view on mobile, 3 from the
 * tablet breakpoint up. This module makes it a continuous carousel:
 *
 * - Clones the first/last few slides to the opposite end of the track, so
 *   there's always another real-looking slide to scroll to in either
 *   direction. When a scroll lands on a clone, the track silently jumps
 *   (scroll-behavior: auto, no animation) to the equivalent real slide —
 *   the clone is identical, so the jump is invisible.
 * - Prev/next always move exactly one slide, using each slide's own
 *   `offsetLeft` rather than a fixed width, so it stays correct across the
 *   1-per-view / 3-per-view breakpoint change.
 *
 * The track is wrapped in a non-scrolling element that the arrows are
 * positioned against. They can't be children of the track: absolutely
 * positioned children of an `overflow-x: auto` element scroll away with its
 * content. Pseudo-elements aren't an option either — the arrows need to be
 * real focusable buttons.
 */

const PREV_ARROW_SVG =
	'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 4.5 8 12l7.5 7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
const NEXT_ARROW_SVG =
	'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8.5 4.5 7.5 7.5-7.5 7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

// Default for the highest --slider-per-view set in the CSS, i.e. how many
// slides can be visible at once — that's how many need cloning at each end
// for the loop to look continuous at every breakpoint. Variants that show more
// (e.g. the logo slider) override it with --slider-max-per-view.
const MAX_PER_VIEW = 3;

/**
 * Create one arrow button.
 *
 * @param {string} modifier 'prev' or 'next'.
 * @param {string} label    Accessible label.
 * @param {string} svg      Icon markup.
 * @return {HTMLButtonElement} The button element.
 */
function createArrow( modifier, label, svg ) {
	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = `wp-block-group__slider-arrow wp-block-group__slider-arrow--${ modifier }`;
	button.setAttribute( 'aria-label', label );
	button.innerHTML = svg;
	return button;
}

/**
 * Clone a slide for the loop buffer, hidden from assistive tech and
 * keyboard/pointer interaction since it duplicates a real slide.
 *
 * @param {HTMLElement} slide Slide to clone.
 * @return {HTMLElement} The clone.
 */
function cloneSlide( slide ) {
	const clone = slide.cloneNode( true );
	clone.setAttribute( 'aria-hidden', 'true' );
	clone.setAttribute( 'inert', '' );
	return clone;
}

/**
 * Wire up a single slider instance.
 *
 * @param {HTMLElement} slider The `.wp-block-group.is-style-slider` element.
 */
function initSlider( slider ) {
	const realSlides = Array.from( slider.children );
	const realCount = realSlides.length;

	if ( realCount < 2 ) {
		return;
	}

	const maxPerView =
		parseInt(
			window
				.getComputedStyle( slider )
				.getPropertyValue( '--slider-max-per-view' ),
			10
		) || MAX_PER_VIEW;
	const cloneCount = Math.min( maxPerView, realCount );
	const startClones = realSlides.slice( -cloneCount ).map( cloneSlide );
	const endClones = realSlides.slice( 0, cloneCount ).map( cloneSlide );

	const startFragment = document.createDocumentFragment();
	startClones.forEach( ( clone ) => startFragment.appendChild( clone ) );
	slider.insertBefore( startFragment, slider.firstChild );

	const endFragment = document.createDocumentFragment();
	endClones.forEach( ( clone ) => endFragment.appendChild( clone ) );
	slider.appendChild( endFragment );

	const slides = [ ...startClones, ...realSlides, ...endClones ];

	const wrap = document.createElement( 'div' );
	wrap.className = 'wp-block-group__slider-wrap';
	// The wrap replaces the slider as the layout's child, so it has to carry
	// the block's alignment — otherwise a wide/full slider silently drops to
	// content width on the frontend while the editor still shows it aligned.
	[ 'alignwide', 'alignfull' ].forEach( ( align ) => {
		if ( slider.classList.contains( align ) ) {
			slider.classList.remove( align );
			wrap.classList.add( align );
		}
	} );
	slider.parentNode.insertBefore( wrap, slider );
	wrap.appendChild( slider );

	const prevBtn = createArrow( 'prev', 'Previous slide', PREV_ARROW_SVG );
	const nextBtn = createArrow( 'next', 'Next slide', NEXT_ARROW_SVG );
	wrap.append( prevBtn, nextBtn );

	let currentIndex = cloneCount; // First real slide.

	/**
	 * Move the track to a slide index.
	 *
	 * @param {number}  index   Index into `slides`.
	 * @param {boolean} instant Skip the scroll animation.
	 */
	const scrollToIndex = ( index, instant ) => {
		const target = slides[ index ];
		if ( ! target ) {
			return;
		}

		if ( instant ) {
			slider.style.scrollBehavior = 'auto';
		}

		slider.scrollLeft = target.offsetLeft;

		if ( instant ) {
			// Force layout before restoring smooth scrolling, so the jump
			// itself doesn't get animated.
			void slider.offsetHeight;
			slider.style.scrollBehavior = '';
		}
	};

	// Position on the first real slide without any animation on load.
	scrollToIndex( currentIndex, true );

	/**
	 * Find the slide index nearest the track's current scroll position.
	 *
	 * @return {number} Nearest index into `slides`.
	 */
	const getNearestIndex = () => {
		let nearest = 0;
		let smallestDiff = Infinity;
		slides.forEach( ( slide, index ) => {
			const diff = Math.abs( slide.offsetLeft - slider.scrollLeft );
			if ( diff < smallestDiff ) {
				smallestDiff = diff;
				nearest = index;
			}
		} );
		return nearest;
	};

	// After the track settles — whether from a button click or a manual
	// swipe/drag — silently rewrap if it landed on a clone.
	let settleTimer;
	const handleSettle = () => {
		clearTimeout( settleTimer );
		settleTimer = setTimeout( () => {
			const nearest = getNearestIndex();

			if ( nearest < cloneCount ) {
				currentIndex = nearest + realCount;
				scrollToIndex( currentIndex, true );
			} else if ( nearest >= cloneCount + realCount ) {
				currentIndex = nearest - realCount;
				scrollToIndex( currentIndex, true );
			} else {
				currentIndex = nearest;
			}
		}, 120 );
	};

	slider.addEventListener( 'scroll', handleSettle, { passive: true } );

	prevBtn.addEventListener( 'click', () => {
		currentIndex = Math.max( 0, currentIndex - 1 );
		scrollToIndex( currentIndex, false );
	} );

	nextBtn.addEventListener( 'click', () => {
		currentIndex = Math.min( slides.length - 1, currentIndex + 1 );
		scrollToIndex( currentIndex, false );
	} );

	// Slide widths change at the 1-per-view / 3-per-view breakpoint —
	// re-snap to the current slide so it stays aligned.
	let resizeTimer;
	window.addEventListener( 'resize', () => {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( () => scrollToIndex( currentIndex, true ), 150 );
	} );
}

/**
 * Initialise every Group Slider on the page.
 */
export default function initGroupSliders() {
	document
		.querySelectorAll( '.wp-block-group.is-style-slider' )
		.forEach( initSlider );
}
