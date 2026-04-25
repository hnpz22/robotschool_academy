<?php
// modulos/academico/temas/eliminar.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$U = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $U . 'modulos/academico/temas/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    header('Location: ' . $U . 'modulos/academico/temas/index.php');
    exit;
}

// Verificar que no tenga actividades asociadas
$s = $pdo->prepare("SELECT COUNT(*) FROM actividades WHERE tema_id = ?");
$s->execute([$id]);
$total = (int)$s->fetchColumn();

if ($total > 0) {
    header('Location: ' . $U . 'modulos/academico/temas/index.php?msg=no_eliminable');
    exit;
}

$pdo->prepare("DELETE FROM temas WHERE id = ?")->execute([$id]);
header('Location: ' . $U . 'modulos/academico/temas/index.php?msg=eliminado');
exit;
