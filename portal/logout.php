<?php
require_once __DIR__ . '/../config/config.php';
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . 'public/index.php');
exit;
