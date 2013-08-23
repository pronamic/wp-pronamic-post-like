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

class Pronamic_WP_PostLikePlugin {
	/**
	 * The main plugin file
	 * 
	 * @var string
	 */
	private $file;

	/**
	 * Constructs and intializes an Pronamic Post Like plugin
	 * 
	 * @param string $file
	 */
	public function __construct( $file ) {
		$this->file       = $file;
		$this->like_types = array();

		add_action( 'template_redirect', array( $this, 'maybe_like' ) );
	}

	/**
	 * Get IP address
	 * 
	 * @return mixed
	 */
	private function get_ip_address() {
		$ip_address = filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING );

		if ( empty( $ip_address ) ) {
			$ip_address = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );
		}

		if ( strpos( $ip_address, ',' ) !== false ) {
			$ip_addresses = explode( ',', $ip_address );
			
			$ip_address = array_shift( $ip_addresses );
		}

		return $ip_address; 
	}

	/**
	 * Maybe like
	 */
	public function maybe_like() {
		if ( filter_has_var( INPUT_GET, 'like' ) && wp_verify_nonce( filter_input( INPUT_GET, 'like_nonce', FILTER_SANITIZE_STRING ), 'pronamic_like' ) ) {
			$like_type = filter_input( INPUT_GET, 'like', FILTER_SANITIZE_STRING );
			if ( empty( $like_type ) ) {
				$like_type = 'like';
			}

			$post_id    = get_the_ID();
			$user_id    = get_current_user_id();
			$ip_address = $this->get_ip_address();

			$url = add_query_arg( array(
				'like'       => false,
				'like_nonce' => false,
				'liked'      => 'no'
			) ) . '#like-' . $like_type;

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
					'comment_content'      => $like_type,
					'comment_author'       => $comment_author,
					'comment_author_email' => $comment_author_email,
					'comment_author_url'   => $comment_author_url,
					'user_id'              => $user_id
				);
					
				$result = wp_insert_comment( $commentdata );

				if ( $result ) {
					$url = add_query_arg( 'liked', $result, $url );
				}
			}
			
			wp_redirect( $url );

			exit;
		}
	}

	/**
	 * Check if current user can vote
	 * 
	 * @param string $post_id
	 */
	public function can_vote( $post_id = null ) {
		global $wpdb;

		// Vars
		$post_id = ( null === $post_id ) ? get_the_ID() : $post_id;
		$user_id = get_current_user_id();
	
		// Condition
		$condiation = $wpdb->prepare( 'user_id = %d', $user_id );

		if ( empty( $user_id ) ) {
			$condiation = $wpdb->prepare( 'comment_author_IP = %s', $this->get_ip_address() );
		}
	
		// Query
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

		// Return
		return empty( $result );
	}

	/**
	 * Get like link
	 * 
	 * @param string $comment
	 * @param string $post_id
	 * @return string
	 */
	public function get_like_link( $comment = 'like', $post_id = null ) {
		$post_id = ( null === $post_id ) ? get_the_ID() : $post_id;
		
		$link = get_permalink( $post_id );
		$link = add_query_arg( 'like', $comment, $link );
		$link = wp_nonce_url( $link, 'pronamic_like', 'like_nonce' );
		
		return $link;
	}

	/**
	 * Get like results for the specified post ID
	 * 
	 * @param string $post_id
	 * @return array
	 */
	public function get_results( $post_id = null ) {
		global $wpdb;
		
		// Vars
		$post_id = ( null === $post_id ) ? get_the_ID() : $post_id;

		$query = "
			SELECT
				comment_content AS type,
				COUNT( comment_ID ) AS count
			FROM
				$wpdb->comments
			WHERE
				comment_post_ID = %d
					AND
				comment_type = 'pronamic_like'
			GROUP BY
				comment_content
			;
		";

		$query = $wpdb->prepare( $query, $post_id );

		$results = $wpdb->get_results( $query, OBJECT_K );

		if ( $results ) {
			$total = 0;
			
			foreach ( $results as $result ) {
				$total += $result->count;
			}
			
			foreach ( $results as $result ) {
				$result->percentage = ( $result->count / $total ) * 100;
			}
		}

		return $results;
	}
}

/**
 * Global init
 */
global $pronamic_post_like_plugin;

$pronamic_post_like_plugin = new Pronamic_WP_PostLikePlugin( __FILE__ );

/**
 * Can vote helper function
 * 
 * @param int $post_id
 */
function pronamic_post_like_can_vote( $post_id = null ) {
	global $pronamic_post_like_plugin;
	
	return $pronamic_post_like_plugin->can_vote( $post_id );
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
