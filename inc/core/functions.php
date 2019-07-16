<?php
/**
 * WP Statuses Functions.
 *
 * @package WP Statuses\inc\core
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get plugin's version.
 *
 * @since  1.0.0
 *
 * @return string the plugin's version.
 */
function wp_statuses_version() {
	return wp_statuses()->version;
}

/**
 * Get the plugin's JS Url.
 *
 * @since  1.0.0
 *
 * @return string the plugin's JS Url.
 */
function wp_statuses_js_url() {
	return wp_statuses()->js_url;
}

/**
 * Get the JS minified suffix.
 *
 * @since  1.0.0
 *
 * @return string the JS minified suffix.
 */
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

/**
 * Get the supported post types.
 *
 * @since 1.2.1
 *
 * @return array The list of supported Post Types name.
 */
function wp_statuses_get_supported_post_types() {
	// All Post Types using the WordPress UI except the Attachment one.
	$post_types = array_diff( get_post_types( array( 'show_ui' => true ) ), array( 'attachment' ) );

	/**
	 * Filter here to disable specific post types.
	 *
	 * @since 1.2.1
	 *
	 * @param array $post_types The list of supported Post Types name.
	 */
	return apply_filters( 'wp_statuses_get_supported_post_types', $post_types );
}

/**
 * Checks if a post type can have custom statuses.
 *
 * @since 1.2.1
 *
 * @param  string  $post_type The name of the Post Type.
 * @return boolean            True if supported. False otherwise.
 */
function wp_statuses_is_post_type_supported( $post_type = '' ) {
	if ( ! $post_type ) {
		return false;
	}

	return in_array( $post_type, wp_statuses_get_supported_post_types(), true );
}

/**
 * Get the registered Post Types for the WordPress built-in statuses.
 *
 * This is used to set the 'post' attribute of the WP_Statuses_Core_Status object.
 * You can use the filter to customize the statuses for your custom post type.
 *
 * @since  1.0.0
 *
 * @param  string $status_name The status name (eg: pending, draft etc..).
 * @return array               The list of Post types supported by the WordPress built-in statuses.
 */
function wp_statuses_get_registered_post_types( $status_name = '' ) {
	/**
	 * Filter here to edit the Post types built-in statuses apply to.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $value       A list of public post types names.
	 * @param string $status_name The status name (eg: pending, draft etc..).
	 */
	return apply_filters( 'wp_statuses_get_registered_post_types', wp_statuses_get_supported_post_types(), $status_name );
}

/**
 * Get a status object.
 *
 * @since  1.0.0
 *
 * @param  mixed                        $status It can be a WP_Statuses_Core_Status object,
 *                                              the name of the status or a regular object.
 * @return WP_Statuses_Core_Status|null         The status object if found. Null otherwise.
 */
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

/**
 * Register a new status for the Password protected visibility.
 *
 * @since  1.0.0
 */
function wp_statuses_register_password_protected() {
	register_post_status( 'password', array(
		'label'                     => _x( 'Password Protected', 'post status', 'wp-statuses' ),
		'public'                    => true,
		'label_count'               => _n_noop( 'Password Protected <span class="count">(%s)</span>', 'Password Protected <span class="count">(%s)</span>', 'wp-statuses' ),
		'post_type'                 => wp_statuses_get_registered_post_types( 'password' ),
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => false,
		'show_in_metabox_dropdown'  => true,
		'show_in_inline_dropdown'   => true,
		'labels'                    => array(
			'metabox_dropdown' => __( 'Password Protected', 'wp-statuses' ),
			'inline_dropdown'  => __( 'Password', 'wp-statuses' ),
		),
		'dashicon'                  => 'dashicons-lock',
	) );
}

/**
 * Map the registered statuses to WP_Statuses_Core_Status objects.
 *
 * @since 1.0.0
 */
function wp_statuses_register() {
	global $wp_post_statuses;

	$wp_post_statuses = array_map( 'wp_statuses_get', $wp_post_statuses );

	/**
	 * Hook here to be sure all available statuses are registered.
	 *
	 * @since 1.3.0
	 */
	do_action( 'wp_statuses_registered' );
}

/**
 * Get the registered statuses for the given post type.
 *
 * @since  1.0.0
 * @since  1.3.0 Add a filter to allow statuses order changes.
 *
 * @param  string $post_type The Name of the post type to get available statuses for.
 * @param  string $context   The context of the dropdown box. It can be:
 *                           - The Publishing metabox ('metabox' ),
 *                           - The inline edit row of the Post Type's Table list ('inline').
 * @return array             A filtered array containing the matching WP_Statuses_Core_Status objects.
 */
function wp_statuses_get_statuses( $post_type = '', $context = 'metabox' ) {
	global $wp_post_statuses;

	if ( empty( $post_type ) ) {
		return array();
	}

	$dropdown_statuses = wp_filter_object_list( $wp_post_statuses, array( "show_in_{$context}_dropdown" => true ) );

	foreach ( $dropdown_statuses as $status_name => $status ) {
		if ( ! in_array( $post_type, $status->post_type, true ) ) {
			unset( $dropdown_statuses[ $status_name ] );
		}
	}

	/**
	 * Filter here to edit the order of the displayed statuses.
	 *
	 * @since  1.3.0
	 *
	 * @param  array  $dropdown_statuses A filtered array containing the matching WP_Statuses_Core_Status objects.
	 * @param  string $context           The context of the dropdown box.
	 */
	$sorted_statuses = apply_filters( 'wp_statuses_get_ordered_statuses', $dropdown_statuses, $context );

	if ( array_diff_key( $dropdown_statuses, $sorted_statuses ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'You need to preserve keys to customize the order.', 'wp-statuses' ), '1.3.0' );
		return $dropdown_statuses;
	}

	return $sorted_statuses;
}

