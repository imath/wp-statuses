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

/**
 * The admin class
 *
 * @since  1.0.0
 */
class WP_Statuses_Admin {
	/**
	 * The post type displayed in the Admin screen.
	 *
	 * @var string
	 */
	public $post_type = '';

	/**
	 * The post type object displayed in the Admin screen.
	 *
	 * @var null|object
	 */
	public $post_type_object = null;

	/**
	 * The required capability to publish post types.
	 *
	 * @var string
	 */
	public $post_type_capability = 'publish_posts';

	/**
	 * The custom labels
	 *
	 * @var  array
	 */
	public $labels = array();

	/**
	 * The class constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
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
		add_action( 'admin_enqueue_scripts', array( $this, 'register_script' ),  1, 1 );
		add_action( 'add_meta_boxes',        array( $this, 'add_meta_box' ),    10, 2 );

		// Press This
		add_filter( 'press_this_save_post',  array( $this, 'reset_status' ),    10, 1 );

		// Block editor
		if ( function_exists( 'register_block_type' ) )  {
			add_action( 'init',                        array( $this, 'register_block_editor_script' ), 1001 );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_asset' ), 10 );
			if ( function_exists( 'block_editor_rest_api_preload' ) ) {
				add_filter( 'block_editor_rest_api_preload_paths', array( $this, 'preload_path' ), 10 );
			} else {
				add_filter( 'block_editor_preload_paths', array( $this, 'preload_path' ), 10 );
			}
		}
	}

	/**
	 * Register and enqueue needed scripts and css.
	 *
	 * @since  1.0.0
	 */
	public function register_script( $press_this = '' ) {

		// Editor's screen
		wp_register_script(
			'wp-statuses',
			sprintf( '%1$sscript%2$s.js', wp_statuses_js_url(), wp_statuses_min_suffix() ),
			array( 'jquery', 'post' ),
			wp_statuses_version(),
			true
		);

		// Regular Admin screens.
		if ( 'press-this.php' !== $press_this ) {
			$current_screen = get_current_screen();

			// Bail if the post type is not supported.
			if ( isset( $current_screen->post_type ) && ! wp_statuses_is_post_type_supported( $current_screen->post_type ) ) {
				return;
			}

			if ( isset( $current_screen->base ) && in_array( $current_screen->base, array( 'page', 'post' ), true ) ) {
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

			// List tables screens
			if ( isset( $current_screen->base ) && 'edit' === $current_screen->base && ! empty( $current_screen->post_type ) ) {
				$inline_statuses = wp_statuses_get_statuses( $current_screen->post_type, 'inline' );
				$statuses        = array();

				foreach ( $inline_statuses as $inline_status ) {
					if ( ! current_user_can( $this->post_type_capability ) && ! in_array( $inline_status->name, array( 'draft', 'pending' ), true ) ) {
						continue;
					}

					$statuses[ $inline_status->name ] = $inline_status->labels['inline_dropdown'];
				}

				$bulk_statuses = $statuses;
				unset( $bulk_statuses['password'] );

				if ( ! empty( $inline_statuses ) ) {
					wp_enqueue_script(
						'wp-statuses-inline',
						sprintf( '%1$sinline-script%2$s.js', wp_statuses_js_url(), wp_statuses_min_suffix() ),
						array( 'inline-edit-post' ),
						wp_statuses_version(),
						true
					);
					wp_localize_script( 'wp-statuses-inline', 'wpStatusesInline', array(
						'inline'       => $statuses,
						'bulk'         => $bulk_statuses,
						'bulk_default' => __( '&mdash; No Change &mdash;', 'wp-statuses' ),
					) );
				}
			}

		// Press This specific screen
		} else {
			$pressthis_statuses = wp_statuses_get_statuses( 'post', 'press_this' );
			$statuses           = array();

			foreach ( $pressthis_statuses as $pressthis_status ) {
				// Only include Press this statuses if the user can use them.
				if ( ! current_user_can( $this->post_type_capability ) ) {
					continue;
				}

				$statuses[ $pressthis_status->name ] = $pressthis_status->labels['press_this_dropdown'];
			}

			if ( ! empty( $statuses ) ) {
				wp_enqueue_script(
					'wp-statuses-press-this',
					sprintf( '%1$spress-this-script%2$s.js', wp_statuses_js_url(), wp_statuses_min_suffix() ),
					array( 'press-this' ),
					wp_statuses_version(),
					true
				);
				wp_localize_script( 'wp-statuses-press-this', 'wpStatusesPressThis', array(
					'statuses' => $statuses,
				) );
			}
		}
	}

	/**
	 * Replace the WordPress Publish metabox by plugin's one.
	 *
	 * @since  1.0.0
	 *
	 * @param string  $post_type The displayed post type.
	 * @param WP_Post $post      The post object.
	 */
	public function add_meta_box( $post_type, $post ) {
		// Bail if the Post Type is not supported, or if post uses Gutenberg block editor
		if ( ! wp_statuses_is_post_type_supported( $post_type ) ) {
			return;
		}

		// Remove the built-in Publish meta box.
		remove_meta_box( 'submitdiv', get_current_screen(), 'side' );

		$publish_callback_args = array( '__back_compat_meta_box' => true );

		if ( post_type_supports( $post_type, 'revisions' ) && 'auto-draft' !== $post->post_status ) {
			$revisions = wp_get_post_revisions( $post->ID, array( 'fields' => 'ids' ) );

			// We should aim to show the revisions meta box only when there are revisions.
			if ( count( $revisions ) > 1 ) {
				$publish_callback_args = array(
					'revisions_count'        => count( $revisions ),
					'revision_id'            => reset( $revisions ),
					'__back_compat_meta_box' => true,
				);
			}
		}

		// Use plugin's Publishing box instead.
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

	/**
	 * The Publishing metabox.
	 *
	 * @since  1.0.0
	 *
	 * @param  WP_Post $post The displayed post object.
	 * @param  array  $args  Additional arguments (eg: revisions' count).
	 */
	public function publishing_box( $post = null, $args = array() ) {
		if ( empty( $post->post_type ) ) {
			return;
		}

		$status = $post->post_status;
		if ( 'auto-draft' === $status ) {
			$status = 'draft';
		} elseif ( ! empty( $post->post_password ) ) {
			$status = 'password';
		}

		// Get the customizable labels
		$statuses_labels = wp_statuses_get_metabox_labels( $post->post_type );

		foreach ( $statuses_labels as $status_name => $labels_list ) {
			$this->labels[ $status_name ] = wp_array_slice_assoc( $labels_list, array(
				'metabox_submit',
				'metabox_save_on',
				'metabox_save_date',
				'metabox_saved_on',
				'metabox_saved_date',
				'metabox_save_now',
				'metabox_save_later',
			) );
		}

		// Load script for the metabox.
		wp_enqueue_script ( 'wp-statuses' );
		wp_localize_script( 'wp-statuses', 'wpStatuses', array(
			'status'          => $status,
			'attributes'      => array(
				'password' => $post->post_password,
				'sticky'   => is_sticky( $post->ID ),
			),
			'strings' => array(
				'previewChanges' => __( 'Preview Changes', 'wp-statuses' ),
				'preview'        => __( 'Preview', 'wp-statuses' ),
				'labels'         => $this->labels,
			),
			'public_statuses' => wp_statuses_get_public_statuses( $post->post_type ),
		) ); ?>

		<div class="submitbox" id="submitpost">
			<div id="minor-publishing">

				<?php
				/**
				 * Take care of minor publishing actions.
				 */
				$this->get_minor_publishing_div( $post, $status ); ?>

				<div id="misc-publishing-actions">

					<?php
					/**
					 * Split actions for a better lisibility.
					 */
					$this->get_status_publishing_div( $post, $status );
					$this->get_status_extra_attributes( $post, $status );
					$this->get_time_publishing_div( $post, $status, $args ); ?>

				</div><!-- #misc-publishing-actions -->
				<div class="clear"></div>

			</div><!-- #minor-publishing -->

			<div id="major-publishing-actions">
				<?php $this->get_major_publishing_div( $post, $status ); ?>

				<div class="clear"></div>
			</div><!-- #major-publishing-actions -->

		</div><!-- #submitpost -->
		<?php
	}

	/**
	 * Output the minor publishing actions.
	 *
	 * @since  1.0.0
	 *
	 * @param  WP_Post $post   The displayed Post object.
	 * @param  string  $status The Post's status.
	 */
	public function get_minor_publishing_div( $post = null, $status = '' ) {
		if ( empty( $post->post_type ) || empty( $status ) ) {
			return;
		}

		// Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key
		?>
		<div style="display:none;">
			<?php submit_button( __( 'Save', 'wp-statuses' ), '', 'save' ); ?>
		</div>

		<div id="minor-publishing-actions">
			<div id="save-action">

				<?php if ( 'draft' === $status && isset( $this->labels['draft'] ) ) : ?>

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
						'publish' === $status ? esc_html__( 'Preview Changes', 'wp-statuses' ) : esc_html__( 'Preview', 'wp-statuses' )
					); ?>
					<input type="hidden" name="wp-preview" id="wp-preview" value="" />
				</div>

			<?php endif;

			/**
			 * Fires before the post time/date setting in the Publish meta box.
			 *
			 * @since WordPress 4.4.0
			 *
			 * @param WP_Post $post WP_Post object for the current post.
			 */
			do_action( 'post_submitbox_minor_actions', $post ); ?>

			<div class="clear"></div>
		</div><!-- #minor-publishing-actions -->
		<?php
	}

	/**
	 * Output the statuses dropdown.
	 *
	 * @since  1.0.0
	 *
	 * @param  WP_Post $post    The displayed Post object.
	 * @param  string  $current The Post's status.
	 */
	public function get_status_publishing_div( $post = null, $current = '' ) {
		if ( empty( $post->post_type ) || empty( $current ) ) {
			return;
		}

		$statuses = wp_statuses_get_statuses( $post->post_type );

		$options        = array( '<select name="post_status" id="wp-statuses-dropdown">' );
		$dashicon       = 'dashicons-post-status';
		$status_display = '';

		foreach ( $statuses as $status ) {
			$current_status = $current;
			$value          = $status->name;

			// Password is a publish status
			if ( 'password' === $status->name ) {
				$value = 'publish';

				// Or a scheduled one.
				if ( 'password' === $current && 'future' === $post->post_status ) {
					$value = 'future';
				}
			}

			// Future will become a publish status
			if ( 'future' === $current ) {
				$current_status = 'publish';

				// Set the Published status as future.
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
		<div class="misc-pub-section">

			<label for="post_status" class="screen-reader-text"><?php esc_html_e( 'Set status', 'wp-statuses' ); ?></label>
			<?php printf(
				'<span class="dashicons %1$s"></span> %2$s',
				sanitize_html_class( $dashicon ),
				join( "\n", $options )
			);

			/**
			 * As WordPress is overriding the $_POST global inside _wp_translate_postdata()
			 * We'll use this input to remember what was the real posted status.
			 *
			 * @see this part of the code, around line 100 of wp-admin/includes/post.php :
			 * if ( isset($post_data['publish']) && ( '' != $post_data['publish'] ) && ( !isset($post_data['post_status']) || $post_data['post_status'] != 'private' ) )
			 *	$post_data['post_status'] = 'publish';
			 */
			?>
			<input type="hidden" name="_wp_statuses_status" id="wp-statuses-status" value="<?php echo esc_attr( $current ); ?>"/>

		</div><!-- .misc-pub-section -->
		<?php
	}

	/**
	 * Output Extra attributes according to the status.
	 *
	 * @since  1.0.0
	 *
	 * @param  WP_Post $post   The displayed Post object.
	 * @param  string  $status The Post's status.
	 */
	public function get_status_extra_attributes( $post = null, $status = '' ) {
		if ( empty( $post->post_type ) || empty( $status ) || ! current_user_can( $this->post_type_capability ) ) {
			return;
		}

		$sticky_class = $password_class = 'hide-if-js';
		if ( wp_statuses_is_public( $status ) && ! $post->post_password ) {
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

	/**
	 * Output the time's selector & revisions' browser.
	 *
	 * @since  1.0.0
	 *
	 * @param  WP_Post $post   The displayed Post object.
	 * @param  string  $status The Post's status.
	 */
	public function get_time_publishing_div( $post = null, $status = '', $args = array() ) {
		if ( empty( $post->post_type ) || empty( $status ) || ! current_user_can( $this->post_type_capability ) ) {
			return;
		}

		global $action;
		$is_future = time() < strtotime( $post->post_date_gmt . ' +0000' );

		/* translators: Publish box date format, see https://secure.php.net/date */
		$datef = __( 'M j, Y @ H:i', 'wp-statuses' );

		// Default stamps.
		$stamps = array(
			/* translators: 1: the scheduled date for the post. */
			'metabox_save_later' => __( 'Schedule for: <b>%1$s</b>', 'wp-statuses' ),
			/* translators: 1: the post’s saved date. */
			'metabox_saved_date' => __( 'Saved on: <b>%1$s</b>', 'wp-statuses' ),
			'metabox_save_now'   => __( 'Save <b>now</b>', 'wp-statuses' ),
			/* translators: 1: the date to save the post on. */
			'metabox_save_date'  => __( 'Save on: <b>%1$s</b>', 'wp-statuses' ),
		);

		if ( isset( $this->labels[ $status ] ) ) {
			$stamps = wp_parse_args( $this->labels[ $status ], $stamps );
		}

		// Post already exists.
		if ( 0 !== (int) $post->ID ) {
			// scheduled for publishing at a future date.
			if ( 'future' === $status || ( 'draft' !== $status && $is_future ) ) {
					$stamp = $stamps['metabox_save_later'];

			// already published.
			} elseif ( ! in_array( $status, array( 'draft', 'future', 'pending' ), true ) ) {
				$stamp = $stamps['metabox_saved_date'];

			// draft, 1 or more saves, no date specified.
			} elseif ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
				$stamp = $stamps['metabox_save_now'];

			// draft, 1 or more saves, future date specified.
			} elseif ( $is_future ) {
				$stamp = $stamps['metabox_save_later'];

			// draft, 1 or more saves, date specified.
			} else {
				$stamp = $stamps['metabox_save_date'];
			}

			$date = date_i18n( $datef, strtotime( $post->post_date ) );

		// draft (no saves, and thus no date specified).
		} else {
			$stamp = $stamps['metabox_save_now'];
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
		/**
		 * Fires after the post time/date setting in the Publish meta box.
		 *
		 * @since WordPress 2.9.0
		 * @since WordPress 4.4.0 Added the `$post` parameter.
		 *
		 * @param WP_Post $post WP_Post object for the current post.
		 */
		do_action( 'post_submitbox_misc_actions', $post );
	}

	/**
	 * Output the major actions of the metabox.
	 *
	 * @since  1.0.0
	 *
	 * @param  WP_Post $post   The displayed Post object.
	 * @param  string  $status The Post's status.
	 */
	public function get_major_publishing_div( $post = null, $status = '' ) {
		if ( empty( $post->post_type ) || empty( $status ) ) {
			return;
		}

		// Default is submit box's default value.
		$text = '';

		if ( isset( $this->labels[ $status ]['metabox_submit'] ) ) {
			$text = $this->labels[ $status ]['metabox_submit'];
		}

		// Submit input arguments.
		$args = array(
			'text'             => $text,
			'type'             => 'primary large',
			'name'             => 'save',
			'wrap'             => false,
			'other_attributes' => array( 'id' => 'publish' ),
		);

		$default_labels = reset( $this->labels );
		$default_status = key( $this->labels );

		// The current post type does not support the Publish status.
		if ( 'publish' !== $default_status ) {
			$args['text'] = __( 'Save', 'wp-statuses' );

			if ( isset( $default_labels['metabox_submit'] ) ) {
				$args['text'] = $default_labels['metabox_submit'];
			}

		// The current post type supports the Publish status.
		} elseif ( in_array( $status, array( 'draft', 'pending' ), true ) || 0 === (int) $post->ID ) {
			$args = array_merge( $args, array(
				'text' => __( 'Submit for Review', 'wp-statuses' ),
				'name' => 'publish',
			) );

			if ( current_user_can( $this->post_type_capability ) ) {
				$args['text'] = __( 'Publish', 'wp-statuses' );

				if ( ! empty( $post->post_date_gmt ) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) {
					$args['text'] = __( 'Schedule', 'wp-statuses' );
				}
			}
		}

		/** This action is documented in wp-admin/includes/meta-boxes.php */
		do_action( 'post_submitbox_start', $post ); ?>

		<div id="delete-action">
			<?php if ( current_user_can( "delete_post", $post->ID ) ) : ?>
				<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>">
					<?php ! EMPTY_TRASH_DAYS ? esc_html_e( 'Delete Permanently', 'wp-statuses' ) : esc_html_e( 'Move to Trash', 'wp-statuses' ); ?>
				</a>
			<?php endif ; ?>
		</div>

		<div id="publishing-action">
			<span class="spinner"></span>
			<?php submit_button( $args['text'], $args['type'], $args['name'], $args['wrap'], $args['other_attributes'] ); ?>
		</div>
		<?php
	}

	/**
	 * Reset the Press This posted post status if needed.
	 *
	 * @since  1.1.0
	 *
	 * @param  array  $post_data The list of Post data.
	 * @return array             The list of Post data.
	 */
	public function reset_status( $post_data = array() ) {
		if ( empty( $_POST['_wp_statuses_status'] ) ) {
			return $post_data;
		}

		// Validdate the status
		$status = get_post_status_object( $_POST['_wp_statuses_status'] );

		if ( ! $status ) {
			return $post_data;
		}

		return array_merge( $post_data, array(
			'post_status' => $status->name,
		) );
	}

	/**
	 * Registers the Block Editor's Sidebar script.
	 *
	 * @since 2.0.0
	 */
	public function register_block_editor_script() {
		wp_register_script(
			'wp-statuses-sidebar',
			sprintf( '%ssidebar.js', wp_statuses_js_url() ),
			array(
				'wp-blocks',
				'wp-components',
				'wp-compose',
				'wp-data',
				'wp-date',
				'wp-edit-post',
				'wp-i18n',
				'wp-plugins',
			),
			wp_statuses_version()
		);

		$test = wp_set_script_translations( 'wp-statuses-sidebar', 'wp-statuses', trailingslashit( wp_statuses()->dir ) . 'languages' );
	}

	/**
	 * Loads the needed CSS/Script for the Block Editor's Sidebar script.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_block_editor_asset() {
		$post_type = get_post_type();
		if ( ! in_array( $post_type, wp_statuses_get_customs_post_types(), true ) ) {
			return;
		}

		$future_control  = '';
		$required_status = wp_statuses_get( 'publish' );

		if ( ! in_array( $post_type, $required_status->post_type, true ) ) {
			$future_control = ', .edit-post-post-schedule';
		}

		wp_enqueue_script( 'wp-statuses-sidebar' );
		wp_add_inline_style( 'wp-edit-post', "
			.edit-post-post-visibility{$future_control} { display: none }
			.editor-post-switch-to-draft { display: none }
			.components-panel__row.wp-statuses-info { display: block }
			.components-panel__row.wp-statuses-info .components-base-control__label,
			.components-panel__row.wp-statuses-info .components-select-control__input,
			.components-panel__row.wp-statuses-info .components-text-control__input {
				display: inline-block;
				max-width: 100%;
				width: 100%;
			}
			.components-base-control.wp-statuses-password { margin-top: 20px }
		" );
	}

	/**
	 * Adds a REST route to preload into the Block Editor.
	 *
	 * @since 2.0.0
	 *
	 * @param  array $paths The list of REST routes to preload.
	 * @return array        The list of REST routes to preload.
	 */
	public function preload_path( $paths = array() ) {
		if ( ! in_array( get_post_type(), wp_statuses_get_customs_post_types(), true ) ) {
			return $paths;
		}

		return array_merge( $paths, array(
			'/wp/v2/statuses?context=edit'
		) );
	}
}
