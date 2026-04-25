<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
        $stmt->execute([$id]);
        $curso = $stmt->fetch();
        if ($curso) {
            if ($curso['imagen'] && file_exists(ROOT . '/uploads/cursos/' . $curso['imagen'])) {
                unlink(ROOT . '/uploads/cursos/' . $curso['imagen']);
            }
            $pdo->prepare("DELETE FROM cursos WHERE id = ?")->execute([$id]);
        }
    }
}
header('Location: ' . BASE_URL . 'modulos/academico/cursos/index.php?msg=eliminado');
exit;
