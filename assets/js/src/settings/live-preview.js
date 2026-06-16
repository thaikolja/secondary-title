/**
 * Live preview for the title-format input.
 *
 * Reads the format value on every input event, substitutes the
 * %title% and %secondary_title% placeholders with the sample
 * values baked into the template, and writes the result into the
 * preview area.
 *
 * Both the format AND the replacement values are escaped before
 * being assigned to innerHTML, so the live preview cannot be used
 * as an XSS vector (the server sanitizes on save as well).
 *
 * @param {Document} root The root document to scope queries.
 */

export function initLiveFormatPreview( root ) {
	const inputs = root.querySelectorAll( '[data-st-format-input]' );
	if ( ! inputs.length ) {
		return;
	}

	inputs.forEach( ( input ) => {
		const targetId = input.getAttribute( 'data-st-preview-target' );
		if ( ! targetId ) {
			return;
		}
		const preview = root.getElementById( targetId );
		if ( ! preview ) {
			return;
		}

		const update = () => {
			const format = input.value || '';

			// Read sample values from data attributes on the preview
			// container (set server-side by the Twig template).
			const sampleTitle = preview.getAttribute( 'data-st-sample-title' ) || '';
			const sampleSecondary = preview.getAttribute( 'data-st-sample-secondary' ) || '';

			// Render: escape EVERYTHING (format + replacements),
			// then write to innerHTML. The rendered HTML contains
			// only escaped text — no tag survives.
			const rendered = escapeHtml( format )
				.replace( /%title%/g, escapeHtml( sampleTitle ) )
				.replace( /%secondary_title%/g, escapeHtml( sampleSecondary ) );

			preview.innerHTML = rendered;
		};

		input.addEventListener( 'input', update );

		// Click-to-insert for the placeholder chips.
		const chips = root.querySelectorAll( '[data-st-placeholder]' );
		chips.forEach( ( chip ) => {
			chip.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				const placeholder = chip.getAttribute( 'data-st-placeholder' );
				if ( ! placeholder ) {
					return;
				}
				insertAtCursor( input, placeholder );
				update();
			} );
		} );

		// Click-to-set for preset example buttons.
		const presets = root.querySelectorAll( '[data-st-preset]' );
		presets.forEach( ( btn ) => {
			btn.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				const value = btn.getAttribute( 'data-st-preset' );
				if ( value === null ) {
					return;
				}
				input.value = value;
				update();
			} );
		} );

		// Initial render.
		update();
	} );
}

/**
 * Escapes the five HTML-significant characters so the value can
 * be safely assigned to innerHTML.
 *
 * @param {string} value
 * @returns {string}
 */
function escapeHtml( value ) {
	return String( value )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#39;' );
}

/**
 * Inserts $text at the cursor position of $input. If the input
 * is not focused, appends the text to the end.
 *
 * @param {HTMLInputElement} input
 * @param {string} text
 */
function insertAtCursor( input, text ) {
	const start = input.selectionStart ?? input.value.length;
	const end = input.selectionEnd ?? input.value.length;
	const before = input.value.slice( 0, start );
	const after = input.value.slice( end );
	input.value = before + text + after;
	const cursor = start + text.length;
	input.focus();
	input.setSelectionRange( cursor, cursor );
}
