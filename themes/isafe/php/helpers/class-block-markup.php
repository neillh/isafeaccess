<?php
/**
 * Mavero\Helpers\Block_Markup class
 *
 * @package Mavero
 */

namespace Mavero\Helpers;

/**
 * Builds serialized block markup for the theme's reusable page sections.
 *
 * Single source of truth for section markup: the section patterns in
 * patterns/ call these with placeholder copy, and the Elementor → blocks
 * migration script calls them with each page's real copy. Output mirrors what
 * the block editor saves, so the blocks validate when opened in the editor.
 */
class Block_Markup {

	/**
	 * Wrap content in block comment delimiters.
	 *
	 * @param string $name    Block name without the `core/` prefix for core blocks.
	 * @param array  $attrs   Block attributes.
	 * @param string $content Inner HTML/markup. Empty for a self-closing block.
	 *
	 * @return string
	 */
	public static function block( string $name, array $attrs = [], string $content = '' ): string {
		$attrs = array_filter( $attrs, fn( $value ) => null !== $value && [] !== $value );

		return get_comment_delimited_block_content( $name, $attrs, $content );
	}

	/**
	 * Heading block.
	 *
	 * @param string $html  Heading inner HTML (already safe).
	 * @param int    $level Heading level.
	 * @param array  $args  Optional `className`, `fontSize`, `uppercase`, `textAlign`, `textColor`.
	 *
	 * @return string
	 */
	public static function heading( string $html, int $level = 2, array $args = [] ): string {
		$classes = [ 'wp-block-heading' ];
		$attrs   = [];
		$style   = [];

		if ( 2 !== $level ) {
			$attrs['level'] = $level;
		}
		if ( ! empty( $args['textAlign'] ) ) {
			$attrs['textAlign'] = $args['textAlign'];
			$classes[]          = 'has-text-align-' . $args['textAlign'];
		}
		if ( ! empty( $args['className'] ) ) {
			$attrs['className'] = $args['className'];
			$classes[]          = $args['className'];
		}
		if ( ! empty( $args['uppercase'] ) ) {
			$attrs['style'] = [ 'typography' => [ 'textTransform' => 'uppercase' ] ];
			$style[]        = 'text-transform:uppercase';
		}
		if ( ! empty( $args['textColor'] ) ) {
			$attrs['textColor'] = $args['textColor'];
			$classes[]          = 'has-' . $args['textColor'] . '-color';
			$classes[]          = 'has-text-color';
		}
		if ( ! empty( $args['fontSize'] ) ) {
			$attrs['fontSize'] = $args['fontSize'];
			$classes[]         = 'has-' . $args['fontSize'] . '-font-size';
		}

		return self::block(
			'heading',
			$attrs,
			sprintf(
				'<h%1$d class="%2$s"%3$s>%4$s</h%1$d>',
				$level,
				esc_attr( implode( ' ', $classes ) ),
				$style ? ' style="' . esc_attr( implode( ';', $style ) ) . '"' : '',
				$html
			)
		);
	}

	/**
	 * Paragraph block.
	 *
	 * @param string $html      Paragraph inner HTML (already safe).
	 * @param string $className Optional class, e.g. `is-kicker`.
	 *
	 * @return string
	 */
	public static function paragraph( string $html, string $className = '' ): string {
		return self::block(
			'paragraph',
			$className ? [ 'className' => $className ] : [],
			$className ? sprintf( '<p class="%s">%s</p>', esc_attr( $className ), $html ) : sprintf( '<p>%s</p>', $html )
		);
	}

	/**
	 * List block.
	 *
	 * @param string[] $items   List item inner HTML (already safe).
	 * @param bool     $ordered Ordered list.
	 * @param string   $className Optional block style class.
	 *
	 * @return string
	 */
	public static function list( array $items, bool $ordered = false, string $className = '' ): string {
		$inner = '';
		foreach ( $items as $item ) {
			$inner .= self::block( 'list-item', [], '<li>' . $item . '</li>' );
		}

		$tag     = $ordered ? 'ol' : 'ul';
		$classes = trim( 'wp-block-list ' . $className );
		$attrs   = array_filter(
			[
				'ordered'   => $ordered ?: null,
				'className' => $className ?: null,
			]
		);

		return self::block( 'list', $attrs, sprintf( '<%1$s class="%2$s">%3$s</%1$s>', $tag, esc_attr( $classes ), $inner ) );
	}

