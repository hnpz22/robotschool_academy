<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_general');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && $id != $_SESSION['usuario_id'])
        $pdo->prepare("DELETE FROM usuarios WHERE id=? AND rol IN ('admin_general','admin_sede')")->execute([$id]);
}
header('Location: ' . BASE_URL . 'modulos/usuarios/index.php?msg=eliminado');
exit;
