/**
 * Tab switching for the settings page.
 *
 * Vanilla JS — no React, no jQuery. Data attribute-driven.
 */

export function initTabs( root ) {
	const tabs    = root.querySelectorAll( '.st-tab' );
	const panels  = root.querySelectorAll( '.st-panel' );

	if ( ! tabs.length || ! panels.length ) { return; }

	tabs.forEach( ( tab ) => {
		tab.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			const name = tab.getAttribute( 'data-tab' );
			if ( ! name ) { return; }

			tabs.forEach( ( t ) => {
				const active = t.getAttribute( 'data-tab' ) === name;
				t.classList.toggle( 'st-tab--active', active );
				t.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );

			panels.forEach( ( p ) => {
				const match = p.getAttribute( 'data-panel' ) === name;
				p.classList.toggle( 'st-panel--active', match );
				p.hidden = ! match;
			} );
		} );
	} );
}
