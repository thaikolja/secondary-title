/**
 * Searchable multi-select widget.
 *
 * Filters the visible list items as the user types into the
 * search input. Hides non-matching items and toggles a "no
 * results" message. Also handles the "Toggle all" button.
 *
 * @param {Document} root The root document to scope queries.
 */

export function initSearchableSelects( root ) {
	const widgets = root.querySelectorAll( '[data-st-multi-select]' );
	if ( ! widgets.length ) {
		return;
	}

	widgets.forEach( ( widget ) => {
		const search = widget.querySelector( '[data-st-multi-select-search]' );
		const items = widget.querySelectorAll( '[data-st-multi-select-item]' );

		if ( ! search || ! items.length ) {
			return;
		}

		const toggle = widget.querySelector( '[data-st-multi-select-toggle]' );
		const listEl = widget.querySelector( '.st-multi-select__list' );

		const labels = [];
		items.forEach( ( item ) => {
			const label = item.querySelector( '.st-multi-select__label' );
			labels.push( {
				item,
				label: label ? label.textContent.toLowerCase() : '',
			} );
		} );

		const filter = () => {
			const needle = search.value.trim().toLowerCase();
			let visible = 0;
			labels.forEach( ( entry ) => {
				const match =
					'' === needle || entry.label.indexOf( needle ) !== -1;
				entry.item.style.display = match ? '' : 'none';
				if ( match ) {
					visible++;
				}
			} );

			// Empty-state message.
			let empty = widget.querySelector( '.st-multi-select__empty' );
			if ( 0 === visible && listEl ) {
				if ( ! empty ) {
					empty = root.createElement( 'p' );
					empty.className = 'st-multi-select__empty description';
					empty.textContent = 'No matches';
					listEl.after( empty );
				}
			} else if ( empty ) {
				empty.remove();
			}
		};

		search.addEventListener( 'input', filter );

		if ( toggle ) {
			toggle.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				// Only toggle the visible (search-filtered) items.
				const visibleCheckboxes = Array.from( items )
					.filter( ( item ) => 'none' !== item.style.display )
					.map( ( item ) =>
						item.querySelector( 'input[type="checkbox"]' )
					)
					.filter( Boolean );

				if ( 0 === visibleCheckboxes.length ) {
					return;
				}

				const allChecked = visibleCheckboxes.every(
					( cb ) => cb.checked
				);
				visibleCheckboxes.forEach( ( cb ) => {
					cb.checked = ! allChecked;
				} );
			} );
		}
	} );
}
