<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Historial de abonos';
$menu_activo = 'pagos';
$U           = BASE_URL;

$pago_id = (int)($_GET['pago_id'] ?? 0);
if (!$pago_id) { header('Location: '.$U.'modulos/pagos/index.php'); exit; }

$pago = $pdo->prepare("
    SELECT p.*, e.nombre_completo AS estudiante, pa.nombre_completo AS padre,
        c.nombre AS curso
    FROM pagos p
    JOIN matriculas m ON m.id = p.matricula_id
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN padres pa ON pa.id = p.padre_id
    JOIN grupos g ON g.id = m.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    WHERE p.id = ?
");
$pago->execute([$pago_id]); $pago = $pago->fetch();
if (!$pago) { header('Location: '.$U.'modulos/pagos/index.php'); exit; }

$abonos = $pdo->prepare("
    SELECT ab.*, u.nombre AS registrado_por_nombre
    FROM pagos_abonos ab JOIN usuarios u ON u.id = ab.registrado_por
    WHERE ab.pago_id = ? ORDER BY ab.fecha DESC
");
$abonos->execute([$pago_id]); $abonos = $abonos->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    Historial de abonos
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/pagos/index.php">Pagos</a>
      <i class="bi bi-chevron-right"></i> <?= h($pago['estudiante']) ?>
    </span></small>
  </div>
</header>
<main class="main-content">
  <div style="max-width:680px;">
    <!-- Resumen del pago -->
    <div class="card-rsal" style="margin-bottom:1.2rem;">
      <div class="card-rsal-title"><i class="bi bi-cash-stack"></i> Resumen del pago</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;text-align:center;">
        <div style="background:var(--gray);border-radius:10px;padding:.8rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:900;color:var(--dark);"><?= formatCOP($pago['valor_total']) ?></div>
          <div style="font-size:.7rem;color:var(--muted);font-weight:600;">Total</div>
        </div>
        <div style="background:#dcfce7;border-radius:10px;padding:.8rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:900;color:#16a34a;"><?= formatCOP($pago['valor_pagado']) ?></div>
          <div style="font-size:.7rem;color:#166534;font-weight:600;">Pagado</div>
        </div>
        <div style="background:var(--red-l);border-radius:10px;padding:.8rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:900;color:var(--red);"><?= formatCOP($pago['valor_total']-$pago['valor_pagado']) ?></div>
          <div style="font-size:.7rem;color:var(--red-d);font-weight:600;">Saldo</div>
        </div>
      </div>
      <div style="margin-top:.8rem;font-size:.82rem;color:var(--muted);">
        <strong>Estudiante:</strong> <?= h($pago['estudiante']) ?> &nbsp;&middot;&nbsp;
        <strong>Padre:</strong> <?= h($pago['padre']) ?> &nbsp;&middot;&nbsp;
        <strong>Curso:</strong> <?= h($pago['curso']) ?>
      </div>
    </div>

    <!-- Abonos -->
    <div class="card-rsal" style="padding:0;overflow:hidden;">
      <?php if (empty($abonos)): ?>
        <div style="text-align:center;padding:2rem;color:var(--muted);">
          <i class="bi bi-inbox" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
          No hay abonos registrados a&uacute;n.
        </div>
      <?php else: ?>
        <table class="table-rsal">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Valor</th>
              <th>Medio</th>
              <th>Comprobante</th>
              <th>Registrado por</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($abonos as $ab): ?>
            <tr>
              <td style="font-size:.82rem;"><?= formatFecha($ab['fecha']) ?></td>
              <td style="font-size:.85rem;font-weight:700;color:#16a34a;"><?= formatCOP($ab['valor']) ?></td>
              <td style="font-size:.82rem;"><?= ucfirst(h($ab['medio_pago'])) ?></td>
              <td style="font-size:.78rem;color:var(--muted);"><?= h($ab['comprobante'] ?: '&mdash;') ?></td>
              <td style="font-size:.78rem;"><?= h($ab['registrado_por_nombre']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div style="margin-top:1rem;">
      <a href="<?= $U ?>modulos/pagos/index.php" class="btn-rsal-secondary">
        <i class="bi bi-arrow-left"></i> Volver a pagos
      </a>
    </div>
  </div>
</main>
<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
