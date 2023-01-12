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
		/* translators: %s is the number of restricted to members pages. */
		'label_count'               => _n_noop( 'Restricted to members <span class="count">(%s)</span>', 'Restricted to members <span class="count">(%s)</span>', 'wp-statuses' ),
		'post_type'                 => array( 'page' ), // Just pages for this example :)
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'show_in_metabox_dropdown'  => true,
		'show_in_inline_dropdown'   => true,
		'labels'                    => array(
			'metabox_submit'   => __( 'Save', 'wp-statuses' ),
			'inline_dropdown'  => __( 'Restricted', 'wp-statuses' ),
		),
		'dashicon'                  => 'dashicons-groups',
	) );
}
add_action( 'init', 'wp_statuses_register_members_restricted', 11 );

/**
 * Filter the content for the "restricted" pages.
 *
 * @since 1.0.0
 *
 * @param string $content The content.
 */
function wp_statuses_restrict_content( $content = '' ) {
	$post = get_post();

	if ( ! isset( $post->post_type ) || 'page' !== $post->post_type ) {
		return $content;
	}

	if ( 'restricted' !== $post->post_status || is_user_logged_in() ) {
		return $content;
	}

	/* translators: %s is the login link. */
	return sprintf( __( 'Please %s to view this content.', 'wp-statuses' ), sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( wp_login_url( get_permalink( $post->ID ) ) ),
		esc_html__( 'log in', 'wp-statuses' )
	) );
}
add_filter( 'the_content', 'wp_statuses_restrict_content', 1, 1 );

/**
 * Add an example of custom status for the Post's post type.
 *
 * @since 1.1.0
 */
function wp_statuses_register_archived_post_status() {
	register_post_status( 'archive', array(
		'label'                       => __( 'Archive', 'wp-statuses' ),
		/* translators: %s is the number of archived posts. */
		'label_count'                 => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>', 'wp-statuses' ),
		'public'                      => false,
		'show_in_admin_all_list'      => false,
		'show_in_admin_status_list'   => true,
		'post_type'                   => array( 'post' ), // Only for posts!
		'show_in_metabox_dropdown'    => true,
		'show_in_inline_dropdown'     => true,
		'show_in_press_this_dropdown' => true,
		'labels'                      => array(
			'metabox_dropdown'    => __( 'Archived', 'wp-statuses' ),
			'metabox_submit'      => __( 'Archive', 'wp-statuses' ),
			'metabox_save_on'     => __( 'Archive on:', 'wp-statuses' ),
			/* translators: Post date information. 1: Date on which the post is to be archived */
			'metabox_save_date'   => __( 'Archive on: <b>%1$s</b>', 'wp-statuses' ),
			'metabox_saved_on'    => __( 'Archived on:', 'wp-statuses' ),
			/* translators: Post date information. 1: Date on which the post was archived */
			'metabox_saved_date'  => __( 'Archived on: <b>%1$s</b>', 'wp-statuses' ),
			'metabox_save_now'    => __( 'Archive <b>now</b>', 'wp-statuses' ),
			'inline_dropdown'     => __( 'Archived', 'wp-statuses' ),
			'press_this_dropdown' => __( 'Add to archives', 'wp-statuses' ),
		),
		'dashicon'                    => 'dashicons-archive',
	) );
}
add_action( 'init', 'wp_statuses_register_archived_post_status', 11 );
