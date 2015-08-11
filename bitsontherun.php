<?php
/*
Plugin Name: JW Platform Plugin
Plugin URI: http://www.jwplayer.com/
Description: This plugin allows you to easily upload and embed videos using the JW Platform (formerly known as Bits on the Run). The embedded video links can be signed, making it harder for viewers to steal your content.
Author: JW Player
Version: 1.3.2
*/

define('BOTR_PLUGIN_DIR', dirname(__FILE__));

// BotR API
require_once(BOTR_PLUGIN_DIR . '/api.php');

// Default settings
define('BOTR_PLAYER', 'ALJ3XQCI');
define('BOTR_TIMEOUT', '0');
define('BOTR_CONTENT_MASK', 'content.jwplatform.com');
define('BOTR_NR_VIDEOS', '5');
define('BOTR_SHOW_WIDGET', 'false');

// Execute when the plugin is enabled
function botr_activate() {
  // Add (but do not override) the settings
  add_option('botr_player', BOTR_PLAYER);
  add_option('botr_timeout', BOTR_TIMEOUT);
  add_option('botr_content_mask', BOTR_CONTENT_MASK);
  add_option('botr_nr_videos', BOTR_NR_VIDEOS);
  add_option('botr_show_widget', BOTR_SHOW_WIDGET);
  add_option('botr_login', NULL);
}
register_activation_hook(__FILE__, 'botr_activate');

// Get the API object
function botr_get_api_instance() {
  $api_key = get_option('botr_api_key');
  $api_secret = get_option('botr_api_secret');

  if (strlen($api_key) == 8 && strlen($api_secret) == 24) {
    return new BotrAPI($api_key, $api_secret);
  } else {
    return null;
  }
}

function botr_print_error($message) {
  ?>
  <div class='error fade'>
    <p>
      <strong><?php echo $message; ?></strong>
    </p>
  </div>
  <?php
}

// Show the login notice in the admin area if necessary
function botr_show_login_notice() {
  if (in_array($_REQUEST['page'], array('botr_login'))) {
    // Don't show the notice if we are logging in or signing up
    return;
  }
  if (!get_option('botr_login')) {
    $login_url = get_admin_url() . 'options-general.php?page=botr_login';
    botr_print_error("Don't forget to <a href='$login_url'>log in</a> to your JW Platform account.");
  }
}
add_action('admin_notices', 'botr_show_login_notice');

// Additions to the page head in the admin area
function botr_admin_head() {
  $ajaxupload_url = plugins_url('upload.js', __FILE__);
  $logic_url = plugins_url('logic.js', __FILE__);
  $style_url = plugins_url('style.css', __FILE__);
  $plugin_url = plugins_url('', __FILE__);
  $content_mask = botr_get_content_mask();
  $nr_videos = get_option('botr_nr_videos');

  ?>
  <link rel="stylesheet" href="<?php echo $style_url;?>" type="text/css" media="screen" />

  <script type="text/javascript" src="<?php echo $ajaxupload_url;?>"></script>
  <script type="text/javascript" src="<?php echo $logic_url;?>"></script>

  <script type='text/javascript'>
    botr.plugin_url = '<?php echo $plugin_url;?>';
    botr.content_mask = '<?php echo $content_mask;?>';
    botr.nr_videos = <?php echo $nr_videos;?>;
  </script>
  <?php
}
add_action('admin_head', 'botr_admin_head');

// Add JQuery-UI Draggable to the included scripts
function botr_enqueue_scripts() {
  wp_enqueue_script('jquery-ui-draggable');
}

add_action('admin_enqueue_scripts', 'botr_enqueue_scripts');

// Add the video widget to the authoring page, if enabled in the settings
function botr_add_video_box() {
  if(get_option('botr_show_widget') == 'true') {
    if (get_option('botr_login')) {
      add_meta_box('botr-video-box', 'JW Platform', 'botr_widget_body', 'post', 'side', 'high');
      add_meta_box('botr-video-box', 'JW Platform', 'botr_widget_body', 'page', 'side', 'high');
    }
  }
}
add_action('admin_menu', 'botr_add_video_box');

// The body of the widget
function botr_widget_body() {
  ?>
  <span class='botr-dashboard-link'>
    <a href='http://dashboard.jwplatform.com'>Open Dashboard</a>
  </span>
  <div id='botr-list-wrapper'>
    <input type='text' value='Search videos' id='botr-search-box' />
    <ul id='botr-video-list'></ul>
  </div>
  <select id='botr-player-select'>
    <option value=''>Default Player</option>
  </select>
  <button id='botr-upload-button' class='button-primary'>Upload a video...</button>
  <?php
}

