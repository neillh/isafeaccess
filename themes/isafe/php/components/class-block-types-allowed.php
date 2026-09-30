<?php
/**
 * Block_Types_Allowed class.
 *
 * @package Mavero
 */

namespace Mavero\Components;

/**
 * Class for disabling certain block types.
 */
class Block_Types_Allowed implements Component {

	/**
	 * List of allowed WP core block types.
	 *
	 * @var array
	 */
	const ALLOWED_CORE_BLOCK_TYPES = [
		'core/audio'           => 'core/audio',
		'core/block'           => 'core/block',
		'core/button'          => 'core/button',
		'core/buttons'         => 'core/buttons',
		'core/columns'         => 'core/columns',
		'core/column'          => 'core/column',
		'core/content'         => 'core/content',
		'core/cover'           => 'core/cover',
		'core/embed'           => 'core/embed',
		'core/featured-image'  => 'core/featured-image',
		'core/gallery'         => 'core/gallery',
		'core/group'           => 'core/group',
		'core/heading'         => 'core/heading',
		'core/html'            => 'core/html',
		'core/image'           => 'core/image',
		'core/list'            => 'core/list',
		'core/list-item'       => 'core/list-item',
		'core/navigation'      => 'core/navigation',
		'core/navigation-link' => 'core/navigation-link',
		'core/navigation-submenu' => 'core/navigation-submenu',
		'core/navigation-overlay-close' => 'core/navigation-overlay-close',
		'core/navigation-overlay-menu' => 'core/navigation-overlay-menu',
		'core/paragraph'       => 'core/paragraph',
		'core/pattern'         => 'core/pattern',
		'core/pullquote'       => 'core/pullquote',
		'core/query-title'     => 'core/query-title',
		'core/query-total'     => 'core/query-total',
		'core/query'           => 'core/query',
		'core/quote'           => 'core/quote',
		'core/search'          => 'core/search',
		'core/separator'       => 'core/separator',
		'core/video'           => 'core/video',
		'core/reusable-blocks' => 'core/reusable-blocks',
		'core/shortcode'       => 'core/shortcode',
		'core/table'           => 'core/table',
		'core/social-link'     => 'core/social-link',
		'core/social-links'    => 'core/social-links',
		'core/spacer'          => 'core/spacer',
		// Site editing, templates and iSafe home page.
		'core/accordion'                 => 'core/accordion',
		'core/accordion-heading'         => 'core/accordion-heading',
		'core/accordion-item'            => 'core/accordion-item',
		'core/accordion-panel'           => 'core/accordion-panel',
		'core/details'                   => 'core/details',
		'core/media-text'                => 'core/media-text',
		'core/icon'                      => 'core/icon',
		'core/site-logo'                 => 'core/site-logo',
		'core/site-title'                => 'core/site-title',
		'core/site-tagline'              => 'core/site-tagline',
		'core/template-part'             => 'core/template-part',
		'core/post-content'              => 'core/post-content',
		'core/post-title'                => 'core/post-title',
		'core/post-featured-image'       => 'core/post-featured-image',
		'core/post-terms'                => 'core/post-terms',
		'core/post-date'                 => 'core/post-date',
		'core/post-excerpt'              => 'core/post-excerpt',
		'core/post-template'             => 'core/post-template',
		'core/query-pagination'          => 'core/query-pagination',
		'core/query-pagination-next'     => 'core/query-pagination-next',
		'core/query-pagination-previous' => 'core/query-pagination-previous',
		'core/query-pagination-numbers'  => 'core/query-pagination-numbers',
		'core/query-no-results'          => 'core/query-no-results',
		'core/term-description'          => 'core/term-description',
		'core/home-link'                 => 'core/home-link',
		'core/page-list'                 => 'core/page-list',
		'core/page-list-item'            => 'core/page-list-item',
	];

