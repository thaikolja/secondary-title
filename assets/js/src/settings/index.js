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
 * Settings page entry point — vanilla JS.
 */
import '../../../css/src/admin.scss';
import { initTabs } from './tabs.js';
import { initLiveFormatPreview } from './live-preview.js';
import { initSearchableSelects } from './searchable-select.js';
import { initSaveBar } from './save-bar.js';
import { initSidebar } from './sidebar.js';
import { initAutomergeWarning } from './automerge-warning.js';
import { initPreviewToggle } from './preview-toggle.js';

document.addEventListener( 'DOMContentLoaded', () => {
	initTabs( document );
	initLiveFormatPreview( document );
	initSearchableSelects( document );
	initSaveBar( document );
	initSidebar( document );
	initAutomergeWarning( document );
	initPreviewToggle( document );
} );
