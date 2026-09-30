/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __unstableCreateElement as createElement } from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import block from './block.json';

const { name } = block;

const transforms = {
	from: [
		{
			type: 'block',
			isMultiBlock: true,
			blocks: [ 'express/button' ],
			transform: ( buttons ) =>
				// Creates the buttons block.
				createBlock(
					name,
					{},
					// Loop the selected buttons.
					buttons.map( ( attributes ) =>
						// Create singular button in the buttons block.
						createBlock( 'express/button', attributes ),
					),
				),
		},
		{
			type: 'block',
			isMultiBlock: true,
			blocks: [ 'core/paragraph' ],
			transform: ( buttons ) =>
				// Creates the buttons block.
				createBlock(
					name,
					{},
					// Loop the selected buttons.
					buttons.map( ( attributes ) => {
						const element = createElement(
							document,
							attributes.content,
						);
						// Remove any HTML tags.
						const text = element.innerText || '';
						// Get first url.
						const link = element.querySelector( 'a' );
						const url = link?.getAttribute( 'href' );
						// Create singular button in the buttons block.
						return createBlock( 'express/button', {
							text,
							url,
						} );
					} ),
				),
			isMatch: ( paragraphs ) => {
				return paragraphs.every( ( attributes ) => {
					const element = createElement(
						document,
						attributes.content,
					);
					const text = element.innerText || '';
					const links = element.querySelectorAll( 'a' );
					return text.length <= 30 && links.length <= 1;
				} );
			},
		},
		{
			type: 'block',
			isMultiBlock: true,
			blocks: [ 'core/buttons', 'core/button' ],
			transform: ( buttons, innerBlocks ) =>
				createBlock(
					'express/buttons',
					{},
					// Loop the selected buttons.
					innerBlocks?.[ 0 ].map( ( buttonBlock ) =>
						createBlock( 'express/button', {
							// Create singular button in the buttons block.
							text: buttonBlock.attributes?.text,
							url: buttonBlock.attributes?.url,
						} ),
					),
				),
		},
	],
};

export default transforms;
