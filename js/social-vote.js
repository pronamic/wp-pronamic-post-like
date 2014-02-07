( function( $ ) {

	function socialVote( type, element ) {
		var data = {
			action: 'pronamic_social_vote',
			post_id: pronamicSocialVote.postId,
			type: type
		};

		$.getJSON( pronamicSocialVote.ajaxUrl, data, function( response ) {
			var countSelector = element.data( 'pronamic-social-vote-count' );

			if ( countSelector ) {
				var $countElement = $( countSelector );

				$countElement.text( response.count );
				$countElement.trigger( 'pronamic-social-vote-count-update' );
			}
		} );
	}

	/**
	 * Facebook
	 * 
	 * @see https://github.com/Automattic/facebook-wordpress/blob/master/extras/google-analytics.php#L19
	 * @see https://developers.facebook.com/docs/reference/javascript/FB.Event.subscribe/
	 */
	function facebookInit() {
		if ( typeof FB === 'object' ) {
			FB.Event.subscribe( 'edge.create', function( url, widget ) {
				var $widget = $( widget );
				
				if ( $widget.has( 'pronamic-social-vote' ) ) {
					socialVote( 'facebook_like', $widget );
				}
			} );
			
			FB.Event.subscribe('edge.remove', function( url, widget ) {
				
			} );
		}
	}
	
	if ( typeof FB_WP === 'object' && FB_WP.queue && FB_WP.queue.add ) { 
		FB_WP.queue.add( facebookInit );
	}

	/**
	 * Twitter
	 * @see https://dev.twitter.com/docs/tfw-javascript
	 */
	if ( typeof twttr === 'object' ) {
		function tweetIntentVote( intentEvent ) {
			var $target = $( intentEvent.target );
			
			if ( $target.has( 'pronamic-social-vote' ) ) {
				socialVote( 'twitter_tweet', $target );
			}
		};

		twttr.ready( function ( twttr ) {
			twttr.events.bind( 'tweet', tweetIntentVote );
		} );
	}

} )( jQuery );