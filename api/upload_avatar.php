<?php
require_once __DIR__ . '/../config/config.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);

if ($isProduction) {
  
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['avatar_base64'])) {
        json_response(['error' => 'No avatar data provided'], 400);
    }
    
    $base64Data = $input['avatar_base64'];
    

    if (!preg_match('/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $base64Data)) {
        json_response(['error' => 'Invalid image format. Use base64'], 400);
    }
    

    $pdo = getDB();
    $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')
        ->execute([$base64Data, $user['id']]);
    
    $_SESSION['user']['avatar_url'] = $base64Data;
    
    json_response(['avatar_url' => $base64Data]);
    
} else {

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'Upload invalide'], 400);
    }

    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    $tmp = $_FILES['avatar']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed, true)) json_response(['error' => 'Format non supporté'], 422);

    $filename = 'u' . $user['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $uploadsDir . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) json_response(['error' => 'Échec de sauvegarde'], 500);

    $publicUrl = '/uploads/' . $filename;

    $pdo = getDB();
    $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')
        ->execute([$publicUrl, $user['id']]);

    $_SESSION['user']['avatar_url'] = $publicUrl;

    json_response(['avatar_url' => $publicUrl]);
}
?>