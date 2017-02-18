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
	public function __construct() {
		//$this->setup_globals();
		//$this->includes();
		$this->hooks();
	}

	/**
	 * Starts the Admin class
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 */
	private function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
	}

	public function add_meta_box( $post_type, $post ) {
		add_meta_box(
			'wp-statuses-publish-box',
			__( 'Publishing', 'wp-statuses' ),
			array( $this, 'publish_box' ),
			$post_type,
			'advanced',
			'high'
		);
	}

	function publish_box( $post = null ) {
		if ( empty( $post->ID ) ) {
			return;
		}

		var_dump( wp_statuses_get_all() );
	}
}
