/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Save the panel's inner blocks only.
 *
 * The surrounding markup (list item, toggle button, panel wrapper) is produced
 * by `template.php` so the Interactivity directives are generated server-side.
 * WordPress renders the inner blocks and passes them to the template as
 * `$content`, which keeps dynamic children such as the Query Loop working.
 *
 * @return {WPElement} Element to render.
 */
export default function Save() {
	return <InnerBlocks.Content />;
}
