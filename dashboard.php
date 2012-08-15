<?php

// The URL to the Bits on the Run dashboard
define('BOTR_DASHBOARD_URL', 'dashboard.bitsontherun.com');
define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);

$path = dirname(__FILE__);
while(!is_file($path . '/wp-config.php'))
{
  $path = dirname($path);
}
require_once($path . '/wp-config.php');

function botr_delegate_login_url($redirect = NULL) {
  if (!current_user_can('manage_options')) {
    return NULL;
  }
  
  $key = get_option('botr_api_key');
  $secret = get_option('botr_api_secret');
  
  if (!$key || !$secret) {
    return NULL;
  }

  if ($redirect) {
    $redirect = urlencode($redirect);
  }

  $timestamp = REQUEST_TIME + 60;
  $string_to_sign = 'account_key=' . $key . '&auth_key=' . $key;

  if ($redirect) {
    $string_to_sign .= '&redirect=' . $redirect;
  }

  $string_to_sign .= '&timestamp=' . $timestamp . $secret;
  $signature = sha1($string_to_sign);

  $url  = 'http://' . BOTR_DASHBOARD_URL . '/delegate_login/?account_key=' . $key . '&auth_key=' . $key;
  
  if ($redirect) {
      $url .= '&redirect=' . $redirect;
  }

  $url .= '&signature=' . $signature . '&timestamp=' . $timestamp;

  return $url;
}

function botr_dashboard() {
  // Create a signed url
  $url = botr_delegate_login_url();
  
  if(!$url) {
    $url = 'http://' . BOTR_DASHBOARD_URL . '/';
  }
  
  // Perform an automatic redirect
  wp_redirect($url);
}

botr_dashboard();

?>