// Add the BotR settings to the media page in the admin panel
function botr_add_settings() {
  add_settings_section('botr_setting_section', 'JW Platform', 'botr_setting_section_header', 'media');

  if(get_option('botr_login'))
  {
    add_settings_field('botr_logout_link', 'Log out', 'botr_logout_link', 'media', 'botr_setting_section');
    add_settings_field('botr_nr_videos', 'Number of videos', 'botr_nr_videos_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_timeout', 'Timeout for signed links', 'botr_timeout_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_content_mask', 'Content DNS mask', 'botr_content_mask_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_player', 'Default player', 'botr_player_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_show_widget', 'Show the widget', 'botr_show_widget_setting', 'media', 'botr_setting_section');

    register_setting('media', 'botr_nr_videos');
    register_setting('media', 'botr_timeout');
    register_setting('media', 'botr_content_mask');
    register_setting('media', 'botr_player');
    register_setting('media', 'botr_show_widget');
  }
  else {
    add_settings_field('botr_login_link', 'Log in', 'botr_login_link', 'media', 'botr_setting_section');
  }
}
add_action('admin_init', 'botr_add_settings');

// Print the header for our settings section
function botr_setting_section_header() {
  // Completely empty
}

// The setting for the default player
function botr_player_setting() {
  $login = get_option('botr_login');
  $loggedin = !empty($login);
  if ($loggedin) {
    $botr_api = botr_get_api_instance();
    $response = $botr_api->call("/players/list");
    $player = get_option('botr_player');

    echo "<select name='botr_player' id='botr_player' />";

    foreach ($response['players'] as $i => $p) {
      $key = $p['key'];
      if ($p['responsive']) {
        $description = htmlentities($p['name']) . ' (Responsive, ' . $p['aspectratio'] . ')';
      } else {
        $description = htmlentities($p['name']) . ' (Fixed size, ' . $p['width'] . 'x' . $p['height'] . ')';
      }
      $select = $key == $player ? "selected='selected'" : "";

      echo "<option value='$key' $select>$description</option>";
    }

    echo "</select>";

    echo "<br />The <a href='http://dashboard.jwplatform.com/players/'>player</a> to use for embedding the videos.";
    echo " If you want to override the default player for a given video, simply append a dash and the corresponding player key to video key in the quicktag. For example: <code>[jwplatform MdkflPz7-35rdi1pO]</code>.";
  }
  else {
    echo "<input type='hidden' name='botr_player' value='" . BOTR_PLAYER . "' />";
    echo "You have to save log in before you can set this option.";
  }
}

// Validate an integer setting.
// If it does not validate, return an error string.
// Otherwise, return false.
function botr_validate_int($name, $value, $default) {
  if (preg_match('/^\d+$/', $value)) {
    return false;
  } else {
    update_option($name, $default);
    return "<br /><span class='botr-error'>Please enter a positive integer number.</span>";
  }
}

// The setting for the signed player timeout
function botr_timeout_setting() {
  $timeout = get_option('botr_timeout');
  $error = botr_validate_int('botr_timeout', $timeout, BOTR_TIMEOUT);

  echo "<input name='botr_timeout' id='botr_timeout' type='text' size='7' value='$timeout' />";
  echo "<br />The duration in minutes for which a <a href='http://www.longtailvideo.com/support/bits-on-the-run/15986/secure-your-videos-with-signing'>signed player</a> will be valid. Set this to 0 (the default) if you don't use signing.";

  if ($error) {
    echo $error;
  }
}

// The setting for the number of videos to show in the widget
function botr_nr_videos_setting() {
  $nr_videos = get_option('botr_nr_videos');
  $error = botr_validate_int('botr_nr_videos', $nr_videos, BOTR_NR_VIDEOS);

  echo "<input name='botr_nr_videos' id='botr_nr_videos' type='text' size='2' value='$nr_videos' />";
  echo "<br />The number of videos to show in the widget on the <i>edit post</i> page.";

  if ($error) {
    echo $error;
  }
}

// Function to return the botr_content_mask
function botr_get_content_mask() {
  $content_mask = get_option('botr_content_mask');
  if ($content_mask == "content.bitsontherun.com") {
    $content_mask = "content.jwplatform.com";
  }
  return $content_mask;
}

