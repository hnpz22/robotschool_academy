<?php
// public/curso_detalle.php &mdash; endpoint AJAX
// Devuelve JSON con info del curso + sus sedes/grupos (sede viene de grupos)
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$nombre = trim($_GET['nombre'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

if (!$nombre && !$id) {
    echo json_encode(['error' => 'Parametro requerido']); exit;
}

// Buscar el curso (ya sin sede_id)
if ($nombre) {
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE nombre = ? AND publicado = 1 LIMIT 1");
    $stmt->execute([$nombre]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND publicado = 1");
    $stmt->execute([$id]);
}
$curso = $stmt->fetch();
if (!$curso) { echo json_encode(['error' => 'Curso no encontrado']); exit; }

// M&oacute;dulos
$mods = $pdo->prepare("SELECT * FROM curso_modulos WHERE curso_id = ? ORDER BY orden");
$mods->execute([$curso['id']]);
$curso['modulos'] = $mods->fetchAll();

// Materiales
$mats = $pdo->prepare("SELECT * FROM curso_materiales WHERE curso_id = ?");
$mats->execute([$curso['id']]);
$curso['materiales'] = $mats->fetchAll();

// Galer&iacute;a
$gal = $pdo->prepare("SELECT * FROM curso_galeria WHERE curso_id = ? ORDER BY orden");
$gal->execute([$curso['id']]);
$curso['galeria'] = array_map(function($g) {
    $g['url'] = BASE_URL . 'uploads/cursos/galeria/' . $g['imagen'];
    return $g;
}, $gal->fetchAll());

$curso['imagen_url'] = $curso['imagen']
    ? BASE_URL . 'uploads/cursos/' . $curso['imagen']
    : null;

// Sedes donde existe este curso: las sedes que tienen grupos de este curso
// (busca tambi&eacute;n por nombre si hay cursos con el mismo nombre &mdash; fase de transici&oacute;n)
$stSedes = $pdo->prepare("
    SELECT DISTINCT s.id AS sede_id, s.nombre AS sede_nombre, s.ciudad AS sede_ciudad,
           c.id AS curso_id
    FROM grupos g
    JOIN sedes s ON s.id = g.sede_id
    JOIN cursos c ON c.id = g.curso_id
    WHERE (g.curso_id = ? OR c.nombre = ?) AND g.activo = 1 AND c.publicado = 1
    ORDER BY s.nombre
");
$stSedes->execute([$curso['id'], $curso['nombre']]);
$sedes_raw = $stSedes->fetchAll();

// Para cada sede, cargar sus grupos con cupos
$sedes = [];
foreach ($sedes_raw as $s) {
    $stG = $pdo->prepare("
        SELECT g.id, g.nombre, g.dia_semana, g.hora_inicio, g.hora_fin, g.modalidad, g.cupo_real,
            (g.cupo_real - COALESCE(
                (SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id = g.id AND m.estado = 'activa')
            ,0)) AS disponibles
        FROM grupos g
        WHERE g.curso_id = ? AND g.sede_id = ? AND g.activo = 1
        ORDER BY g.dia_semana, g.hora_inicio
    ");
    $stG->execute([$s['curso_id'], $s['sede_id']]);
    $grupos = $stG->fetchAll();

    $sedes[] = [
        'curso_id'    => $s['curso_id'],
        'sede_id'     => $s['sede_id'],
        'sede_nombre' => $s['sede_nombre'],
        'sede_ciudad' => $s['sede_ciudad'],
        'grupos'      => $grupos,
        'cupos'       => max(0, (int)array_sum(array_column($grupos, 'disponibles'))),
    ];
}
$curso['sedes'] = $sedes;
$curso['valor_fmt'] = formatCOP($curso['valor']);

echo json_encode($curso, JSON_UNESCAPED_UNICODE);
