/*
 * Copyright (C) 2026-2026 by Kolja Nolte
 * https://www.kolja-nolte.com
 * kolja.nolte@gmail.com
 *
 * All Rights Reserved.
 *
 * This code is proprietary software. You may not use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of
 * the Software without express written permission from the author.
 *
 * @author Kolja Nolte <kolja.nolte@gmail.com>
 * @date 2024-2026
 */

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
 * WordPress packages are externalized at build time and provided
 * by the block editor at runtime.
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