// The setting for the content mask
function botr_content_mask_setting() {
  $content_mask = botr_get_content_mask();
  if(!$content_mask) {
    // An empty content mask, or the variable was somehow removed entirely
    $content_mask = BOTR_CONTENT_MASK;
    update_option('botr_content_mask', $content_mask);
  }
  echo "<input name='botr_content_mask' id='botr_content_mask' type='text' value='$content_mask' class='regular-text' />";
  echo "<br />The <a href='http://www.longtailvideo.com/support/bits-on-the-run/21627/dns-mask-our-content-servers'>DNS mask</a> of the BOTR content server. ";
  echo "Please note <strong>a content mask will make https video embeds impossible</strong>.";
}

// The setting which determines whether we show the widget on the authoring page (or only in the "Add media" window)
function botr_show_widget_setting() {
  $show_widget = get_option('botr_show_widget');
  echo "<input name='botr_show_widget' id='botr_show_widget' type='checkbox' ";
  checked('true', $show_widget);
  echo " value='true' /> ";
  echo "<label for='botr_show_widget'>Show the JW Platform widget on the authoring page.</label><br />";
  echo "Note that the widget is also accessible from the <em>Add media</em> window.";
}

// The login link on the settings page
function botr_login_link() {
  $login_url = get_admin_url() . 'options-general.php?page=botr_login';
  echo "In order to use this plugin, please <a href='$login_url'>log in</a> first.";
}

// The logout link on the settings page
function botr_logout_link() {
  $logout_url = get_admin_url() . 'options-general.php?page=botr_logout';
  $user = get_option('botr_login');
  echo "Logged in as user <em>$user</em><br><a href='$logout_url'>Log out</a>";
}

// Print the login page
function botr_login_form() {
  ?>
<div class="wrap">
<h2>JW Platform login</h2>
  
<form method="post" action="">
<p>In order to use the JW Platform plugin, you are required to log in.</p>
<table class="form-table">
  
<tr valign="top">
<th scope="row">Username</th>
<td><input type="text" name="username"></td>
</tr>
  
<tr valign="top">
<th scope="row">Password</th>
<td><input type="password" name="password">
<p class="description">Your password will not be stored in the Wordpress database.</p></td>
</tr>
  
</table>

<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('botr-login-nonce');?>">
  
<p class="submit"><input type="submit" class="button-primary" value="Log in"></p>
  
</form></div><?php
}

// The login page
function botr_login() {
  if (!current_user_can('manage_options')) {
    botr_print_error('You do not have sufficient privileges to access this page.');
    return;
  }
  
  if(!isset($_REQUEST['username'], $_REQUEST['password'])) {
    botr_login_form();
    return;
  }
  
  // Check the nonce (counter XSRF)
  $nonce = $_REQUEST['_wpnonce'];
  if(!wp_verify_nonce($nonce, 'botr-login-nonce')) {
    botr_print_error('Could not verify the form data.');
    botr_login_form();
    return;
  }
  
  $login = $_REQUEST['username'];
  $password = $_REQUEST['password'];

  $keysecret = botr_get_api_key_secret($login, $password);

  if ($keysecret === NULL) {
    botr_print_error('Communications with the JW Platform API failed. Please try again later.');
    botr_login_form();
  }
  elseif (!isset($keysecret['key'], $keysecret['secret'])) {
    botr_print_error('Your login credentials were not accepted. Please try again.');
    botr_login_form();
  }
  else {
    // Perform the login.
    update_option('botr_login', $login);
    update_option('botr_api_key', $keysecret['key']);
    update_option('botr_api_secret', $keysecret['secret']);
    echo '<h2>Logged in</h2><p>Logged in successfully. Returning you to the <a href="options-media.php">media settings</a> page...</p>';
    // Perform a manual JavaScript redirect
    echo '<script type="application/x-javascript">document.location.href = "options-media.php"</script>';
  }
}

/**
 * Return an associative array with keys 'key' and 'secret', containing the API
 * key and secret for the account with the specified login credentials.
 *
 * If the credentials are invalid, return an empty array.
 *
 * If the API call failed, return NULL.
 */
function botr_get_api_key_secret($login, $password) {
  require_once 'api.php';

  // Create an API object without key and secret.
  $api = new BotrAPI('', '');
  $params = array(
    'account_login' => $login,
    'account_password' => $password,
  );
  $response = $api->call('/accounts/credentials/show', $params);

  if (!$response) {
    return NULL;
  }
  if ($response['status'] != 'ok') {
    if ($response['status'] == 'error' && $response['code'] == 'NotFound') {
      return array();
    }
    return NULL;
  }

  // No errors.
  return array(
    'key' => $response['account']['key'],
    'secret' => $response['account']['secret'],
  );
}

