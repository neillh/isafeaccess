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
	 * Position meta key (e.g. "Director").
	 *
	 * Edited in the Team Member sidebar panel (js/modules/_team-member-meta.js)
	 * and displayed by the "Team Position" paragraph variation.
	 *
	 * @var string
	 */
	const META_POSITION = 'team_position';

	/**
	 * LinkedIn profile URL meta key. Output as Person `sameAs`.
	 *
	 * @var string
	 */
	const META_LINKEDIN = 'team_linkedin';

	/**
	 * Credentials meta key: list of { name, issuer }. Output as Person `hasCredential`.
	 *
	 * @var string
	 */
	const META_CREDENTIALS = 'team_credentials';

	/**
	 * Areas of expertise meta key: list of strings. Output as Person `knowsAbout`.
	 *
	 * @var string
	 */
	const META_KNOWS_ABOUT = 'team_knows_about';

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
		add_filter( 'get_block_type_variations', [ $this, 'register_position_variation' ], 10, 2 );
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
			[ 'custom-fields', 'thumbnail', 'title', 'editor', 'excerpt' ],
		);
	}

	/**
	 * Register Team Member meta, exposed to REST for the editor and block bindings.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$auth_callback = function ( $allowed, $meta_key, $post_id ) {
			return current_user_can( 'edit_post', $post_id );
		};

		register_post_meta(
			'team',
			self::META_POSITION,
			[
				'label'             => __( 'Position', 'mavero' ),
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => $auth_callback,
			]
		);

		register_post_meta(
			'team',
			self::META_LINKEDIN,
			[
				'label'             => __( 'LinkedIn URL', 'mavero' ),
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => $auth_callback,
			]
		);

		register_post_meta(
			'team',
			self::META_CREDENTIALS,
			[
				'label'             => __( 'Credentials', 'mavero' ),
				'type'              => 'array',
				'single'            => true,
				'default'           => [],
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => [
								'name'   => [ 'type' => 'string' ],
								'issuer' => [ 'type' => 'string' ],
							],
						],
					],
				],
				'sanitize_callback' => [ $this, 'sanitize_credentials' ],
				'auth_callback'     => $auth_callback,
			]
		);

		register_post_meta(
			'team',
			self::META_KNOWS_ABOUT,
			[
				'label'             => __( 'Areas of expertise', 'mavero' ),
				'type'              => 'array',
				'single'            => true,
				'default'           => [],
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [ 'type' => 'string' ],
					],
				],
				'sanitize_callback' => [ $this, 'sanitize_knows_about' ],
				'auth_callback'     => $auth_callback,
			]
		);
	}

	/**
	 * Sanitize credentials, dropping rows without a name.
	 *
	 * @param mixed $value Raw meta value.
	 *
	 * @return array List of [ 'name' => string, 'issuer' => string ].
	 */
	public function sanitize_credentials( $value ): array {
		$credentials = [];

		foreach ( (array) $value as $row ) {
			$name = sanitize_text_field( $row['name'] ?? '' );

			if ( '' === $name ) {
				continue;
			}

			$credentials[] = [
				'name'   => $name,
				'issuer' => sanitize_text_field( $row['issuer'] ?? '' ),
			];
		}

		return $credentials;
	}

	/**
	 * Sanitize areas of expertise, dropping empty and duplicate entries.
	 *
	 * @param mixed $value Raw meta value.
	 *
	 * @return string[]
	 */
	public function sanitize_knows_about( $value ): array {
		$topics = array_map( 'sanitize_text_field', array_filter( (array) $value, 'is_string' ) );

		return array_values( array_unique( array_filter( $topics ) ) );
	}

	/**
	 * Add a "Team Position" variation of core/paragraph bound to the position meta.
	 *
	 * Being a paragraph, it keeps all the core paragraph styling controls.
	 *
	 * @param array          $variations Registered variations.
	 * @param \WP_Block_Type $block_type Block type.
	 *
	 * @return array
	 */
	public function register_position_variation( $variations, $block_type ): array {
		if ( 'core/paragraph' !== $block_type->name ) {
			return $variations;
		}

		$variations[] = [
			'name'        => 'team-position',
			'title'       => __( 'Team Position', 'mavero' ),
			'description' => __( 'Displays the team member\'s position.', 'mavero' ),
			'category'    => 'mavero-blocks',
			'icon'        => 'id',
			'keywords'    => [ 'team', 'position', 'job title', 'role' ],
			'scope'       => [ 'inserter', 'transform' ],
			'attributes'  => [
				'placeholder' => __( 'Position', 'mavero' ),
				'metadata'    => [
					'name'     => __( 'Team Position', 'mavero' ),
					'bindings' => [
						'content' => [
							'source' => 'core/post-meta',
							'args'   => [ 'key' => self::META_POSITION ],
						],
					],
				],
			],
			'isActive'    => [ 'metadata.bindings.content.args.key' ],
		];

		return $variations;
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

		$post      = $context->post;
		$permalink = get_permalink( $post );
		$person_id = $permalink . '#person';

		$person = [
			'@type' => 'Person',
			'@id'   => $person_id,
			'name'  => get_the_title( $post ),
			'url'   => $permalink,
		];

		$position = get_post_meta( $post->ID, self::META_POSITION, true );

		if ( $position ) {
			$person['jobTitle'] = $position;
		}

		// Manual excerpt if set, otherwise the start of the bio (without the "[…]" more string).
		$description = has_excerpt( $post )
			? wp_strip_all_tags( $post->post_excerpt )
			: wp_trim_words( excerpt_remove_blocks( strip_shortcodes( $post->post_content ) ), 55, '…' );

		if ( $description ) {
			$person['description'] = $description;
		}

		$linkedin = get_post_meta( $post->ID, self::META_LINKEDIN, true );

		if ( $linkedin ) {
			$person['sameAs'] = [ $linkedin ];
		}

		$credentials = [];

		foreach ( (array) get_post_meta( $post->ID, self::META_CREDENTIALS, true ) as $row ) {
			if ( empty( $row['name'] ) ) {
				continue;
			}

			$credential = [
				'@type' => 'EducationalOccupationalCredential',
				'name'  => $row['name'],
			];

			if ( ! empty( $row['issuer'] ) ) {
				$credential['recognizedBy'] = [
					'@type' => 'Organization',
					'name'  => $row['issuer'],
				];
			}

			$credentials[] = $credential;
		}

		if ( $credentials ) {
			$person['hasCredential'] = $credentials;
		}

		$knows_about = array_values( array_filter( (array) get_post_meta( $post->ID, self::META_KNOWS_ABOUT, true ) ) );

		if ( $knows_about ) {
			$person['knowsAbout'] = $knows_about;
		}

		// Yoast outputs the featured image as the page's primary image; reference it.
		if ( has_post_thumbnail( $post ) && class_exists( '\Yoast\WP\SEO\Config\Schema_IDs' ) ) {
			$person['image'] = [
				'@id' => $context->canonical . \Yoast\WP\SEO\Config\Schema_IDs::PRIMARY_IMAGE_HASH,
			];
		}

		// Tie the person to the Organization node Yoast already outputs.
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
