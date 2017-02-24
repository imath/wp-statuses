<?php
/**
 * WP Statuses Custom.
 *
 * @package WP Statuses\inc\core
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register a Public status so that it will be in the loop.
 *
 * We'll use the_content() to filter the content's display.
 *
 * @since 1.0.0
 */
function wp_statuses_register_members_restricted() {
	register_post_status( 'restricted', array(
		'label'                     => _x( 'Restricted to members', 'post status', 'wp-statuses' ),
		'public'                    => true,
		'label_count'               => _n_noop( 'Restricted to members <span class="count">(%s)</span>', 'Restricted to members <span class="count">(%s)</span>', 'wp-statuses' ),
		'post_type'                 => array( 'page' ), // Just pages for this example :)
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'show_in_metabox_dropdown'  => true,
		'show_in_inline_dropdown'   => true,
		'labels'                    => array(
			'inline_dropdown'  => __( 'Restricted', 'wp-statuses' ),
		),
		'dashicon'                  => 'dashicons-groups',
	) );
}
add_action( 'init', 'wp_statuses_register_members_restricted', 11 );

/**
 * Makes sure it's possible to directly Publish a restricted status.
 *
 * @see this part of the code, around line 100 of wp-admin/includes/post.php :
 * if ( isset($post_data['publish']) && ( '' != $post_data['publish'] ) && ( !isset($post_data['post_status']) || $post_data['post_status'] != 'private' ) )
 *	$post_data['post_status'] = 'publish';
 *
 * @since 1.0.0
 *
 * @param  array $data    The data to be inserted into the database.
 * @param  array $postarr The submitted data.
 * @return array          he data to be inserted into the database.
 */
function wp_statuses_publish_as_restricted( $data = array(), $postarr = array() ) {
	// It's not a Publish action
	if ( empty( $postarr['publish'] ) ) {
		return $data;
	}

	if ( ! empty( $postarr['_wp_statuses_status'] ) && 'restricted' === $postarr['_wp_statuses_status'] ) {
		$data['post_status'] = 'restricted';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'wp_statuses_publish_as_restricted', 10, 2 );

/**
 * Filter the content for the "restricted" pages.
 *
 * @since 1.0.0
 *
 * @param string $content The content.
 */
function wp_statuses_restrict_content( $content = '' ) {
	$post = get_post();

	if ( 'page' !== $post->post_type ) {
		return $content;
	}

	if ( 'restricted' !== $post->post_status || is_user_logged_in() ) {
		return $content;
	}

	return sprintf( __( 'Please %s to view this content.', 'wp-statuses' ), sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( wp_login_url( get_permalink( $post->ID ) ) ),
		esc_html__( 'log in', 'wp-statuses' )
	) );
}
add_filter( 'the_content', 'wp_statuses_restrict_content', 1, 1 );
