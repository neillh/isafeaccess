/**
 * Brand Carousel block view script.
 * Dot count is calculated from the number of scroll pages (total slides / visible slides),
 * so it stays correct at every breakpoint. Re-renders on resize.
 *
 * Active dot state is set immediately on click — not derived from the scroll event —
 * because smooth-scroll + snap makes event-based detection unreliable on desktop.
 * The scroll event is still used for manual swipe/drag updates.
 */
document
	.querySelectorAll( '.wp-block-mavero-brand-carousel' )
	.forEach( ( carousel ) => {
		const track = carousel.querySelector(
			'.wp-block-mavero-brand-carousel__track'
		);
		const prevBtn = carousel.querySelector(
			'.wp-block-mavero-brand-carousel__arrow--prev'
		);
		const nextBtn = carousel.querySelector(
			'.wp-block-mavero-brand-carousel__arrow--next'
		);
		const dotsContainer = carousel.querySelector(
			'.wp-block-mavero-brand-carousel__dots'
		);

		if ( ! track ) {
			return;
		}

		const slides = Array.from(
			track.querySelectorAll( '.wp-block-mavero-brand-carousel__slide' )
		);
		const totalSlides = slides.length;

		const getSlideWidth = () => {
			const slide = slides[ 0 ];
			if ( ! slide ) return 0;
			const gap = parseFloat( getComputedStyle( track ).gap ) || 0;
			return slide.offsetWidth + gap;
		};

		const getVisibleCount = () => {
			const sw = getSlideWidth();
			return sw > 0 ? Math.round( track.clientWidth / sw ) : 1;
		};

		const getPageCount = () =>
			Math.ceil( totalSlides / getVisibleCount() );

		const getCurrentPage = () => {
			const sw = getSlideWidth();
			const perPage = getVisibleCount();
			return sw > 0
				? Math.round( track.scrollLeft / ( sw * perPage ) )
				: 0;
		};

		const setActiveDot = ( page ) => {
			if ( ! dotsContainer ) return;
			dotsContainer
				.querySelectorAll( '.wp-block-mavero-brand-carousel__dot' )
				.forEach( ( dot, i ) =>
					dot.classList.toggle( 'is-active', i === page )
				);
		};

		const scrollToPage = ( page ) => {
			const sw = getSlideWidth();
			const perPage = getVisibleCount();
			const clamped = Math.max( 0, Math.min( getPageCount() - 1, page ) );
			// Set dot immediately — don't wait for scroll events.
			setActiveDot( clamped );
			track.scrollTo( {
				left: clamped * perPage * sw,
				behavior: 'smooth',
			} );
		};

		const renderDots = () => {
			if ( ! dotsContainer ) return;
			const pageCount = getPageCount();
			const current = getCurrentPage();
			dotsContainer.innerHTML = '';
			for ( let i = 0; i < pageCount; i++ ) {
				const dot = document.createElement( 'button' );
				dot.className =
					'wp-block-mavero-brand-carousel__dot' +
					( i === current ? ' is-active' : '' );
				dot.setAttribute( 'role', 'tab' );
				dot.setAttribute(
					'aria-label',
					`Go to page ${ i + 1 } of ${ pageCount }`
				);
				dot.addEventListener( 'click', () => scrollToPage( i ) );
				dotsContainer.appendChild( dot );
			}
		};

		prevBtn?.addEventListener( 'click', () =>
			scrollToPage( getCurrentPage() - 1 )
		);

		nextBtn?.addEventListener( 'click', () =>
			scrollToPage( getCurrentPage() + 1 )
		);

		// Update dots for manual swipe/drag (not needed for click-based nav).
		track.addEventListener(
			'scroll',
			() => setActiveDot( getCurrentPage() ),
			{ passive: true }
		);

		// Re-render dots when the viewport changes breakpoints.
		let resizeTimer;
		window.addEventListener( 'resize', () => {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( renderDots, 150 );
		} );

		renderDots();
	} );