/**
 * Get the list of statuses labels for a post type.
 *
 * @since  1.2.0
 *
 * @param  string $post_type The Name of the post type to get the statuses' labels for.
 * @return array             An associative array listing labels for each status.
 */
function wp_statuses_get_metabox_labels( $post_type = '' ) {
	global $wp_post_statuses;

	$labels = array();

	foreach ( $wp_post_statuses as $status_name => $status ) {
		if ( ! empty( $post_type ) && ! in_array( $post_type, $status->post_type, true ) ) {
			continue;
		}

		if ( empty( $status->labels ) ) {
			continue;
		}

		$labels[ $status_name ] = $status->labels;
	}

	return $labels;
}

/**
 * Get public statuses regarding the context.
 *
 * @since 1.0.0
 *
 * @param string $post_type The name of the post type, statuses are applying to.
 * @param string $context   Whether public statuses are requested for an wp-admin usage or not.
 *                          Default: 'admin'.
 * @return array            A list of public statuses' names.
 */
function wp_statuses_get_public_statuses( $post_type = '', $context = 'admin' ) {
	global $wp_post_statuses;

	if ( $post_type ) {
		// Validate the post type
		$type = get_post_type_object( $post_type );
		if ( ! $type ) {
			$post_type = '';
		}
	}

	$public_statuses = array();
	$args            = array( 'public' => true );
	$operator        = 'AND';

	// Draft and Pending are protected
	if ( 'admin' === $context ) {
		$args['protected'] = true;
		$operator          = 'OR';
	}

	$statuses = wp_filter_object_list( $wp_post_statuses, $args, $operator );

	foreach ( $statuses as $status ) {
		if ( $post_type && ! in_array( $post_type, $status->post_type, true ) ) {
			continue;
		}

		$public_statuses[] = $status->name;
	}

	/**
	 * Filter here to edit the public statuses.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $public_statuses A list of public statuses' names.
	 * @param string $post_type       The name of the post type, statuses are applying to.
	 * @param string $context         Whether public statuses are requested for an wp-admin usage or not.
	 */
	return apply_filters( 'wp_statuses_get_public_statuses', $public_statuses, $post_type, $context );
}

/**
 * Checks if a status is public
 *
 * @since 1.1.0
 *
 * @param  string $status The status to check.
 * @return bool           True if a status is public. False otherwise.
 */
function wp_statuses_is_public( $status = '' ) {
	$context = '';

	if ( is_admin() && ! wp_doing_ajax() ) {
		$context = 'admin';
	}

	$statuses = wp_statuses_get_public_statuses( '', $context );

	if ( ! $statuses || ! $status ) {
		return false;
	}

	return in_array( $status, $statuses, true );
}

/**
 * Unregisters a status for the given list of post type names.
 *
 * @since 1.3.0
 *
 * @param  string $status    The status name
 * @param  array  $post_type A list of post type names.
 * @return boolean           True if the Status has been unregistered for the post types.
 *                           False otherwise.
 */
function wp_statuses_unregister_status_for_post_type( $status = '', $post_type = array() ) {
	if ( ! doing_action( 'wp_statuses_registered' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'You need to hook to the wp_statuses_registered action to unregister a status for one or more post type.', 'wp-statuses' ), '1.3.0' );
		return false;
	}

	global $wp_post_statuses;
	$post_types = (array) $post_type;
	$statuses   = (array) $status;

	if ( ! isset( $wp_post_statuses[ $status ]->post_type ) || ! $post_types ) {
		return false;
	}

	if ( 'publish' === $status ) {
		$statuses = array_merge( $statuses, array( 'draft', 'pending' ) );
	}

	foreach ( $statuses as $s ) {
		$wp_post_statuses[ $s ]->post_type = array_diff( $wp_post_statuses[ $s ]->post_type, $post_types );
	}

	return true;
}

/**
 * Gets the registered post types for statuses in REST Requests.
 *
 * @since 2.0.0
 *
 * @param  array $data       The status data.
 * @param  string $attribute The REST field's name attribute.
 * @return array             The list of supported post types.
 */
function wp_statuses_rest_get_post_types( $data, $attribute ) {
	$value = array();

	if ( 'post_type' !== $attribute || ! isset( $data['slug'] ) ) {
		return $value;
	}

	global $wp_post_statuses;
	$statuses = wp_list_pluck( $wp_post_statuses, 'post_type', 'name' );

	if ( isset( $statuses[ $data['slug'] ] ) ) {
		$rest_post_types = get_post_types( array( 'show_in_rest' => true ) );
		$value           = array_intersect( $rest_post_types, $statuses[ $data['slug'] ] );
	}

	return array_values( $value );
}

/**
 * Registers a new property for the REST Status controller schema.
 *
 * @since 2.0.0
 */
function wp_statuses_register_post_types_field() {
	register_rest_field( 'status', 'post_type', array(
		'get_callback'    => 'wp_statuses_rest_get_post_types',
		'schema'          => array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'The list of post types the status applies to.', 'wp-statuses' ),
			'type'        => 'array',
			'readonly'    => true,
		),
	) );
}
add_action( 'rest_api_init', 'wp_statuses_register_post_types_field', 11 );
