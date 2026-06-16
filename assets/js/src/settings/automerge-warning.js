/**
 * Toggles the auto-merge OFF warning below the live preview
 * with a smooth slide animation when the user flips the toggle.
 */
export function initAutomergeWarning( root ) {
	const toggle = root.querySelector( '#st-toggle-auto-show' );
	const warning = root.querySelector( '#st-automerge-warning' );

	if ( ! toggle || ! warning ) {
		return;
	}

	const update = () => {
		if ( toggle.checked ) {
			warning.classList.remove( 'st-automerge-warning--visible' );
		} else {
			warning.classList.add( 'st-automerge-warning--visible' );
		}
	};

	toggle.addEventListener( 'change', update );
}
