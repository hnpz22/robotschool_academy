<?php
// modulos/academico/actividades/eliminar.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$U = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $U . 'modulos/academico/actividades/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    header('Location: ' . $U . 'modulos/academico/actividades/index.php');
    exit;
}

$pdo->prepare("DELETE FROM actividades WHERE id = ?")->execute([$id]);
header('Location: ' . $U . 'modulos/academico/actividades/index.php?msg=eliminada');
exit;
