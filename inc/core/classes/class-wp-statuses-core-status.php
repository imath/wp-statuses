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
	public  $show_in_publishing_dropdown = null;
	public  $show_in_quickedit_dropdown = null;

	/**
	 * Constructor.
	 *
	 * @param WP_Statuses_Core_Status|object $status Status object.
	 */
	public function __construct( $status ) {
		foreach ( get_object_vars( $status ) as $key => $value ) {
			$this->{$key} = $value;
		}
	}
}
