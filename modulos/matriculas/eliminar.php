<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $sf = getSedeFiltro();
    if ($id) {
        $s = $pdo->prepare("SELECT * FROM matriculas WHERE id=?");
        $s->execute([$id]); $m = $s->fetch();
        if ($m && (!$sf || $m['sede_id'] == $sf))
            $pdo->prepare("DELETE FROM matriculas WHERE id=?")->execute([$id]);
    }
}
header('Location: ' . BASE_URL . 'modulos/matriculas/index.php?msg=eliminada');
exit;
