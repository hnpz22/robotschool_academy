<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) $pdo->prepare("DELETE FROM evaluaciones WHERE id=?")->execute([$id]);
}
header('Location: ' . BASE_URL . 'modulos/academico/evaluaciones/index.php?msg=eliminada');
exit;
