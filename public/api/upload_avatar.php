<?php
require_once __DIR__ . '/../../config/config.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json(['error' => 'Method not allowed'], 405);

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
	json(['error' => 'Upload invalide'], 400);
}

$uploadsDir = __DIR__ . '/../uploads';
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

$tmp = $_FILES['avatar']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif','webp'];
if (!in_array($ext, $allowed, true)) json(['error' => 'Format non supporté'], 422);

$filename = 'u' . $user['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$dest = $uploadsDir . '/' . $filename;
if (!move_uploaded_file($tmp, $dest)) json(['error' => 'Échec de sauvegarde'], 500);

$publicUrl = '/uploads/' . $filename;

$pdo = db();
$pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')
	->execute([$publicUrl, $user['id']]);

$_SESSION['user']['avatar_url'] = $publicUrl;

json(['avatar_url' => $publicUrl]);