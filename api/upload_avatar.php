<?php
require_once __DIR__ . '/../config/config.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Vérifier si c'est une requête JSON (base64) ou FormData
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    // Mode JSON (base64)
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['avatar_base64'])) {
        json_response(['error' => 'No avatar data provided'], 400);
    }
    
    $base64Data = $input['avatar_base64'];
    
    if (!preg_match('/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $base64Data, $matches)) {
        json_response(['error' => 'Invalid image format. Use PNG, JPG, JPEG, GIF or WEBP'], 400);
    }
    
    $imageType = $matches[1];
    
    // Décoder l'image base64
    $base64Data = str_replace('data:image/'.$imageType.';base64,', '', $base64Data);
    $imageData = base64_decode($base64Data);
    
    if ($imageData === false) {
        json_response(['error' => 'Invalid base64 data'], 400);
    }
    
    // Vérifier la taille (max 2MB)
    if (strlen($imageData) > 2 * 1024 * 1024) {
        json_response(['error' => 'Image too large (max 2MB)'], 400);
    }
    
    // Créer le dossier uploads
    $uploadsDir = __DIR__ . '/../public/uploads'; // Chemodifé pour public/uploads
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }
    
    // Générer un nom de fichier unique
    $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $imageType;
    $dest = $uploadsDir . '/' . $filename;
    
    // Sauvegarder le fichier
    if (!file_put_contents($dest, $imageData)) {
        json_response(['error' => 'Échec de sauvegarde de l\'image'], 500);
    }
    
    $publicUrl = '/uploads/' . $filename;
    
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
    $stmt->execute([$publicUrl, $user['id']]);
    
    $_SESSION['user']['avatar_url'] = $publicUrl;
    
    json_response(['avatar_url' => $publicUrl]);
    
} else {
    // Mode FormData (multipart)
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        json_response(['error' => 'Upload invalide. Code erreur: ' . $_FILES['avatar']['error']], 400);
    }

    $uploadsDir = __DIR__ . '/../public/uploads'; // Chemin modifié
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }

    $tmp = $_FILES['avatar']['tmp_name'];
    $originalName = $_FILES['avatar']['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    
    if (!in_array($ext, $allowed, true)) {
        json_response(['error' => 'Format non supporté. Utilisez JPG, PNG, GIF ou WEBP'], 422);
    }

    // Vérifier la taille du fichier (max 2MB)
    if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
        json_response(['error' => 'Image trop lourde (max 2MB)'], 422);
    }

    // Vérifier que c'est bien une image
    $imageInfo = getimagesize($tmp);
    if ($imageInfo === false) {
        json_response(['error' => 'Fichier n\'est pas une image valide'], 422);
    }

    $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
    $dest = $uploadsDir . '/' . $filename;
    
    if (!move_uploaded_file($tmp, $dest)) {
        json_response(['error' => 'Échec de sauvegarde'], 500);
    }

    $publicUrl = '/uploads/' . $filename;

    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
    $stmt->execute([$publicUrl, $user['id']]);

    $_SESSION['user']['avatar_url'] = $publicUrl;

    json_response(['avatar_url' => $publicUrl]);
}
?>