<?php

function pronamic_post_like_link_shortcode( $atts, $content = '' ) {
	extract( shortcode_atts( array(
		'post_id' => null,
		'type'    => 'user_vote'
	), $atts ) );

	$post_id = isset( $post_id ) ? $post_id : get_the_ID();
	$content = empty( $content ) ? __( 'Like', 'pronamic_post_like' ) : $content;
	
	$link = pronamic_get_post_like_link( $type, $post_id );
	
	$output = sprintf( 
		'<a href="%s">%s</a>',
		esc_attr( $link ),
		esc_html( $content )
	);
	
	return $output;
}

add_shortcode( 'pronamic_post_like_link', 'pronamic_post_like_link_shortcode' );
