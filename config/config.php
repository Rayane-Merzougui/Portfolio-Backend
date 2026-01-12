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
        // Solution 1: SQLite (la plus simple et garantie de fonctionner)
        $dbFile = __DIR__ . '/database.sqlite';
        
        // Créer le répertoire si nécessaire
        $dbDir = dirname($dbFile);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Créer le fichier SQLite s'il n'existe pas
        if (!file_exists($dbFile)) {
            touch($dbFile);
        }
        
        $dsn = "sqlite:$dbFile";
        
        try {
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Créer les tables si nécessaire
            createSQLiteTables($pdo);
            
            error_log("SQLite connection successful!");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("SQLite connection failed: " . $e->getMessage());
            // Continuer pour essayer PostgreSQL
        }
        
        // Solution 2: Essayer PostgreSQL comme fallback
        try {
            // Récupérer l'URL PostgreSQL depuis les variables d'environnement
            $databaseUrl = getenv('DATABASE_URL');
            
            if (!$databaseUrl) {
                throw new Exception('DATABASE_URL not set');
            }
            
            error_log("DATABASE_URL: " . substr($databaseUrl, 0, 50) . "...");
            
            // Nettoyer l'URL (enlever les espaces et retours à la ligne)
            $databaseUrl = trim($databaseUrl);
            
            // Méthode robuste pour parser l'URL PostgreSQL
            // Format: postgresql://username:password@host:port/database
            
            // Extraire les parties avec parse_url
            $parsed = parse_url($databaseUrl);
            
            if (!$parsed) {
                throw new Exception('Failed to parse DATABASE_URL');
            }
            
            $host = $parsed['host'] ?? '';
            $port = $parsed['port'] ?? 5432;
            $user = $parsed['user'] ?? '';
            $pass = $parsed['pass'] ?? '';
            $dbname = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';
            
            error_log("Parsed: host=$host, port=$port, user=$user, db=$dbname");
            
            if (!$host || !$user || !$pass || !$dbname) {
                throw new Exception('Missing database connection parameters');
            }
            
            // Essayer différentes variations du DSN
            $dsnOptions = [
                "pgsql:host=$host;port=$port;dbname=$dbname",
                "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
                "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=prefer",
                "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=disable",
            ];
            
            foreach ($dsnOptions as $dsn) {
                try {
                    error_log("Trying DSN: " . str_replace($pass, '****', $dsn));
                    
                    $pdo = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    
                    // Tester la connexion
                    $pdo->query("SELECT 1");
                    error_log("PostgreSQL connection successful with: " . str_replace($pass, '****', $dsn));
                    
                    return $pdo;
                    
                } catch (Exception $e) {
                    error_log("Failed with DSN: " . $e->getMessage());
                    continue;
                }
            }
            
            throw new Exception('All PostgreSQL connection attempts failed');
            
        } catch (Exception $e) {
            error_log("PostgreSQL fallback also failed: " . $e->getMessage());
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

function createSQLiteTables($pdo) {
    // Créer la table users
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            name TEXT NOT NULL,
            avatar_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Créer la table articles
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            title TEXT NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ');
    
    // Créer les index
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_articles_user_id ON articles(user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_articles_created_at ON articles(created_at DESC)');
    
    error_log("SQLite tables created or already exist");
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