<?php
// Point d'entrée unique pour l'API
require_once __DIR__ . '/config/config.php';

// Journalisation pour déboguer
error_log("=== API REQUEST ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Path: " . $_SERVER['REQUEST_URI']);

// Extraire l'endpoint
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Nettoyer l'URL
$request_uri = strtok($request_uri, '?');
$base_path = dirname($script_name);

// Extraire le chemin de l'endpoint
if (strpos($request_uri, $script_name) === 0) {
    $endpoint = substr($request_uri, strlen($script_name));
} else if (strpos($request_uri, '/api.php/') === 0) {
    $endpoint = substr($request_uri, strlen('/api.php/'));
} else {
    $endpoint = trim($request_uri, '/');
}

$endpoint = trim($endpoint, '/');
error_log("Endpoint final: '" . $endpoint . "'");

// Gestion des endpoints
$routes = [
    'register' => 'api/register.php',
    'login' => 'api/login.php',
    'me' => 'api/me.php',
    'articles' => 'api/articles.php',
    'logout' => 'api/logout.php',
    'upload_avatar' => 'api/upload_avatar.php',
];

// Si endpoint vide, montrer la documentation
if ($endpoint === '') {
    json_response([
        'message' => 'Portfolio API',
        'status' => 'online',
        'endpoints' => array_keys($routes),
        'environment' => $isProduction ? 'production' : 'development'
    ]);
}

// Trouver et exécuter le bon fichier
if (isset($routes[$endpoint])) {
    $file_path = __DIR__ . '/' . $routes[$endpoint];
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log("File not found: " . $file_path);
        json_response(['error' => 'Endpoint file not found'], 404);
    }
} else {
    error_log("Endpoint not in routes: " . $endpoint);
    json_response(['error' => 'Endpoint not found: ' . $endpoint], 404);
}
?>