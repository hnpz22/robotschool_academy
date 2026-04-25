<?php
// modulos/extracurriculares/programas/eliminar.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$U = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

// Recuperar contrato_id antes de borrar para redirigir
$cid = (int)$pdo->query("SELECT contrato_id FROM ec_programas WHERE id = $id")->fetchColumn();

$s = $pdo->prepare("SELECT COUNT(*) FROM ec_sesiones WHERE programa_id = ?");
$s->execute([$id]);
if ((int)$s->fetchColumn() > 0) {
    $dest = $cid ? "contratos/ver.php?id=$cid&msg=no_eliminable" : "programas/index.php?msg=no_eliminable";
    header('Location: ' . $U . 'modulos/extracurriculares/' . $dest);
    exit;
}

$pdo->prepare("DELETE FROM ec_programas WHERE id = ?")->execute([$id]);
$dest = $cid ? "contratos/ver.php?id=$cid&msg=prog_eliminado" : "programas/index.php?msg=eliminado";
header('Location: ' . $U . 'modulos/extracurriculares/' . $dest);
exit;
