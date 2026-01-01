<?php
require_once __DIR__ . '/../config/config.php';
$user = current_user();
if (!$user) json_response(['user' => null]); 
json_response(['user' => $user]); 
?>