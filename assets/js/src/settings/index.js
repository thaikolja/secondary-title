/**
 * Settings page entry point — vanilla JS.
 */
import '../../../css/src/admin.scss';
import { initTabs } from './tabs.js';
import { initLiveFormatPreview } from './live-preview.js';
import { initSearchableSelects } from './searchable-select.js';
import { initSaveBar } from './save-bar.js';

document.addEventListener( 'DOMContentLoaded', () => {
	initTabs( document );
	initLiveFormatPreview( document );
	initSearchableSelects( document );
	initSaveBar( document );
} );
