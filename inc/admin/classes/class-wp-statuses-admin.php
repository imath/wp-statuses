<?php
/**
 * WP Statuses Admin Class.
 *
 * @package WP Statuses\admin\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class WP_Statuses_Admin {
	public $post_type = '';
	public $post_type_object = null;
	public $post_type_capability = 'publish_posts';

	public function __construct() {
		//$this->setup_globals();
		//$this->includes();
		$this->hooks();
	}

	/**
	 * Starts the Admin class
	 *
	 * @since 1.0.0
	 */
	public static function start() {
		if ( ! is_admin() ) {
			return;
		}

		$wp_statuses = wp_statuses();

		if ( empty( $wp_statuses->admin ) ) {
			$wp_statuses->admin = new self;
		}

		return $wp_statuses->admin;
	}

	/**
	 * Setups the action and filters to hook to
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_script' ), 1 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
	}

	public function register_script() {
		wp_register_script(
			'wp-statuses',
			sprintf( '%1$sscript%2$s.js', wp_statuses_js_url(), wp_statuses_min_suffix() ),
			array( 'jquery', 'post' ),
			wp_statuses_version(),
			true
		);

		$current_screen = get_current_screen();
		if ( isset( $current_screen->id ) && in_array( $current_screen->id, array( 'page', 'post' ), true ) ) {
			wp_add_inline_style( 'edit', '
				#wp-statuses-publish-box .inside {
					margin: 0;
					padding: 0;
				}

				#wp-statuses-dropdown {
					width: calc( 100% - 29px );
				}

				#misc-publishing-actions .misc-pub-section span.dashicons {
					vertical-align: middle;
					color: #82878c;
					padding-right: 3px;
				}
			' );
		}
	}

	public function add_meta_box( $post_type, $post ) {
		global $publish_callback_args;

		// The Publishing box
		add_meta_box(
			'wp-statuses-publish-box',
			__( 'Publishing', 'wp-statuses' ),
			array( $this, 'publishing_box' ),
			$post_type,
			'side',
			'high',
			$publish_callback_args
		);

		// Validate the post type.
		$this->post_type_object = get_post_type_object( $post_type );

		if ( is_a( $this->post_type_object, 'WP_Post_Type' ) ) {
			$this->post_type            = $post_type;
			$this->post_type_capability = $this->post_type_object->cap->publish_posts;
		}
	}

	public function publishing_box( $post = null, $args = array() ) {
		if ( empty( $post->post_type ) ) {
			return;
		}

		$status = $post->post_status;
		if ( 'auto-draft' === $status ) {
			$status = 'draft';
		}

		// Load script for the metabox.
		wp_enqueue_script ( 'wp-statuses' );
		wp_localize_script( 'wp-statuses', 'wpStatuses', array(
			'status'     => $status,
			'attributes' => array(
				'password' => $post->post_password,
				'sticky'   => is_sticky( $post->ID ),
			),
		) );
		?>
		<div class="submitbox" id="submitpost">

			<?php
			/**
			 * Split parts for a better visibility.
			 */
			$this->get_minor_publishing_div( $post, $status );
			$this->get_major_publishing_div( $post, $status );
			$this->get_status_extra_attributes( $post, $status );
			$this->get_publishing_time_div( $post, $status, $args ); ?>

		</div><!-- #submitpost -->
		<?php
	}

	public function get_minor_publishing_div( $post = null, $status = '' ) {
		if ( empty( $post->post_type ) || empty( $status ) ) {
			return;
		}
		?>
		<div id="minor-publishing">

			<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
			<div style="display:none;">
				<?php submit_button( __( 'Save' ), '', 'save' ); ?>
			</div>

			<div id="minor-publishing-actions">
				<div id="save-action">

					<?php if ( 'draft' === $status ) : ?>

						<input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save Draft', 'wp-statuses' ); ?>" class="button" />

					<?php elseif ( 'pending' === $status && current_user_can( $this->post_type_capability ) ) : ?>

						<input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save as Pending', 'wp-statuses' ); ?>" class="button" />

					<?php endif ; ?>

					<span class="spinner"></span>

				</div>

				<?php if ( is_post_type_viewable( $this->post_type_object ) ) : ?>

					<div id="preview-action">
						<?php printf( '<a class="preview button" href="%1$s" target="wp-preview-%2$s" id="post-preview">%3$s</a>',
							esc_url( get_preview_post_link( $post ) ),
							(int) $post->ID,
							'draft' === $status ? esc_html__( 'Preview', 'wp-statuses' ) : esc_html__( 'Preview Changes', 'wp-statuses' )
						); ?>
						<input type="hidden" name="wp-preview" id="wp-preview" value="" />
					</div>

				<?php endif;

				/**
				 * Fires before the post time/date setting in the Publish meta box.
				 *
				 * @since 4.4.0
				 *
				 * @param WP_Post $post WP_Post object for the current post.
				 */
				do_action( 'post_submitbox_minor_actions', $post ); ?>

				<div class="clear"></div>
			</div><!-- #minor-publishing-actions -->
		</div><!-- #minor-publishing -->
		<?php
	}

	public function get_major_publishing_div( $post = null, $current = '' ) {
		if ( empty( $post->post_type ) || empty( $current ) ) {
			return;
		}

		$statuses = wp_statuses_get_metabox_statuses( $post->post_type );

		$options        = array( '<select name="post_status" id="wp-statuses-dropdown">' );
		$dashicon       = 'dashicons-post-status';
		$status_display = '';

		foreach ( $statuses as $status ) {
			$current_status = $current;
			$value          = $status->name;

			// Password is a publish status
			if ( 'password' === $status->name ) {
				$value = 'publish';
			}
			// Future will become a publish status
			if ( 'future' === $current ) {
				$current_status = 'publish';

				if ( 'publish' === $status->name ) {
					$value = 'future';
				}
			}

			$selected = selected( $current_status, $status->name, false );

			if ( $selected ) {
				$dashicon       = $status->dashicon;
				$status_display = $status->labels['metabox_dropdown'];
			}

			$options[] = '<option value="' . esc_attr( $value ) .'" ' . $selected . ' data-dashicon="' . esc_attr( $status->dashicon ) . '" data-status="' . $status->name . '">' . esc_html( $status->labels['metabox_dropdown'] ) . '</option>';
		}

		if ( ! current_user_can( $this->post_type_capability ) ) {
			$options = array(
				sprintf( '<input type="hidden" name="post_status" value="%s">', esc_attr( $current ) ),
				sprintf( '<span id="post-status-display">%s</span>', esc_html( $status_display ) ),
			);
		} else {
			$options[] = '</select>';
		}

		?>
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">

				<label for="post_status" class="screen-reader-text"><?php esc_html_e( 'Set status', 'wp-statuses' ); ?></label>
				<?php printf(
					'<span class="dashicons %1$s"></span> %2$s',
					sanitize_html_class( $dashicon ),
					join( "\n", $options )
				); ?>

			</div><!-- .misc-pub-section -->
		</div><!-- #misc-publishing-actions -->
		<?php
	}

	public function get_status_extra_attributes( $post = null, $status = '' ) {
		if ( empty( $post->post_type ) || empty( $status ) || ! current_user_can( $this->post_type_capability ) ) {
			return;
		}

		$sticky_class = $password_class = 'hide-if-js';
		if ( 'private' !== $status && ! $post->post_password ) {
			$sticky_class = '';
		}

		if ( 'private' !== $status && ! empty( $post->post_password ) ) {
			$password_class = '';
		}

		?>
		<div class="misc-pub-section misc-pub-attributes" id="wp-statuses-attibutes">
			<div id="post-attibutes-input">
				<?php if ( 'post' === $post->post_type && current_user_can( 'edit_others_posts' ) ) : ?>
					<span id="sticky-span" class="<?php echo sanitize_html_class( $sticky_class ); ?> wp-statuses-attribute-container">
						<input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked( is_sticky( $post->ID ) ); ?> />
						<label for="sticky" class="selectit">
							<?php esc_html_e( 'Stick this post to the front page', 'wp-statuses' ); ?>
						</label>
						<br />
					</span>
				<?php endif ; ?>

				<span id="password-span" class="<?php echo sanitize_html_class( $password_class ); ?> wp-statuses-attribute-container">
					<label for="post_password"><?php _e( 'Password:', 'wp-statuses' ); ?></label>
					<input type="text" name="post_password" id="post_password" value="<?php echo esc_attr( $post->post_password ); ?>"  maxlength="255" />
					<br />
				</span>

				<?php
				/**
				 * Hook here if you need to add some extra attibutes for your custom status.
				 *
				 * @since 1.0.0
				 *
				 * @param WP_Post $post   The Post object.
				 * @param string  $status The current status for the post.
				 */
				do_action( 'wp_statuses_metabox_extra_attributes', $post, $status );?>
			</div>
		</div><!-- .misc-pub-attributes -->
		<?php
	}

	public function get_publishing_time_div( $post = null, $status = '', $args = array() ) {
		if ( empty( $post->post_type ) || empty( $status ) || ! current_user_can( $this->post_type_capability ) ) {
			return;
		}

		global $action;
		$is_future = time() < strtotime( $post->post_date_gmt . ' +0000' );

		/* translators: Publish box date format, see https://secure.php.net/date */
		$datef = __( 'M j, Y @ H:i' );

		// Post already exists.
		if ( 0 !== (int) $post->ID ) {
			// scheduled for publishing at a future date.
			if ( 'future' === $status || ( 'draft' !== $status && $is_future ) ) {
				/* translators: Post date information. 1: Date on which the post is currently scheduled to be published */
				$stamp = __( 'Scheduled for: <b>%1$s</b>', 'wp-statuses' );

			// already published.
			} elseif ( 'publish' === $post->post_status || 'private' === $post->post_status ) {
				/* translators: Post date information. 1: Date on which the post was published */
				$stamp = __( 'Published on: <b>%1$s</b>', 'wp-statuses' );

			// draft, 1 or more saves, no date specified.
			} elseif ( '0000-00-00 00:00:00' == $post->post_date_gmt ) {
				$stamp = __( 'Publish <b>immediately</b>', 'wp-statuses' );

			// draft, 1 or more saves, future date specified.
			} elseif ( $is_future ) {
				/* translators: Post date information. 1: Date on which the post is to be published */
				$stamp = __( 'Schedule for: <b>%1$s</b>', 'wp-statuses' );

			// draft, 1 or more saves, date specified.
			} else {
				/* translators: Post date information. 1: Date on which the post is to be published */
				$stamp = __( 'Publish on: <b>%1$s</b>', 'wp-statuses' );
			}

			$date = date_i18n( $datef, strtotime( $post->post_date ) );

		// draft (no saves, and thus no date specified).
		} else {
			$stamp = __( 'Publish <b>immediately</b>', 'wp-statuses' );
			$date = date_i18n( $datef, strtotime( current_time( 'mysql' ) ) );
		}

		if ( ! empty( $args['args']['revisions_count'] ) ) : ?>
			<div class="misc-pub-section misc-pub-revisions">
				<?php
					/* translators: Post revisions heading. 1: The number of available revisions */
					printf( __( 'Revisions: %s', 'wp-statuses' ), '<b>' . number_format_i18n( $args['args']['revisions_count'] ) . '</b>' );
				?>
				<a class="hide-if-no-js" href="<?php echo esc_url( get_edit_post_link( $args['args']['revision_id'] ) ); ?>">
					<span aria-hidden="true"><?php echo esc_html_x( 'Browse', 'revisions', 'wp-statuses' ); ?></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Browse revisions', 'wp-statuses' ); ?></span>
				</a>
			</div><!-- .misc-pub-revisions -->
		<?php endif; ?>

			<div class="misc-pub-section curtime misc-pub-curtime">
				<span id="timestamp">
					<?php printf( $stamp, $date ); ?>
				</span>

				<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" role="button">
					<span aria-hidden="true"><?php _e( 'Edit', 'wp-statuses' ); ?></span>
					<span class="screen-reader-text"><?php _e( 'Edit date and time', 'wp-statuses' ); ?></span>
				</a>

				<fieldset id="timestampdiv" class="hide-if-js">
					<legend class="screen-reader-text"><?php esc_html_e( 'Date and time', 'wp-statuses' ); ?></legend>

					<?php touch_time( ( $action === 'edit' ), 1 ); ?>
				</fieldset>
			</div><!-- .misc-pub-curtime -->
		<?php
	}
}
