<?php
/*
Plugin Name: Bits on the Run
Plugin URI: http://www.bitsontherun.com/
Description: This plugin allows you to easily upload and embed videos using the Bits on the Run platform. The embedded video links can be signed, making it harder for viewers to steal your content.
Author: Koen Vossen and Remco van Bree
Version: 0.3
*/

/* Use https:// instead of http:// for SSL requests. */
function botr_fix_protocol($option) {
    if($_SERVER["HTTPS"] == "on") {
        $option = preg_replace('|/+$|', '', $option);
        $option = preg_replace('|http://|', 'https://', $option);
    }
    return $option;
}

define('BOTR_PLUGIN_DIR', dirname(__FILE__));
define('BOTR_PLUGIN_URL', botr_fix_protocol(get_option('siteurl')) . '/wp-content/plugins/bitsontherun');

require_once(BOTR_PLUGIN_DIR . '/api.php');

/* Default settings */
define('BOTR_PLAYER', 'ALJ3XQCI');
define('BOTR_TIMEOUT', '60');
define('BOTR_CONTENT_MASK', 'content.bitsontherun.com');
define('BOTR_NR_VIDEOS', '5');


function botr_init() {
    register_activation_hook(__FILE__, 'botr_activate');

    add_action('admin_init', 'botr_add_settings');
    add_action('admin_head', 'botr_admin_head');
    add_action('admin_menu', 'botr_add_video_box');
    add_action('admin_notices', 'botr_show_api_key_notice');

    add_filter('the_content', 'botr_replace_quicktags');
}

botr_init();

function botr_activate() {
    add_option('botr_player', BOTR_PLAYER);
    add_option('botr_timeout', BOTR_TIMEOUT);
    add_option('botr_content_mask', BOTR_CONTENT_MASK);
    add_option('botr_nr_videos', BOTR_NR_VIDEOS);
}

function botr_get_api_instance() {
    $api_key = get_option('botr_api_key');
    $api_secret = get_option('botr_api_secret');

    if (strlen($api_key) == 8 && strlen($api_secret) == 24) {
        return new BotrAPI($api_key, $api_secret);
    } else {
        return null;
    }
}

function botr_show_api_key_notice() {
    if (botr_test_api_keys() != 'valid') {
        $settings_url = botr_fix_protocol(get_option('siteurl')) . '/wp-admin/options-media.php#botr';

        echo <<<HTML
            <div id='message' class='error fade'>
            <p><strong>Don't forget to enter your Bits on the Run API key and secret on the <a href='$settings_url'>media settings page</a>.</strong></p>
            </div>
HTML;
    }
}

function botr_admin_head() {
    $plugin_url = BOTR_PLUGIN_URL;
    $content_mask = get_option('botr_content_mask');
    $nr_videos = get_option('botr_nr_videos');

    echo <<<HTML

<link rel="stylesheet" href="$plugin_url/style.css" type="text/css" media="screen" />

<script type="text/javascript" src="$plugin_url/ajaxupload.js"></script>
<script type="text/javascript" src="$plugin_url/logic.js"></script>

<script type='text/javascript'>
    botr.plugin_url = '$plugin_url';
    botr.content_mask = '$content_mask';
    botr.nr_videos = $nr_videos;
</script>

HTML;
}

function botr_add_video_box() {
    if (botr_test_api_keys() == 'valid') {
        if(function_exists('add_meta_box')) {
            add_meta_box('botr-video-box', 'Bits on the Run', 'botr_inner_custom_box', 'post', 'side', 'high');
            add_meta_box('botr-video-box', 'Bits on the Run', 'botr_inner_custom_box', 'page', 'side', 'high');
        } else {
            add_action('dbx_post_sidebar', 'botr_old_custom_box');
            add_action('dbx_page_sidebar', 'botr_old_custom_box');
        }
    }
}

function botr_inner_custom_box() {
    echo <<<HTML
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
HTML;
}

function botr_old_custom_box() {
    echo <<<HTML
        <div class="dbx-box-wrapper">
          <fieldset id="botr-video-fieldset" class="dbx-box">
            <div class="dbx-handle-wrapper"><h3 class="dbx-handle">Bits on the Run</h3></div>
            <div class="dbx-content-wrapper">
              <div class="dbx-content">
HTML;

    botr_inner_custom_box();

    echo <<<HTML
              </div>
            </div>
          </fieldset>
        </div>
HTML;
}

