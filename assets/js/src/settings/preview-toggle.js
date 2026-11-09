/*
 * Copyright (C) 2026-2026 by Kolja Nolte
 * https://www.kolja-nolte.com
 * kolja.nolte@gmail.com
 *
 * All Rights Reserved.
 *
 * This code is proprietary software. You may not use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of
 * the Software without express written permission from the author.
 *
 * @author Kolja Nolte <kolja.nolte@gmail.com>
 * @date 2024-2026
 */

/**
 * Toggles the format preview between light and dark mode.
 *
 * @param {Document} root The root document to scope queries.
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
