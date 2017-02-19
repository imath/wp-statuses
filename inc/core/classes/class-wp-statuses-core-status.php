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

class WP_Statuses_Core_Status {

	public  $label = false;
	public  $label_count = false;
	public  $labels = array();
	public  $exclude_from_search = null;
	private $_builtin = false;
	public  $public = null;
	public	$internal = null;
	public	$protected = null;
	public  $private = null;
	public	$publicly_queryable = null;
	public  $post_type = array();
	public  $show_in_admin_status_list = null;
	public	$show_in_admin_all_list = null;
	public  $show_in_metabox_dropdown = null;
	public  $show_in_quickedit_dropdown = null;
	public  $dashicon = 'dashicons-post-status';

	/**
	 * Constructor.
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
				$this->post_type = array( 'post', 'page' );
			}

			if ( ! isset( $status->show_in_metabox_dropdown ) ) {
				$this->show_in_metabox_dropdown = true;
			}

			if ( ! isset( $status->show_in_quickedit_dropdown ) ) {
				$this->show_in_quickedit_dropdown = true;
			}
		}

		$this->labels = array_merge( $this->labels, array(
			'label'       => $this->label,
			'label_count' => $this->label_count,
		) );

		if ( ! isset( $this->labels['metabox_dropdown'] ) ) {
			$this->labels['metabox_dropdown'] = $this->labels['label'];
		}

		if ( $this->show_in_quickedit_dropdown && ! isset( $this->labels['quickedit_dropdown'] ) ) {
			$this->labels['quickedit_dropdown'] = $this->labels['metabox_dropdown'];
		}
	}

	public function get_initial_types_data( $name = '' ) {
		$labels = array(
			'publish'    => array(
				'labels' => array(
					'metabox_dropdown'   => __( 'Publicly published', 'wp-statuses' ),
					'quickedit_dropdown' => __( 'Published', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-visibility',
			),
			'private'    => array(
				'labels' => array(
					'metabox_dropdown'   => __( 'Privately Published', 'wp-statuses' ),
					'quickedit_dropdown' => __( 'Private', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-hidden',
			),
			'future'     => array(
				'labels' => array(
					'metabox_dropdown'   => __( 'Scheduled', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-calendar-alt',
			),
			'pending'    => array(
				'labels' => array(
					'metabox_dropdown'   => __( 'Pending Review', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-flag',
			),
			'draft'      => array(
				'labels' => array(
					'metabox_dropdown'   => __( 'Draft', 'wp-statuses' ),
				),
				'dashicon' => 'dashicons-edit',
			),
		);

		if ( ! isset( $labels[ $name ] ) ) {
			return null;
		}

		return $labels[ $name ];
	}
}
