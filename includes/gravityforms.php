<?php

/**
 * User registered store additional password and key for liking posts
 *
 * @param string $user_id
 * @param array $user_config
 * @param array $entry
 * @param string $password
 */
function pronamic_post_like_gform_user_registered( $user_id, $user_config, $entry, $password ) {
	$key = gform_get_meta( $entry['id'], 'pronamic_post_like_key' );

	update_user_meta( $user_id, 'pronamic_post_like_password', $password );
	update_user_meta( $user_id, 'pronamic_post_like_key', $key );
}

add_action( 'gform_user_registered', 'pronamic_post_like_gform_user_registered', 10, 4 );


/**
 * Gravity Forms entry created hook wich will store an extra post like key
 *
 * @param array $lead
 */
function pronamic_post_like_gform_entry_created( $lead ) {
	gform_update_meta( $lead['id'], 'post_id', get_the_ID() );
}

add_action( 'gform_entry_created', 'pronamic_post_like_gform_entry_created' );

function pronamic_gform_post_like_link_shortcode( $atts, $content = '' ) {
	extract( shortcode_atts( array(
		'id'   => null,
		'type' => 'user_vote',
	), $atts ) );
	
	$output = '';
	
	if ( method_exists( 'RGFormsModel', 'get_lead' ) ) {
		$lead = RGFormsModel::get_lead( $id );

		if ( $lead ) {
			$post_id = gform_get_meta( $id, 'post_id' );

			$link = pronamic_get_post_like_link( $type, $post_id );

			$link = add_query_arg( 'gform_lid', $id, $link );
			
			$output = sprintf(
				'<a href="%s">%s</a>',
				esc_attr( $link ),
				esc_html( $content )
			);
		}
	}
	
	return $output;
}

add_shortcode( 'pronamic_gform_post_like_link', 'pronamic_gform_post_like_link_shortcode' );


/**
 * Pronamic post like Gravity Forms entry info
 * 
 * @param string $form_id
 * @param array $lead
 */
function pronamic_post_like_gform_entry_info( $form_id, $lead ) {
	$id = $lead['id'];
	
	$comment_id = gform_get_meta( $id, 'pronamic_post_like_comment_id' );

	_e( 'Liked: ', 'pronamic_post_like' );

	if ( $comment_id ) {
		printf(
			'<a href="%s">%s</a>',
			esc_attr( get_edit_comment_link( $comment_id ) ),
			__( 'Yes', 'pronamic_post_like' )
		);
	} else {
		_e( 'No', 'pronamic_post_like' );
	}
}

add_action( 'gform_entry_info', 'pronamic_post_like_gform_entry_info', 10, 2 );

/**
 * Liked
 * 
 * @param string $post_id
 * @param string $type
 */
function pronamic_post_like_gform_liked( $post_id, $comment_id, $type ) {
	if ( filter_has_var( INPUT_GET, 'gform_lid' ) ) {
		$id = filter_input( INPUT_GET, 'gform_lid', FILTER_SANITIZE_STRING );
		
		gform_update_meta( $id, 'pronamic_post_like_comment_id', $comment_id );
	}
}

add_action( 'pronamic_post_like_liked', 'pronamic_post_like_gform_liked', 10, 3 );
