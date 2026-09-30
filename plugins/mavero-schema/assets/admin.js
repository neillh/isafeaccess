/* global jQuery, wp */
( function ( $ ) {
	'use strict';

	/**
	 * Add a row. The template carries the literal token __i__ everywhere an
	 * index belongs; swap it for the next free index so the posted array keys
	 * stay unique. Indices are never reused after a removal — the settings
	 * sanitiser reindexes on save, so gaps are harmless.
	 */
	$( document ).on( 'click', '.mavero-add', function () {
		var $btn      = $( this ),
			$repeater = $btn.closest( '.mavero-repeater' ),
			next      = parseInt( $btn.data( 'next' ), 10 ) || 0,
			html      = $repeater.find( '.mavero-tpl' ).html();

		if ( ! html ) {
			return;
		}

		$repeater.find( '.mavero-rows' ).append( html.split( '__i__' ).join( String( next ) ) );
		$btn.data( 'next', next + 1 );
	} );

	$( document ).on( 'click', '.mavero-remove', function () {
		$( this ).closest( '.mavero-row' ).remove();
	} );

	$( document ).on( 'click', '.mavero-media-pick', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.mavero-media' ),
			frame = wp.media( {
				title: 'Select image',
				multiple: false,
				library: { type: 'image' }
			} );

		frame.on( 'select', function () {
			var img = frame.state().get( 'selection' ).first().toJSON(),
				src = ( img.sizes && img.sizes.thumbnail ) ? img.sizes.thumbnail.url : img.url;

			$wrap.find( '.mavero-media-id' ).val( img.id );
			$wrap.find( '.mavero-media-preview' ).html(
				$( '<img>' ).attr( { src: src, width: 60, height: 60 } )
			);
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.mavero-media-clear', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.mavero-media' );

		$wrap.find( '.mavero-media-id' ).val( 0 );
		$wrap.find( '.mavero-media-preview' ).empty();
	} );
}( jQuery ) );