	/**
	 * Hatched separator.
	 *
	 * @return string
	 */
	public static function hatched(): string {
		return self::block( 'separator', [ 'className' => 'is-style-hatched' ], '<hr class="wp-block-separator has-alpha-channel-opacity is-style-hatched"/>' );
	}

	/**
	 * A row of buttons (Outline style unless a button sets 'style' => 'fill'|'link').
	 *
	 * @param array $buttons List of [ 'text' => string, 'url' => string, 'style' => string ].
	 * @param array $args    Optional `justify` (left|center|right).
	 *
	 * @return string
	 */
	public static function buttons( array $buttons, array $args = [] ): string {
		$inner = '';
		foreach ( $buttons as $button ) {
			$href   = ! empty( $button['url'] ) ? ' href="' . esc_url( $button['url'] ) . '"' : '';
			$style  = $button['style'] ?? 'outline';
			$class  = 'fill' === $style ? '' : 'is-style-' . $style;
			$inner .= self::block(
				'button',
				$class ? [ 'className' => $class ] : [],
				sprintf( '<div class="%s"><a class="wp-block-button__link wp-element-button"%s>%s</a></div>', esc_attr( trim( 'wp-block-button ' . $class ) ), $href, esc_html( $button['text'] ) )
			);
		}

		$attrs = ! empty( $args['justify'] ) ? [ 'layout' => [ 'type' => 'flex', 'justifyContent' => $args['justify'] ] ] : [];

		return self::block( 'buttons', $attrs, '<div class="wp-block-buttons">' . $inner . '</div>' );
	}

	/**
	 * Image block for a media library attachment.
	 *
	 * @param int    $id   Attachment ID.
	 * @param string $size Image size slug.
	 * @param string $alt  Alt text; falls back to the attachment's alt.
	 * @param array  $args Optional `width` (CSS length) and `className`.
	 *
	 * @return string Empty string if the attachment doesn't exist.
	 */
	public static function image( int $id, string $size = 'large', string $alt = '', array $args = [] ): string {
		$url = wp_get_attachment_image_url( $id, $size );
		if ( ! $url ) {
			return '';
		}
		if ( '' === $alt ) {
			$alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		}

		$attrs      = [
			'id'              => $id,
			'width'           => $args['width'] ?? null,
			'sizeSlug'        => $size,
			'linkDestination' => 'none',
			'className'       => $args['className'] ?? null,
		];
		$classes    = trim( sprintf( 'wp-block-image size-%s%s %s', $size, isset( $args['width'] ) ? ' is-resized' : '', $args['className'] ?? '' ) );
		// Core's image save adds height:auto whenever only a width is set.
		$img_style  = isset( $args['width'] ) ? ' style="width:' . esc_attr( $args['width'] ) . ';height:auto"' : '';
		$figure     = sprintf(
			'<figure class="%s"><img src="%s" alt="%s" class="wp-image-%d"%s/></figure>',
			esc_attr( $classes ),
			esc_url( $url ),
			esc_attr( $alt ),
			$id,
			$img_style
		);

		return self::block( 'image', $attrs, $figure );
	}

	/**
	 * Full-width section wrapper (constrained content).
	 *
	 * @param string $inner     Inner block markup.
	 * @param string $className Section class(es), e.g. `isafe-split is-style-section-light`.
	 * @param array  $padding   [ top, bottom ] CSS lengths.
	 *
	 * @return string
	 */
	public static function section( string $inner, string $className, array $padding = [ '60px', '60px' ] ): string {
		[ $top, $bottom ] = $padding;

		return self::block(
			'group',
			[
				'align'     => 'full',
				'className' => $className,
				'style'     => [ 'spacing' => [ 'padding' => [ 'top' => $top, 'bottom' => $bottom ] ] ],
				'layout'    => [ 'type' => 'constrained' ],
			],
			sprintf(
				'<div class="wp-block-group alignfull %s" style="padding-top:%s;padding-bottom:%s">%s</div>',
				esc_attr( $className ),
				esc_attr( $top ),
				esc_attr( $bottom ),
				$inner
			)
		);
	}

