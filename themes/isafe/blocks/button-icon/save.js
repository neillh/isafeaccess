/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	RichText,
	useBlockProps,
	__experimentalGetBorderClassesAndStyles as getBorderClassesAndStyles,
	__experimentalGetColorClassesAndStyles as getColorClassesAndStyles,
	__experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles,
	__experimentalGetElementClassName,
} from '@wordpress/block-editor';

export default function save( { attributes, className } ) {
	const {
		textAlign,
		fontSize,
		linkTarget,
		rel,
		style,
		text,
		title,
		url,
		width,
		fullWidthOnMobile,
		buttonStyle,
		iconPosition,
		source,
		ariaLabel,
		iconSize,
		size,
	} = attributes;

	if ( ! text && ! source ) {
		return null;
	}

	const borderProps = getBorderClassesAndStyles( attributes );
	const colorProps = getColorClassesAndStyles( attributes );
	const spacingProps = getSpacingClassesAndStyles( attributes );
	const buttonClasses = classnames(
		'wp-block-mavero-button__link-text',
		// colorProps.className,
		{
			[ `has-text-align-${ textAlign }` ]: textAlign,
			// For backwards compatibility add style that isn't provided via
			// block support.
			'no-border-radius': style?.border?.radius === 0,
		},
	);

	const anchorStyle = {
		...borderProps.style,
		...spacingProps.style,
	};

	// The use of a `title` attribute here is soft-deprecated, but still applied
	// if it had already been assigned, for the sake of backward-compatibility.
	// A title will no longer be assigned for new or updated button block links.

	const wrapperClasses = classnames( className, {
		[ `has-custom-width wp-block-mavero-button__width-${ width }` ]: width,
		[ `has-custom-font-size` ]: fontSize || style?.typography?.fontSize,
		[ `has-custom-size is-size-${ size }` ]: size,
		[ `has-full-width-on-mobile` ]: fullWidthOnMobile,
	} );

	return (
		<div { ...useBlockProps.save( { className: wrapperClasses } ) }>
			<a
				className={ classnames(
					'wp-block-mavero-button__link',
					colorProps.className,
					__experimentalGetElementClassName( 'button' ),
					{
						[ `is-icon-only` ]: 'icon' === buttonStyle,
					}
				) }
				href={ url }
				title={ title }
				target={ linkTarget }
				rel={ rel }
				style={ anchorStyle }
				aria-label={ ariaLabel }
			>
				{ buttonStyle !== 'icon' &&
					<RichText.Content
						tagName="span"
						className={ buttonClasses }
						value={ text }
					/>
				}
				{ buttonStyle !== 'default' &&
					<div
						dangerouslySetInnerHTML={ { __html: source } }
						className={ classnames(
							'wp-block-mavero-button__link-icon',
							`has-icon-position-${ iconPosition }`,
						) }
						style={ { height: `${ iconSize }px`, width: `${ iconSize }px` } }
					/>
				}
			</a>
		</div>
	);
}
