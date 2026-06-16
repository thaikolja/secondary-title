/**
 * Save bar: native confirm() on the "Reset" button.
 *
 * @param {Document} root The root document to scope queries.
 */

export function initSaveBar( root ) {
	const reset = root.querySelector( '[data-st-reset]' );
	if ( reset ) {
		reset.addEventListener( 'click', ( event ) => {
			const message = reset.getAttribute( 'data-confirm' );
			if ( message && ! window.confirm( message ) ) {
				event.preventDefault();
			}
		} );
	}
}
