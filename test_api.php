<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';

echo json_encode([
    'status' => 'testing',
    'database' => 'connected',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
]);