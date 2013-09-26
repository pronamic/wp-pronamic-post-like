<?php

/**
 * Can vote helper function
 *
 * @param int $post_id
 */
function pronamic_post_like_can_vote( $post_id = null, $comment_type = 'pronamic_like' ) {
	global $pronamic_post_like_plugin;

	return $pronamic_post_like_plugin->can_vote( $post_id, $comment_type );
}

/**
 * Get post like link
 *
 * @param string $comment
 * @param string $post_id
 */
function pronamic_get_post_like_link( $comment = 'like', $post_id = null ) {
	global $pronamic_post_like_plugin;

	return $pronamic_post_like_plugin->get_like_link( $comment, $post_id );
}

/**
 * Get post like results
 *
 * @param string $post_id
 */
function pronamic_get_post_like_results( $post_id = null ) {
	global $pronamic_post_like_plugin;

	return $pronamic_post_like_plugin->get_results( $post_id );
}

/**
 * Get comment count
 */
function pronamic_get_comment_count( $post_id, $comment_type ) {
	global $pronamic_post_like_plugin;

	return $pronamic_post_like_plugin->get_comment_count( $post_id, $comment_type );
}

/**
 * Register post like type
 *
 * @param string $type the comment type
 * @param string $comment the default comment
 */
function pronamic_register_post_like_type( $type, $comment ) {
	global $pronamic_post_like_plugin;

	$pronamic_post_like_plugin->types[$type] = $comment;
}

/**
 * Get user by key
 *
 * @param string $key
 */
function pronamic_post_like_get_user_by_key( $key ) {
	global $wpdb;

	$query = $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'pronamic_post_like_key' AND meta_value = %s LIMIT 1", $key );

	if ( ! $user_id = $wpdb->get_var( $query ) )
		return false;

	$user = new WP_User( $user_id );

	return $user;
}
