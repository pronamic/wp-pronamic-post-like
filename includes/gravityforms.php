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
	gform_update_meta( $lead['id'], 'pronamic_post_like_key', wp_generate_password( 16, false ) );
}

add_action( 'gform_entry_created', 'pronamic_post_like_gform_entry_created' );

/**
 * Vote link shortcode
 *
 * @param array $atts
 * @param string $content
 * @return string
*/
function pronamic_shortcode_pronamic_vote_link( $atts, $content = '' ) {
	extract( shortcode_atts( array(
	'id' => null
	), $atts ) );

	$output = '';

	if ( method_exists( 'RGFormsModel', 'get_lead' ) ) {
		$lead = RGFormsModel::get_lead( $id );

		if ( $lead ) {
			$url = $lead['source_url'];
			$key = gform_get_meta( $id, 'pronamic_post_like_key' );

			$url = add_query_arg( 'ppl_key', $key, $url );
			$content = empty( $content ) ? __( 'Vote', 'pronamic_post_like' ) : $content;

			$output .= sprintf( '<a href="%s">%s</a>', esc_attr( $url ), $content );
		}
	}

	return $output;
}

add_shortcode( 'pronamic_vote_link', 'pronamic_shortcode_pronamic_vote_link' );
