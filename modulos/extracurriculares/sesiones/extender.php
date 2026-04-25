<?php
// modulos/extracurriculares/sesiones/extender.php
// Agrega 4 sesiones mas al programa despues de la ultima sesion existente
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$U = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php');
    exit;
}

$programa_id = (int)($_POST['programa_id'] ?? 0);
if (!$programa_id) { header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php'); exit; }

$cant = (int)($_POST['cantidad'] ?? 4);
if ($cant < 1 || $cant > 20) $cant = 4;

// Datos del programa
$s = $pdo->prepare("SELECT * FROM ec_programas WHERE id = ?");
$s->execute([$programa_id]);
$P = $s->fetch();
if (!$P) { header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php'); exit; }

// Ultima sesion existente
$u = $pdo->prepare("SELECT fecha, numero_sesion FROM ec_sesiones
                    WHERE programa_id = ?
                    ORDER BY numero_sesion DESC, fecha DESC LIMIT 1");
$u->execute([$programa_id]);
$ultima = $u->fetch();

if (!$ultima) {
    // No hay sesiones previas -> redirige al generar inicial
    header('Location: ' . $U . "modulos/extracurriculares/programas/ver.php?id=$programa_id&msg=sin_sesiones");
    exit;
}

$fecha_next = (new DateTime($ultima['fecha']))->modify('+7 days');
$num_next   = (int)$ultima['numero_sesion'] + 1;

// Insertar N sesiones mas
$ins = $pdo->prepare("INSERT INTO ec_sesiones
    (programa_id, numero_sesion, fecha, hora_inicio, hora_fin, estado)
    VALUES (?,?,?,?,?,'programada')");

for ($i = 0; $i < $cant; $i++) {
    $ins->execute([
        $programa_id,
        $num_next + $i,
        $fecha_next->format('Y-m-d'),
        $P['hora_inicio'],
        $P['hora_fin']
    ]);
    $fecha_next->modify('+7 days');
}

// Actualizar el total_sesiones del programa para mantener coherencia
$pdo->prepare("UPDATE ec_programas SET total_sesiones = total_sesiones + ? WHERE id = ?")
    ->execute([$cant, $programa_id]);

header('Location: ' . $U . "modulos/extracurriculares/programas/ver.php?id=$programa_id&msg=extendido&n=$cant");
exit;
