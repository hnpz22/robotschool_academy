<?php
// modulos/reportes/cartera.php &mdash; Reporte de cartera por estado y fechas
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$U           = BASE_URL;
$menu_activo = 'reportes';
$sede_filtro = getSedeFiltro();

// Filtros
$filtro_estado = $_GET['estado']     ?? '';
$fecha_desde   = $_GET['fecha_desde']?? '';
$fecha_hasta   = $_GET['fecha_hasta']?? '';
$filtro_curso  = (int)($_GET['curso_id'] ?? 0);
$imprimir      = isset($_GET['imprimir']);

// Actualizar vencidos
$pdo->query("UPDATE pagos p JOIN matriculas m ON m.id=p.matricula_id
    SET p.estado='vencido'
    WHERE p.estado IN ('pendiente','parcial') AND p.fecha_limite < CURDATE() AND p.valor_pagado < p.valor_total"
    . ($sede_filtro ? ' AND m.sede_id='.(int)$sede_filtro : ''));

$where  = ['1=1'];
$params = [];
if ($sede_filtro)  { $where[] = 'm.sede_id = ?';            $params[] = $sede_filtro; }
if ($filtro_estado){ $where[] = 'p.estado = ?';             $params[] = $filtro_estado; }
if ($filtro_curso) { $where[] = 'c.id = ?';                 $params[] = $filtro_curso; }
if ($fecha_desde)  { $where[] = 'DATE(p.created_at) >= ?';  $params[] = $fecha_desde; }
if ($fecha_hasta)  { $where[] = 'DATE(p.created_at) <= ?';  $params[] = $fecha_hasta; }

