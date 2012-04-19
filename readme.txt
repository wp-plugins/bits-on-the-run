=== Bits on the Run ===
Contributors: LongTail Video
Tags: bitsontherun, botr, video, media
Donate link: http://www.bitsontherun.com
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 0.7

Upload videos to and embed videos from the Bits on the Run platform.

== Description ==

The Bits on the Run plugin for WordPress provides publishers with the ability to
manage the videos they host using the Bits on the Run platform, directly within
the WordPress CMS. Video uploads and embeds can be performed directly within the
Wordpress post / page editor. This custom plugin has been built by
LongTail Video, the creators of the JW Player for Flash and HTML5. It provides
users with an easy way to manage and embed their videos - hosted in
Bits on the Run - and publish them to WordPress, using the JW Player.

Bits on the Run is an online video platform, developed by LongTail Video, built
for website owners, web developers, and video producers to upload, manage,
customize and publish online video content with ease.

Key Features

* Fast video streaming for playback in a video player, in both HTML5 and Flash modes
* Inline search field available to locate specific videos within your video library
* Quick click-to-publish for a specific video that inserts it into your post/page
* Top security using video signing to protect embedded video urls
* Seamless integration & full support for the JW Player
* Quicktags to locate custom players
* Support for uploading videos using our custom widget

[Sign up for a free Bits on the Run account!](https://www.longtailvideo.com/bits-on-the-run/sign-up/)

Documentation

Full documentation on installation, setup and getting started can be found on
our [Support Site](http://www.longtailvideo.com/support/bits-on-the-run/).

If you have any questions, comments, problems or suggestions please post on our
[User Forum](http://www.longtailvideo.com/support/forums/bits-on-the-run/).

Note: This module requires PHP5.

== Installation ==

0. Make sure your Wordpress install is running on PHP 5, as the plugin will not 
   work with earlier versions.
1. Unpack the zip-file and put the resulting folder in the wp-content/plugins
   directory of your Wordpress install.
2. Login as Wordpress admin.
3. Go the the plugins page, the Bits on the Run plugin should be visible.
   Click "activate" to enable the plugin.
4. Enter your API key and secret on the media settings page (you can find 
   the key and secret on your Bits on the Run account page).
5. Change the rest of the settings to your liking. Don't forget to enable 
   secure content in your Bits on the Run account if you want to make use
   of the signed links.
   It is also possible to enable the widget as a box inside the authoring
   environment, in addition to the "Add media" window.

We also advise you to install the PHP cURL library (usually already installed).
See http://php.net/manual/en/book.curl.php for more info, or ask your system
administrator.

== Screenshots ==

1. You can start uploading and embedding videos with the new Bits on the Run
   widget that will appear in the "add media" window. If you enabled the
   "Show widget" setting, the widget will also appear in the side bar when
   you're editing a post. For more detailed instructions, 
   [see this tutorial](http://www.longtailvideo.com/support/bits-on-the-run/15959/using-our-wordpress-plugin).

== Frequently Asked Questions ==

= Can I override the default player for a specific video? =

Yes, you can. Simply append a dash and the corresponding player key to video key
in the quicktag. For example: [bitsontherun MdkflPz7-35rdi1pO].

= Does this plugin work with caching solutions like WP-Supercache? =

Yes, it does. However, you should disable the signing functionality of the
plugin, since the caching might interfere with the signing timeout logic. Simply
go to Settings > Media and set the BOTR signing timeout to 0.

= Can I search through only my playlists? =

Yes, you can. In order to do this, simply write "playlist:" (without the quotes)
in front of your search query in the widget.

== Changelog ==

= 0.7 =

* Dropped Wordpress 2.7 compatibility.
* Added the widget to the "Add media" window, making the old widget optional.
* Added support for embedding playlists using the widget.

= 0.6 =

* Allowed for disabling of signing mechanism by setting timeout to 0. 
* Made timeout=0 the default.

= 0.5 =

* Made embedded players SSL-aware.

= 0.4 =

* Fixed Javascript for WP < 3.0, bind() didn't work in JQuery 1.2.6.

= 0.3 =

* Removed no-thumb icon. 

= 0.2 =

* HTML-escaped player titles.
* Warn when API keys are invalid.
* Show wait cursor during AJAX calls.
* Reset upload widgets on error.
* Fixed form problem in IE.

= 0.1 =

* Initial release.

