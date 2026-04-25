<?php
require_once __DIR__ . '/../config/config.php';
require_once ROOT . '/config/auth.php';
requireLogin();

if (!in_array($_SESSION['usuario_rol'], ['docente','admin_sede','admin_general'])) {
    http_response_code(403); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'docente/index.php'); exit;
}

$matricula_id = (int)($_POST['matricula_id'] ?? 0);
$rubrica_id   = (int)($_POST['rubrica_id']   ?? 0);
$grupo_id     = (int)($_POST['grupo_id']     ?? 0);
$fecha        = $_POST['fecha']               ?? date('Y-m-d');
$obs          = trim($_POST['observaciones']  ?? '');
$criterios    = $_POST['criterio_id']         ?? [];
$puntajes     = $_POST['puntaje']             ?? [];

if (!$matricula_id || !$rubrica_id || empty($criterios)) {
    header('Location: ' . BASE_URL . 'docente/index.php?grupo='.$grupo_id.'&error=datos');
    exit;
}

try {
    $pdo->beginTransaction();

    // Crear evaluaci&oacute;n
    $pdo->prepare("INSERT INTO evaluaciones (matricula_id,rubrica_id,docente_id,fecha,observaciones) VALUES (?,?,?,?,?)")
        ->execute([$matricula_id, $rubrica_id, $_SESSION['usuario_id'], $fecha, $obs]);
    $eval_id = $pdo->lastInsertId();

    // Insertar puntajes por criterio
    $stmt = $pdo->prepare("INSERT INTO evaluacion_detalle (evaluacion_id,criterio_id,puntaje) VALUES (?,?,?)");
    foreach ($criterios as $i => $criterio_id) {
        $puntaje = min((int)($puntajes[$i] ?? 0), 999);
        $stmt->execute([$eval_id, (int)$criterio_id, $puntaje]);
    }

    $pdo->commit();
    header('Location: ' . BASE_URL . 'docente/index.php?grupo='.$grupo_id.'&msg=ok');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ' . BASE_URL . 'docente/index.php?grupo='.$grupo_id.'&error=bd');
    exit;
}