	/**
	 * Two columns, vertically centred.
	 *
	 * @param string $first      First column markup.
	 * @param string $second     Second column markup.
	 * @param string $first_width Optional flex-basis for the first column, e.g. `40%`.
	 * @param string $valign      Vertical alignment: `top` or `center`.
	 *
	 * @return string
	 */
	public static function two_columns( string $first, string $second, string $first_width = '', string $valign = 'center' ): string {
		$column = function ( string $inner, string $width = '' ) use ( $valign ) {
			$attrs = [ 'verticalAlignment' => $valign ];
			$style = '';
			if ( $width ) {
				$attrs['width'] = $width;
				$style          = ' style="flex-basis:' . esc_attr( $width ) . '"';
			}

			return self::block( 'column', $attrs, '<div class="wp-block-column is-vertically-aligned-' . $valign . '"' . $style . '>' . $inner . '</div>' );
		};

		return self::block(
			'columns',
			[
				'verticalAlignment' => $valign,
				'style'             => [ 'spacing' => [ 'blockGap' => [ 'left' => 'var:preset|spacing|60' ] ] ],
			],
			'<div class="wp-block-columns are-vertically-aligned-' . $valign . '">' . $column( $first, $first_width ) . $column( $second ) . '</div>'
		);
	}

	/**
	 * Page hero: full-width photo cover with H1, hatched divider and subtitle.
	 *
	 * @param array $args {
	 *     @type int    $image_id  Background attachment ID.
	 *     @type string $title     H1 text.
	 *     @type array  $eyebrow   Optional pill above the H1: [ 'text', 'url' ].
	 *     @type string $subtitle  Optional subtitle text.
	 *     @type string $body      Optional extra block markup under the subtitle.
	 *     @type string $alt       Optional image alt text.
	 *     @type int    $dim       Overlay opacity 0–100. Default 70.
	 *     @type int    $min_height Min height in vh. Default 80.
	 *     @type bool   $overlap   Reserve room for overlapping service cards.
	 * }
	 *
	 * @return string
	 */
	public static function page_hero( array $args ): string {
		$args = wp_parse_args(
			$args,
			[
				'image_id'   => 0,
				'title'      => '',
				'eyebrow'    => [],
				'subtitle'   => '',
				'body'       => '',
				'alt'        => '',
				'dim'        => 70,
				'min_height' => 80,
				'overlap'    => false,
			]
		);

		$id     = (int) $args['image_id'];
		$url    = $id ? wp_get_attachment_image_url( $id, 'full' ) : '';
		$dim    = (int) round( $args['dim'] / 10 ) * 10;
		$bottom = $args['overlap'] ? 'calc(90px + var(--wp--preset--spacing--50))' : 'var:preset|spacing|60';
		$bottom_css = $args['overlap'] ? $bottom : 'var(--wp--preset--spacing--60)';

		$inner = '';
		if ( ! empty( $args['eyebrow']['text'] ) ) {
			$label  = esc_html( $args['eyebrow']['text'] );
			$inner .= self::paragraph(
				! empty( $args['eyebrow']['url'] ) ? '<a href="' . esc_url( $args['eyebrow']['url'] ) . '">' . $label . '</a>' : $label,
				'is-pill'
			);
		}
		$inner .= self::heading( esc_html( $args['title'] ), 1, [ 'textColor' => 'base' ] );
		$inner .= self::hatched();
		if ( $args['subtitle'] ) {
			$inner .= self::block(
				'paragraph',
				[
					'style'    => [ 'typography' => [ 'fontWeight' => '800', 'lineHeight' => '1.2' ] ],
					'fontSize' => 'x-large',
				],
				'<p class="has-x-large-font-size" style="font-weight:800;line-height:1.2">' . esc_html( $args['subtitle'] ) . '</p>'
			);
		}
		$inner .= $args['body'];

		$group = self::block(
			'group',
			[
				'style'  => [ 'spacing' => [ 'blockGap' => 'var:preset|spacing|30' ] ],
				'layout' => [ 'type' => 'constrained', 'contentSize' => '560px', 'justifyContent' => 'left' ],
			],
			'<div class="wp-block-group">' . $inner . '</div>'
		);

		$attrs = [
			'url'           => $url ?: null,
			'id'            => $id ?: null,
			'alt'           => '' !== $args['alt'] ? $args['alt'] : null, // A comment attribute, not sourced from the <img>.
			'dimRatio'      => $dim,
			'gradient'      => 'hero-overlay',
			'minHeight'     => (int) $args['min_height'],
			'minHeightUnit' => 'vh',
			'isDark'        => true,
			'align'         => 'full',
			'className'     => 'isafe-hero',
			'style'         => [ 'spacing' => [ 'padding' => [ 'top' => 'var:preset|spacing|60', 'bottom' => $bottom ] ] ],
			'textColor'     => 'base',
			'layout'        => [ 'type' => 'constrained' ],
		];

		$img = $url ? sprintf(
			'<img class="wp-block-cover__image-background wp-image-%d" alt="%s" src="%s" data-object-fit="cover"/>',
			$id,
			esc_attr( $args['alt'] ),
			esc_url( $url )
		) : '';

		return self::block(
			'cover',
			$attrs,
			sprintf(
				'<div class="wp-block-cover alignfull isafe-hero has-base-color has-text-color" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:%1$s;min-height:%2$dvh">%3$s<span aria-hidden="true" class="wp-block-cover__background has-background-dim-%4$d has-background-dim wp-block-cover__gradient-background has-background-gradient has-hero-overlay-gradient-background"></span><div class="wp-block-cover__inner-container">%5$s</div></div>',
				esc_attr( $bottom_css ),
				(int) $args['min_height'],
				$img,
				$dim,
				$group
			)
		);
	}

