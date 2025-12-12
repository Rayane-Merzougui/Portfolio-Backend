<?php
require_once __DIR__ . '/../config/config.php';

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, Accept, Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    error_log("Received data: " . $input);
    
    $data = json_decode($input, true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Register test endpoint works',
        'received_data' => $data,
        'method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => time()
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Register test endpoint - use POST',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
}