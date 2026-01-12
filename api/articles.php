<?php
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_GET['per_page'] ?? 10); // Ajout du paramètre per_page
    $offset = ($page - 1) * $perPage;

    // Correction pour PostgreSQL : utiliser fetchColumn()
    $total = (int)$pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
    
    $stmt = $pdo->prepare(
        'SELECT a.id, a.title, a.body, a.created_at, u.name AS author_name, u.avatar_url
         FROM articles a 
         LEFT JOIN users u ON a.user_id = u.id
         ORDER BY a.created_at DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll();

    // Formatage des dates pour plus de cohérence
    foreach ($list as &$item) {
        $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
    }
    unset($item);

    json_response([ 
        'items' => $list,
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => (int)ceil($total / $perPage),
        'hasMore' => ($page * $perPage) < $total
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_auth();
    $data = require_json_post();
    $title = trim((string)($data['title'] ?? ''));
    $body = trim((string)($data['body'] ?? ''));
    if ($title === '' || $body === '') json_response(['error' => 'Titre et contenu requis'], 422); 

    try {
        // Correction pour compatibilité avec SQLite et PostgreSQL
        $stmt = $pdo->prepare('INSERT INTO articles (user_id, title, body) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $title, $body]);
        
        // Récupérer l'ID selon le driver
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $id = (int)$pdo->lastInsertId('articles_id_seq');
        } else {
            $id = (int)$pdo->lastInsertId();
        }
        
        // Récupérer l'article créé avec les infos de l'auteur
        $stmt = $pdo->prepare(
            'SELECT a.id, a.title, a.body, a.created_at, u.name AS author_name, u.avatar_url
             FROM articles a 
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        $article = $stmt->fetch();
        
        json_response(['ok' => true, 'id' => $id, 'article' => $article], 201); 
    } catch (Exception $e) {
        json_response(['error' => 'Erreur lors de la création: ' . $e->getMessage()], 500);
    }
}

json_response(['error' => 'Method not allowed'], 405); 
?>