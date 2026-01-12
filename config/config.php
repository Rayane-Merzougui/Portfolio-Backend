<?php
declare(strict_types=1);

// ============ ENVIRONNEMENT ============
$isProduction = getenv('RENDER') !== false || (getenv('APP_ENV') === 'production');

// ============ CORS ============
if ($isProduction) {
    // Liste des origines autorisées en production
    $allowedOrigins = [
        'https://portfoliofrontend-kohl.vercel.app',
        'https://portfolio-frontend.vercel.app'
    ];
} else {
    // Origine en développement
    $allowedOrigins = ['http://localhost:5173'];
}

// Vérifier et définir l'origine CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, Accept, Origin, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Content-Type: application/json; charset=utf-8');

// Gestion des requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============ SESSIONS ============
ini_set('session.cookie_samesite', $isProduction ? 'None' : 'Lax');
ini_set('session.cookie_secure', $isProduction);
ini_set('session.cookie_httponly', true);
ini_set('session.use_strict_mode', true);
ini_set('session.gc_maxlifetime', 86400); 

session_start();

if (!$isProduction) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ============ BASE DE DONNÉES ============
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo) return $pdo;

    global $isProduction;

    if ($isProduction) {
        // Essayer d'abord PostgreSQL
        try {
            $databaseUrl = getenv('DATABASE_URL');
            
            if ($databaseUrl) {
                $url = parse_url($databaseUrl);
                $host = $url['host'];
                $db   = ltrim($url['path'], '/');
                $user = $url['user'];
                $pass = $url['pass'];
                $port = $url['port'] ?? 5432;
                
                $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
                error_log("Trying PostgreSQL connection...");
                
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                $pdo->query("SELECT 1");
                error_log("PostgreSQL connection successful!");
                return $pdo;
            }
        } catch (PDOException $e) {
            error_log("PostgreSQL failed: " . $e->getMessage());
            // Continuer pour essayer MySQL
        }
        
        // Si PostgreSQL échoue, essayer MySQL avec les variables d'environnement
        try {
            $host = getenv('DB_HOST');
            $db   = getenv('DB_NAME');
            $user = getenv('DB_USER');
            $pass = getenv('DB_PASSWORD');
            $port = getenv('DB_PORT') ?: 3306;
            
            if (!$host || !$db || !$user || !$pass) {
                throw new Exception('Database configuration incomplete');
            }
            
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            error_log("Trying MySQL connection...");
            
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]);
            
            $pdo->query("SELECT 1");
            error_log("MySQL connection successful!");
            return $pdo;
            
        } catch (PDOException $e) {
            error_log("MySQL also failed: " . $e->getMessage());
            throw new Exception('Database connection error. Please try again later.');
        }
        
    } else {
        // Configuration locale
        $host = '127.0.0.1';
        $db   = 'portfolio_db';
        $user = 'portfolio_user';
        $pass = 'portfolio_pass';
        $port = 3306;
        
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        return $pdo;
    }
}

// ============ FONCTIONS UTILITAIRES ============
function json_response($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function require_json_post(): array {
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        json_response(['error' => 'No data received'], 400);
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_response(['error' => 'Invalid JSON: ' . json_last_error_msg()], 400);
    }
    
    if (!is_array($data)) {
        json_response(['error' => 'Data must be a JSON object'], 400);
    }
    
    return $data;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_auth(): array {
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }
    return $user;
}

// Fonction pour valider l'email
function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Fonction pour valider le mot de passe
function validate_password(string $password): bool {
    return strlen($password) >= 6;
}
?>