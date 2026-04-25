<?php
// modulos/evaluaciones/criterios_ajax.php
// Devuelve JSON con los criterios de una r&uacute;brica
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

header('Content-Type: application/json; charset=utf-8');
$rubrica_id = (int)($_GET['rubrica_id'] ?? 0);
if (!$rubrica_id) { echo json_encode(['criterios'=>[],'total'=>0]); exit; }

$criterios = $pdo->prepare("SELECT * FROM rubrica_criterios WHERE rubrica_id=? ORDER BY orden");
$criterios->execute([$rubrica_id]);
$criterios = $criterios->fetchAll();

$total = array_sum(array_column($criterios, 'puntaje_max'));
echo json_encode(['criterios' => $criterios, 'total' => $total], JSON_UNESCAPED_UNICODE);
