<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';

$info = [
    'status' => 'diagnostic',
    'environment' => $isProduction ? 'production' : 'development',
    'database_variables' => [
        'DATABASE_URL' => getenv('DATABASE_URL') ? '***SET***' : 'NOT SET',
        'DB_HOST' => getenv('DB_HOST') ?: 'NOT SET',
        'DB_NAME' => getenv('DB_NAME') ?: 'NOT SET',
        'DB_USER' => getenv('DB_USER') ?: 'NOT SET',
        'DB_PASSWORD' => getenv('DB_PASSWORD') ? '***SET***' : 'NOT SET',
        'DB_PORT' => getenv('DB_PORT') ?: 'NOT SET',
    ],
    'php_version' => PHP_VERSION,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_pgsql' => extension_loaded('pdo_pgsql'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
    ],
    'timestamp' => date('Y-m-d H:i:s'),
];

try {
    $pdo = getDB();
    $info['database_connection'] = 'SUCCESS';
    $info['database_driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    // Test des tables
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
    $info['tables'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $info['database_connection'] = 'FAILED';
    $info['database_error'] = $e->getMessage();
}

echo json_encode($info, JSON_PRETTY_PRINT);
?>