	/**
	 * Intro band: lead copy (bold) followed by a hatched divider.
	 *
	 * @param string $blocks Block markup for the lead copy (use `is-kicker` paragraphs).
	 *
	 * @return string
	 */
	public static function intro( string $blocks ): string {
		return self::section( $blocks . self::hatched(), 'isafe-intro-band', [ '60px', '0' ] );
	}

	/**
	 * Text + image split section.
	 *
	 * @param array $args {
	 *     @type string $heading    Section H2 text.
	 *     @type string $kicker     Optional kicker under the H2.
	 *     @type string $body       Block markup for the copy.
	 *     @type array  $buttons    Optional [ [ 'text', 'url' ] ].
	 *     @type int    $image_id   Attachment ID.
	 *     @type string $image_alt  Optional alt text.
	 *     @type string $image_side `right` (default) or `left`.
	 *     @type string $style      Optional section style slug, e.g. `section-light`.
	 *     @type string $media_after Optional block markup below the image.
	 * }
	 *
	 * @return string
	 */
	public static function split( array $args ): string {
		$args = wp_parse_args(
			$args,
			[
				'heading'    => '',
				'kicker'     => '',
				'body'       => '',
				'buttons'    => [],
				'image_id'   => 0,
				'image_alt'  => '',
				'image_side'  => 'right',
				'style'       => '',
				'media_after' => '',
			]
		);

		$text = '';
		if ( $args['heading'] ) {
			$text .= self::heading( esc_html( $args['heading'] ), 2, [ 'uppercase' => true ] );
		}
		if ( $args['kicker'] ) {
			$text .= self::paragraph( esc_html( $args['kicker'] ), 'is-kicker' );
		}
		$text .= $args['body'];
		if ( $args['buttons'] ) {
			$text .= self::buttons( $args['buttons'] );
		}

		$media = self::hatched() . self::image( (int) $args['image_id'], 'large', $args['image_alt'] ) . $args['media_after'];

		$columns = 'left' === $args['image_side'] ? self::two_columns( $media, $text, '', 'top' ) : self::two_columns( $text, $media, '', 'top' );
		$classes = trim( 'isafe-split is-image-' . $args['image_side'] . ( $args['style'] ? ' is-style-' . $args['style'] : '' ) );

		return self::section( $columns, $classes );
	}

	/**
	 * Section heading block group: H2, optional kicker, hatched divider.
	 *
	 * @param string $heading H2 text.
	 * @param string $kicker  Optional kicker text.
	 *
	 * @return string
	 */
	public static function section_heading( string $heading, string $kicker = '' ): string {
		$out = self::heading( esc_html( $heading ), 2, [ 'uppercase' => true ] );
		if ( $kicker ) {
			$out .= self::paragraph( esc_html( $kicker ), 'is-kicker' );
		}

		return $out . self::hatched();
	}

	/**
	 * Logo slider row (client or manufacturer logos).
	 *
	 * @param int[] $ids Attachment IDs.
	 *
	 * @return string
	 */
	public static function logo_slider( array $ids ): string {
		$images = '';
		foreach ( $ids as $id ) {
			$images .= self::image( (int) $id, 'full', trim( str_replace( [ '-', '_' ], ' ', get_the_title( $id ) ) ) );
		}

		return self::block(
			'group',
			[
				'className' => 'is-style-slider is-logo-slider',
				'layout'    => [ 'type' => 'flex', 'flexWrap' => 'nowrap' ],
			],
			'<div class="wp-block-group is-style-slider is-logo-slider">' . $images . '</div>'
		);
	}

