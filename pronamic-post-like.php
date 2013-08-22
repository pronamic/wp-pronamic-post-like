<?php
/*
Plugin Name: Pronamic Post Like
Plugin URI: http://pronamic.eu/wp-plugins/post-like/
Description: 
 
Version: 1.0.0
Requires at least: 3.0

Author: Pronamic
Author URI: http://pronamic.eu/

Text Domain: pronamic_post_like
Domain Path: /languages/

License: GPL

GitHub URI: https://github.com/pronamic/wp-pronamic-post-like
*/

function pronamic_get_post_like_link( $comment = 'like', $post_id = null ) {
	$post_id = ( null === $post_id ) ? get_the_ID() : $post_id;
	
	$link = get_permalink( $post_id );
	$link = add_query_arg( 'like', $comment, $link );
	$link = wp_nonce_url( $link, 'pronamic_like', 'like_nonce' );
	
	return $link;
}

function pronamic_post_like_the_content( $content ) {
	$like_methods = array(
		'like' => __( 'Like', '' ),
		'fun'  => __( 'Fun', '' ),
		'cute' => __( 'Cute', '' ),
		'wow'  => __( 'Wow', '' )
	);
	
	foreach ( $like_methods as $name => $label ) {
		$content .= sprintf( '<a href="%s">%s</a> ', 
			pronamic_get_post_like_link( $name ),
			$label
		);
	}

	$results = pronamic_post_like_results( get_the_ID() );
	
	var_dump( $results );
	
	return $content;
}

add_filter( 'the_content', 'pronamic_post_like_the_content' );

function pronamic_post_like_can_vote( $post_id, $user_id, $ip_address ) {
	global $wpdb;

	$user_id = get_current_user_id();
	
	$condiation = $wpdb->prepare( 'user_id = %d', $user_id );

	if ( empty( $user_id ) ) {
		$ip_address = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );

		$condiation = $wpdb->prepare( 'comment_author_IP = %s', $ip_address );
	} else {
		
	}

	$query = "
		SELECT
			COUNT( comment_ID )
		FROM
			$wpdb->comments
		WHERE
			comment_post_ID = %d
				AND
			comment_type = 'pronamic_like'
				AND
			$condiation
		;			
	";
	
	$query = $wpdb->prepare( $query, $post_id );

	$result = $wpdb->get_var( $query );

	return empty( $result );
}

function pronamic_post_like_results( $post_id ) {
	global $wpdb;

	$query = "
		SELECT
			comment_content,
			COUNT( comment_ID )
		FROM
			$wpdb->comments
		WHERE
			comment_post_ID = %d
				AND
			comment_type = 'pronamic_like'
		GROUP BY
			comment_content
	";

	$query = $wpdb->prepare( $query, $post_id );
	
	$results = $wpdb->get_results( $query );
	
	return $results;
}

function pronamic_post_like_template_redirect() {
	if ( filter_has_var( INPUT_GET, 'like' ) && wp_verify_nonce( filter_input( INPUT_GET, 'like_nonce', FILTER_SANITIZE_STRING ), 'pronamic_like' ) ) {
		$post_id    = get_the_ID();
		$user_id    = get_current_user_id();
		$ip_address = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );

		if ( pronamic_post_like_can_vote( $post_id, $user_id, $ip_address ) ) {
			$comment_author       = __( 'Anonymous', 'pronamic_post_like' );
			$comment_author_email = null;
			$comment_author_url   = null;

			if ( is_user_logged_in() ) {
				$user = wp_get_current_user();
			
				if ( empty( $user->display_name ) )
					$user->display_name = $user->user_login;
			
				$comment_author       = esc_sql( $user->display_name );
				$comment_author_email = esc_sql( $user->user_email );
				$comment_author_url   = esc_sql( $user->user_url );
			}

			$commentdata = array(
				'comment_post_ID'      => $post_id,
				'comment_author_IP'    => $ip_address,
				'comment_type'         => 'pronamic_like',
				'comment_content'      => 'like',
				'comment_author'       => $comment_author,
				'comment_author_email' => $comment_author_email,
				'comment_author_url'   => $comment_author_url,
				'user_id'              => $user_id
			);
			
			$result = wp_insert_comment( $commentdata );
			
			if ( $result ) {
				echo 'liked';
			}
		} else {
			echo 'nop';
		}
		
		exit;
	}
}

add_action( 'template_redirect', 'pronamic_post_like_template_redirect' );
