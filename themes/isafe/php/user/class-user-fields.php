<?php
/**
 * Mavero\Components\User\User_Fields class
 *
 * @package Mavero
 */

namespace Mavero\Components\User;

use Mavero\Components\Component;
use Mavero\Components\Templater;

/**
 * Class to add support for custom field for user and co-author.
 */
class User_Fields implements Component, Templater {

	const USER_META_NAMES = [
		'role'      => 'byline_role',
		'job_title' => 'job_title',
	];

	const USER_CONTACT_METHODS = [
		'facebook'  => 'facebook',
		'twitter'   => 'twitter',
		'instagram' => 'instagram',
		'linkedin'  => 'linkedin',
	];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_action( 'show_user_profile', [ $this, 'add_user_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'add_user_fields' ] );

		add_action( 'personal_options_update', [ $this, 'update_profile_fields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'update_profile_fields' ] );
		add_action( 'coauthors_guest_author_fields', [ $this, 'coauthors_guest_author_fields' ], 10, 2 );

		add_filter( 'user_contactmethods', [ $this, 'update_user_contact_methods' ], 10, 2 );
	}

	/**
	 * Add custom fields to user profile.
	 *
	 * @return void
	 */
	public function add_user_fields() {

		if ( IS_PROFILE_PAGE ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		} else {
			$user_id = filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT );
		}

		if ( empty( $user_id ) ) {
			return;
		}

		?>
		<div class="mavero-custom-fields">
			<h2><?php echo esc_html__( 'Additional mavero profile fields', 'mavero' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php foreach ( self::get_custom_fields() as $field ) : ?>
					<tr class="user-<?php echo esc_attr( $field['name'] ); ?>-wrap">
						<th>
							<label for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						</th>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( $field['name'] ); ?>"
								id="<?php echo esc_attr( $field['name'] ); ?>"
								value="<?php echo esc_attr( \get_user_meta( $user_id, $field['name'], true ) ); ?>"
								class="regular-text"
							/>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Update profile fields.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function update_profile_fields( $user_id ) {
		foreach ( self::get_custom_fields() as $field ) {
			if ( isset( $_POST[ $field['name'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				\update_user_meta( $user_id, $field['name'], sanitize_text_field( $_POST[ $field['name'] ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}
	}

	/**
	 * Add custom fields to the Co-Authors Plus guest author form.
	 *
	 * @param array $fields Fields.
	 * @param array $groups Queried groups.
	 */
	public function coauthors_guest_author_fields( $fields, $groups ) {
		if ( in_array( 'about', $groups ) || in_array( 'all', $groups ) ) {
			foreach ( self::get_custom_fields() as $custom_field ) {
				$fields[] = [
					'key'   => $custom_field['name'],
					'label' => $custom_field['label'],
					'group' => 'about',
				];
			}
		}

		if ( in_array( 'contact-info', $groups ) || in_array( 'all', $groups ) ) {
			foreach ( self::get_custom_contact_fields() as $contact_field ) {
				$fields[] = [
					'key'   => $contact_field['name'],
					'label' => $contact_field['label'],
					'input' => $contact_field['input'],
					'group' => 'contact-info',
				];
			}
		}

		return $fields;
	}

	/**
	 * Filters the user contact methods.
	 *
	 * @param string[]      $methods Array of contact method labels keyed by contact method.
	 * @param \WP_User|null $user    WP_User object or null if none was provided.
	 */
	public function update_user_contact_methods( $methods, $user ) {
		if ( is_array( $methods ) ) {
			$methods = array_merge( $methods, self::USER_CONTACT_METHODS );
		}

		return $methods;
	}

	/**
	 * Get custom profile fields list.
	 */
	public function get_custom_fields() {
		$default_fields = [
			[
				'name'  => self::USER_META_NAMES['role'],
				'label' => __( 'Byline role', 'mavero' ),
			],
			[
				'name'  => self::USER_META_NAMES['job_title'],
				'label' => __( 'Job title', 'mavero' ),
			],
		];

		return apply_filters( 'mavero_authors_custom_fields', $default_fields );
	}

	/**
	 * Get custom user contact fields list.
	 */
	public function get_custom_contact_fields() {
		$default_fields = [
			[
				'name'  => self::USER_CONTACT_METHODS['facebook'],
				'label' => __( 'Facebook', 'mavero' ),
				'input' => 'url',
			],
			[
				'name'  => self::USER_CONTACT_METHODS['twitter'],
				'label' => __( 'Twitter', 'mavero' ),
				'input' => 'url',
			],
			[
				'name'  => self::USER_CONTACT_METHODS['linkedin'],
				'label' => __( 'Linkedin', 'mavero' ),
				'input' => 'url',
			],
			[
				'name'  => self::USER_CONTACT_METHODS['instagram'],
				'label' => __( 'Instagram', 'mavero' ),
				'input' => 'url',
			],
		];

		return apply_filters( 'mavero_authors_custom_contact_fields', $default_fields );
	}

	/**
	 * Get template tags.
	 *
	 * @return array[]
	 */
	public function get_template_tags(): array {
		return [
			'get_user_custom_field' => [ $this, 'get_user_custom_field' ],
		];
	}

	/**
	 * Get all custom fields for author profile.
	 *
	 * @return string[]
	 */
	protected function get_all_custom_fields() {
		return array_merge( self::USER_META_NAMES, self::USER_CONTACT_METHODS );
	}

	/**
	 * Get user custom data.
	 *
	 * @param string $field   Custom field slug.
	 * @param null   $user_id Author id, if not set, will try to get it from the post using co-author.
	 * @param bool   $is_user Is user or guest author.
	 *
	 * @return string
	 */
	public function get_user_custom_field( $field, $user_id = null, $is_user = true ) {
		$fields = $this->get_all_custom_fields();

		if ( ! array_key_exists( $field, $fields ) ) {
			return '';
		}

		if ( ! $user_id && function_exists( 'get_coauthors' ) ) {
			$co_authors = get_coauthors( get_the_ID() );
			if ( ! empty( $co_authors ) ) {
				$is_user = $co_authors[0] instanceof \WP_User;
				$user_id = $co_authors[0]->ID;
			}
		}
		if ( $is_user ) {
			return \get_user_meta( $user_id, $fields[ $field ], true );
		} else {
			return \get_post_meta( $user_id, 'cap-' . $fields[ $field ], true );
		}
	}
}
