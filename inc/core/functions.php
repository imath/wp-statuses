<?php

function wp_statuses_version() {
	return wp_statuses()->version;
}

function wp_statuses_js_url() {
	return wp_statuses()->js_url;
}

function wp_statuses_min_suffix() {
	$min = '.min';

	if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG )  {
		$min = '';
	}

	/**
	 * Filter here to edit the minified suffix.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $min The minified suffix.
	 */
	return apply_filters( 'wp_statuses_min_suffix', $min );
}


function wp_statuses_get( $status = null ) {
	if ( empty( $status ) ) {
		return null;
	}

	if ( is_a( $status, 'WP_Statuses_Core_Status' ) ) {
		$_status = $status;
	} elseif ( is_object( $status ) ) {
		$_status = new WP_Statuses_Core_Status( $status );
	} else {
		global $wp_post_statuses;

		if ( isset( $wp_post_statuses[ $status ] ) ) {
			if ( ! is_a( $wp_post_statuses[ $status ], 'WP_Statuses_Core_Status' ) ) {
				$wp_post_statuses[ $status ] = new WP_Statuses_Core_Status( $wp_post_statuses[ $status ] );
			}

			$_status = $wp_post_statuses[ $status ];
		}
	}

	if ( empty( $_status ) ) {
		return null;
	}

	return $_status;
}

function wp_status_register_password_protected() {
	register_post_status( 'password', array(
		'label'                     => _x( 'Password Protected', 'post status', 'wp-statuses' ),
		'public'                    => true,
		'label_count'               => _n_noop( 'Password Protected <span class="count">(%s)</span>', 'Password Protected <span class="count">(%s)</span>', 'wp-statuses' ),
		'post_type'                 => array( 'post' ),
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => false,
		'show_in_metabox_dropdown'  => true,
		'dashicon'                  => 'dashicons-lock',
	) );
}

function wp_statuses_register() {
	global $wp_post_statuses;

	$wp_post_statuses = array_map( 'wp_statuses_get', $wp_post_statuses );
}

function wp_statuses_get_metabox_statuses( $post_type = '' ) {
	global $wp_post_statuses;

	if ( empty( $post_type ) ) {
		return array();
	}

	$dropdown_statuses = wp_filter_object_list( $wp_post_statuses, array( 'show_in_metabox_dropdown' => true ) );

	foreach ( $dropdown_statuses as $status_name => $status ) {
		if ( ! in_array( $post_type, $status->post_type, true ) ) {
			unset( $dropdown_statuses[ $status_name ] );
		}
	}

	return $dropdown_statuses;
}
