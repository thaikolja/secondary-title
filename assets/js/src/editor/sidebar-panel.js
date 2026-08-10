/**
 * Gutenberg sidebar panel component.
 *
 * Renders inside the post editor's sidebar, showing:
 *   - The secondary title input (text field).
 *   - A live preview of the rendered format with the current post's
 *     title and the typed secondary title.
 */

import {
	useState,
	useEffect,
	useMemo,
	useCallback,
	useRef,
} from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

const META_KEY = '_secondary_title';
const DEBOUNCE_MS = 300;

export function SidebarPanel() {
	const postId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);
	const postTitle = useSelect(
		( select ) => select( 'core/editor' ).getEditedPostAttribute( 'title' ),
		[]
	);
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const initial = useMemo( () => {
		if (
			typeof window === 'undefined' ||
			! window.SecondaryTitleBootstrap
		) {
			return { secondary: '', format: '%secondary_title%: %title%' };
		}
		return window.SecondaryTitleBootstrap;
	}, [] );

	const [ secondary, setSecondary ] = useState( initial.secondary );
	const [ format ] = useState( initial.format );
	const { editPost } = useDispatch( 'core/editor' );

	const timerRef = useRef( null );

	const persist = useCallback(
		( value ) => {
			if ( timerRef.current ) {
				clearTimeout( timerRef.current );
			}

			timerRef.current = setTimeout( () => {
				const restBase = postType
					? `wp/v2/${ postType }s`
					: 'wp/v2/posts';

				apiFetch( {
					path: addQueryArgs( `/${ restBase }/${ postId }`, {} ),
					method: 'PUT',
					data: { meta: { [ META_KEY ]: value } },
				} ).catch( () => {
					// Fallback for post types without meta REST support.
					editPost( { meta: { [ META_KEY ]: value } } );
				} );
			}, DEBOUNCE_MS );
		},
		[ postId, postType, editPost ]
	);

	// Sync initial value into the editor meta on mount (once).
	useEffect( () => {
		editPost( { meta: { [ META_KEY ]: initial.secondary } } );
		// Only run on mount — ignore deps changes.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useEffect( () => {
		persist( secondary );
	}, [ secondary, persist ] );

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
				onChange={ setSecondary }
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
