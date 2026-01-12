<?php
require_once __DIR__ . '/config/config.php';

try {
    $pdo = getDB();
    
    // Test de la connexion
    $pdo->query('SELECT 1');
    
    // Récupérer des infos sur la base
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'")->fetchAll();
    $tableCount = count($tables);
    
    json_response([
        'status' => 'healthy',
        'database' => 'connected',
        'tables' => array_column($tables, 'table_name'),
        'table_count' => $tableCount,
        'environment' => $isProduction ? 'production' : 'development',
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    json_response([
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], 500);
}