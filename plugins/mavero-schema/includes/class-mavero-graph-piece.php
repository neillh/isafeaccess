<?php
/**
 * A generic Yoast graph piece that emits a pre-built list of nodes.
 *
 * This file is loaded lazily by Mavero_Pieces::ensure_piece_class(), never from
 * the plugin bootstrap. That matters: WordPress loads active plugins in the
 * order stored in the `active_plugins` option, and `mavero-schema` sorts before
 * `wordpress-seo`. At the moment this plugin's main file is parsed, Yoast's
 * autoloader is not registered yet, so extending its abstract class at that
 * point would silently fail and every graph piece would go missing.
 *
 * By the time Yoast applies `wpseo_schema_graph_pieces` during wp_head, the
 * abstract is available, which is when this class gets declared.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' ) ) {
	return;
}

class Mavero_Graph_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {

	/**
	 * @var array List of finished schema nodes.
	 */
	private $nodes;

	/**
	 * @param mixed  $context    Yoast Meta_Tags_Context.
	 * @param array  $nodes      Finished nodes to emit.
	 * @param string $identifier Unique identifier for this piece. Yoast keys the
	 *                           pieces it generates by identifier, so two pieces
	 *                           sharing one would overwrite each other.
	 */
	public function __construct( $context, array $nodes, $identifier ) {
		$this->context    = $context;
		$this->nodes      = $nodes;
		$this->identifier = $identifier;
	}

	public function is_needed() {
		return ! empty( $this->nodes );
	}

	public function generate() {
		return $this->nodes;
	}
}
