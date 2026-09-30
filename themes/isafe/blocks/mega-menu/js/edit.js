/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockControls,
	InspectorControls,
	RichText,
	store as blockEditorStore,
	__experimentalLinkControl as LinkControl,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Popover,
	SelectControl,
	ToggleControl,
	ToolbarButton,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { displayShortcut } from '@wordpress/keycodes';
import { link, linkOff } from '@wordpress/icons';

/**
 * Starter layout: two link columns plus a promo column.
 *
 * @type {Array}
 */
const TEMPLATE = [
	[
		'core/columns',
		{},
		[
			[
				'core/column',
				{},
				[
					[ 'core/heading', { level: 3, placeholder: __( 'Column heading', 'mavero' ) } ],
					[ 'core/list', {}, [ [ 'core/list-item' ] ] ],
				],
			],
			[
				'core/column',
				{},
				[
					[ 'core/heading', { level: 3, placeholder: __( 'Column heading', 'mavero' ) } ],
					[ 'core/list', {}, [ [ 'core/list-item' ] ] ],
				],
			],
			[
				'core/column',
				{},
				[
					[ 'core/heading', { level: 3, placeholder: __( 'Featured', 'mavero' ) } ],
					[ 'core/paragraph', { placeholder: __( 'Short promo copy…', 'mavero' ) } ],
					[ 'core/buttons', {}, [ [ 'core/button' ] ] ],
				],
			],
		],
	],
];

/**
 * Edit component for the Mega Menu block.
 *
 * @param {Object}   props               Block properties.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {boolean}  props.isSelected    Whether the block itself is selected.
 * @param {string}   props.clientId      Block client ID.
 * @return {WPElement} Element to render.
 */
export default function Edit( {
	attributes,
	setAttributes,
	isSelected,
	clientId,
} ) {
	const { label, panelWidth, url, opensInNewTab, kind, id, type } =
		attributes;
	const [ isPinned, setIsPinned ] = useState( false );
	const [ isEditingURL, setIsEditingURL ] = useState( false );
	const [ popoverAnchor, setPopoverAnchor ] = useState( null );

	const isURLSet = !! url;

	// Keep the panel visible while the author is working inside it.
	const hasSelectedInnerBlock = useSelect(
		( select ) =>
			select( blockEditorStore ).hasSelectedInnerBlock( clientId, true ),
		[ clientId ]
	);

	const isPanelOpen = isPinned || isSelected || hasSelectedInnerBlock;

	const blockProps = useBlockProps( {
		ref: setPopoverAnchor,
		className: `wp-block-mavero-mega-menu wp-block-mavero-mega-menu--${ panelWidth }${
			isURLSet ? ' is-linked' : ''
		}${ isPanelOpen ? ' is-open' : '' }`,
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wp-block-mavero-mega-menu__panel-inner' },
		{ template: TEMPLATE, templateLock: false }
	);

	/**
	 * Clear the link and close the link editing popover.
	 */
	function unlink() {
		setAttributes( {
			url: undefined,
			opensInNewTab: false,
			rel: undefined,
			kind: undefined,
			id: undefined,
			type: undefined,
		} );
		setIsEditingURL( false );
	}

	return (
		<>
			<BlockControls group="block">
				{ ! isURLSet && (
					<ToolbarButton
						name="link"
						icon={ link }
						title={ __( 'Link', 'mavero' ) }
						shortcut={ displayShortcut.primary( 'k' ) }
						onClick={ () => setIsEditingURL( true ) }
					/>
				) }
				{ isURLSet && (
					<ToolbarButton
						name="link"
						icon={ linkOff }
						title={ __( 'Unlink', 'mavero' ) }
						shortcut={ displayShortcut.primaryShift( 'k' ) }
						onClick={ unlink }
						isActive
					/>
				) }
			</BlockControls>

			{ isSelected && ( isEditingURL || isURLSet ) && (
				<Popover
					placement="bottom"
					onClose={ () => setIsEditingURL( false ) }
					anchor={ popoverAnchor }
					focusOnMount={ isEditingURL ? 'firstElement' : false }
					__unstableSlotName="__unstable-block-tools-after"
					shift
				>
					<LinkControl
						className="wp-block-navigation-link__inline-link-input"
						value={ { url, opensInNewTab, kind, id, type } }
						onChange={ ( {
							url: newURL = '',
							opensInNewTab: newOpensInNewTab,
							kind: newKind,
							id: newId,
							type: newType,
							title: newTitle,
						} ) => {
							setAttributes( {
								url: newURL,
								opensInNewTab: !! newOpensInNewTab,
								kind: newKind,
								id: newId,
								type: newType,
								// Seed an empty label from the chosen entity.
								...( ! label && newTitle
									? { label: newTitle }
									: {} ),
							} );
						} }
						onRemove={ unlink }
						forceIsEditingLink={ isEditingURL }
					/>
				</Popover>
			) }

			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'mavero' ) }>
					<SelectControl
						label={ __( 'Panel width', 'mavero' ) }
						value={ panelWidth }
						options={ [
							{
								label: __( 'Full width', 'mavero' ),
								value: 'full',
							},
							{
								label: __( 'Wide', 'mavero' ),
								value: 'wide',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { panelWidth: value } )
						}
						help={ __(
							'Full width spans the viewport. Wide matches the site content width.',
							'mavero'
						) }
					/>
					<ToggleControl
						label={ __( 'Keep panel open while editing', 'mavero' ) }
						checked={ isPinned }
						onChange={ setIsPinned }
						help={ __(
							'The panel also opens automatically whenever this block or its contents are selected.',
							'mavero'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<li { ...blockProps }>
				<span className="wp-block-mavero-mega-menu__trigger">
					<span
						className={
							isURLSet
								? 'wp-block-mavero-mega-menu__link'
								: 'wp-block-mavero-mega-menu__toggle'
						}
					>
						<RichText
							identifier="label"
							tagName="span"
							className="wp-block-mavero-mega-menu__label"
							value={ label }
							onChange={ ( value ) =>
								setAttributes( { label: value } )
							}
							placeholder={ __( 'Menu label…', 'mavero' ) }
							allowedFormats={ [] }
							withoutInteractiveFormatting
						/>
						{ ! isURLSet && (
							<span
								className="wp-block-mavero-mega-menu__icon"
								aria-hidden="true"
							/>
						) }
					</span>
					{ isURLSet && (
						<span
							className="wp-block-mavero-mega-menu__toggle wp-block-mavero-mega-menu__toggle--icon"
							aria-hidden="true"
						>
							<span className="wp-block-mavero-mega-menu__icon" />
						</span>
					) }
				</span>

				<div className="wp-block-mavero-mega-menu__panel">
					<div { ...innerBlocksProps } />
				</div>
			</li>
		</>
	);
}
