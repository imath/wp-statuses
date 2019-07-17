<?php
/**
 * WP Statuses Status' Object Class.
 *
 * @package WP Statuses\inc\core\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The Status Object
 *
 * @since  1.0.0
 */
class WP_Statuses_Core_Status {

	/**
	 * The label of the status.
	 *
	 * @var bool|string
	 */
	public $label = false;

	/**
	 * The Post Types' List Table views label
	 *
	 * @var bool|string
	 */
	public $label_count = false;

	/**
	 * The status labels.
	 *
	 * To customize dropdowns.
	 *
	 * @var array
	 */
	public $labels = array();


	/**
	 * Whether to exclude posts with this post status from search.
	 *
	 * @var null|bool
	 */
	public $exclude_from_search = null;

	/**
	 * Whether it's a WordPress built-in status.
	 *
	 * @var bool
	 */
	private $_builtin = false;

	/**
	 * Whether posts of this status should be shown
	 * in the front end of the site.
	 *
	 * @var null|bool
	 */
	public $public = null;

	/**
	 * Whether the status is for internal use only.
	 *
	 * @var null|bool
	 */
	public $internal = null;

	/**
	 * Whether posts with this status should be protected.
	 *
	 * @var null|bool
	 */
	public $protected = null;

	/**
	 * Whether posts with this status should be private.
	 *
	 * @var null|bool
	 */
	public $private = null;

	/**
	 * Whether posts with this status should be publicly-queryable.
	 *
	 * @var null|bool
	 */
	public $publicly_queryable = null;

	/**
	 * The list of post types the status applies to.
	 *
	 * @var array
	 */
	public $post_type = array();

	/**
	 * Whether to include posts in the edit listing for their post type
	 *
	 * @var null|bool
	 */
	public $show_in_admin_status_list = null;

	/**
	 * Show in the list of statuses with post counts at the top of the edit
	 * listings.
	 *
	 * @var null|bool
	 */
	public $show_in_admin_all_list = null;

	/**
	 * Whether to use the status in WordPress's Publishing metabox.
	 *
	 * @var null|bool
	 */
	public $show_in_metabox_dropdown = null;

	/**
	 * Whether to use the status in WordPress's List Table inline/bulk edit actions.
	 *
	 * @var null|bool
	 */
	public $show_in_inline_dropdown = null;

	/**
	 * Whether to use the status in WordPress's Press this Editor.
	 *
	 * @var null|bool
	 */
	public $show_in_press_this_dropdown = null;

	/**
	 * The dashicon to use for the status.
	 *
	 * @var string
	 */
	public $dashicon = 'dashicons-post-status';

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param WP_Statuses_Core_Status|object $status Status object.
	 */
	public function __construct( $status ) {
		foreach ( get_object_vars( $status ) as $key => $value ) {
			$this->{$key} = $value;
		}

		$status_data = $this->get_initial_types_data( $status->name );

		if ( $status_data ) {
			$this->labels   = $status_data['labels'];
			$this->dashicon = $status_data['dashicon'];

			if ( ! isset( $status->post_type )  ) {
				$this->post_type = wp_statuses_get_registered_post_types( $status->name );
			}

			if ( ! isset( $status->show_in_metabox_dropdown ) ) {
				$this->show_in_metabox_dropdown = true;
			}

			if ( ! isset( $status->show_in_inline_dropdown ) ) {
				$this->show_in_inline_dropdown = true;
			}
		}

		$this->labels = wp_parse_args( $this->labels, array(
			'label'              => $this->label,
			'label_count'        => $this->label_count,
			'metabox_dropdown'   => $this->label,
			'metabox_publish'    => __( 'Publish', 'wp-statuses' ),
			'metabox_submit'     => __( 'Update', 'wp-statuses' ),
			'metabox_save_on'    => __( 'Publish on:', 'wp-statuses' ),
			/* translators: Post date information. 1: Date on which the post is to be published */
			'metabox_save_date'  => __( 'Publish on: <b>%1$s</b>', 'wp-statuses' ),
			'metabox_saved_on'   => __( 'Published on:', 'wp-statuses' ),
			/* translators: Post date information. 1: Date on which the post was published */
			'metabox_saved_date' => __( 'Published on: <b>%1$s</b>', 'wp-statuses' ),
			'metabox_save_now'   => __( 'Publish <b>immediately</b>', 'wp-statuses' ),
			/* translators: Post date information. 1: Date on which the post is to be published */
			'metabox_save_later' => __( 'Schedule for: <b>%1$s</b>', 'wp-statuses' ),
			'inline_dropdown'    => $this->label,
		) );
	}

	/**
	 * Get the additional properties for the WordPress built-in statuses.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $name The name of the status.
	 * @return array        The additional properties matching the name of the status.
	 */
	public function get_initial_types_data( $name = '' ) {
		/**
		 * Filter here to edit the WordPress default statuses labels.
		 *
		 * @since 1.3.0
		 *
		 * @param array $value An associative array keyed by status names.
		 */
		$labels = apply_filters( 'wp_statuses_initial_labels', array(
			'publish'    => array(
				'labels' => array(
					'metabox_dropdown' => __( 'Publicly published', 'wp-statuses' ),
					'inline_dropdown'  => __( 'Published', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-visibility',
			),
			'private'    => array(
				'labels' => array(
					'metabox_dropdown'   => __( 'Privately Published', 'wp-statuses' ),
					'metabox_submit'     => __( 'Save as Private', 'wp-statuses' ),
					'metabox_save_on'    => __( 'Save as Private on:', 'wp-statuses' ),
					/* translators: Post date information. 1: Date on which the post is to be saved privately */
					'metabox_save_date'  => __( 'Save as Private on: <b>%1$s</b>', 'wp-statuses' ),
					'metabox_saved_on'   => __( 'Saved as Private on:', 'wp-statuses' ),
					/* translators: Post date information. 1: Date on which the post was saved privately */
					'metabox_saved_date' => __( 'Saved as Private on: <b>%1$s</b>', 'wp-statuses' ),
					'metabox_save_now'   => __( 'Save as private <b>now</b>', 'wp-statuses' ),
					'inline_dropdown'    => __( 'Private', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-hidden',
			),
			'pending'    => array(
				'labels' => array(
					'metabox_dropdown' => __( 'Pending Review', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-flag',
			),
			'draft'      => array(
				'labels' => array(
					'metabox_dropdown' => __( 'Draft', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-edit',
			),
		) );

		if ( ! isset( $labels[ $name ] ) ) {
			return null;
		}

		return $labels[ $name ];
	}

	/**
	 * Is the status a built-in one ?
	 *
	 * @since  2.0.0
	 *
	 * @return boolean True if the status is a built-in one. False otherwise.
	 */
	public function is_builtin() {
		return true === $this->_builtin;
	}
}
