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
        // Parse l'URL PostgreSQL manuellement
        $databaseUrl = getenv('DATABASE_URL');
        
        if (!$databaseUrl) {
            throw new Exception('DATABASE_URL environment variable is not set');
        }
        
        // Exemple: postgresql://user:pass@host:port/dbname
        // Parse l'URL
        $pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)$/';
        
        if (!preg_match($pattern, $databaseUrl, $matches)) {
            error_log("Invalid DATABASE_URL format: " . $databaseUrl);
            throw new Exception('Invalid database URL format');
        }
        
        $user = $matches[1];
        $pass = $matches[2];
        $host = $matches[3];
        $port = $matches[4];
        $dbname = $matches[5];
        
        error_log("Parsed DB: host=$host, db=$dbname, user=$user, port=$port");
        
        // Essayez plusieurs méthodes de connexion
        
        // Méthode 1: PDO avec PostgreSQL
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            error_log("Trying DSN: $dsn");
            
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ]);
            
            // Test de connexion
            $pdo->query('SELECT 1');
            error_log("PostgreSQL connection successful with DSN method!");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("Method 1 failed: " . $e->getMessage());
        }
        
        // Méthode 2: Sans SSL
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=disable";
            error_log("Trying DSN without SSL: $dsn");
            
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            $pdo->query('SELECT 1');
            error_log("PostgreSQL connection successful without SSL!");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("Method 2 failed: " . $e->getMessage());
        }
        
        // Méthode 3: Utiliser pg_connect (si disponible)
        if (function_exists('pg_connect')) {
            try {
                $connString = "host=$host port=$port dbname=$dbname user=$user password=$pass";
                $pgconn = pg_connect($connString);
                
                if ($pgconn) {
                    error_log("pg_connect successful!");
                    // Créer un wrapper PDO pour pg_connect
                    // Note: Ceci est simplifié, en réalité vous devriez adapter votre code
                    // ou utiliser pg_* fonctions directement
                    throw new Exception('Use pg_connect functions instead of PDO');
                }
            } catch (Exception $e) {
                error_log("pg_connect failed: " . $e->getMessage());
            }
        }
        
        // Si tout échoue
        error_log("All connection methods failed!");
        throw new Exception('Unable to connect to database. Please check configuration.');
        
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