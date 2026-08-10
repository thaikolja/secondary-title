/**
 * Block editor entry point for the /secondary-title canvas block.
 *
 * Registers the block. PHP (Block\Registrar) wires the
 * server-side render callback (Block\ServerRender) so the block
 * renders the formatted secondary title on the front end
 * without any client-side JavaScript.
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import edit from './edit.js';

registerBlockType( metadata.name, {
	...metadata,
	edit,
	// No save() function: the block is server-side rendered.
} );

// Mark as translated for tooling.
__( 'Secondary Title' );
__(
	'Renders the secondary title of the current post using the configured format.'
);
