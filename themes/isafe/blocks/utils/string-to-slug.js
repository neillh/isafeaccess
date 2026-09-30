/*
 * Convert heading title to slug for id.
 *
 * @param {string} str Heading text
 * @return {string} Slug for id arrtibute.
 */
export const stringToSlug = ( str ) => {
	str = str.replace( /^\s+|\s+$/g, '' ); // trim
	str = str.toLowerCase();

	// remove accents, swap ñ for n, etc
	const from = 'àáäâèéëêìíïîòóöôùúüûñçěščřžýúůďťň·/_,:;';
	const to = 'aaaaeeeeiiiioooouuuuncescrzyuudtn------';

	for ( let i = 0, l = from.length; i < l; i++ ) {
		str = str.replace( new RegExp( from.charAt( i ), 'g' ), to.charAt( i ) );
	}

	str = str.replace( '.', '-' ) // replace a dot by a dash
		.replace( /[^a-z0-9 -]/g, '' ) // remove invalid chars
		.replace( /\s+/g, '-' ) // collapse whitespace and replace by a dash
		.replace( /-+/g, '-' ) // collapse dashes
		.replace( /\//g, '' ); // collapse all forward-slashes

	if ( str.endsWith( '-' ) ) {
		str = str.slice( 0, -1 ); // remove the trailing hyphen
	}

	return str;
};
