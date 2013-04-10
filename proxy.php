<?php

$path = dirname(__FILE__);
while(!is_file($path . '/wp-config.php'))
{
  $path = dirname($path);
}
require_once($path . '/wp-config.php');

function botr_json_error($message) {
    $message = json_encode($message);
    return '{ "status" : "error", "message" : ' . $message . '}';
}

$BOTR_PROXY_METHODS = array(
    '/videos/list',
    '/channels/list',
    '/videos/create',
    '/videos/thumbnails/show',
    '/players/list',
);

function botr_proxy() {
    global $BOTR_PROXY_METHODS;

    if (!current_user_can('edit_posts')) {
        echo botr_json_error('Access denied');
        return;
    }

    $method = $_REQUEST['method'];

    if ($method === null) {
        echo botr_json_error('Method was not specified');
        return;
    }

    if (!in_array($method, $BOTR_PROXY_METHODS)) {
        echo botr_json_error('Access denied');
        return;
    }

    $botr_api = botr_get_api_instance();

    if ($botr_api === null) {
        echo botr_json_error('Enter your API key and secret first');
        return;
    }

    $params = array();

    foreach ($_REQUEST as $name => $value) {
        if ($name != 'method')
            $params[$name] = $value;
    }

    $params['api_format'] = 'php';
    $response = $botr_api->call($method, $params);
    echo json_encode($response);
}

if ($_REQUEST['method'] == 'upload_ready') {
    // This supplies a valid target for the redirect after the upload call.
    echo '{ "status" : "ok" }';
} else {
    botr_proxy();
}

?>

