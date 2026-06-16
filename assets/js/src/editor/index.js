/**
 * Block editor entry point.
 *
 * Registers a PluginDocumentSettingPanel that exposes the secondary
 * title input + a live format preview to Gutenberg's sidebar.
 *
 * The actual React component lives in ./sidebar-panel.js. PHP
 * (SidebarPanel) pre-populates window.SecondaryTitleBootstrap with
 * the current post's title + secondary title + the format.
 *
 * @wordpress/* dependencies are provided by @wordpress/scripts.
 */

import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { SidebarPanel } from './sidebar-panel.js';

registerPlugin( 'secondary-title-sidebar', {
	render: SidebarPanel,
	icon: null,
} );

// Mark as translated for tooling.
__( 'Secondary Title' );
