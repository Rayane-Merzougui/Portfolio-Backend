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
$pdo->prepare('INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)')
    ->execute([$email, $hash, $name]);

$id = (int)$pdo->lastInsertId();
$_SESSION['user'] = ['id' => $id, 'email' => $email, 'name' => $name, 'avatar_url' => null];

json_response(['user' => $_SESSION['user']]); 
?>