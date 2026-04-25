<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        // Solo eliminar si no tiene evaluaciones
        $chk = $pdo->prepare("SELECT COUNT(*) FROM evaluaciones WHERE rubrica_id=?");
        $chk->execute([$id]);
        if ($chk->fetchColumn() == 0)
            $pdo->prepare("DELETE FROM rubricas WHERE id=?")->execute([$id]);
    }
}
header('Location: ' . BASE_URL . 'modulos/academico/rubricas/index.php?msg=eliminada');
exit;
