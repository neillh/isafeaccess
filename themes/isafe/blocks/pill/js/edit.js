/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	useInnerBlocksProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

const ALLOWED_BLOCKS = ['mavero/icon'];

/**
 * Edit component for the Pill block.
 *
 * @param {Object} props Block properties.
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const { attributes, setAttributes, clientId } = props;
	const { text } = attributes;

	const hasIcon = useSelect(
		(select) => {
			const { getBlock } = select('core/block-editor');
			const block = getBlock(clientId);
			return !!(block && block.innerBlocks.length);
		},
		[clientId]
	);

	const blockProps = useBlockProps({
		className: 'wp-block-mavero-pill',
	});

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wp-block-mavero-pill__icon' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			renderAppender: hasIcon
				? false
				: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<div {...blockProps}>
			<div {...innerBlocksProps} />
			<RichText
				tagName="span"
				className="wp-block-mavero-pill__text"
				value={text}
				onChange={(newText) => setAttributes({ text: newText })}
				placeholder={__('Pill text…', 'mavero')}
				allowedFormats={['core/bold', 'core/italic']}
			/>
		</div>
	);
}