// Print the logout page
function botr_logout_form() {
  ?>
<div class="wrap">
<h2>JW Platform log out</h2>
  
<form method="post" action="">
<p>You can use this page to log out of your JW Platform account.<br>
Note that, while signed out, videos will not be embedded.</p>

<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('botr-logout-nonce');?>">
  
<p class="submit"><input type="submit" class="button-primary" value="Log out" name="logout"></p>
  
</form></div><?php
}

// The logout page
function botr_logout() {
  if (!current_user_can('manage_options')) {
    botr_print_error('You do not have sufficient privileges to access this page.');
    return;
  }
  
  if(!isset($_REQUEST['logout'])) {
    botr_logout_form();
    return;
  }
  
  // Check the nonce (counter XSRF)
  $nonce = $_REQUEST['_wpnonce'];
  if(!wp_verify_nonce($nonce, 'botr-logout-nonce')) {
    botr_print_error('Could not verify the form data.');
    botr_logout_form();
    return;
  }
  
  // Perform the logout.
  update_option('botr_login', NULL);
  update_option('botr_api_key', '');
  update_option('botr_api_secret', '');
  echo '<h2>Logged out</h2><p>Logged out successfully. Returning you to the <a href="options-media.php">media settings</a> page...</p>';
  // Perform a manual JavaScript redirect
  echo '<script type="application/x-javascript">document.location.href = "options-media.php"</script>';
}

// Add the login and logout pages
function botr_add_login_pages() {
  // Note that this function is slightly hacky because it uses
  // $_registered_pages directly. There does not seem to be another way to add
  // an admin page without menu entry in the current WordPress version though.
  
  global $_registered_pages;
  
  $hooks = array('botr_login', 'botr_logout');
  
  foreach ($hooks as $h) {
    $hookname = get_plugin_page_hookname($h, 'options-general.php');
    
    if (!empty($hookname)) {
      add_action($hookname, $h);  
      $_registered_pages[$hookname] = true;
    }
  }
}
add_action('admin_menu', 'botr_add_login_pages');

function botr_handle_shortcode($atts) {
  $login = get_option('botr_login');
  if (empty($login)) {
      return '';
  }
  if (array_keys($atts) == array(0)) {
      $regex = '/([0-9a-z]{8})(?:[-_])?([0-9a-z]{8})?/i';
      $m = array();
      if (preg_match($regex, $atts[0], $m)) {
          return botr_create_js_embed($m);
      }
  }
  // Invalid shortcode
  return '';
}
add_shortcode('jwplatform', 'botr_handle_shortcode');
add_shortcode('bitsontherun', 'botr_handle_shortcode');

// Create the JS embed code for the BotR player
// $arguments is an array:
// - 0: ignored
// - 1: the video hash
// - 2: the player hash (or null for default player)
function botr_create_js_embed($arguments) {
  $video_hash = $arguments[1];
  $player_hash = $arguments[2] ? $arguments[2] : get_option('botr_player');
  $content_mask = botr_get_content_mask();
  $timeout = intval(get_option('botr_timeout'));
  $path = "players/$video_hash-$player_hash.js";
  $protocol = (is_ssl() && $content_mask == BOTR_CONTENT_MASK) ? 'https' : 'http';
  if($timeout < 1) {
    $url = "$protocol://$content_mask/$path";
  } else {
    $api_secret = get_option('botr_api_secret');
    $expires = time() + 60 * $timeout;
    $signature = md5("$path:$expires:$api_secret");
    $url = "$protocol://$content_mask/$path?exp=$expires&sig=$signature";
  }
  return "<script type='text/javascript' src='$url'></script>";
}

// Add the BotR tab to the menu of the "Add media" window
function botr_media_menu($tabs) {
  if(get_option('botr_login')) {
    $newtab = array('botr' => 'JW Platform');
    return array_merge($tabs, $newtab);
  }
}
add_filter('media_upload_tabs', 'botr_media_menu');

// output the contents of the BotR tab in the "Add media" page
function media_botr_page() {
  media_upload_header();

  ?>
  <form class="media-upload-form type-form validate" id="video-form" enctype="multipart/form-data" method="post" action="">
    <h3 class="media-title">Embed videos from JW Platform</h3>
    <div id="media-items">
      <div id="botr-video-box" class="media-item">
        <?php botr_widget_body(); ?>
      </div>
    </div>
  </form>
  <?php
}

// Make our iframe show up in the "Add media" page
function botr_media_handle() {
  return wp_iframe('media_botr_page');
}
add_action('media_upload_botr', 'botr_media_handle');

?>
