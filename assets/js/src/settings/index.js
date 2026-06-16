/**
 * Settings page entry point.
 *
 * Vanilla JS (ES module). Two responsibilities:
 *   1. Live format preview: as the user types in the title-format
 *      input, replace %title% and %secondary_title% in the preview
 *      area with the sample values baked into the template.
 *   2. Searchable multi-selects for post types and categories.
 *
 * No React, no jQuery. Uses the W3C-standard Element API.
 */

import '../../../css/src/admin.scss';

import { initLiveFormatPreview } from './live-preview.js';
import { initSearchableSelects } from './searchable-select.js';
import { initSaveBar } from './save-bar.js';

document.addEventListener( 'DOMContentLoaded', () => {
	initLiveFormatPreview( document );
	initSearchableSelects( document );
	initSaveBar( document );
} );