	/**
	 * Logo panel CTA: brand logo + divider beside a heading, copy and button.
	 *
	 * @param array $args {
	 *     @type string $heading    H2 text.
	 *     @type string $subheading Optional H3 text.
	 *     @type string $body       Block markup for the copy.
	 *     @type array  $buttons    Optional [ [ 'text', 'url' ] ].
	 *     @type int    $logo_id    Logo attachment ID. Default 133 (colour logo with tagline).
	 *     @type string $style      Optional section style slug. Default `section-light`.
	 * }
	 *
	 * @return string
	 */
	public static function logo_cta( array $args ): string {
		$args = wp_parse_args(
			$args,
			[
				'heading'    => '',
				'subheading' => '',
				'body'       => '',
				'buttons'    => [],
				'logo_id'    => 133,
				'style'      => 'section-light',
			]
		);

		$logo = self::image( (int) $args['logo_id'], 'full', __( 'iSafe Access — Height Safety Specialist', 'mavero' ), [ 'width' => '290px' ] ) . self::hatched();

		$text = self::heading( esc_html( $args['heading'] ), 2, [ 'uppercase' => true ] );
		if ( $args['subheading'] ) {
			$text .= self::heading( esc_html( $args['subheading'] ), 3 );
		}
		$text .= $args['body'];
		if ( $args['buttons'] ) {
			$text .= self::buttons( $args['buttons'] );
		}

		$classes = trim( 'isafe-logo-cta' . ( $args['style'] ? ' is-style-' . $args['style'] : '' ) );

		return self::section( self::two_columns( $logo, $text, '40%' ), $classes );
	}

	/**
	 * Three coloured service cards (light / green / dark) in a row.
	 *
	 * @param array $cards   List of [ 'title', 'items' => string[], 'button' => [ 'text', 'url' ], 'style' ].
	 * @param bool  $overlap Pull the row up over the hero above.
	 *
	 * @return string
	 */
	public static function service_cards( array $cards, bool $overlap = true ): string {
		$styles  = [ 'section-light', 'section-green', 'section-dark' ];
		$columns = '';
		$pad     = 'var:preset|spacing|30';
		$pad_css = 'var(--wp--preset--spacing--30)';

		foreach ( array_values( $cards ) as $i => $card ) {
			$style  = $card['style'] ?? $styles[ $i % 3 ];
			$inner  = self::heading( esc_html( $card['title'] ), 2, [ 'uppercase' => true ] );
			$inner .= self::hatched();
			if ( ! empty( $card['items'] ) ) {
				$inner .= str_replace(
					[ '<!-- wp:list {"className":"is-style-chevrons"} -->', '<ul class="wp-block-list is-style-chevrons">' ],
					[ '<!-- wp:list {"className":"is-style-chevrons","fontSize":"small"} -->', '<ul class="wp-block-list is-style-chevrons has-small-font-size">' ],
					self::list( array_map( 'esc_html', $card['items'] ), false, 'is-style-chevrons' )
				);
			}
			if ( ! empty( $card['button'] ) ) {
				$inner .= self::buttons( [ $card['button'] ] );
			}

			$columns .= self::block(
				'column',
				[
					'className' => 'is-style-' . $style,
					'style'     => [
						'spacing' => [
							'padding'  => [ 'top' => $pad, 'right' => $pad, 'bottom' => $pad, 'left' => $pad ],
							'blockGap' => $pad,
						],
					],
				],
				sprintf(
					'<div class="wp-block-column is-style-%1$s" style="padding-top:%2$s;padding-right:%2$s;padding-bottom:%2$s;padding-left:%2$s">%3$s</div>',
					esc_attr( $style ),
					$pad_css,
					$inner
				)
			);
		}

		$row = self::block(
			'columns',
			[ 'style' => [ 'spacing' => [ 'blockGap' => [ 'top' => '0', 'left' => '0' ] ] ] ],
			'<div class="wp-block-columns">' . $columns . '</div>'
		);

		$margin = $overlap ? '-90px' : '0';

		return self::block(
			'group',
			[
				'align'     => 'full',
				'className' => 'isafe-service-cards',
				'style'     => [
					'spacing' => [
						'margin'  => [ 'top' => $margin ],
						'padding' => [ 'top' => '0', 'bottom' => 'var:preset|spacing|30' ],
					],
				],
				'layout'    => [ 'type' => 'constrained' ],
			],
			sprintf(
				'<div class="wp-block-group alignfull isafe-service-cards" style="margin-top:%s;padding-top:0;padding-bottom:var(--wp--preset--spacing--30)">%s</div>',
				$margin,
				$row
			)
		);
	}