	const EXCLUDED_NON_CORE_BLOCKS = [
		'safe-svg/svg-icon',
		'videopress/video',
		'xwp/demo-details',
		'xwp/demo-dynamic-block-with-variation',
		'xwp/demo-dynamic-block',
		'xwp/wp-dynamic-block-template',
		'xwp/wp-static-block-template',
		'jetpack/ai-assistant',
		'jetpack/blogging-prompt',
		'jetpack/business-hours',
		'jetpack/button',
		'jetpack/calendly',
		'jetpack/contact-info',
		'jetpack/address',
		'jetpack/email',
		'jetpack/phone',
		'jetpack/donations',
		'jetpack/eventbrite',
		'jetpack/gif',
		'jetpack/google-calendar',
		'jetpack/mailchimp',
		'jetpack/map',
		'jetpack/markdown',
		'jetpack/opentable',
		'jetpack/podcast-player',
		'premium-content/container',
		'premium-content/logged-out-view',
		'premium-content/subscriber-view',
		'premium-content/buttons',
		'premium-content/login-button',
		'jetpack/rating-star',
		'jetpack/recurring-payments',
		'jetpack/repeat-visitor',
		'jetpack/revue',
		'jetpack/send-a-message',
		'jetpack/whatsapp-button',
		'jetpack/slideshow',
		'jetpack/story',
		'jetpack/tiled-gallery',
		'jetpack/payments-intro',
		'jetpack/payment-buttons',
		'yoast-seo/breadcrumbs',
		'core-embed/flickr',
		'core-embed/animoto',
		'core-embed/cloudup',
		'core-embed/dailymotion',
		'core-embed/funnyordie',
		'core-embed/hulu',
		'core-embed/imgur',
		'core-embed/issuu',
		'core-embed/kickstarter',
		'core-embed/mixcloud',
		'core-embed/pinterest',
		'core-embed/reddit',
		'core-embed/reverbnation',
		'core-embed/screenrant',
		'core-embed/scribd',
		'core-embed/slideshare',
		'core-embed/smugmug',
		'core-embed/soundcloud',
		'core-embed/spotify',
		'core-embed/tiktok',
		'core-embed/ted',
		'core-embed/tumblr',
		'core-embed/videopress',
		'core-embed/vine',
		'core-embed/wordpress',
	];

	/**
	 * Register any needed hooks/filters.
	 */
	public function init() {
		if ( version_compare( $GLOBALS['wp_version'], '5.8-alpha-1', '<' ) ) {
			add_filter( 'allowed_block_types', [ $this, 'filter_maybe_disable_block_types' ] );
		} else {
			add_filter( 'allowed_block_types_all', [ $this, 'filter_maybe_disable_block_types' ] );
		}
	}

	/**
	 * Get all allowed core block types.
	 *
	 * @return array
	 */
	public function get_allowed_core_block_types() {
		return apply_filters( 'mavero_allowed_core_block_types', self::ALLOWED_CORE_BLOCK_TYPES );
	}

	/**
	 * Filter the list of allowed blocks.
	 *
	 * @return array Allowed blocks types.
	 */
	public function filter_maybe_disable_block_types() {
		$registered_block_types   = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$allowed_core_block_types = $this->get_allowed_core_block_types();

		$allowed_block_types = [];
		foreach ( $registered_block_types as $block_type => $_ ) {
			// Disable all WP blocks not in the allow-list.
			$is_core_block              = false !== strpos( $block_type, 'core/' );
			$is_allowed_in_core         = $is_core_block && isset( $allowed_core_block_types[ $block_type ] );
			$is_excluded_non_core_block = ! $is_core_block && ! in_array( $block_type, self::EXCLUDED_NON_CORE_BLOCKS, true );
			if ( $is_excluded_non_core_block || $is_allowed_in_core ) {
				$allowed_block_types[] = $block_type;
			}
		}

		return apply_filters( 'mavero_allowed_block_types', $allowed_block_types );
	}
}
