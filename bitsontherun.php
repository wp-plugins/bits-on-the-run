<?php
/*
Plugin Name: Bits on the Run
Plugin URI: http://www.bitsontherun.com/
Description: This plugin allows you to easily upload and embed videos using the Bits on the Run platform. The embedded video links can be signed, making it harder for viewers to steal your content.
Author: LongTail Video
Version: 0.6
*/

define('BOTR_PLUGIN_DIR', dirname(__FILE__));

// BotR API
require_once(BOTR_PLUGIN_DIR . '/api.php');

// Default settings
define('BOTR_PLAYER', 'ALJ3XQCI');
define('BOTR_TIMEOUT', '0');
define('BOTR_CONTENT_MASK', 'content.bitsontherun.com');
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

// Show the API key notice in the admin area if necessary
function botr_show_api_key_notice() {
  if (botr_test_api_keys() != 'valid') {
    $settings_url = get_admin_url() . 'options-media.php';
    ?>
    <div id='message' class='error fade'>
      <p>
        <strong>Don't forget to enter your Bits on the Run API key and secret on the <a href='<?php echo $settings_url; ?>'>media settings page</a>.</strong>
      </p>
    </div>
    <?php
  }
}
add_action('admin_notices', 'botr_show_api_key_notice');

// Additions to the page head in the admin area
function botr_admin_head() {
  $ajaxupload_url = plugins_url('ajaxupload.js', __FILE__);
  $logic_url = plugins_url('logic.js', __FILE__);
  $plugin_url = plugins_url('', __FILE__);
  $content_mask = get_option('botr_content_mask');
  $nr_videos = get_option('botr_nr_videos');

  ?>
  <link rel="stylesheet" href="<?php echo $plugin_url;?>/style.css" type="text/css" media="screen" />

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

// Add the video widget to the authoring page, if enabled in the settings
function botr_add_video_box() {
  if(get_option('botr_show_widget') == 'true') {
    if (botr_test_api_keys() == 'valid') {
      add_meta_box('botr-video-box', 'Bits on the Run', 'botr_widget_body', 'post', 'side', 'high');
      add_meta_box('botr-video-box', 'Bits on the Run', 'botr_widget_body', 'page', 'side', 'high');
    }
  }
}
add_action('admin_menu', 'botr_add_video_box');

// The body of the widget
function botr_widget_body() {
  ?>
  <div id='botr-list-wrapper'>
    <input type='text' value='Search videos' id='botr-search-box' />
    <ul id='botr-video-list'>
    </ul>
  </div>
  <fieldset id='botr-upload-video'>
    <div class='botr-row'>
      <label for='botr-upload-title'>Title</label>
      <span>
        <input type='text' id='botr-upload-title' name='botr-upload-title' />
      </span>
    </div>
    <div class='botr-row'>
      <label for='botr-upload-file'>File</label>
      <span>
        <a href='#' id='botr-upload-browse' class='button'>Choose file</a>
        <input type='text' id='botr-upload-file' name='botr-upload-file' value='no file selected' disabled='disabled' />
        <input type='text' id='botr-progress-bar' value='0%' readonly='readonly' />
      </span>
    </div>
    <div class='botr-row'>
        <p id='botr-upload-message'></p>
        <button type='submit' id='botr-upload-button' class='button-primary'>Upload video</button>
    </div>
  </fieldset>
  <?php
}

// Add the BotR settings to the media page in the admin panel
function botr_add_settings() {
  add_settings_section('botr_setting_section', 'Bits on the Run', 'botr_setting_section_header', 'media');

  add_settings_field('botr_api_key', 'API key', 'botr_api_key_setting', 'media', 'botr_setting_section');
  add_settings_field('botr_api_secret', 'API secret', 'botr_api_secret_setting', 'media', 'botr_setting_section');
  add_settings_field('botr_nr_videos', 'Number of videos', 'botr_nr_videos_setting', 'media', 'botr_setting_section');
  add_settings_field('botr_timeout', 'Timeout for signed links', 'botr_timeout_setting', 'media', 'botr_setting_section');
  add_settings_field('botr_content_mask', 'Content DNS mask', 'botr_content_mask_setting', 'media', 'botr_setting_section');
  add_settings_field('botr_player', 'Default player', 'botr_player_setting', 'media', 'botr_setting_section');
  add_settings_field('botr_show_widget', 'Show the widget', 'botr_show_widget_setting', 'media', 'botr_setting_section');

  register_setting('media', 'botr_api_key');
  register_setting('media', 'botr_api_secret');
  register_setting('media', 'botr_nr_videos');
  register_setting('media', 'botr_timeout');
  register_setting('media', 'botr_content_mask');
  register_setting('media', 'botr_player');
  register_setting('media', 'botr_show_widget');
}
add_action('admin_init', 'botr_add_settings');

// Print the header for our settings section
function botr_setting_section_header() {
  // Completely empty
}

// Check whether the API keys are valid
// Returns:
// - absent  if the API key or secret were not filled in or are of an incorrect length
// - valid   if the key and secret are valid
// - invalid if they key and secret are present and of the correct length, but are invalid
function botr_test_api_keys($do_test_call = false) {
  $botr_api = botr_get_api_instance();

  if ($botr_api === null) {
    return 'absent';
  } else if (!$do_test_call) {
    return 'valid';
  } else {
    $params = array('result_limit' => 1);
    $response = $botr_api->call('/videos/list', $params);

    if ($response && $response['status'] == 'ok') {
      return 'valid';
    } else {
      return 'invalid';
    }
  }
}

// The setting for the API key
function botr_api_key_setting() {
  $botr_api_key = get_option('botr_api_key');
  echo "<input name='botr_api_key' size='8' maxlength='8' type='text' value='$botr_api_key' />";

  if ($botr_api_key && strlen($botr_api_key) != 8) {
    echo "<br /><span class='botr-error'>Your API key should be 8 characters long.</span>";
  }
}

// The setting for the API secret
function botr_api_secret_setting() {
  $botr_api_secret = get_option('botr_api_secret');
  echo "<input name='botr_api_secret' size='24' maxlength='24' type='text' value='$botr_api_secret' />";
  echo "<br />You can find the API key and secret on your Bits on the Run <a href='http://dashboard.bitsontherun.com/account/'>account page</a>.";

  if ($botr_api_secret && strlen($botr_api_secret) != 24) {
    echo "<br /><span class='botr-error'>Your API secret should be 24 characters long.</span>";
  }
}

// The setting for the default player
function botr_player_setting() {
  $result = botr_test_api_keys(true);
  if ($result == 'valid') {
    $botr_api = botr_get_api_instance();
    $response = $botr_api->call("/players/list");
    $player = get_option('botr_player');

    echo "<select name='botr_player' id='botr_player' />";

    foreach ($response['players'] as $i => $p) {
      $key = $p['key'];
      $description = htmlentities($p['name']) . ' (' . $p['width'] . 'x' . $p['height'] . ')';
      $select = $key == $player ? "selected='selected'" : "";

      echo "<option value='$key' $select>$description</option>";
    }

    echo "</select>";

    echo "<br />The <a href='http://dashboard.bitsontherun.com/players/'>player</a> to use for embedding the videos.";
    echo " If you want to override the default player for a given video, simply append a dash and the corresponding player key to video key in the quicktag. For example: <code>[bitsontherun MdkflPz7-35rdi1pO]</code>.";
  } else {
    if ($result == 'absent') {
      $message = "You have to save your API key and secret before you can set this option.";
    } else {
      $message = "<span class='botr-error'>Could not contact the API, make sure your keys are valid.</span>";
    }

    echo "<input type='hidden' name='botr_player' value='" . BOTR_PLAYER . "' />";
    echo $message;
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

// The setting for the content mask
function botr_content_mask_setting() {
  $content_mask = get_option('botr_content_mask');
  echo "<input name='botr_content_mask' id='botr_content_mask' type='text' value='$content_mask' class='regular-text' />";
  echo "<br />The <a href='http://www.longtailvideo.com/support/bits-on-the-run/21627/dns-mask-our-content-servers'>DNS mask</a> of the BOTR content server.";
}

// The setting which determines whether we show the widget on the authoring page (or only in the "Add media" window)
function botr_show_widget_setting() {
  $show_widget = get_option('botr_show_widget');
  echo "<input name='botr_show_widget' id='botr_show_widget' type='checkbox' ";
  checked('true', $show_widget);
  echo " value='true' /> ";
  echo "<label for='botr_show_widget'>Show the Bits on the Run widget on the authoring page.</label><br />";
  echo "Note that the widget is also accessible from the media manager";
}

// Replace the BotR quicktags with a JS embed code
function botr_replace_quicktags($content) {
    if (botr_test_api_keys() == 'valid') {
        $regex = '/\[bitsontherun ([0-9a-z]{8})(?:[-_])?([0-9a-z]{8})?\]/si';
        return preg_replace_callback($regex, "botr_create_js_embed", $content);
    } else {
        return $content;
    }
}
add_filter('the_content', 'botr_replace_quicktags');

// Create the JS embed code for the BotR player
// $arguments is an array:
// - 0: ignored
// - 1: the video hash
// - 2: the player hash (or null for default player)
function botr_create_js_embed($arguments) {
  $video_hash = $arguments[1];
  $player_hash = $arguments[2] ? $arguments[2] : get_option('botr_player');
  $content_mask = get_option('botr_content_mask');
  $timeout = intval(get_option('botr_timeout'));
  $path = "players/$video_hash-$player_hash.js";
  if($timeout < 1) {
    $url = "http://$content_mask/$path";
  } else {
    $api_secret = get_option('botr_api_secret');
    $expires = time() + 60 * $timeout;
    $signature = md5("$path:$expires:$api_secret");
    if(is_ssl()) {
      $url = "https://$content_mask/$path?exp=$expires&sig=$signature";
    }
    else {
      $url = "http://$content_mask/$path?exp=$expires&sig=$signature";
    }
  }
  return "<script type='text/javascript' src='$url'></script>";
}

// Add the BotR tab to the menu of the "Add media" window
function botr_media_menu($tabs) {
  $newtab = array('botr' => 'Bits on the Run');
  return array_merge($tabs, $newtab);
}
add_filter('media_upload_tabs', 'botr_media_menu');

// output the contents of the BotR tab in the "Add media" page
function media_botr_page() {
  media_upload_header();

  ?>
  <form class="media-upload-form type-form validate" id="video-form" enctype="multipart/form-data" method="post" action="">
    <h3 class="media-title">Embed videos from Bits on the Run</h3>
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
