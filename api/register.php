<?php
require_once __DIR__ . '/../config/config.php';

$data = require_json_post();
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');
$name = trim((string)($data['name'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Email invalide'], 422); 
if (strlen($password) < 6) json_response(['error' => 'Mot de passe trop court'], 422); 
if ($name === '') json_response(['error' => 'Nom requis'], 422); 

$pdo = getDB(); 
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) json_response(['error' => 'Email déjà utilisé'], 409); 

$hash = password_hash($password, PASSWORD_DEFAULT);

// PostgreSQL : utiliser RETURNING pour récupérer l'ID
$stmt = $pdo->prepare('INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?) RETURNING id');
$stmt->execute([$email, $hash, $name]);
$result = $stmt->fetch();
$id = (int)$result['id'];

// Récupérer l'utilisateur complet pour avoir les valeurs par défaut (comme avatar_url)
$stmt = $pdo->prepare('SELECT id, email, name, avatar_url FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'avatar_url' => $user['avatar_url'],
];

json_response(['user' => $_SESSION['user']]); 
?>