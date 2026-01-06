<?php
require_once __DIR__ . '/../config/config.php';
session_destroy();
json_response(['ok' => true]); 
?>