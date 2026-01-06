<?php
// Point d'entrée pour les pages statiques ou redirection vers api.php

require_once __DIR__ . '/config/config.php'; // CHEMIN CORRIGÉ

error_log("=== INDEX.PHP ACCESSED ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);

// Extraire le chemin
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

error_log("Clean path: " . $path);

if (strpos($path, 'api/') === 0) {

    $api_endpoint = substr($path, 4); 
    

    $_GET['action'] = $api_endpoint;
    require_once __DIR__ . '/api.php';
    exit();
}


if ($path === '' || $path === 'index.php') {
    json_response([
        'message' => 'Portfolio Backend API',
        'status' => 'online',
        'environment' => $isProduction ? 'production' : 'development',
        'timestamp' => date('c'),
        'endpoints' => [
            'GET  /api.php/articles' => 'List articles',
            'POST /api.php/register' => 'Register new user',
            'POST /api.php/login' => 'User login',
            'GET  /api.php/me' => 'Get current user',
            'POST /api.php/logout' => 'User logout',
            'POST /api.php/upload_avatar' => 'Upload avatar'
        ],
        'usage_note' => 'Use api.php directly or prefix with /api/ via index.php'
    ]);
} else {
    json_response(['error' => 'Not found'], 404);
}
?>