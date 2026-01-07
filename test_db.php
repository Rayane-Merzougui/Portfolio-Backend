<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$isProduction = getenv('RENDER') !== false;

if ($isProduction) {
    $dbVars = [
        'DB_HOST' => getenv('DB_HOST'),
        'DB_NAME' => getenv('DB_NAME'),
        'DB_USER' => getenv('DB_USER'),
        'DB_PASSWORD' => getenv('DB_PASSWORD') ? '***SET***' : 'NOT SET',
        'DB_PORT' => getenv('DB_PORT'),
        'RENDER' => getenv('RENDER'),
        'APP_ENV' => getenv('APP_ENV'),
    ];
    
    try {
        $host = getenv('DB_HOST');
        $db   = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASSWORD');
        $port = getenv('DB_PORT') ?: 3306;
        
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        $dbTest = [
            'status' => 'SUCCESS',
            'connection' => 'OK',
            'test_query' => $result['test'] === 1 ? 'OK' : 'FAILED',
        ];
        
    } catch (Exception $e) {
        $dbTest = [
            'status' => 'ERROR',
            'error' => $e->getMessage(),
            'dsn' => $dsn ?? 'N/A',
            'user' => $user ?? 'N/A',
        ];
    }
    
    echo json_encode([
        'environment' => 'production',
        'database_variables' => $dbVars,
        'database_test' => $dbTest,
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_PRETTY_PRINT);
    
} else {
    echo json_encode([
        'environment' => 'development',
        'message' => 'Running in local development mode',
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_PRETTY_PRINT);
}
?>