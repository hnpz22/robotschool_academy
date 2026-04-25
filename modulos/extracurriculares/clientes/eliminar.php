<?php
// modulos/extracurriculares/clientes/eliminar.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$U = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $U . 'modulos/extracurriculares/clientes/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    header('Location: ' . $U . 'modulos/extracurriculares/clientes/index.php');
    exit;
}

$s = $pdo->prepare("SELECT COUNT(*) FROM ec_contratos WHERE cliente_id = ?");
$s->execute([$id]);
if ((int)$s->fetchColumn() > 0) {
    header('Location: ' . $U . 'modulos/extracurriculares/clientes/index.php?msg=no_eliminable');
    exit;
}

$pdo->prepare("DELETE FROM ec_clientes WHERE id = ?")->execute([$id]);
header('Location: ' . $U . 'modulos/extracurriculares/clientes/index.php?msg=eliminado');
exit;
