<?php
/**
 * Mavero\Components\Mega_Menu class
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for integrating the `mavero/mega-menu` block with core's Navigation block.
 *
 * The Navigation block declares a fixed `allowedBlocks` list in its `block.json`,
 * which prevents third-party blocks from being inserted into a menu. Since that
 * list travels through the `block_type_metadata` filter we can append to it.
 *
 * This can't live in `blocks/mega-menu/init.php` because Block_Registry requires
 * those files on `init` priority 999, long after core has registered its blocks.
 */
class Mega_Menu implements Component {

	/**
	 * The block to make insertable inside core/navigation.
	 *
	 * @var string
	 */
	const BLOCK_NAME = 'mavero/mega-menu';

	/**
	 * Server-only block that stands in for a Navigation block nested in a mega menu panel.
	 *
	 * @var string
	 */
	const NAVIGATION_PROXY_BLOCK_NAME = 'mavero/mega-menu-navigation';

	/**
	 * Navigation menu keys currently being rendered through the proxy, to stop menus that reference each other looping forever.
	 *
	 * @var array
	 */
	protected $rendering_navigations = [];

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function init() {
		add_filter( 'block_type_metadata', [ $this, 'filter_navigation_allowed_blocks' ] );
		add_action( 'init', [ $this, 'action_register_navigation_proxy_block' ] );
		add_filter( 'block_core_navigation_render_inner_blocks', [ $this, 'filter_proxy_nested_navigation_blocks' ] );
	}

	/**
	 * Append the mega menu block to core/navigation's allowed inner blocks.
	 *
	 * @param array $metadata Metadata for registering a block type.
	 *
	 * @return array Modified metadata.
	 */
	public function filter_navigation_allowed_blocks( $metadata ) {
		if ( 'core/navigation' !== ( $metadata['name'] ?? '' ) ) {
			return $metadata;
		}

		// Bail if core ever drops the restriction — with no list, everything is allowed.
		if ( empty( $metadata['allowedBlocks'] ) || ! is_array( $metadata['allowedBlocks'] ) ) {
			return $metadata;
		}

		if ( ! in_array( self::BLOCK_NAME, $metadata['allowedBlocks'], true ) ) {
			$metadata['allowedBlocks'][] = self::BLOCK_NAME;
		}

		return $metadata;
	}

	/**
	 * Register the block that renders Navigation blocks nested in a mega menu panel.
	 *
	 * It's never saved to content, only swapped in at render time by
	 * filter_proxy_nested_navigation_blocks(), so it's hidden from the inserter.
	 */
	public function action_register_navigation_proxy_block() {
		register_block_type(
			self::NAVIGATION_PROXY_BLOCK_NAME,
			[
				'attributes'      => [
					'block' => [
						'type' => 'object',
					],
				],
				'supports'        => [
					'inserter' => false,
					'html'     => false,
				],
				'render_callback' => [ $this, 'render_navigation_proxy_block' ],
			]
		);
	}

	/**
	 * Hide Navigation blocks nested in mega menu panels from the parent Navigation block.
	 *
	 * Core's Navigation block renders nothing at all if a `core/navigation` block
	 * appears anywhere in its inner block tree (a guard against menus that include
	 * themselves), so a menu inside a mega menu panel would blank the whole parent
	 * menu. Swapping each nested Navigation block for a proxy block keeps it out of
	 * that tree check; the proxy then renders the original block in place.
	 *
	 * @param \WP_Block_List $inner_blocks Navigation block menu items.
	 *
	 * @return \WP_Block_List Menu items with nested Navigation blocks proxied.
	 */
	public function filter_proxy_nested_navigation_blocks( $inner_blocks ) {
		if ( ! $inner_blocks instanceof \WP_Block_List ) {
			return $inner_blocks;
		}

		foreach ( $inner_blocks as $index => $inner_block ) {
			if ( self::BLOCK_NAME !== $inner_block->name ) {
				continue;
			}

			$parsed_block = $this->proxy_navigation_blocks( $inner_block->parsed_block );

			// Set the parsed block (not a WP_Block) so the list rebuilds it with its own context.
			if ( $parsed_block !== $inner_block->parsed_block ) {
				$inner_blocks[ $index ] = $parsed_block;
			}
		}

		return $inner_blocks;
	}

	/**
	 * Recursively replace `core/navigation` blocks with the proxy block.
	 *
	 * @param array $parsed_block Parsed block.
	 *
	 * @return array Parsed block with nested Navigation blocks replaced.
	 */
	protected function proxy_navigation_blocks( array $parsed_block ): array {
		if ( empty( $parsed_block['innerBlocks'] ) ) {
			return $parsed_block;
		}

		foreach ( $parsed_block['innerBlocks'] as $key => $child ) {
			if ( 'core/navigation' === $child['blockName'] ) {
				// Replaced one-for-one so the parent's innerContent placeholders still line up.
				$parsed_block['innerBlocks'][ $key ] = [
					'blockName'    => self::NAVIGATION_PROXY_BLOCK_NAME,
					'attrs'        => [ 'block' => $child ],
					'innerBlocks'  => [],
					'innerHTML'    => '',
					'innerContent' => [],
				];
			} else {
				$parsed_block['innerBlocks'][ $key ] = $this->proxy_navigation_blocks( $child );
			}
		}

		return $parsed_block;
	}

	/**
	 * Render the original Navigation block held by the proxy block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Rendered Navigation block.
	 */
	public function render_navigation_proxy_block( $attributes ): string {
		if ( empty( $attributes['block'] ) || ! is_array( $attributes['block'] ) ) {
			return '';
		}

		$navigation_block = $attributes['block'];

		// Menus are keyed by their `ref`, or by their markup when the items are stored inline.
		$key = ! empty( $navigation_block['attrs']['ref'] )
			? 'ref:' . (int) $navigation_block['attrs']['ref']
			: 'inline:' . md5( serialize_block( $navigation_block ) );

		// This menu is already being rendered further up, e.g. two menus whose mega menus contain each other.
		if ( isset( $this->rendering_navigations[ $key ] ) ) {
			return '';
		}

		$this->rendering_navigations[ $key ] = true;
		$output                              = render_block( $navigation_block );
		unset( $this->rendering_navigations[ $key ] );

		return $output;
	}
}
