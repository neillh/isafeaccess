<?php
/**
 * Post Type Project.
 *
 * Case studies of completed work. Each project can be tagged with several
 * industries (see Taxonomies\Industry); archives live at /projects/ and
 * /industries/{term}/.
 *
 * @package Mavero
 */

namespace Mavero\Components\Post_Types;

use Mavero\Components\Component;
use Mavero\Components\Taxonomies\Industry;

/**
 * Project Post Type.
 */
class Project implements Component {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'project';

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! function_exists( 'register_extended_post_type' ) ) {
			add_action( 'admin_notices', [ $this, 'missing_dependency_notice' ] );
			return;
		}

		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$admin_cols    = [
			'featured_image' => [
				'title'          => __( 'Image', 'mavero' ),
				'featured_image' => 'thumbnail',
				'width'          => 60,
				'height'         => 60,
			],
			'title',
			'industry'       => [
				'taxonomy' => Industry::TAXONOMY,
			],
			'date'           => [
				'title'   => __( 'Date', 'mavero' ),
				'default' => 'DESC',
			],
		];
		$admin_cols    = apply_filters( 'mavero_admin_column_project', $admin_cols );
		$admin_filters = apply_filters(
			'mavero_admin_filter_project',
			[
				'industry' => [
					'taxonomy' => Industry::TAXONOMY,
				],
			]
		);

		register_extended_post_type(
			self::POST_TYPE,
			[
				'admin_filters' => $admin_filters,
				'admin_cols'    => $admin_cols,
				'show_in_rest'  => true,
				'hierarchical'  => false,
				'menu_icon'     => 'dashicons-portfolio',
				'menu_position' => 24,
				'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ],
			],
			[
				'singular' => __( 'Project', 'mavero' ),
				'plural'   => __( 'Projects', 'mavero' ),
				'slug'     => 'projects',
			],
		);
	}

	/**
	 * Warn when Composer dependencies haven't been installed.
	 *
	 * @return void
	 */
	public function missing_dependency_notice() {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Projects are unavailable: run `composer install --no-dev` in the theme directory to install johnbillion/extended-cpts.', 'mavero' )
		);
	}
}
