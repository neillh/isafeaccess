/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { SVG, Path } from '@wordpress/primitives';

const META_LINKEDIN = 'team_linkedin';

const LinkedInIcon = () => (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
		<Path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.125 2.062 2.062 0 0 1 0 4.125zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
	</SVG>
);

/**
 * Edit component for the Team LinkedIn block.
 *
 * Reads the LinkedIn URL from the current team member (post context). When it
 * is empty the icon is dimmed here and the block renders nothing on the front end.
 *
 * @param {Object}   props               Block properties.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {Object}   props.context       Block context.
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes, context } ) {
	const { openInNewTab } = attributes;
	const { postId, postType } = context;

	const [ meta ] = useEntityProp( 'postType', postType, 'meta', postId );
	const linkedin = meta?.[ META_LINKEDIN ] || '';

	const blockProps = useBlockProps( {
		className: linkedin ? undefined : 'is-empty',
		title: linkedin || __( 'No LinkedIn URL set for this team member', 'mavero' ),
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'mavero' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Open in new tab', 'mavero' ) }
						checked={ openInNewTab }
						onChange={ ( value ) => setAttributes( { openInNewTab: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<span className="wp-block-mavero-team-linkedin__link">
					<LinkedInIcon />
				</span>
			</div>
		</>
	);
}
