<?php
// modulos/academico/asistencia/eliminar.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede'); // Solo admins pueden eliminar sesiones

$sesion_id    = (int)($_GET['id'] ?? 0);
$filtro_grupo = (int)($_GET['grupo'] ?? 0);
$filtro_mes   = $_GET['mes'] ?? date('Y-m');
$U            = BASE_URL;

if (!$sesion_id) { header('Location: ' . $U . 'modulos/academico/asistencia/index.php'); exit; }

$sede_filtro = getSedeFiltro();
if ($sede_filtro) {
    $stm = $pdo->prepare("SELECT se.* FROM sesiones se JOIN grupos g ON g.id=se.grupo_id WHERE se.id=? AND g.sede_id=?");
    $stm->execute([$sesion_id, $sede_filtro]);
} else {
    $stm = $pdo->prepare("SELECT * FROM sesiones WHERE id=?");
    $stm->execute([$sesion_id]);
}
$sesion = $stm->fetch();

if (!$sesion) { header('Location: ' . $U . 'modulos/academico/asistencia/index.php'); exit; }

$pdo->prepare("DELETE FROM sesiones WHERE id=?")->execute([$sesion_id]);

header('Location: ' . $U . 'modulos/academico/asistencia/index.php?grupo=' . $filtro_grupo . '&mes=' . $filtro_mes . '&msg=sesion_eliminada');
exit;
