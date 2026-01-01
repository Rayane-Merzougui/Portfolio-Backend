<?php
// Inclure la configuration unique
require_once __DIR__ . '/config/config.php';

error_log("=== API.PHP CALLED ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);

$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

$request_uri = strtok($request_uri, '?');

if (strpos($request_uri, $script_name) === 0) {
    $endpoint = substr($request_uri, strlen($script_name));
} else {
    $path = parse_url($request_uri, PHP_URL_PATH);
    $endpoint = str_replace('/api.php', '', $path);
}

$endpoint = trim($endpoint, '/');
error_log("Extracted endpoint: '" . $endpoint . "'");

if ($endpoint === '') {
    json_response([
        'message' => 'API is running', 
        'available_endpoints' => ['register', 'login', 'me', 'articles', 'logout', 'upload_avatar'],
        'usage' => 'Use /api.php/endpoint_name'
    ]);
}

switch($endpoint) {
    case 'register':
        require_once __DIR__ . '/api/register.php';
        break;
    case 'login':
        require_once __DIR__ . '/api/login.php';
        break;
    case 'me':
        require_once __DIR__ . '/api/me.php';
        break;
    case 'articles':
        require_once __DIR__ . '/api/articles.php';
        break;
    case 'logout':
        require_once __DIR__ . '/api/logout.php';
        break;
    case 'upload_avatar':
        require_once __DIR__ . '/api/upload_avatar.php';
        break;
    default:
        error_log("Endpoint not found: " . $endpoint);
        json_response(['error' => 'Endpoint not found: ' . $endpoint], 404);
}
?>