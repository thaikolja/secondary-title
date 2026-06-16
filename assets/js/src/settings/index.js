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
