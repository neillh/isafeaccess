<?php
/**
 * File_Upload_Types class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Custom Redirects.
 */
class File_Upload_Types implements Component {

	const ALLOWED_TYPES = [
		'tif'  => 'image/tiff',
		'tiff' => 'image/tiff',
	];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		// phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.upload_mimes -- Mimes manually reviewed.
		add_filter( 'upload_mimes', [ $this, 'add_mime_types' ] );
	}

	/**
	 * Add mime types.
	 *
	 * @param array $mimes Mime types keyed by the file extension regex corresponding to those types.
	 * @return array
	 */
	public function add_mime_types( $mimes ) {
		return array_merge( $mimes, self::ALLOWED_TYPES );
	}
}
