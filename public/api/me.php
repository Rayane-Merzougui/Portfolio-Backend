<?php
require_once __DIR__ . '/../../config/config.php';
$user = current_user();
if (!$user) json(['user' => null]);
json(['user' => $user]);