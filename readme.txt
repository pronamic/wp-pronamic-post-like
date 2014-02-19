=== Pronamic Post Like ===
Contributors: pronamic, remcotolsma
Tags: pronamic, post, like
Requires at least: 3.0
Tested up to: 3.8.1
Stable tag: 1.0.2

Pronamic Post Like is a powerful, extendable post like/vote plugin that helps 
you to setup any kind of like/vote system. 

== Description ==

...

= Gravity Forms =

[pronamic_gform_post_like_link id="{entry_id}"]Like[/pronamic_gform_post_like_link]


== Installation ==

If you want to implement a social vote system you can add the 
'pronamic-social-vote' to the Facebook like button widget or 
Twitter tweet button.

To auto update the number votes you can specify an the following
data attribute:

	data-pronamic-social-vote-count=".pronamic-social-vote-count" 


== Developers ==

*	php ~/wp/svn/i18n-tools/makepot.php wp-plugin ~/wp/git/pronamic-post-like ~/wp/git/pronamic-post-like/languages/pronamic_post_like.pot


== Screenshots ==

...


== Changelog ==

= 1.0.2 =
*	Added Gravity Forms entry meta column for the liked comment ID.

= 1.0.1 =
*	Update to version 1.0.1.

= 1.0.0 =
*	Removed shortcode vote link wich used a unique key in the user meta ([pronamic_vote_link]).
*	Added specific shortcode for in Gravity Forms notifications ([pronamic_gform_post_like_link id="{entry_id}"]Like[/pronamic_gform_post_like_link]).
*	Initial release.


== Links ==

*	[Pronamic](http://pronamic.eu/)
*	[Remco Tolsma](http://remcotolsma.nl/)
*	[Markdown's Syntax Documentation][markdown syntax]

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
		"Markdown is what the parser uses to process much of the readme file"


== Pronamic plugins ==

*	[Pronamic Google Maps](http://wordpress.org/extend/plugins/pronamic-google-maps/)
*	[Gravity Forms (nl)](http://wordpress.org/extend/plugins/gravityforms-nl/)
*	[Pronamic Page Widget](http://wordpress.org/extend/plugins/pronamic-page-widget/)
*	[Pronamic Page Teasers](http://wordpress.org/extend/plugins/pronamic-page-teasers/)
*	[Maildit](http://wordpress.org/extend/plugins/maildit/)
*	[Pronamic Framework](http://wordpress.org/extend/plugins/pronamic-framework/)
*	[Pronamic iDEAL](http://wordpress.org/extend/plugins/pronamic-ideal/)