$pagos = $pdo->prepare("
    SELECT p.*,
        e.nombre_completo AS estudiante,
        pa.nombre_completo AS padre, pa.telefono,
        c.nombre AS curso, g.nombre AS grupo,
        s.nombre AS sede_nombre,
        (p.valor_total - p.valor_pagado) AS saldo
    FROM pagos p
    JOIN matriculas m  ON m.id  = p.matricula_id
    JOIN estudiantes e ON e.id  = m.estudiante_id
    JOIN padres pa     ON pa.id = e.padre_id
    JOIN grupos g      ON g.id  = m.grupo_id
    JOIN cursos c      ON c.id  = g.curso_id
    JOIN sedes s       ON s.id  = m.sede_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY FIELD(p.estado,'vencido','pendiente','parcial','pagado','exonerado'), p.fecha_limite ASC
");
$pagos->execute($params);
$pagos = $pagos->fetchAll();

// Totales por estado
$totales = [
    'vencido'    => ['n'=>0,'total'=>0,'pagado'=>0,'saldo'=>0],
    'pendiente'  => ['n'=>0,'total'=>0,'pagado'=>0,'saldo'=>0],
    'parcial'    => ['n'=>0,'total'=>0,'pagado'=>0,'saldo'=>0],
    'pagado'     => ['n'=>0,'total'=>0,'pagado'=>0,'saldo'=>0],
    'exonerado'  => ['n'=>0,'total'=>0,'pagado'=>0,'saldo'=>0],
];
$gran_total = $gran_pagado = $gran_saldo = 0;
foreach ($pagos as $p) {
    $est = $p['estado'];
    if (isset($totales[$est])) {
        $totales[$est]['n']++;
        $totales[$est]['total']  += $p['valor_total'];
        $totales[$est]['pagado'] += $p['valor_pagado'];
        $totales[$est]['saldo']  += $p['saldo'];
    }
    $gran_total  += $p['valor_total'];
    $gran_pagado += $p['valor_pagado'];
    $gran_saldo  += $p['saldo'];
}

// Cursos para filtro
$where_c = $sede_filtro ? 'WHERE sede_id='.(int)$sede_filtro : '';
$cursos  = $pdo->query("SELECT id, nombre FROM cursos $where_c ORDER BY nombre")->fetchAll();

$colores = [
    'pagado'   =>['bg'=>'#f0fdf4','border'=>'#86efac','text'=>'#16a34a','badge'=>'be-activa'],
    'parcial'  =>['bg'=>'#fffbeb','border'=>'#fcd34d','text'=>'#ca8a04','badge'=>'be-pendiente'],
    'pendiente'=>['bg'=>'#f0f9ff','border'=>'#7dd3fc','text'=>'#0369a1','badge'=>'be-pre'],
    'vencido'  =>['bg'=>'#fff0f1','border'=>'#fca5a5','text'=>'#dc2626','badge'=>'be-inactiva'],
    'exonerado'=>['bg'=>'#f5f3ff','border'=>'#c4b5fd','text'=>'#7c3aed','badge'=>'be-suspendida'],
];
$labels = ['pagado'=>'Pagado','parcial'=>'Parcial','pendiente'=>'Pendiente','vencido'=>'Vencido','exonerado'=>'Exonerado'];

$titulo = 'Reporte de Cartera';
if (!$imprimir) require_once ROOT . '/includes/head.php';
?>
<?php if ($imprimir): ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Cartera &mdash; ROBOTSchool Academy</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
  h1 { font-size:14px; margin-bottom:2px; }
  h2 { font-size:12px; margin-bottom:8px; color:#555; }
  table { width:100%; border-collapse:collapse; margin-top:8px; }
  th { background:#1DA99A; color:#fff; padding:4px 6px; text-align:left; font-size:10px; }
  td { padding:4px 6px; border-bottom:1px solid #eee; font-size:10px; }
  .resumen td { font-weight:bold; background:#f5f5f5; }
  .total-row td { font-weight:bold; background:#1DA99A; color:#fff; }
  .est-v { color:#dc2626; } .est-p { color:#ca8a04; } .est-ok { color:#16a34a; }
  @media print { body { margin:10mm; } }
</style>
</head><body>
<h1>&#128202; Reporte de Cartera &mdash; ROBOTSchool Academy</h1>
<h2>Generado: <?= date('d/m/Y H:i') ?><?= $fecha_desde ? " &middot; Desde: $fecha_desde" : '' ?><?= $fecha_hasta ? " &middot; Hasta: $fecha_hasta" : '' ?><?= $filtro_estado ? " &middot; Estado: ".ucfirst($filtro_estado) : '' ?></h2>

<table>
  <thead><tr><th>#</th><th>Estudiante</th><th>Padre</th><th>Curso</th><th>Concepto</th><th>Valor Total</th><th>Pagado</th><th>Saldo</th><th>Vence</th><th>Estado</th></tr></thead>
  <tbody>
  <?php foreach ($pagos as $i => $p):
    $cls = $p['estado']==='vencido'?'est-v':($p['estado']==='pagado'?'est-ok':'est-p');
  ?>
  <tr>
    <td><?= $i+1 ?></td>
    <td><?= h($p['estudiante']) ?></td>
    <td><?= h($p['padre']) ?></td>
    <td style="font-size:9px;"><?= h($p['curso']) ?></td>
    <td style="font-size:9px;"><?= h($p['observaciones'] ?: 'Pago matr&iacute;cula') ?></td>
    <td><?= formatCOP($p['valor_total']) ?></td>
    <td><?= formatCOP($p['valor_pagado']) ?></td>
    <td class="<?= $cls ?>"><?= formatCOP($p['saldo']) ?></td>
    <td><?= $p['fecha_limite'] ? formatFecha($p['fecha_limite']) : '&mdash;' ?></td>
    <td class="<?= $cls ?>"><?= ucfirst($p['estado']) ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="total-row">
    <td colspan="5">TOTAL GENERAL (<?= count($pagos) ?> pagos)</td>
    <td><?= formatCOP($gran_total) ?></td>
    <td><?= formatCOP($gran_pagado) ?></td>
    <td><?= formatCOP($gran_saldo) ?></td>
    <td colspan="2"></td>
  </tr>
  </tbody>
</table>

<table style="margin-top:16px; width:auto;">
  <thead><tr><th>Estado</th><th>Cant.</th><th>Valor Total</th><th>Recaudado</th><th>Por cobrar</th></tr></thead>
  <tbody>
  <?php foreach ($totales as $est => $t): if (!$t['n']) continue; ?>
  <tr class="resumen">
    <td><?= ucfirst($est) ?></td>
    <td><?= $t['n'] ?></td>
    <td><?= formatCOP($t['total']) ?></td>
    <td><?= formatCOP($t['pagado']) ?></td>
    <td><?= formatCOP($t['saldo']) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>window.onload=()=>window.print();</script>
</body></html>
<?php exit; endif; ?>

<body>
<?php require_once ROOT . '/includes/sidebar.php'; ?>
<main class="main-content">
<header class="main-header">
  <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="bi bi-list"></i></button>
  <div class="header-title">
    <h1><i class="bi bi-bar-chart-fill" style="color:var(--teal);"></i> Reporte de Cartera</h1>
    <div class="breadcrumb-rsal"><a href="<?= $U ?>modulos/reportes/index.php">Reportes</a> <i class="bi bi-chevron-right"></i> Cartera</div>
  </div>
  <div style="display:flex;gap:.6rem;margin-left:auto;">
    <a href="?<?= http_build_query(array_merge($_GET,['imprimir'=>'1'])) ?>" target="_blank"
       style="padding:.5rem 1rem;background:var(--teal);color:#fff;border-radius:10px;font-size:.82rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
      <i class="bi bi-printer-fill"></i> Imprimir
    </a>
  </div>
</header>

<div class="content-wrapper">

  <!-- Filtros -->
  <form method="GET" style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:1.2rem;margin-bottom:1.2rem;display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label style="font-size:.78rem;font-weight:600;display:block;margin-bottom:.3rem;">Estado</label>
      <select name="estado" style="padding:.5rem .8rem;border:1.5px solid var(--border);border-radius:8px;font-family:'Nunito',sans-serif;font-size:.82rem;">
        <option value="">Todos los estados</option>
        <?php foreach ($labels as $val => $lbl): ?>
        <option value="<?= $val ?>" <?= $filtro_estado===$val?'selected':'' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="font-size:.78rem;font-weight:600;display:block;margin-bottom:.3rem;">Curso</label>
      <select name="curso_id" style="padding:.5rem .8rem;border:1.5px solid var(--border);border-radius:8px;font-family:'Nunito',sans-serif;font-size:.82rem;">
        <option value="">Todos los cursos</option>
        <?php foreach ($cursos as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $filtro_curso==$c['id']?'selected':'' ?>><?= h($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="font-size:.78rem;font-weight:600;display:block;margin-bottom:.3rem;">Desde</label>
      <input type="date" name="fecha_desde" value="<?= h($fecha_desde) ?>"
             style="padding:.5rem .8rem;border:1.5px solid var(--border);border-radius:8px;font-family:'Nunito',sans-serif;font-size:.82rem;">
    </div>
    <div>
      <label style="font-size:.78rem;font-weight:600;display:block;margin-bottom:.3rem;">Hasta</label>
      <input type="date" name="fecha_hasta" value="<?= h($fecha_hasta) ?>"
             style="padding:.5rem .8rem;border:1.5px solid var(--border);border-radius:8px;font-family:'Nunito',sans-serif;font-size:.82rem;">
    </div>
    <button type="submit" style="padding:.5rem 1.2rem;background:var(--teal);color:#fff;border:none;border-radius:8px;font-family:'Nunito',sans-serif;font-size:.82rem;font-weight:700;cursor:pointer;">
      <i class="bi bi-funnel-fill"></i> Filtrar
    </button>
    <a href="?" style="padding:.5rem .9rem;border:1.5px solid var(--border);border-radius:8px;font-size:.82rem;color:var(--dark);text-decoration:none;">Limpiar</a>
  </form>

  <!-- Resumen por estado -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.8rem;margin-bottom:1.2rem;">
    <?php foreach ($totales as $est => $t):
      $c = $colores[$est];
    ?>
    <div style="background:<?= $c['bg'] ?>;border:1.5px solid <?= $c['border'] ?>;border-radius:12px;padding:1rem;text-align:center;cursor:pointer;"
         onclick="window.location='?estado=<?= $est ?><?= $fecha_desde?"&fecha_desde=$fecha_desde":'' ?><?= $fecha_hasta?"&fecha_hasta=$fecha_hasta":'' ?>'">
      <div style="font-size:.7rem;font-weight:700;color:<?= $c['text'] ?>;text-transform:uppercase;margin-bottom:.4rem;"><?= $labels[$est] ?></div>
      <div style="font-size:1.5rem;font-weight:900;color:<?= $c['text'] ?>;font-family:'Poppins',sans-serif;"><?= $t['n'] ?></div>
      <div style="font-size:.72rem;color:<?= $c['text'] ?>;margin-top:.3rem;"><?= formatCOP($t['total']) ?></div>
      <?php if ($t['saldo'] > 0): ?>
      <div style="font-size:.68rem;color:<?= $c['text'] ?>;opacity:.7;">Saldo: <?= formatCOP($t['saldo']) ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Gran total -->
  <div style="background:linear-gradient(135deg,var(--teal),var(--teal-d));border-radius:14px;padding:1.2rem;margin-bottom:1.2rem;display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;color:#fff;text-align:center;">
    <div>
      <div style="font-size:.72rem;font-weight:700;opacity:.8;text-transform:uppercase;margin-bottom:.3rem;">Facturado total</div>
      <div style="font-size:1.6rem;font-weight:900;font-family:'Poppins',sans-serif;"><?= formatCOP($gran_total) ?></div>
    </div>
    <div>
      <div style="font-size:.72rem;font-weight:700;opacity:.8;text-transform:uppercase;margin-bottom:.3rem;">Recaudado</div>
      <div style="font-size:1.6rem;font-weight:900;font-family:'Poppins',sans-serif;"><?= formatCOP($gran_pagado) ?></div>
    </div>
    <div>
      <div style="font-size:.72rem;font-weight:700;opacity:.8;text-transform:uppercase;margin-bottom:.3rem;">Cartera pendiente</div>
      <div style="font-size:1.6rem;font-weight:900;font-family:'Poppins',sans-serif;"><?= formatCOP($gran_saldo) ?></div>
    </div>
  </div>

  <!-- Tabla detallada -->
  <?php if (empty($pagos)): ?>
  <div style="text-align:center;padding:3rem;color:var(--muted);">
    <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:.8rem;"></i>
    No hay pagos con los filtros seleccionados.
  </div>
  <?php else: ?>
  <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;">
    <table class="table-rsal">
      <thead>
        <tr>
          <th>Estudiante</th>
          <th>Padre / Tel</th>
          <th>Curso</th>
          <th>Concepto</th>
          <th style="text-align:right;">Total</th>
          <th style="text-align:right;">Pagado</th>
          <th style="text-align:right;">Saldo</th>
          <th>Vence</th>
          <th>Estado</th>
          <th>Recibo</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($pagos as $p):
        $c = $colores[$p['estado']];
      ?>
      <tr>
        <td style="font-size:.82rem;font-weight:600;"><?= h($p['estudiante']) ?></td>
        <td>
          <div style="font-size:.82rem;"><?= h($p['padre']) ?></div>
          <?php if ($p['telefono']): ?>
          <div style="font-size:.75rem;color:var(--muted);"><?= h($p['telefono']) ?></div>
          <?php endif; ?>
        </td>
        <td style="font-size:.78rem;"><?= h($p['curso']) ?></td>
        <td style="font-size:.78rem;color:var(--muted);max-width:160px;"><?= h($p['observaciones'] ?: 'Pago matr&iacute;cula') ?></td>
        <td style="text-align:right;font-size:.85rem;font-weight:700;"><?= formatCOP($p['valor_total']) ?></td>
        <td style="text-align:right;font-size:.85rem;color:#16a34a;font-weight:700;"><?= formatCOP($p['valor_pagado']) ?></td>
        <td style="text-align:right;font-size:.85rem;font-weight:700;color:<?= $c['text'] ?>;"><?= formatCOP($p['saldo']) ?></td>
        <td style="font-size:.78rem;"><?= $p['fecha_limite'] ? formatFecha($p['fecha_limite']) : '&mdash;' ?></td>
        <td><span class="badge-estado <?= $c['badge'] ?>"><?= $labels[$p['estado']] ?></span></td>
        <td>
          <a href="<?= $U ?>modulos/pagos/recibo.php?id=<?= $p['id'] ?>" target="_blank"
             style="padding:.3rem .6rem;background:var(--teal);color:#fff;border-radius:6px;font-size:.72rem;font-weight:700;text-decoration:none;white-space:nowrap;">
            <i class="bi bi-receipt"></i> Recibo
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:var(--gray);font-weight:700;">
          <td colspan="4" style="font-size:.85rem;">TOTAL (<?= count($pagos) ?> pagos)</td>
          <td style="text-align:right;font-size:.85rem;"><?= formatCOP($gran_total) ?></td>
          <td style="text-align:right;font-size:.85rem;color:#16a34a;"><?= formatCOP($gran_pagado) ?></td>
          <td style="text-align:right;font-size:.85rem;color:var(--orange);"><?= formatCOP($gran_saldo) ?></td>
          <td colspan="3"></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>
</main>
<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body></html>