	/**
	 * WPForms "Contact Us" form block.
	 *
	 * @param string $variant `on-green` (white-on-green CTA, the Elementor "Over
	 *                        Green" theme) or `on-light` (dark text on white/grey).
	 * @param string $title   Form title to look up.
	 *
	 * @return string Empty string if WPForms or the form is missing.
	 */
	public static function wpforms_form( string $variant = 'on-green', string $title = 'Contact Us' ): string {
		if ( ! function_exists( 'wpforms' ) ) {
			return '';
		}
		$form = get_posts(
			[
				'post_type'   => 'wpforms',
				'title'       => $title,
				'post_status' => 'publish',
				'numberposts' => 1,
				'fields'      => 'ids',
			]
		);
		if ( ! $form ) {
			return '';
		}

		$on_green = 'on-green' === $variant;
		$text     = $on_green ? '#FFFFFF' : '#24272A';

		return self::block(
			'wpforms/form-selector',
			[
				// WPForms expects the editor-assigned clientId (it scopes the
				// style vars with it); one form per page, so a fixed value is fine.
				'clientId'              => 'isafe-form-' . $variant,
				'formId'                => (string) $form[0],
				'displayTitle'          => false,
				'displayDesc'           => false,
				'theme'                 => 'default',
				'fieldSize'             => 'medium',
				'fieldBorderStyle'      => 'solid',
				'fieldBorderSize'       => '1px',
				'fieldBorderRadius'     => '3px',
				'fieldBackgroundColor'  => $on_green ? '#FFFFFF57' : '#FFFFFF',
				'fieldBorderColor'      => $on_green ? '#FFFFFF' : '#C9C9C9',
				'fieldTextColor'        => $text,
				'fieldMenuColor'        => '#FFFFFF',
				'labelSize'             => 'medium',
				'labelColor'            => $text,
				'labelSublabelColor'    => $text,
				'labelErrorColor'       => $on_green ? '#24272A' : '#D63637',
				'buttonSize'            => 'medium',
				'buttonBorderStyle'     => 'solid',
				'buttonBorderSize'      => '1px',
				'buttonBorderRadius'    => '3px',
				'buttonBackgroundColor' => $on_green ? '#FFFFFF03' : '#69BF4A',
				'buttonBorderColor'     => $on_green ? '#FFFFFF' : '#69BF4A',
				'buttonTextColor'       => '#FFFFFF',
				'containerPadding'      => '0px',
				'containerBorderStyle'  => 'none',
				'backgroundImage'       => 'none',
				'backgroundColor'       => 'rgba( 0, 0, 0, 0 )',
			]
		);
	}

	/**
	 * Team member cards in a row.
	 *
	 * @param array $members List of [ 'name', 'role', 'bio' (HTML), 'linkedin' (URL), 'image_id' ].
	 *
	 * @return string
	 */
	public static function team_grid( array $members ): string {
		$columns = '';
		foreach ( $members as $member ) {
			$inner = '';
			if ( ! empty( $member['image_id'] ) ) {
				$inner .= self::image( (int) $member['image_id'], 'medium', $member['name'] );
			}
			$inner .= self::heading( esc_html( $member['name'] ), 3 );
			if ( ! empty( $member['role'] ) ) {
				$inner .= self::paragraph( esc_html( $member['role'] ), 'isafe-team-member__role' );
			}
			if ( ! empty( $member['linkedin'] ) ) {
				$inner .= self::block(
					'social-links',
					[],
					'<ul class="wp-block-social-links">' . self::block( 'social-link', [ 'url' => esc_url_raw( $member['linkedin'] ), 'service' => 'linkedin' ] ) . '</ul>'
				);
			}
			// Elementor bios separate paragraphs with <br><br>.
			$bio    = preg_replace( '#(\s*<br\s*/?>\s*){2,}#i', '</p><p>', (string) ( $member['bio'] ?? '' ) );
			$inner .= self::html_to_blocks( '<p>' . $bio . '</p>' );
			$columns .= self::block( 'column', [ 'className' => 'isafe-team-member' ], '<div class="wp-block-column isafe-team-member">' . $inner . '</div>' );
		}

		return self::block(
			'columns',
			[
				'className' => 'isafe-team',
				'style'     => [ 'spacing' => [ 'blockGap' => [ 'left' => 'var:preset|spacing|50' ] ] ],
			],
			'<div class="wp-block-columns isafe-team">' . $columns . '</div>'
		);
	}

