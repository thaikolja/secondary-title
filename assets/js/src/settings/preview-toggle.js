/**
 * Toggles the format preview between light and dark mode.
 */
export function initPreviewToggle( root ) {
	const btn = root.querySelector( '[data-st-preview-toggle]' );
	const preview = root.querySelector( '#st-format-preview' );

	if ( ! btn || ! preview ) {
		return;
	}

	const label = btn.querySelector( 'span' );

	btn.addEventListener( 'click', () => {
		const isDark = preview.classList.toggle( 'st-preview-hero--dark' );
		btn.classList.toggle( 'st-preview-toggle--on', isDark );

		if ( label ) {
			label.textContent = isDark ? 'Light' : 'Dark';
		}
	} );
}
