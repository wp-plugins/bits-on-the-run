=== Bits on the Run ===
Contributors: LongTail Video
Tags: bitsontherun, botr, video, media
Donate link: http://www.bitsontherun.com
Requires at least: 2.7
Tested up to: 3.1
Stable tag: 0.6

Upload videos to and embed videos from the Bits on the Run platform.

== Description ==

This module allows you to easily upload and embed videos using the Bits on the
Run platform. The embedded video links can be signed, making it harder for
viewers to steal your content.

== Installation ==

0. Make sure your Wordpress install is running on PHP5, the plugin will not 
   work with earlier versions.
1. Unpack the zip-file and put the resulting folder in the wp-content/plugins
   directory of your Wordpress install.
2. Login as Wordpress admin.
3. Go the the plugins page, the Bits on the Run plugin should be visible.
   Click "activate" to enable the plugin.
4. Enter your API key and secret on the media settings page (you can find 
   these on your Bits on the Run account page).
5. Change the rest of the settings to your liking. Don't forget to enable 
   secure content in your Bits on the Run account if you want to make use
   of the signed links.

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

Yes, it does. However, you should disable the signing functionality of the plugin,
since the caching might interfere with the signing timeout logic. Simply go to
Settings > Media and set the BOTR signing timeout to 0.

== Changelog ==

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

