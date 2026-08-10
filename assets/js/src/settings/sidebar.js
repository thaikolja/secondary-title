/**
 * Sidebar — taxonomy switcher + term search.
 *
 * Reads the taxonomy <select> value, shows the matching term list,
 * hides all others, then filters the visible list as the user types
 * into the search input.
 *
 * Vanilla JS, no React, no jQuery.
 *
 * @param {Document} root
 */
export function initSidebar( root ) {
	const sidebar = root.querySelector( '[data-st-sidebar]' );
	if ( ! sidebar ) {
		return;
	}

	const select = sidebar.querySelector( '[data-st-taxonomy-select]' );
	const lists = sidebar.querySelectorAll( '[data-st-term-list]' );

	if ( ! select || ! lists.length ) {
		return;
	}

	const search = sidebar.querySelector( '[data-st-sidebar-search]' );

	/** Show only the list that matches the current taxonomy value. */
	function switchTaxonomy() {
		const value = select.value;
		lists.forEach( ( list ) => {
			const match = list.getAttribute( 'data-st-term-list' ) === value;
			list.hidden = ! match;
		} );
		// Reset search when switching taxonomy.
		if ( search ) {
			search.value = '';
			filterTerms( '' );
		}
	}

	/**
	 * Filter visible items in the active list.
	 *
	 * @param {string} needle The search term to filter by.
	 */
	function filterTerms( needle ) {
		const active = sidebar.querySelector(
			'[data-st-term-list]:not([hidden])'
		);
		if ( ! active ) {
			return;
		}

		const items = active.querySelectorAll( '.st-term-list__item' );
		items.forEach( ( item ) => {
			const label = item.getAttribute( 'data-label' ) || '';
			item.style.display =
				'' === needle || label.includes( needle ) ? '' : 'none';
		} );
	}

	select.addEventListener( 'change', switchTaxonomy );

	if ( search ) {
		search.addEventListener( 'input', () => {
			filterTerms( search.value.trim().toLowerCase() );
		} );
	}

	// Initial state — driven by the current select value.
	switchTaxonomy();
}
