<?php
/**
 * This file helps to generate the json object to register icon collection from svg files.
 *
 * Put all svg files in svg directory and run file with `php generate-svg-json.php | pbcopy` and paste the json in a new file and reformat.
 *
 * For example: See icons.js
 *
 * @package Express
 */

// File is to be run from command line.
if ( php_sapi_name() !== 'cli' ) {
	exit;
}

/*
 * Note this is standalone script.
 */
/**
 * Convert pascal case to space.
 *
 * @param string $text String to convert.
 *
 * @return string Converted string.
 */
function convert_pascal_case_to_space( $text ) {
	$text = preg_replace( '/([a-z])([A-Z])/s', '$1 $2', $text );
	return ucwords( strtolower( $text ) );
}

$object = [];

$svg_files = glob( __DIR__ . '/svg/*.svg' );

foreach ( $svg_files as $file ) {
	$svg_file = basename( $file );
	if ( preg_match( '/\.svg$/', $svg_file ) ) {
		$filename  = str_replace( '.svg', '', $svg_file );
		$icon_name = str_replace( 'icon-phosphor-', '', $filename );
		$label     = convert_pascal_case_to_space( $icon_name );
		$object[]  = [
			'name'   => $icon_name,
			'label'  => $label,
			'source' => file_get_contents( $file ), // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		];
	}
}

echo json_encode( $object ); // phpcs:ignore
exit;
