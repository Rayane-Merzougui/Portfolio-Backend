<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';

$info = [
    'status' => 'diagnostic',
    'environment' => $isProduction ? 'production' : 'development',
    'php_version' => PHP_VERSION,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_sqlite' => extension_loaded('pdo_sqlite'),
        'pdo_pgsql' => extension_loaded('pdo_pgsql'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
    ],
    'timestamp' => date('Y-m-d H:i:s'),
];

// Vérifier si database.sqlite existe
$sqliteFile = __DIR__ . '/database.sqlite';
$info['sqlite_file'] = [
    'exists' => file_exists($sqliteFile),
    'path' => $sqliteFile,
    'writable' => is_writable(dirname($sqliteFile)),
];

// Tester la connexion
try {
    $pdo = getDB();
    $info['database_connection'] = 'SUCCESS';
    $info['database_driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    // Lister les tables
    if ($info['database_driver'] === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    } else {
        $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
    }
    
    $info['tables'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
} catch (Exception $e) {
    $info['database_connection'] = 'FAILED';
    $info['database_error'] = $e->getMessage();
    $info['error_trace'] = $e->getTraceAsString();
}

echo json_encode($info, JSON_PRETTY_PRINT);
?>