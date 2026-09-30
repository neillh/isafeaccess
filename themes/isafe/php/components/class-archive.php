<?php
/**
 * Archive class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class Archive
 */
class Archive implements Component, Templater {

	/**
	 * Initialize.
	 */
	public function init() {
		add_filter( 'pre_get_posts', [ $this, 'post_types_archives' ] );
	}

	/**
	 * Get post types.
	 *
	 * @return array
	 */
	public function get_post_types() {
		$public_cpts = get_post_types( [ 'publicly_queryable' => true ] );

		return array_diff( $public_cpts, [ 'attachment', 'page' ] );
	}

	/**
	 * Extend archive to include all post types.
	 *
	 * @param \WP_Query $query query.
	 *
	 * @return \WP_Query
	 */
	public function post_types_archives( $query ) {
		if ( ! is_admin() && ( $query->is_category() || is_tag() ) && $query->is_main_query() ) {
			$query->set( 'post_type', $this->get_post_types() );
		}

		return $query;
	}

	/**
	 * Get template tags.
	 *
	 * @return array
	 */
	public function get_template_tags() {
		return [
			'get_the_archive_post_types' => [ $this, 'get_post_types' ],
		];
	}
}
