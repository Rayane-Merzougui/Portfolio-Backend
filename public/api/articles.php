<?php
require_once __DIR__ . '/../../config/config.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$page = max(1, (int)($_GET['page'] ?? 1));
	$perPage = 10;
	$offset = ($page - 1) * $perPage;

	$total = (int)$pdo->query('SELECT COUNT(*) AS c FROM articles')->fetch()['c'];
	$stmt = $pdo->prepare(
		'SELECT a.id, a.title, a.body, a.created_at, u.name AS author_name, u.avatar_url
		 FROM articles a JOIN users u ON a.user_id = u.id
		 ORDER BY a.created_at DESC
		 LIMIT :limit OFFSET :offset'
	);
	$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
	$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
	$stmt->execute();
	$list = $stmt->fetchAll();

	json([
		'items' => $list,
		'page' => $page,
		'perPage' => $perPage,
		'total' => $total,
		'totalPages' => (int)ceil($total / $perPage),
	]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$user = require_auth();
	$data = require_json_post();
	$title = trim((string)($data['title'] ?? ''));
	$body = trim((string)($data['body'] ?? ''));
	if ($title === '' || $body === '') json(['error' => 'Titre et contenu requis'], 422);

	$stmt = $pdo->prepare('INSERT INTO articles (user_id, title, body) VALUES (?, ?, ?)');
	$stmt->execute([$user['id'], $title, $body]);
	json(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
}

json(['error' => 'Method not allowed'], 405);