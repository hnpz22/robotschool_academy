<?php
// index.php &mdash; ROBOTSchool Academy Learning
// Punto de entrada principal

if (file_exists('config/config.php')) {
    require_once 'config/config.php';
    // Si ya est&aacute; logueado, ir al dashboard
    if (!empty($_SESSION['usuario_id'])) {
        header('Location: dashboard.php');
        exit;
    }
}
// Si no, ir al login
header('Location: login.php');
exit;
