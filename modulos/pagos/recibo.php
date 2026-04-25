<?php
// modulos/pagos/recibo.php &mdash; Recibo imprimible tipo POS 80mm
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$pago_id = (int)($_GET['id'] ?? 0);
if (!$pago_id) { header('Location: '.BASE_URL.'modulos/pagos/index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT p.*,
        e.nombre_completo AS estudiante, e.numero_doc, e.tipo_doc,
        pa.nombre_completo AS padre, pa.telefono, pa.email,
        c.nombre AS curso, g.nombre AS grupo,
        g.dia_semana, g.hora_inicio, g.hora_fin,
        m.periodo, s.nombre AS sede_nombre, s.direccion AS sede_dir, s.telefono AS sede_tel
    FROM pagos p
    JOIN matriculas m  ON m.id  = p.matricula_id
    JOIN estudiantes e ON e.id  = m.estudiante_id
    JOIN padres pa     ON pa.id = e.padre_id
    JOIN grupos g      ON g.id  = m.grupo_id
    JOIN cursos c      ON c.id  = g.curso_id
    JOIN sedes s       ON s.id  = m.sede_id
    WHERE p.id = ?
");
$stmt->execute([$pago_id]);
$p = $stmt->fetch();
if (!$p) { header('Location: '.BASE_URL.'modulos/pagos/index.php'); exit; }

// Abonos del pago
$abonos = $pdo->prepare("
    SELECT pa.*, u.nombre AS registrado_por
    FROM pagos_abonos pa
    LEFT JOIN usuarios u ON u.id = pa.registrado_por
    WHERE pa.pago_id = ?
    ORDER BY pa.fecha ASC
");
$abonos->execute([$pago_id]);
$abonos = $abonos->fetchAll();

$dias = ['lunes'=>'Lun','martes'=>'Mar','miercoles'=>'Mi&eacute;',
         'jueves'=>'Jue','viernes'=>'Vie','sabado'=>'S&aacute;b','domingo'=>'Dom'];

$estados_label = ['pagado'=>'PAGADO','parcial'=>'PAGO PARCIAL','pendiente'=>'PENDIENTE','vencido'=>'VENCIDO','exonerado'=>'EXONERADO'];
$medios_label  = ['efectivo'=>'Efectivo','transferencia'=>'Transferencia','nequi'=>'Nequi',
                  'daviplata'=>'Daviplata','pse'=>'PSE','otro'=>'Otro'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo #<?= str_pad($p['id'],6,'0',STR_PAD_LEFT) ?></title>
<style>
  @page { size: 80mm auto; margin: 4mm; }

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 11px;
    color: #111;
    width: 72mm;
    margin: 0 auto;
    background: #fff;
  }

  .center { text-align: center; }
  .right  { text-align: right; }
  .bold   { font-weight: bold; }
  .line   { border-top: 1px dashed #999; margin: 4px 0; }
  .dline  { border-top: 2px solid #111; margin: 4px 0; }

  .logo-text {
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 1px;
  }
  .subtitle {
    font-size: 9px;
    color: #555;
    margin-bottom: 2px;
  }
  .recibo-num {
    font-size: 13px;
    font-weight: bold;
    margin: 4px 0 2px;
  }
  .estado-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 8px;
    border: 1.5px solid #111;
    border-radius: 3px;
    margin: 3px 0;
  }
  .row {
    display: flex;
    justify-content: space-between;
    margin: 2px 0;
  }
  .label { color: #555; font-size: 10px; }
  .val   { font-size: 10px; font-weight: bold; text-align: right; }
  .total-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    font-weight: bold;
    margin: 4px 0;
  }
  .saldo-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    font-weight: bold;
    margin: 2px 0;
    color: <?= $p['estado']==='pagado' ? '#16a34a' : '#dc2626' ?>;
  }
  .abono-row {
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    padding: 1px 0;
    color: #333;
  }
  .footer {
    font-size: 9px;
    color: #555;
    text-align: center;
    margin-top: 6px;
    line-height: 1.5;
  }
  .qr-placeholder {
    width: 40px; height: 40px;
    border: 1px solid #ccc;
    margin: 4px auto;
    display: flex; align-items: center; justify-content: center;
    font-size: 7px; color: #aaa; text-align: center;
  }

  @media screen {
    body { border: 1px dashed #ccc; padding: 8px; margin: 20px auto; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
    .no-screen { display: none; }
  }
  @media print {
    .no-print { display: none; }
  }
</style>
</head>
<body>

<!-- Botones solo en pantalla -->
<div class="no-print" style="text-align:center;margin-bottom:10px;font-family:sans-serif;">
  <button onclick="window.print()"
          style="padding:.5rem 1.4rem;background:#1DA99A;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;margin-right:.5rem;">
    &#128438; Imprimir recibo
  </button>
  <button onclick="window.close()"
          style="padding:.5rem 1rem;border:1.5px solid #ccc;background:#fff;border-radius:8px;font-size:.85rem;cursor:pointer;">
    Cerrar
  </button>
</div>

<!-- RECIBO -->
<div class="center">
  <div class="logo-text">ROBOTSchool</div>
  <div class="subtitle">Academy Learning &middot; Escuelas STEAM Colombia SAS</div>
  <div class="subtitle"><?= h($p['sede_nombre']) ?></div>
  <?php if ($p['sede_dir']): ?><div class="subtitle"><?= h($p['sede_dir']) ?></div><?php endif; ?>
  <?php if ($p['sede_tel']): ?><div class="subtitle">Tel: <?= h($p['sede_tel']) ?></div><?php endif; ?>
</div>

<div class="dline"></div>

<div class="center">
  <div class="recibo-num">RECIBO N&deg; <?= str_pad($p['id'],6,'0',STR_PAD_LEFT) ?></div>
  <div class="subtitle"><?= date('d/m/Y H:i') ?></div>
  <div class="estado-badge"><?= $estados_label[$p['estado']] ?? strtoupper($p['estado']) ?></div>
</div>

<div class="dline"></div>

<!-- Datos del estudiante -->
<div class="bold" style="font-size:10px;margin-bottom:2px;">ESTUDIANTE</div>
<div style="font-size:11px;font-weight:bold;"><?= h($p['estudiante']) ?></div>
<?php if ($p['numero_doc']): ?>
<div style="font-size:9px;color:#555;"><?= h($p['tipo_doc'] ?? 'Doc') ?>: <?= h($p['numero_doc']) ?></div>
<?php endif; ?>

<div class="line"></div>

<div class="bold" style="font-size:10px;margin-bottom:2px;">ACUDIENTE</div>
<div style="font-size:10px;"><?= h($p['padre']) ?></div>
<?php if ($p['telefono']): ?><div style="font-size:9px;color:#555;">Tel: <?= h($p['telefono']) ?></div><?php endif; ?>
<?php if ($p['email']): ?><div style="font-size:9px;color:#555;"><?= h($p['email']) ?></div><?php endif; ?>

<div class="line"></div>

<!-- Curso -->
<div class="bold" style="font-size:10px;margin-bottom:2px;">CURSO</div>
<div style="font-size:11px;font-weight:bold;"><?= h($p['curso']) ?></div>
<div style="font-size:9px;color:#555;"><?= h($p['grupo']) ?> &middot; <?= $dias[$p['dia_semana']] ?? '' ?> <?= substr($p['hora_inicio'],0,5) ?>&ndash;<?= substr($p['hora_fin'],0,5) ?></div>
<div style="font-size:9px;color:#555;">Per&iacute;odo: <?= h($p['periodo']) ?></div>

<div class="line"></div>

<!-- Concepto -->
<div class="bold" style="font-size:10px;margin-bottom:2px;">CONCEPTO</div>
<div style="font-size:10px;"><?= h($p['observaciones'] ?: 'Pago matr&iacute;cula') ?></div>
<?php if ($p['fecha_limite']): ?>
<div style="font-size:9px;color:#555;">Vence: <?= formatFecha($p['fecha_limite']) ?></div>
<?php endif; ?>

<div class="line"></div>

<!-- Valores -->
<div class="row">
  <span class="label">Valor total:</span>
  <span class="val"><?= formatCOP($p['valor_total']) ?></span>
</div>

<?php if (!empty($abonos)): ?>
<div style="font-size:9px;color:#555;margin-top:3px;margin-bottom:1px;">Abonos registrados:</div>
<?php foreach ($abonos as $ab): ?>
<div class="abono-row">
  <span><?= formatFecha($ab['fecha']) ?> &mdash; <?= h($medios_label[$ab['medio_pago']] ?? $ab['medio_pago']) ?></span>
  <span><?= formatCOP($ab['valor']) ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="dline"></div>

<div class="total-row">
  <span>PAGADO:</span>
  <span><?= formatCOP($p['valor_pagado']) ?></span>
</div>
<div class="saldo-row">
  <span>SALDO<?= $p['valor_pagado'] >= $p['valor_total'] ? ' &#10003;' : ':' ?></span>
  <span><?= formatCOP($p['valor_total'] - $p['valor_pagado']) ?></span>
</div>

<div class="dline"></div>

<?php if ($p['estado'] === 'pagado'): ?>
<div class="center bold" style="font-size:13px;margin:4px 0;">*** PAGO COMPLETO ***</div>
<?php elseif ($p['estado'] === 'vencido'): ?>
<div class="center bold" style="font-size:11px;margin:4px 0;color:#dc2626;">* PAGO VENCIDO *</div>
<?php endif; ?>

<!-- Firma -->
<div style="margin-top:10px;display:flex;justify-content:space-between;font-size:9px;color:#555;">
  <div style="text-align:center;width:48%;">
    <div style="border-top:1px solid #999;padding-top:3px;margin-top:14px;">Firma responsable</div>
  </div>
  <div style="text-align:center;width:48%;">
    <div style="border-top:1px solid #999;padding-top:3px;margin-top:14px;">Sello / Recibido</div>
  </div>
</div>

<div class="line"></div>

<div class="footer">
  Este recibo es v&aacute;lido como comprobante de pago.<br>
  Conserve este documento para cualquier reclamaci&oacute;n.<br>
  robotschool.com.co &middot; info@robotschool.com.co<br>
  Generado: <?= date('d/m/Y H:i:s') ?>
</div>

</body>
</html>
