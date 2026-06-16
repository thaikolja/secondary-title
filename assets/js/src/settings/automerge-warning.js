/**
 * Toggles the auto-merge OFF warning below the live preview
 * when the user flips the "Auto-merge" toggle.
 */
export function initAutomergeWarning( root ) {
	const toggle = root.querySelector( '#st-toggle-auto-show' );
	const warning = root.querySelector( '#st-automerge-warning' );

	if ( ! toggle || ! warning ) {
		return;
	}

	const update = () => {
		if ( toggle.checked ) {
			warning.hidden = true;
		} else {
			warning.hidden = false;
		}
	};

	toggle.addEventListener( 'change', update );
	update();
}
