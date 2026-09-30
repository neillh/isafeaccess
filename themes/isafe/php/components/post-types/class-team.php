<?php
/**
 * Post Type Team.
 *
 * @package Mavero
 */

namespace Mavero\Components\Post_Types;

use Mavero\Components\Component;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Team Post Type.
 */
class Team implements Component {

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_filter( 'manage_team_posts_columns', [ $this, 'add_thumbnail_column' ] );
		add_action( 'manage_team_posts_custom_column', [ $this, 'render_thumbnail_column' ], 10, 2 );
		add_action( 'admin_head', [ $this, 'column_styles' ] );

		if ( defined( 'WPSEO_VERSION' ) ) {
			add_filter( 'wpseo_schema_webpage', [ $this, 'filter_webpage_schema' ], 10, 2 );
			add_filter( 'wpseo_schema_graph', [ $this, 'filter_schema_graph' ], 10, 2 );
		}
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$admin_cols    = [
			'date' => [
				'title'   => __( 'Date', 'mavero' ),
				'default' => 'DESC',
			],
		];
		$admin_cols    = apply_filters( 'mavero_admin_column_team', $admin_cols );
		$admin_filters = apply_filters( 'mavero_admin_filter_team', [] );

		register_extended_post_type(
			'team',
			[
				'admin_filters' => $admin_filters,
				'admin_cols'    => $admin_cols,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-groups',
			],
			[
				'singular' => __( 'Team Member', 'mavero' ),
				'plural'   => __( 'Team Members', 'mavero' ),
				'slug'     => 'team',
			],
		);

		add_post_type_support(
			'team',
			[ 'custom-fields', 'thumbnail', 'title', 'editor' ],
		);
	}

    /**
     * Add a thumbnail column to posts
     */
    public function add_thumbnail_column( $columns ) {
		unset( $columns['comments'], $columns['author'], $columns['tags'] );
		$new = [ 'post_thumbnail' => __( 'Thumbnail' ) ];
		return $new + $columns;
    }

    /**
     * Render thumbnail or grey placeholder
     */
    public function render_thumbnail_column( $column, $post_id ) {
        if ( $column === 'post_thumbnail' ) {
            $thumb = get_the_post_thumbnail( $post_id, [60, 60] );

            if ( $thumb ) {
                echo $thumb;
            } else {
                echo '<div style="width:60px;height:60px;background:#ccc;"></div>';
            }
        }
    }

    /**
     * Add column styles
     */
    public function column_styles() {
        echo '<style>
            .column-post_thumbnail { width: 70px; text-align:center; }
            .column-post_thumbnail img { max-width:60px; height:auto; }
        </style>';
    }

	/**
	 * Make single Team pages a ProfilePage and point mainEntity at our Person node.
	 *
	 * @param array $data
	 * @param \Yoast\WP\SEO\Context\Meta_Tags_Context $context
	 *
	 * @return array
	 */
	public function filter_webpage_schema( $data, $context ) {

		if ( empty( $context->post ) || 'team' !== $context->post->post_type ) {
			return $data;
		}

		// Ensure @type includes ProfilePage.
		$types = isset( $data['@type'] ) ? (array) $data['@type'] : [ 'WebPage' ];
		if ( ! in_array( 'ProfilePage', $types, true ) ) {
			$types[] = 'ProfilePage';
		}
		$data['@type'] = $types;

		// Our custom Person node will use this @id.
		$person_id = get_permalink( $context->post ) . '#person';

		$data['mainEntity'] = [
			'@id' => $person_id,
		];

		// Optional: also say the page is about this person.
		$data['about'] = [
			'@id' => $person_id,
		];

		return $data;
	}

	/**
	 * Inject a Person node into Yoast's graph for Team CPT singles.
	 *
	 * @param array $graph
	 * @param \Yoast\WP\SEO\Context\Meta_Tags_Context $context
	 *
	 * @return array
	 */
	public function filter_schema_graph( $graph, $context ) {

		if ( empty( $context->post ) || 'team' !== $context->post->post_type ) {
			return $graph;
		}

		if ( ! function_exists( 'get_field' ) ) {
			return $graph; // ACF not available.
		}

		$post = $context->post;

		$person_id = get_permalink( $post ) . '#person';

		// ACF fields from "The Team" group on this Team CPT.
		$job_title = get_field( 'job_title', $post->ID );
		$email     = get_field( 'email', $post->ID );
		$phone     = get_field( 'phone', $post->ID );
		$same_as   = get_field( 'sameAs', $post->ID );

		// Collect sameAs URLs from ACF (group or repeater of { site, url }).
		$same_as_urls = [];

		if ( is_array( $same_as ) ) {
			// Repeater: array of rows with 'site' and 'url'.
			if ( isset( $same_as[0] ) && is_array( $same_as[0] ) ) {
				foreach ( $same_as as $row ) {
					if ( ! empty( $row['url'] ) ) {
						$same_as_urls[] = esc_url_raw( $row['url'] );
					}
				}
			}
			// Group: single array with 'site' and 'url'.
			elseif ( isset( $same_as['url'] ) ) {
				$same_as_urls[] = esc_url_raw( $same_as['url'] );
			}
		}

		// Featured image URL for Person image.
		$image_url = get_the_post_thumbnail_url( $post, 'full' );

		// Build the Person node.
		$person = [
			'@type' => 'Person',
			'@id'   => $person_id,
			'name'  => get_the_title( $post ),
		];

		if ( $image_url ) {
			$person['image'] = [
				'@type'  => 'ImageObject',
				'@id'    => $person_id . '#image',
				'url'    => $image_url,
				'caption'=> get_the_title( $post ),
			];
		}

		if ( $job_title ) {
			$person['jobTitle'] = $job_title;
		}

		if ( $email ) {
			$person['email'] = 'mailto:' . sanitize_email( $email );
		}

		if ( $phone ) {
			$person['telephone'] = $phone;
		}

		// Country-only address (always USA).
		$person['address'] = [
			'@type'          => 'PostalAddress',
			'addressCountry' => 'US',
		];

		if ( ! empty( $same_as_urls ) ) {
			$person['sameAs'] = array_values( array_unique( $same_as_urls ) );
		}

		// Tie the person to the Premium & Exotic Organization node Yoast already outputs.
		if ( class_exists( '\Yoast\WP\SEO\Config\Schema_IDs' ) && isset( $context->site_url ) ) {
			$person['worksFor'] = [
				'@id' => $context->site_url . \Yoast\WP\SEO\Config\Schema_IDs::ORGANIZATION_HASH,
			];
		}

		// Append or replace any existing Person node with same @id (safety).
		$found = false;

		foreach ( $graph as $index => $node ) {
			if ( isset( $node['@id'] ) && $node['@id'] === $person_id ) {
				$graph[ $index ] = array_merge( $node, $person );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$graph[] = $person;
		}

		return $graph;
	}

}
