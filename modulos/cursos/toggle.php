<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$id          = (int)($_GET['id'] ?? 0);
$sede_filtro = getSedeFiltro();

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
    $stmt->execute([$id]);
    $curso = $stmt->fetch();
    if ($curso) {
        $pdo->prepare("UPDATE cursos SET publicado = NOT publicado WHERE id = ?")
            ->execute([$id]);
    }
}
header('Location: ' . BASE_URL . 'modulos/cursos/index.php?msg=editado');
exit;
