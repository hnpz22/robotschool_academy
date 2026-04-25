<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $sf = getSedeFiltro();
    if ($id) {
        $s = $pdo->prepare("SELECT * FROM equipos WHERE id=?");
        $s->execute([$id]); $eq = $s->fetch();
        if ($eq && (!$sf || $eq['sede_id'] == $sf))
            $pdo->prepare("DELETE FROM equipos WHERE id=?")->execute([$id]);
    }
}
header('Location: ' . BASE_URL . 'modulos/equipos/index.php?msg=eliminado');
exit;
