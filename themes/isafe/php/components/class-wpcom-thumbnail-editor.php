<?php
/**
 * Mavero\Components\WPCom_Thumbnail_Editor class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for managing compatibility with WP.com thumbnail editor.
 *
 * @link https://github.com/Automattic/wpcom-thumbnail-editor
 */
class WPCom_Thumbnail_Editor implements Component {

	/**
	 * Initialize the component.
	 */
	public function init() {
		add_filter( 'wpcom_thumbnail_editor_photon_is_available', [ $this, 'is_photon_available' ] );
		add_filter( 'wpcom_thumbnail_editor_preview_args', [ $this, 'filter_thumbnail_editor_preview_image' ], 10, 3 );
		add_filter( 'wpcom_thumbnail_editor_args', [ $this, 'filter_only_aspect_ratios' ] );
		add_filter( 'image_get_intermediate_size', [ $this, 'filter_image_intermediate_size_thumbnail_editor' ], 10, 3 );
	}

	/**
	 * Filter photos availability status.
	 *
	 * @return bool
	 */
	public function is_photon_available() {
		if ( defined( 'JETPACK_DEV_DEBUG' ) && true === JETPACK_DEV_DEBUG ) {
			return false;
		}

		return function_exists( 'jetpack_photon_url' );
	}

	/**
	 * Filter the image size for the thumbnail editor preview.
	 *
	 * @param array  $args          Arguments.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Image size.
	 *
	 * @return array
	 */
	public function filter_thumbnail_editor_preview_image( $args, $attachment_id, $size ) {
		global $_wp_additional_image_sizes;

		$size_info      = $_wp_additional_image_sizes[ $size ] ?? [];
		$filtered_sizes = [];

		if ( empty( $size_info ) ) {
			return $args;
		}

		// Find the first matching size in array of sizes.
		foreach ( Post_Thumbnails::POST_THUMBNAIL_CCB_IMAGE_SIZES as $aspect_ratio_sizes ) {
			if ( array_key_exists( $size, $aspect_ratio_sizes ) ) {
				// Replace size_info with the middle item of aspect_ratio_sizes.
				$values = array_values( $aspect_ratio_sizes );

				// Sort the sizes array by width in descending order.
				usort(
					$values,
					function ( $a, $b ) {
						return $b['width'] <=> $a['width'];
					}
				);

				// Remove the sizes which are greater than 1000px to avoid the image overflowing the screen size.
				$values = array_filter(
					$values,
					function ( $item ) {
						return $item['width'] <= 1000;
					}
				);

				$values         = array_values( $values );
				$filtered_sizes = $values[ array_key_first( $values ) ] ?? [];

				break;
			}
		}

		if ( ! empty( $filtered_sizes ) ) {
			$size_info = $filtered_sizes;
		}

		return [
			'resize' => [
				$size_info['width'],
				$size_info['height'],
			],
			'fit'    => [
				400,
				400,
			],
		];
	}

	/**
	 * Filter the aspect ratios for the thumbnail editor.
	 *
	 * @param array $args Arguments.
	 *
	 * @return array
	 */
	public function filter_only_aspect_ratios( $args ) {
		// Generate array of aspect ratio and size names, remove nested array of each size.
		// Sort the image sizes in a way that image width is less than 1000px comes first.
		// This is so that the image does not overflow the screen size.
		$args['image_ratio_map'] = array_map(
			function ( $sizes ) {
				$keys = array_keys( $sizes );

				usort(
					$keys,
					function ( $a, $b ) use ( $sizes ) {
						$width_a = $sizes[ $a ]['width'];
						$width_b = $sizes[ $b ]['width'];

						// If both are over 1000 or both are under 1000, sort by value.
						if ( ( $width_a > 1000 && $width_b > 1000 ) || ( $width_a <= 1000 && $width_b <= 1000 ) ) {
							return $width_a <=> $width_b;
						}

						// If A is over 1000 and B is under, A comes later.
						if ( $width_a > 1000 && $width_b <= 1000 ) {
							return 1;
						}

						// If B is over 1000 and A is under, B comes later.
						if ( $width_b > 1000 && $width_a <= 1000 ) {
							return -1;
						}
					}
				);

				return $keys;
			},
			Post_Thumbnails::POST_THUMBNAIL_CCB_IMAGE_SIZES
		);

		return $args;
	}

	/**
	 * Thumbnail editor loads image with 1024x1024 size in the editor screen.
	 * This causes the image to be not loaded in proportion to the aspect ratio if we have higher image sizes available.
	 *
	 * This filter will load the full size image in the editor screen.
	 *
	 * @param array  $data    Array of image data.
	 * @param int    $post_id Post ID.
	 * @param string $size    Image size.
	 *
	 * @return array
	 */
	public function filter_image_intermediate_size_thumbnail_editor( $data, $post_id, $size ) {
		if ( ! is_admin() || ! is_array( $size ) ) {
			return $data;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is not required here.

		if ( 'wpcom_thumbnail_edit' !== $action ) {
			return $data;
		}

		// Even though infinite loop will not happen since we are returning early if the size is not array.
		// We are adding this for safety.
		remove_filter( 'image_get_intermediate_size', [ $this, 'filter_image_intermediate_size_thumbnail_editor' ], 10 );

		return image_get_intermediate_size( $post_id, 'full' );
	}
}
