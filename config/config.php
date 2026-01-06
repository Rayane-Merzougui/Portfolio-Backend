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

// Gestion des requêtes OPTIONS - DOIT ÊTRE TRÈS TÔT DANS LE SCRIPT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============ SESSIONS ============
// IMPORTANT: Pour CORS avec credentials, utiliser 'None' et secure en production
ini_set('session.cookie_samesite', $isProduction ? 'None' : 'Lax');
ini_set('session.cookie_secure', $isProduction);
ini_set('session.cookie_httponly', true);
ini_set('session.use_strict_mode', true);
ini_set('session.gc_maxlifetime', 86400); // 24 heures

// Démarrer la session après avoir défini les en-têtes CORS
session_start();

// Pour le débogage (à désactiver en production)
if (!$isProduction) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    error_log("Session ID: " . session_id());
    error_log("Session data: " . json_encode($_SESSION));
}

// ============ BASE DE DONNÉES ============
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo) return $pdo;

    // Déclarer $isProduction comme globale
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
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        
        // En production, réponse générique
        if ($isProduction) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Database connection error',
                'message' => 'Please try again later'
            ]);
        } else {
            // En développement, montrer plus de détails
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Database connection failed',
                'details' => $e->getMessage()
            ]);
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
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }
    return $user;
}

// Fonction pour nettoyer les données
function sanitize_input(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fonction pour générer une réponse d'erreur
function error_response(string $message, int $code = 400): void {
    json_response(['error' => $message], $code);
}
?>