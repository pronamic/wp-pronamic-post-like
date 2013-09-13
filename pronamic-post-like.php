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

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );

		add_action( 'template_redirect', array( $this, 'maybe_like' ) );

		add_action( 'wp_update_comment_count', array( $this, 'update_comment_count' ) );
		
		// AJAX
		$action = 'pronamic_social_vote';
		
		add_action( "wp_ajax_$action", array( $this, 'ajax_social_vote' ) );
		add_action( "wp_ajax_nopriv_$action", array( $this, 'ajax_social_vote' ) );
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		/*
		 * JavaScript Interfaces for Twitter for Websites
		 * @see https://dev.twitter.com/docs/tfw-javascript
		 */
		wp_enqueue_script(
			'twitter-widgets',
			'http://platform.twitter.com/widgets.js',
			array( ),
			false,
			true
		);

		/*
		 * Facebook SDK for JavaScript
		 * @see http://wordpress.org/plugins/facebook/
		 * @see https://developers.facebook.com/docs/reference/javascript/
		 */
		$deps = array( 'jquery', 'twitter-widgets' );
		if ( wp_script_is( 'facebook-jssdk' ) ) {
			$deps[] = 'facebook-jssdk';
		}

		/*
		 * Social vote
		 */
		wp_enqueue_script(
			'pronamic-social-vote',
			plugins_url( '/js/social-vote.js' , $this->file ),
			$deps,
			'1.0.0',
			true
		);
		
		wp_localize_script(
			'pronamic-social-vote',
			'pronamicSocialVote',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'postId'  => get_the_ID()
			)
		);
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
	 * Get comment base
	 */
	private function get_comment_data( $post_id = null ) {
		$post_id    = ( null === $post_id ) ? get_the_ID() : $post_id;

		$user_id    = get_current_user_id();
		$ip_address = $this->get_ip_address();

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
			'comment_author'       => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url'   => $comment_author_url,
			'user_id'              => $user_id
		);
		
		return $commentdata;
	}
	
	/**
	 * AJAX social vote
	 */
	public function ajax_social_vote() {
		$response = new stdClass();
		$response->status = 'error';

		$post_id = filter_input( INPUT_GET, 'post_id', FILTER_SANITIZE_STRING );
		$type    = filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING );

		$user_id    = get_current_user_id();
		$ip_address = $this->get_ip_address();

		if ( pronamic_post_like_can_vote( $post_id, $type ) ) {
			$comment_data = $this->get_comment_data( $post_id );

			$content = __( 'I voted on this post.', 'pronamic_post_like' );

			switch( $type ) {
				case 'facebook_like':
					$content = __( 'I liked this post on Facebook.', 'pronamic_post_like' );
					
					break;
				case 'twitter_tweet':
					$content = __( 'I tweeted this post on Twitter.', 'pronamic_post_like' );
					
					break;
			}

			$comment_data['comment_type']    = $type;
			$comment_data['comment_content'] = $content;
					
			$result = wp_insert_comment( $comment_data );
			
			if ( $result ) {
				$response->status = 'ok';
			}
		}

		$response->count  = $this->get_comment_count( $post_id, array( 'facebook_like', 'twitter_tweet' ) );

		// Output
		header( 'Content-type: application/json' );

		echo json_encode( $response );
		
		die();
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

			$post_id = get_the_ID();

			$url = add_query_arg( array(
				'like'       => false,
				'like_nonce' => false,
				'liked'      => 'no'
			) ) . '#like-' . $like_type;

			if ( pronamic_post_like_can_vote( $post_id, 'pronamic_like' ) ) {
				$comment_data = $this->get_comment_data( $post_id );
				
				$comment_data['comment_type']    = 'pronamic_like';
				$comment_data['comment_content'] = $like_type;
					
				$result = wp_insert_comment( $comment_data );

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
	public function can_vote( $post_id = null, $comment_type = 'pronamic_like' ) {
		global $wpdb;

		// Vars
		$post_id = ( null === $post_id ) ? get_the_ID() : $post_id;
		$user_id = get_current_user_id();

		if ( ! is_array( $comment_type ) ) {
			$comment_type = array( $comment_type );
		}

		$comment_type = "'" . join( "', '", $comment_type ) . "'";
	
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
				comment_type IN ($comment_type)
					AND
				$condiation
			;			
		";

		$query = $wpdb->prepare( $query, $post_id, $comment_type );
	
		$result = $wpdb->get_var( $query );

		$can_vote = empty( $result );

		// Return
		return $can_vote;
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
	public function get_results( $post_id = null, $comment_type = 'pronamic_like' ) {
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
				comment_type = %s
			GROUP BY
				comment_content
			;
		";

		$query = $wpdb->prepare( $query, $post_id, $comment_type );

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

	/**
	 * Get comment count
	 * 
	 * @var $comment_type
	 */
	public function get_comment_count( $post_id, $comment_type ) {
		global $wpdb;
		
		// Vars
		if ( ! is_array( $comment_type ) ) {
			$comment_type = array( $comment_type );
		}

		$comment_type = "'" . join( "', '", $comment_type ) . "'";

		// Return
		return (int) $wpdb->get_var( $wpdb->prepare( "
			SELECT
				COUNT(*)
			FROM
				$wpdb->comments
			WHERE
				comment_post_ID = %d
					AND
				comment_approved = '1'
					AND
				comment_type IN ($comment_type)
			", $post_id, $comment_type
		) );
	}

	/**
	 * Update comment count
	 * 
	 * @see https://github.com/WordPress/WordPress/blob/3.6/wp-includes/comment.php#L1620
	 */
	public function update_comment_count( $post_id ) {
		global $wpdb;

		$comment_type = array( 'pronamic_like', 'pronamic_social_vote' );
		$comment_type = "'" . join( "', '", $comment_type ) . "'";

		$new = (int) $wpdb->get_var( $wpdb->prepare( "
			SELECT
				COUNT(*)
			FROM
				$wpdb->comments
			WHERE
				comment_post_ID = %d
					AND
				comment_approved = '1'
					AND
				comment_type NOT IN ($comment_type)
			", $post_id
		) );

		$wpdb->update( $wpdb->posts, array( 'comment_count' => $new ), array( 'ID' => $post_id ) );
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
