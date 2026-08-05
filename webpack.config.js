/**
 * WordPress Scripts webpack overrides.
 *
 * Multiple entry points:
 *   - settings:     vanilla JS for the settings page (tabs, live preview, searchable select).
 *   - editor:       React component for the Gutenberg sidebar panel.
 *   - block:        React component + block.json for the /secondary-title canvas block.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'settings/settings': path.resolve( __dirname, 'assets/js/src/settings/index.js' ),
		'editor/editor':     path.resolve( __dirname, 'assets/js/src/editor/index.js' ),
		'block/index':       path.resolve( __dirname, 'assets/js/src/block/index.js' ),
	},
	output: {
		path: path.resolve( __dirname, 'assets/js/dist' ),
		filename: '[name].js',
	},
};
