<?php
/**
 * Optional Review schema for the site's testimonials.
 *
 * Re-emits testimonials inside Yoast's graph, attached to `#organization`, on a
 * chosen set of pages. Testimonials are the `isatc_testimonial` post type, now
 * registered by the iSafe theme (Mavero\Components\Post_Types\Testimonial) —
 * the post type and `isatc_*` meta keys kept the names of the retired ISA
 * Testimonial Carousel plugin so existing testimonials carried straight over.
 *
 * The post type is queried directly rather than tracking which testimonials a
 * carousel block rendered: blocks render the page body after wp_head, where
 * Yoast has already built and printed its graph.
 *
 * On the schema itself: reviews a business publishes about itself on its own
 * site are not eligible for Google review rich results, and an AggregateRating
 * over them is a guidelines problem rather than a win. This feature ships off by
 * default and never generates an AggregateRating. It exists because the markup
 * still carries meaning for AI and LLM retrieval — which is a deliberate choice
 * to make with open eyes, not a default to inherit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mavero_Reviews {

	const POST_TYPE = 'isatc_testimonial';

	public static function init() {
		add_filter( 'wpseo_schema_graph_pieces', array( __CLASS__, 'add_reviews' ), 12, 2 );
	}

	/**
	 * Add Review nodes to Yoast's graph on the configured pages.
	 *
	 * @param array $pieces
	 * @param mixed $context
	 * @return array
	 */
	public static function add_reviews( $pieces, $context ) {
		if ( ! Mavero_Pieces::ensure_piece_class() ) {
			return $pieces;
		}

		$nodes = self::review_nodes();

		if ( empty( $nodes ) ) {
			return $pieces;
		}

		$pieces[] = new Mavero_Graph_Piece( $context, $nodes, 'mavero_reviews' );

		return $pieces;
	}

	/**
	 * @return array
	 */
	public static function review_nodes() {
		$settings = Mavero_Settings::get( 'reviews' );

		if ( empty( $settings['takeover'] ) ) {
			return array();
		}

		$current = Mavero_Pieces::current_page_id();

		if ( $current <= 0 || ! in_array( $current, array_map( 'intval', (array) $settings['pages'] ), true ) ) {
			return array();
		}

		if ( ! post_type_exists( self::POST_TYPE ) ) {
			return array();
		}

		$max = (int) $settings['max'];

		$posts = get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => ( $max > 0 ) ? $max : -1,
				'orderby'          => 'menu_order date',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		$org_id = mavero_schema_org_id();
		$out    = array();

		foreach ( $posts as $post ) {
			$node = self::build_review( $post, $org_id, ! empty( $settings['allow_org_author'] ) );

			if ( $node ) {
				$out[] = $node;
			}
		}

		return $out;
	}

	/**
	 * Build one Review node.
	 *
	 * @param WP_Post $post
	 * @param string  $org_id           @id of the reviewed business.
	 * @param bool    $allow_org_author Accept a company name when no person is named.
	 * @return array|null
	 */
	private static function build_review( $post, $org_id, $allow_org_author ) {
		$person   = trim( (string) get_post_meta( $post->ID, 'isatc_client_name', true ) );
		$business = trim( (string) get_post_meta( $post->ID, 'isatc_client_business', true ) );

		// A Review with no author is not valid structured data. Testimonials
		// credited only to a company are kept as Organization authors, which is
		// a legitimate author type.
		if ( '' !== $person ) {
			$author = array(
				'@type' => 'Person',
				'name'  => $person,
			);
		} elseif ( $allow_org_author && '' !== $business ) {
			$author = array(
				'@type' => 'Organization',
				'name'  => $business,
			);
		} else {
			return null;
		}

		if ( '' !== $person && '' !== $business ) {
			$author['affiliation'] = array(
				'@type' => 'Organization',
				'name'  => $business,
			);
		}

		$node = array(
			'@type'        => 'Review',
			'@id'          => trailingslashit( home_url( '/' ) ) . '#/schema/review/' . $post->ID,
			'author'       => $author,
			'itemReviewed' => array( '@id' => $org_id ),
		);

		$body = self::plain_content( $post );

		if ( '' !== $body ) {
			$node['reviewBody'] = $body;
		}

		$rating = (int) get_post_meta( $post->ID, 'isatc_rating', true );

		// Never invent a rating: only emit one that was actually recorded.
		if ( $rating > 0 ) {
			$node['reviewRating'] = array(
				'@type'       => 'Rating',
				'ratingValue' => $rating,
				'bestRating'  => 5,
				'worstRating' => 1,
			);
		}

		$date = get_post_time( 'c', true, $post );

		if ( $date ) {
			$node['datePublished'] = $date;
		}

		return apply_filters( 'mavero_schema_review', $node, $post );
	}

	/**
	 * Plain-text testimonial body, reusing the theme's own accessor when present.
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	private static function plain_content( $post ) {
		$theme = 'Mavero\\Components\\Post_Types\\Testimonial';
		$text  = is_callable( array( $theme, 'get_plain_content' ) )
			? $theme::get_plain_content( $post )
			: $post->post_content;

		return trim( wp_strip_all_tags( strip_shortcodes( $text ) ) );
	}

	/**
	 * Count published testimonials, for the admin screen.
	 *
	 * @return int
	 */
	public static function count() {
		if ( ! post_type_exists( self::POST_TYPE ) ) {
			return 0;
		}

		$counts = wp_count_posts( self::POST_TYPE );

		return isset( $counts->publish ) ? (int) $counts->publish : 0;
	}
}
