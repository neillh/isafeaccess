<?php
/**
 * Mavero\Components\User\Authors class
 *
 * @package Mavero
 */

namespace Mavero\Components\User;

use Mavero\Components\Component;
use Mavero\Components\Templater;

/**
 * Helper Class for Authors.
 */
class Authors implements Component, Templater {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_filter( 'get_avatar', [ $this, 'filter_get_avatar' ], 10, 6 );
	}

	/**
	 * Get template tags.
	 *
	 * @return array[]
	 */
	public function get_template_tags() {
		return [
			'get_authors_details' => [ $this, 'get_authors_details' ],
		];
	}

	/**
	 * Filter the avatar for default avatars where id_or_email is empty.
	 *
	 * Note: This is temporary until https://secure.gravatar.com/avatar/?s=32&d=blank&r=g is 404.
	 *
	 * @param string          $avatar      Image tag for the user's avatar.
	 * @param int|string|bool $id_or_email A user ID, email address, or comment object.
	 * @param int             $size        Square avatar width and height in pixels to retrieve.
	 * @param string          $default_img URL to a default image to use if no avatar is available.
	 * @param string          $alt         Alternative text to use in the avatar image tag.
	 * @param array           $args        Arguments passed to get_avatar_data(), after processing.
	 *
	 * @return string
	 */
	public function filter_get_avatar( $avatar, $id_or_email, $size, $default_img, $alt, $args ) {
		if ( ! empty( $id_or_email ) ) {
			return $avatar;
		}
		$class = [ 'avatar', 'avatar-' . (int) $args['size'], 'photo', 'avatar-default' ];

		if ( $args['class'] ) {
			if ( is_array( $args['class'] ) ) {
				$class = array_merge( $class, $args['class'] );
			} else {
				$class[] = $args['class'];
			}
		}

		// Get fallback image.
		$url = sprintf(
			'https://secure.gravatar.com/avatar/%s?d=%s&s=%d',
			md5( strtolower( trim( $id_or_email ) ) ),
			urlencode( $default_img ),
			(int) $args['size']
		);

		$avatar = sprintf(
			"<img alt='%s' src='%s' class='%s' height='%d' width='%d'/>",
			esc_attr( $alt ),
			esc_url( $url ),
			esc_attr( implode( ' ', $class ) ),
			(int) $args['height'],
			(int) $args['width']
		);

		return $avatar;
	}

	/**
	 * Get the Author data.
	 *
	 * @access public
	 *
	 * @param int $author_id The author whose data is being collected.
	 *
	 * @return array List of author data if any, otherwise empty.
	 */
	public function get_authors_details( $author_id ) {

		global $coauthors_plus;

		if ( ! method_exists( $coauthors_plus, 'get_coauthor_by' ) ) {
			return [];
		}

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $author_id );
		$account  = $coauthor->linked_account ? get_user_by( 'login', $coauthor->linked_account ) : false;

		$user_id = $account ? $account->ID : '';
		$is_user = 'guest-author' !== $coauthor->type;
		$alt     = sprintf(
			/* translators: %s: Author display name. */
			__( 'Profile picture of %s', 'mavero' ),
			$coauthor->display_name
		);

		$author_data = [
			'user_id'      => $user_id,
			'coauthor_id'  => $coauthor->ID,
			'description'  => $coauthor->description,
			'first_name'   => $coauthor->first_name,
			'last_name'    => $coauthor->last_name,
			'user_email'   => $coauthor->user_email,
			'display_name' => $coauthor->display_name,
			'byline_role'  => xwp_theme()->get_user_custom_field( 'role', $coauthor->ID, $is_user ),
			'job_title'    => xwp_theme()->get_user_custom_field( 'job_title', $coauthor->ID, $is_user ),
			'avatar_url'   => coauthors_get_avatar( $coauthor, 128, '', $alt ),
			'user_url'     => esc_url( get_author_posts_url( $coauthor->ID, $coauthor->user_nicename ?? '' ) ),
			'social_data'  => [
				'twitter'   => [
					'link'  => xwp_theme()->get_user_custom_field( 'twitter', $coauthor->ID, $is_user ),
					'label' => esc_html__( 'Twitter', 'mavero' ),
				],
				'linkedin'  => [
					'link'  => xwp_theme()->get_user_custom_field( 'linkedin', $coauthor->ID, $is_user ),
					'label' => esc_html__( 'Linkedin', 'mavero' ),
				],
				'instagram' => [
					'link'  => xwp_theme()->get_user_custom_field( 'instagram', $coauthor->ID, $is_user ),
					'label' => esc_html__( 'Instagram', 'mavero' ),
				],
				'facebook'  => [
					'link'  => xwp_theme()->get_user_custom_field( 'facebook', $coauthor->ID, $is_user ),
					'label' => esc_html__( 'Facebook', 'mavero' ),
				],
			],
		];

		return $author_data;
	}
}
