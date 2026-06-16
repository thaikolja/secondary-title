<?php
/**
 * Classic Editor meta box for the secondary title.
 *
 * Renders a proper WordPress meta box (not the v2.x.x jQuery
 * injection) with a clean input, a live format preview, and a
 * nonce-protected save handler.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Editor;

use WP_Post;
use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;
use Thaikolja\SecondaryTitle\Meta\Sanitizer as MetaSanitizer;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Renderer\Format as Format;
use Thaikolja\SecondaryTitle\Renderer\Wrapper as Wrapper;

/**
 * Classic Editor meta box.
 *
 * @since 3.0.0
 */
final class MetaBox {

	/**
	 * The meta box ID (used for hooks and CSS targeting).
	 *
	 * Stable across versions so addons that hide / restyle the box
	 * via CSS keep working.
	 *
	 * @var string
	 */
	public const ID = 'secondary_title_classic_meta_box';

	/**
	 * The form field name (stable across versions).
	 *
	 * @var string
	 */
	public const FIELD_NAME = 'secondary_post_title';

	/**
	 * Nonce action used when persisting the value via save_post.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'secondary_title_save_classic';

	/**
	 * @var MetaRepository
	 */
	private readonly MetaRepository $meta_repository;

	/**
	 * @var MetaSanitizer
	 */
	private readonly MetaSanitizer $meta_sanitizer;

	/**
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $settings_repository;

	/**
	 * @var Format
	 */
	private readonly Format $format;

	/**
	 * @var Wrapper
	 */
	private readonly Wrapper $wrapper;

	/**
	 * @param MetaRepository    $meta_repository    Meta read/write.
	 * @param MetaSanitizer     $meta_sanitizer     Meta sanitizer.
	 * @param SettingsRepository $settings_repository Settings repository.
	 * @param Format             $format              Title format.
	 * @param Wrapper            $wrapper             Output wrapper.
	 */
	public function __construct(
		MetaRepository $meta_repository,
		MetaSanitizer $meta_sanitizer,
		SettingsRepository $settings_repository,
		Format $format,
		Wrapper $wrapper
	) {
		$this->meta_repository    = $meta_repository;
		$this->meta_sanitizer     = $meta_sanitizer;
		$this->settings_repository = $settings_repository;
		$this->format              = $format;
		$this->wrapper             = $wrapper;
	}

	/**
	 * Registers the WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save' ], 10, 2 );
	}

	/**
	 * `add_meta_boxes` callback. Registers the meta box for every
	 * post type that has the plugin enabled.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		$enabled = $this->enabled_post_types();

		if ( [] === $enabled ) {
			return;
		}

		add_meta_box(
			self::ID,
			__( 'Secondary Title', 'secondary-title' ),
			[ $this, 'render' ],
			$enabled,
			'normal',
			'high'
		);
	}

	/**
	 * Renders the meta box HTML.
	 *
	 * Called by WordPress with the current WP_Post as the only
	 * argument.
	 *
	 * @param WP_Post $post The current post.
	 *
	 * @return void
	 */
	public function render( WP_Post $post ): void {
		$secondary_title = (string) get_post_meta( $post->ID, Plugin::META_KEY, true );
		$format          = (string) $this->settings_repository->get( SettingsDefaults::OPTION_TITLE_FORMAT, SettingsDefaults::TITLE_FORMAT );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_ACTION );
		?>
		<div class="st-meta-box">
			<label for="st-classic-secondary-title" class="screen-reader-text">
				<?php esc_html_e( 'Secondary title', 'secondary-title' ); ?>
			</label>
			<input
				type="text"
				id="st-classic-secondary-title"
				class="st-input widefat"
				name="<?php echo esc_attr( self::FIELD_NAME ); ?>"
				value="<?php echo esc_attr( $secondary_title ); ?>"
				placeholder="<?php esc_attr_e( 'Enter secondary title here', 'secondary-title' ); ?>"
			/>

			<?php if ( $this->settings_repository->get( SettingsDefaults::OPTION_AUTO_SHOW ) === SettingsDefaults::ON ) : ?>
				<p class="st-meta-box__preview-label">
					<?php esc_html_e( 'Preview', 'secondary-title' ); ?>
				</p>
				<div class="st-meta-box__preview" data-st-classic-preview
				     data-st-format="<?php echo esc_attr( $format ); ?>"
				     data-st-secondary="<?php echo esc_attr( $secondary_title ); ?>"
				     data-st-title="<?php echo esc_attr( $post->post_title ); ?>">
					<?php
					$preview = $this->format->render(
						$post->post_title,
						$this->wrapper->wrap( $secondary_title ),
						$post->ID
					);
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Title format is sanitized on save.
					echo $preview;
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * `save_post` callback. Persists the secondary title from the
	 * Classic Editor form submission.
	 *
	 * Performs nonce check + capability check before touching the
	 * database. Auto-saves and revisions are ignored.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		/**
		 * The field may be missing if the box wasn't rendered (the
		 * post type is disabled, or the user has hidden the box).
		 */
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Check below.
		$raw_value = $_POST[ self::FIELD_NAME ] ?? null;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! is_string( $raw_value ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_ACTION ] ) ) {
			return;
		}

		$nonce = (string) $_POST[ self::NONCE_ACTION ];
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save via the repository (sanitization — including wp_unslash — happens there).
		$this->meta_repository->save( $post_id, sanitize_text_field( $raw_value ) );
	}

	/**
	 * Returns the list of post types the secondary title is
	 * enabled for, falling back to all public post types when the
	 * setting is empty.
	 *
	 * @return array<int, string>
	 */
	private function enabled_post_types(): array {
		$enabled = (array) $this->settings_repository->get( SettingsDefaults::OPTION_POST_TYPES, [] );

		if ( [] !== $enabled ) {
			return array_values( array_filter( $enabled, 'post_type_exists' ) );
		}

		// Empty = all public post types except attachments.
		$public = get_post_types( [ 'public' => true ] );
		return array_values( array_filter( $public, static fn ( string $t ): bool => 'attachment' !== $t ) );
	}
}
