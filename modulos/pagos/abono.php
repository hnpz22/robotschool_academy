<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pago_id      = (int)($_POST['pago_id']      ?? 0);
    $valor        = (float)($_POST['valor']       ?? 0);
    $medio        = $_POST['medio_pago']          ?? 'efectivo';
    $comprobante  = trim($_POST['comprobante']    ?? '');
    $observaciones= trim($_POST['observaciones']  ?? '');

    if ($pago_id && $valor > 0) {
        // Registrar abono
        $pdo->prepare("INSERT INTO pagos_abonos (pago_id,valor,medio_pago,comprobante,observaciones,registrado_por) VALUES (?,?,?,?,?,?)")
            ->execute([$pago_id,$valor,$medio,$comprobante,$observaciones,$_SESSION['usuario_id']]);

        // Actualizar valor_pagado y recalcular estado
        $pago = $pdo->prepare("SELECT * FROM pagos WHERE id=?");
        $pago->execute([$pago_id]); $pago = $pago->fetch();

        $nuevo_pagado = $pago['valor_pagado'] + $valor;
        $nuevo_pagado = min($nuevo_pagado, $pago['valor_total']); // no superar el total

        // Determinar nuevo estado sem&aacute;foro
        if ($nuevo_pagado >= $pago['valor_total']) {
            $nuevo_estado = 'pagado';
        } elseif ($nuevo_pagado > 0) {
            $nuevo_estado = 'parcial';
        } else {
            $nuevo_estado = 'pendiente';
        }

        $pdo->prepare("UPDATE pagos SET valor_pagado=?, estado=? WHERE id=?")
            ->execute([$nuevo_pagado, $nuevo_estado, $pago_id]);
    }
}
header('Location: ' . BASE_URL . 'modulos/pagos/index.php?msg=abono');
exit;
