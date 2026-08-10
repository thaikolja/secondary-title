/**
 * Edit component for the /secondary-title canvas block.
 *
 * Wraps the block's server-side rendered output with editor-only
 * controls:
 *   - Which post to read the secondary title from (default: current).
 *   - Which HTML tag wraps the secondary title (default: span).
 *
 * Uses ServerSideRender to preview the exact PHP output inside the
 * block canvas, so what the editor shows is what the front end
 * will render.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useSelect } from '@wordpress/data';

const TAGS = [
	{ label: 'span', value: 'span' },
	{ label: 'em', value: 'em' },
	{ label: 'strong', value: 'strong' },
	{ label: 'small', value: 'small' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { postId, wrapperTag } = attributes;
	const blockProps = useBlockProps();

	const currentPostId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Secondary Title', 'secondary-title' ) }>
					<TextControl
						label={ __( 'Post ID', 'secondary-title' ) }
						help={ __(
							'Leave 0 to use the current post.',
							'secondary-title'
						) }
						type="number"
						value={ postId || 0 }
						onChange={ ( value ) =>
							setAttributes( {
								postId: parseInt( value, 10 ) || 0,
							} )
						}
					/>
					<SelectControl
						label={ __( 'Wrapper tag', 'secondary-title' ) }
						value={ wrapperTag }
						options={ TAGS }
						onChange={ ( value ) =>
							setAttributes( { wrapperTag: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="secondary-title/secondary-title"
					attributes={ {
						postId: postId || currentPostId,
						wrapperTag,
					} }
					EmptyResponsePlaceholder={ () => (
						<p className="st-block-empty">
							{ __(
								'(no secondary title set)',
								'secondary-title'
							) }
						</p>
					) }
				/>
			</div>
		</>
	);
}
