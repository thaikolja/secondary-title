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
 * Gutenberg sidebar panel component.
 *
 * Renders inside the post editor's sidebar, showing:
 *   - The secondary title input (text field).
 *   - A live preview of the rendered format with the current post's
 *     title and the typed secondary title.
 *
 * The value is stored on the post entity (`meta._secondary_title`)
 * so it is saved with Update / Publish. The format string comes from
 * `window.SecondaryTitleBootstrap.format`.
 */

import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const META_KEY = '_secondary_title';
const DEFAULT_FORMAT = '%secondary_title%: %title%';

export function SidebarPanel() {
	const postTitle = useSelect(
		( select ) => select( 'core/editor' ).getEditedPostAttribute( 'title' ),
		[]
	);
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		postType || 'post',
		'meta'
	);

	const format = useMemo( () => {
		if (
			typeof window === 'undefined' ||
			! window.SecondaryTitleBootstrap ||
			! window.SecondaryTitleBootstrap.format
		) {
			return DEFAULT_FORMAT;
		}
		return window.SecondaryTitleBootstrap.format;
	}, [] );

	const secondary =
		meta && typeof meta[ META_KEY ] === 'string' ? meta[ META_KEY ] : '';

	const onChange = ( value ) => {
		setMeta( {
			...( meta || {} ),
			[ META_KEY ]: value,
		} );
	};

	const preview = useMemo( () => {
		return ( format || '' )
			.replace( /%title%/g, postTitle || '' )
			.replace( /%secondary_title%/g, secondary || '' );
	}, [ format, postTitle, secondary ] );

	return (
		<PluginDocumentSettingPanel
			name="secondary-title-panel"
			title={ __( 'Secondary Title', 'secondary-title' ) }
		>
			<TextControl
				label={ __( 'Secondary title', 'secondary-title' ) }
				help={ __(
					'The secondary title is shown next to the post title based on the title format.',
					'secondary-title'
				) }
				value={ secondary }
				onChange={ onChange }
				placeholder={ __(
					'Enter secondary title here',
					'secondary-title'
				) }
			/>

			<div className="st-format-field__preview" data-st-preview>
				<header className="st-format-field__preview-head">
					<span
						className="dashicons dashicons-visibility"
						aria-hidden="true"
					></span>
					<span>{ __( 'Preview', 'secondary-title' ) }</span>
				</header>
				<div
					className="st-format-field__preview-body"
					dangerouslySetInnerHTML={ { __html: preview } }
				/>
			</div>
		</PluginDocumentSettingPanel>
	);
}
