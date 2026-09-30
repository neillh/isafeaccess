/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useBlockEditContext } from '@wordpress/block-editor';

/**
 * useHasSelectedInnerBlock
 * Determine whether one of the inner blocks currently is selected
 *
 * @return {boolean} whether the block is the ancestor of selected blocks
 */
export function useHasSelectedInnerBlock() {
	const { clientId } = useBlockEditContext();

	return useSelect( ( select ) => select( 'core/block-editor' ).hasSelectedInnerBlock( clientId, true ) );
}
