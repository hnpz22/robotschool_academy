<?php
// modulos/extracurriculares/sesiones/generar.php
// Genera las 4 sesiones automaticamente para un programa
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

// Datos del programa y contrato
$s = $pdo->prepare("SELECT p.*, ct.fecha_inicio AS contrato_inicio, ct.id AS contrato_id
                    FROM ec_programas p
                    JOIN ec_contratos ct ON ct.id = p.contrato_id
                    WHERE p.id = ?");
$s->execute([$programa_id]);
$P = $s->fetch();
if (!$P) { header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php'); exit; }

// Ver si ya tiene sesiones
$existe = $pdo->prepare("SELECT COUNT(*) FROM ec_sesiones WHERE programa_id = ?");
$existe->execute([$programa_id]);
$n_existentes = (int)$existe->fetchColumn();

if ($n_existentes > 0) {
    header('Location: ' . $U . "modulos/extracurriculares/programas/ver.php?id=$programa_id&msg=ya_generadas");
    exit;
}

// Calcular fechas de las 4 sesiones
// Primera sesion: primer dia_semana despues o igual a contrato.fecha_inicio
$dias_num = [
    'lunes'=>1,'martes'=>2,'miercoles'=>3,'jueves'=>4,
    'viernes'=>5,'sabado'=>6,'domingo'=>7
];
$dia_target = $dias_num[$P['dia_semana']] ?? 1;

$fecha = new DateTime($P['contrato_inicio']);
$dia_actual = (int)$fecha->format('N'); // 1-7

$offset = ($dia_target - $dia_actual + 7) % 7;
if ($offset > 0) {
    $fecha->modify("+$offset days");
}

// Insertar 4 sesiones espaciadas 7 dias
$ins = $pdo->prepare("INSERT INTO ec_sesiones
    (programa_id, numero_sesion, fecha, hora_inicio, hora_fin, estado)
    VALUES (?,?,?,?,?,'programada')");

for ($i = 1; $i <= 4; $i++) {
    $ins->execute([
        $programa_id,
        $i,
        $fecha->format('Y-m-d'),
        $P['hora_inicio'],
        $P['hora_fin']
    ]);
    $fecha->modify('+7 days');
}

// Marcar el programa como en curso si estaba planeado
if ($P['estado'] === 'planeado') {
    $pdo->prepare("UPDATE ec_programas SET estado = 'en_curso' WHERE id = ?")->execute([$programa_id]);
}

header('Location: ' . $U . "modulos/extracurriculares/programas/ver.php?id=$programa_id&msg=sesiones_generadas");
exit;
