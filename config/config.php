<?php
declare(strict_types=1);

// ============ ENVIRONNEMENT ============
$isProduction = getenv('RENDER') !== false || (getenv('APP_ENV') === 'production');

// ============ CORS ============
if ($isProduction) {
    $allowedOrigin = 'https://portfoliofrontend-kohl.vercel.app/';
} else {
    $allowedOrigin = 'http://localhost:5173';
}

header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, Accept, Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Content-Type: application/json; charset=utf-8');

// Gestion des requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============ SESSIONS ============
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $isProduction); // CORRECTION ICI : $isProduction au lieu de true/false
ini_set('session.cookie_httponly', true);
ini_set('session.use_strict_mode', true);

session_start();

// ============ BASE DE DONNÉES ============
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo) return $pdo;

    // Déclarer $isProduction comme globale pour l'utiliser dans la fonction
    global $isProduction;

    // Configuration pour production (Render)
    if ($isProduction) {
        $host = getenv('DB_HOST');
        $db   = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASSWORD');
    } else {
        // Configuration locale
        $host = '127.0.0.1';
        $db   = 'portfolio_db';
        $user = 'portfolio_user';
        $pass = 'portfolio_pass';
    }
    
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Loguer l'erreur sans afficher de détails sensibles
        error_log("Database connection failed: " . $e->getMessage());
        
        if ($isProduction) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => 'Database connection error']);
        } else {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        exit();
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
    if (!$user) json_response(['error' => 'Unauthorized'], 401);
    return $user;
}
?>