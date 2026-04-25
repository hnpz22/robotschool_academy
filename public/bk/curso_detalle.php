<?php
// public/curso_detalle.php &mdash; endpoint AJAX
// Devuelve JSON con toda la info del curso para el modal
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }

// Datos del curso
$stmt = $pdo->prepare("
    SELECT c.*, s.nombre AS sede_nombre, s.ciudad AS sede_ciudad
    FROM cursos c JOIN sedes s ON s.id = c.sede_id
    WHERE c.id = ? AND c.publicado = 1
");
$stmt->execute([$id]);
$curso = $stmt->fetch();
if (!$curso) { echo json_encode(['error' => 'Curso no encontrado']); exit; }

// M&oacute;dulos
$mods = $pdo->prepare("SELECT * FROM curso_modulos WHERE curso_id = ? ORDER BY orden");
$mods->execute([$id]);
$curso['modulos'] = $mods->fetchAll();

// Materiales
$mats = $pdo->prepare("SELECT * FROM curso_materiales WHERE curso_id = ?");
$mats->execute([$id]);
$curso['materiales'] = $mats->fetchAll();

// Galer&iacute;a
$gal = $pdo->prepare("SELECT * FROM curso_galeria WHERE curso_id = ? ORDER BY orden");
$gal->execute([$id]);
$galeria = $gal->fetchAll();
$curso['galeria'] = array_map(function($g) {
    $g['url'] = BASE_URL . 'uploads/cursos/galeria/' . $g['imagen'];
    return $g;
}, $galeria);

// Imagen principal URL
$curso['imagen_url'] = $curso['imagen']
    ? BASE_URL . 'uploads/cursos/' . $curso['imagen']
    : null;

// Grupos/horarios con cupos
$grupos = $pdo->prepare("
    SELECT g.*,
        (g.cupo_real - COALESCE(
            (SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id = g.id AND m.estado = 'activa'), 0
        )) AS disponibles
    FROM grupos g
    WHERE g.curso_id = ? AND g.activo = 1
    ORDER BY FIELD(g.dia_semana,'lunes','martes','miercoles','jueves','viernes','sabado','domingo'), g.hora_inicio
");
$grupos->execute([$id]);
$curso['grupos'] = $grupos->fetchAll();

// Formatear valor
$curso['valor_fmt'] = formatCOP($curso['valor']);

echo json_encode($curso, JSON_UNESCAPED_UNICODE);
