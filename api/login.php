<?php
require_once __DIR__ . '/../config/config.php';

$data = require_json_post();
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id, email, password_hash, name, avatar_url FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['error' => 'Identifiants invalides'], 401); 
}

$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'avatar_url' => $user['avatar_url'],
];

json_response(['user' => $_SESSION['user']]); 
?>