	/**
	 * Convert simple editor HTML (paragraphs, lists, headings) to block markup.
	 *
	 * Used to migrate WYSIWYG content (e.g. Elementor text-editor widgets).
	 * Inline markup (strong, em, a, br) is kept; unknown wrappers are unwrapped.
	 *
	 * @param string $html Source HTML.
	 * @param string $paragraph_class Optional class for every paragraph.
	 *
	 * @return string
	 */
	public static function html_to_blocks( string $html, string $paragraph_class = '' ): string {
		$html = trim( wp_kses_post( $html ) );
		if ( '' === $html ) {
			return '';
		}

		$doc = new \DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$root = $doc->getElementById( 'root' );
		if ( ! $root ) {
			return self::paragraph( $html, $paragraph_class );
		}

		$inner_html = function ( \DOMNode $node ) use ( $doc ) {
			$out = '';
			foreach ( $node->childNodes as $child ) {
				$out .= $doc->saveHTML( $child );
			}
			// Strip inline styles/spans Elementor's editor leaves behind.
			$out = preg_replace( '#<span[^>]*>(.*?)</span>#s', '$1', $out );
			$out = preg_replace( '# style="[^"]*"#', '', $out );

			return trim( str_replace( '&nbsp;', ' ', $out ) );
		};

		$block_tags = [ 'p', 'div', 'ul', 'ol', 'blockquote', 'h2', 'h3', 'h4', 'h5', 'h6' ];
		$has_block  = function ( \DOMNode $node ) use ( $block_tags ) {
			foreach ( $node->childNodes as $child ) {
				if ( $child instanceof \DOMElement && in_array( strtolower( $child->tagName ), $block_tags, true ) ) {
					return true;
				}
			}
			return false;
		};

		// Walk a node's children, emitting blocks. Inline runs become paragraphs;
		// block-level elements nested where they shouldn't be (e.g. a
		// <blockquote> inside a <p>) are emitted as their own blocks.
		$process = function ( \DOMNode $parent ) use ( &$process, $doc, $inner_html, $has_block, $paragraph_class ) {
			$out    = '';
			$inline = '';
			$flush  = function () use ( &$inline, &$out, $paragraph_class ) {
				$text = trim( $inline );
				if ( '' !== trim( wp_strip_all_tags( $text ) ) ) {
					$out .= self::paragraph( $text, $paragraph_class );
				}
				$inline = '';
			};

			foreach ( iterator_to_array( $parent->childNodes ) as $node ) {
				$tag = $node instanceof \DOMElement ? strtolower( $node->tagName ) : '#text';

				switch ( $tag ) {
					case 'p':
					case 'div':
						$flush();
						if ( $has_block( $node ) ) {
							$out .= $process( $node );
							break;
						}
						$content = $inner_html( $node );
						if ( '' !== trim( wp_strip_all_tags( $content ) ) ) {
							$out .= self::paragraph( $content, $paragraph_class );
						}
						break;
					case 'blockquote':
						$flush();
						$quote = $has_block( $node ) ? $process( $node ) : self::paragraph( $inner_html( $node ) );
						$out  .= self::block( 'quote', [], '<blockquote class="wp-block-quote">' . $quote . '</blockquote>' );
						break;
					case 'ul':
					case 'ol':
						$flush();
						$items = [];
						foreach ( $node->childNodes as $li ) {
							if ( $li instanceof \DOMElement && 'li' === strtolower( $li->tagName ) ) {
								$items[] = $inner_html( $li );
							}
						}
						if ( $items ) {
							$out .= self::list( $items, 'ol' === $tag );
						}
						break;
					case 'h2':
					case 'h3':
					case 'h4':
					case 'h5':
					case 'h6':
						$flush();
						$out .= self::heading( $inner_html( $node ), max( 3, (int) substr( $tag, 1 ) ) );
						break;
					default:
						$inline .= $doc->saveHTML( $node );
				}
			}
			$flush();

			return $out;
		};

		$out = $process( $root );

		return $out;
	}
}
