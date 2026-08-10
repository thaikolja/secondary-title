/**
 * Toggles the auto-merge OFF warning below the live preview
 * with a smooth slide animation when the user flips the toggle.
 * Also updates the sidebar Plugin Info row in real-time.
 *
 * @param {Document} root The root document to scope queries.
 */
export function initAutomergeWarning( root ) {
	const toggle = root.querySelector( '#st-toggle-auto-show' );
	const warning = root.querySelector( '#st-automerge-warning' );

	if ( ! toggle || ! warning ) {
		return;
	}

	const infoEl = root.querySelector( '[data-st-info-automerge]' );

	const update = () => {
		const on = toggle.checked;

		// Slide warning
		if ( on ) {
			warning.classList.remove( 'st-automerge-warning--visible' );
		} else {
			warning.classList.add( 'st-automerge-warning--visible' );
		}

		// Update sidebar Plugin Info row
		if ( infoEl ) {
			infoEl.textContent = on ? 'Enabled' : 'Disabled';
			infoEl.className =
				'st-info-row__value st-info-row__value--' +
				( on ? 'on' : 'off' );
		}
	};

	toggle.addEventListener( 'change', update );
}
