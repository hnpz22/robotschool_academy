<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$id      = (int)($_GET['id']    ?? 0);
$curso   = (int)($_GET['curso'] ?? 0);

if ($id && $curso) {
    $stmt = $pdo->prepare("SELECT * FROM curso_galeria WHERE id=? AND curso_id=?");
    $stmt->execute([$id, $curso]);
    $foto = $stmt->fetch();
    if ($foto) {
        $archivo = ROOT . '/uploads/cursos/galeria/' . $foto['imagen'];
        if (file_exists($archivo)) unlink($archivo);
        $pdo->prepare("DELETE FROM curso_galeria WHERE id=?")->execute([$id]);
    }
}
header('Location: ' . BASE_URL . 'modulos/cursos/form.php?id=' . $curso . '&msg=editado');
exit;
