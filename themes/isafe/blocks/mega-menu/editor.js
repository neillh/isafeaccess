/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import metadata from './block.json';
import edit from './js/edit';
import save from './js/save';

registerBlockType( metadata.name, { edit, save } );