function botr_add_settings() {
    add_settings_section('botr_setting_section', 'Bits on the Run', 'botr_setting_section_header', 'media');

    add_settings_field('botr_api_key', 'API key', 'botr_api_key_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_api_secret', 'API secret', 'botr_api_secret_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_nr_videos', 'Number of videos', 'botr_nr_videos_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_timeout', 'Timeout for signed links', 'botr_timeout_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_content_mask', 'Content DNS mask', 'botr_content_mask_setting', 'media', 'botr_setting_section');
    add_settings_field('botr_player', 'Default player', 'botr_player_setting', 'media', 'botr_setting_section');

    register_setting('media', 'botr_api_key');
    register_setting('media', 'botr_api_secret');
    register_setting('media', 'botr_nr_videos');
    register_setting('media', 'botr_timeout');
    register_setting('media', 'botr_content_mask');
    register_setting('media', 'botr_player');
}

function botr_setting_section_header() {
    echo "<a name='botr'></a>";
}

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

function botr_api_key_setting() {
    $botr_api_key = get_option('botr_api_key');
    echo "<input name='botr_api_key' size='8' maxlength='8' type='text' value='$botr_api_key' />";

    if ($botr_api_key && strlen($botr_api_key) != 8) {
        echo "<br /><span class='botr-error'>Your API key should be 8 characters long.</span>";
    }
}

function botr_api_secret_setting() {
    $botr_api_secret = get_option('botr_api_secret');
    echo "<input name='botr_api_secret' size='24' maxlength='24' type='text' value='$botr_api_secret' />";
    echo "<br />You can find the API key and secret on your Bits on the Run <a href='http://dashboard.bitsontherun.com/account/'>account page</a>.";

    if ($botr_api_secret && strlen($botr_api_secret) != 24) {
        echo "<br /><span class='botr-error'>Your API secret should be 24 characters long.</span>";
    }
}

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
        echo " If you want to override the default player for a given video, simply append a dash and the corresponding player key to video key in the quicktag. For example: <i>[bitsontherun MdkflPz7-35rdi1pO]</i>.";
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

function botr_validate_int($name, $value, $default) {
    if (preg_match('/^\d+$/', $value)) {
        return false;
    } else {
        update_option($name, $default);
        return "<br /><span class='botr-error'>Please enter a positive integer number.</span>";
    }
}

function botr_timeout_setting() {
    $timeout = get_option('botr_timeout');
    $error = botr_validate_int('botr_timeout', $timeout, BOTR_TIMEOUT);

    echo "<input name='botr_timeout' id='botr_timeout' type='text' size='7' value='$timeout' />";
    echo "<br />The duration in minutes for which a <a href='http://www.bitsontherun.com/documentation/content-server/security.html'>secured player</a> will be valid. Don't forget to enable <i>secure content</i> on your <a href='http://dashboard.bitsontherun.com/account/'>account page</a> if you want to use this feature.";

    if ($error) {
        echo $error;
    }
}

function botr_nr_videos_setting() {
    $nr_videos = get_option('botr_nr_videos');
    $error = botr_validate_int('botr_nr_videos', $nr_videos, BOTR_NR_VIDEOS);

    echo "<input name='botr_nr_videos' id='botr_nr_videos' type='text' size='2' value='$nr_videos' />";
    echo "<br />The number of videos to show in the widget on the <i>edit post</i> page.";

    if ($error) {
        echo $error;
    }
}

function botr_content_mask_setting() {
    $content_mask = get_option('botr_content_mask');
    echo "<input name='botr_content_mask' id='botr_content_mask' type='text' value='$content_mask' class='regular-text' />";
    echo "<br />The <a href='http://www.bitsontherun.com/documentation/content-server/dns-masking.html'>DNS mask</a> of the BOTR content server.";
}

function botr_replace_quicktags($content) {
    if (botr_test_api_keys() == 'valid') {
        $regex = '/\[bitsontherun ([0-9a-z]{8})(?:[-_])?([0-9a-z]{8})?\]/si';
        return preg_replace_callback($regex, "botr_create_js_embed", $content);
    } else {
        return $content;
    }
}

function botr_create_js_embed($arguments) {
    $video_hash = $arguments[1];
    $player_hash = $arguments[2] ? $arguments[2] : get_option('botr_player');
    $path = "players/$video_hash-$player_hash.js";

    $api_secret = get_option('botr_api_secret');
    $expires = time() + 60 * intval(get_option('botr_timeout'));
    $signature = md5("$path:$expires:$api_secret");

    $content_mask = get_option('botr_content_mask');
    $url = "http://$content_mask/$path?exp=$expires&sig=$signature";

    return "<script type='text/javascript' src='$url'></script>";
}

?>
