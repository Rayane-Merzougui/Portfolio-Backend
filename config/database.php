<?php
// Configuration de la base de données
class DatabaseConfig {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        // Détermine l'environnement
        $isProduction = getenv('APP_ENV') === 'production' || getenv('RENDER') !== false;
        
        if ($isProduction) {
            // Configuration Render/Production
            $host = getenv('DB_HOST');
            $dbname = getenv('DB_NAME');
            $username = getenv('DB_USER');
            $password = getenv('DB_PASSWORD'); 
        } else {
            // Configuration locale
            $host = 'localhost';
            $dbname = 'portfolio';
            $username = 'root';
            $password = '';
        }
        
        try {
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // En production, loguer l'erreur sans afficher de détails
            error_log("Database connection failed: " . $e->getMessage());
            
            // Message générique selon l'environnement
            if ($isProduction) {
                header('HTTP/1.1 500 Internal Server Error');
                echo json_encode([
                    "status" => "error",
                    "message" => "Database connection error"
                ]);
            } else {
                // En local, afficher plus de détails
                die("Database connection failed: " . $e->getMessage());
            }
            exit();
        }
    }
    
    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConfig();
        }
        return self::$instance->pdo;
    }
}

// Fonction utilitaire pour obtenir la connexion
function getDB() {
    return DatabaseConfig::getConnection();
}
?>