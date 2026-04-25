<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $sede_filtro = getSedeFiltro();
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id=?");
        $stmt->execute([$id]); $grupo = $stmt->fetch();
        if ($grupo && (!$sede_filtro || $grupo['sede_id'] == $sede_filtro)) {
            $pdo->prepare("DELETE FROM grupos WHERE id=?")->execute([$id]);
        }
    }
}
header('Location: ' . BASE_URL . 'modulos/grupos/index.php?msg=eliminado');
exit;
