<?php
// modulos/extracurriculares/contratos/index.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Contratos &mdash; Extracurriculares';
$menu_activo = 'ec_contratos';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

$q_buscar  = trim($_GET['buscar'] ?? '');
$q_cliente = (int)($_GET['cliente'] ?? 0);
$q_estado  = $_GET['estado'] ?? '';
$q_vigente = $_GET['vigente'] ?? ''; // si / no / ''

$where  = ['1=1'];
$params = [];

if ($q_cliente) { $where[] = 'ct.cliente_id = ?'; $params[] = $q_cliente; }
if ($q_estado)  { $where[] = 'ct.estado = ?';    $params[] = $q_estado; }
if ($q_vigente === 'si') {
    $where[] = "ct.estado = 'vigente' AND CURDATE() BETWEEN ct.fecha_inicio AND ct.fecha_fin";
}
if ($q_buscar) {
    $where[] = '(ct.nombre LIKE ? OR ct.codigo LIKE ? OR cl.nombre LIKE ?)';
    $params[] = "%$q_buscar%"; $params[] = "%$q_buscar%"; $params[] = "%$q_buscar%";
}

$sql = "SELECT ct.*,
        cl.nombre AS cliente_nombre, cl.tipo AS cliente_tipo, cl.ciudad AS cliente_ciudad,
        (SELECT COUNT(*) FROM ec_programas p WHERE p.contrato_id = ct.id) AS total_programas,
        (SELECT COALESCE(SUM(p.cantidad_ninos), 0) FROM ec_programas p WHERE p.contrato_id = ct.id) AS total_ninos
        FROM ec_contratos ct
        JOIN ec_clientes cl ON cl.id = ct.cliente_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY
          CASE ct.estado
            WHEN 'vigente' THEN 1 WHEN 'borrador' THEN 2 WHEN 'suspendido' THEN 3
            WHEN 'finalizado' THEN 4 WHEN 'cancelado' THEN 5 END,
          ct.fecha_inicio DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contratos = $stmt->fetchAll();
$total = count($contratos);

$clientes = $pdo->query("SELECT id, nombre FROM ec_clientes WHERE activo = 1 ORDER BY nombre")->fetchAll();

$estado_labels = [
    'borrador'   => ['Borrador',   '#6B7280', '#F3F4F6'],
    'vigente'    => ['Vigente',    '#0d6e5f', '#d1fae5'],
    'suspendido' => ['Suspendido', '#b85f00', '#fff2d6'],
    'finalizado' => ['Finalizado', '#1f2937', '#E5E7EB'],
    'cancelado'  => ['Cancelado',  '#991b1b', '#fde3e4'],
];

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Contratos <small>Acuerdos con clientes de extracurriculares</small></div>
  <a href="<?= $U ?>modulos/extracurriculares/contratos/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo contrato
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creado'):   ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Contrato creado correctamente.</div>
  <?php elseif ($msg === 'editado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Contrato actualizado.</div>
  <?php elseif ($msg === 'eliminado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Contrato eliminado.</div>
  <?php elseif ($msg === 'no_eliminable'): ?><div class="alert-rsal alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> No se puede eliminar: tiene programas asociados.</div>
  <?php endif; ?>

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Un contrato define el acuerdo comercial con un cliente: per&iacute;odo, valor y condiciones. Cada contrato puede tener uno o varios programas acad&eacute;micos en paralelo.
  </div>

  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="GET" style="display:grid;grid-template-columns:1fr 200px 150px 130px auto;gap:.8rem;align-items:end;">
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Buscar</label>
        <input type="text" name="buscar" value="<?= h($q_buscar) ?>"
               placeholder="Nombre c&oacute;digo cliente..."
               style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;"/>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Cliente</label>
        <select name="cliente" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="0">Todos</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $q_cliente == $c['id'] ? 'selected' : '' ?>><?= h($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Estado</label>
        <select name="estado" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="">Todos</option>
          <?php foreach ($estado_labels as $k => $v): ?>
            <option value="<?= $k ?>" <?= $q_estado === $k ? 'selected' : '' ?>><?= $v[0] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Vigencia</label>
        <select name="vigente" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="">Todos</option>
          <option value="si" <?= $q_vigente === 'si' ? 'selected' : '' ?>>En curso hoy</option>
        </select>
      </div>
      <button type="submit" class="btn-rsal-primary" style="padding:.6rem 1rem;">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
    </form>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
    <div style="font-size:.85rem;color:var(--muted);">
      <strong style="color:var(--dark);"><?= $total ?></strong> contrato<?= $total != 1 ? 's' : '' ?> encontrado<?= $total != 1 ? 's' : '' ?>
    </div>
  </div>

  <?php if (empty($contratos)): ?>
    <div class="empty-state">
      <i class="bi bi-file-earmark-text"></i>
      <h3>No hay contratos registrados</h3>
      <p>Crea un contrato asociado a un cliente para empezar a definir programas.</p>
      <a href="<?= $U ?>modulos/extracurriculares/contratos/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Crear primer contrato
      </a>
    </div>
  <?php else: ?>
    <div class="card-rsal" style="padding:0;overflow:hidden;">
      <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead>
          <tr style="background:var(--gray);">
            <th style="padding:.75rem;text-align:left;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">C&oacute;digo</th>
            <th style="padding:.75rem;text-align:left;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Contrato</th>
            <th style="padding:.75rem;text-align:left;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Cliente</th>
            <th style="padding:.75rem;text-align:left;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Per&iacute;odo</th>
            <th style="padding:.75rem;text-align:center;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Programas</th>
            <th style="padding:.75rem;text-align:right;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Valor total</th>
            <th style="padding:.75rem;text-align:center;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Estado</th>
            <th style="padding:.75rem;text-align:center;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Acci&oacute;n</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($contratos as $ct):
            $e = $estado_labels[$ct['estado']] ?? $estado_labels['borrador'];
          ?>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:.7rem .75rem;font-family:monospace;color:var(--muted);font-size:.78rem;"><?= h($ct['codigo'] ?: '&mdash;') ?></td>
            <td style="padding:.7rem .75rem;font-weight:700;color:var(--dark);"><?= h($ct['nombre']) ?></td>
            <td style="padding:.7rem .75rem;">
              <div style="font-weight:600;"><?= h($ct['cliente_nombre']) ?></div>
              <div style="font-size:.7rem;color:var(--muted);text-transform:capitalize;"><?= h($ct['cliente_tipo']) ?> &middot; <?= h($ct['cliente_ciudad']) ?></div>
            </td>
            <td style="padding:.7rem .75rem;font-size:.78rem;color:var(--muted);">
              <?= date('d/m/Y', strtotime($ct['fecha_inicio'])) ?><br>
              <?= date('d/m/Y', strtotime($ct['fecha_fin'])) ?>
            </td>
            <td style="padding:.7rem .75rem;text-align:center;font-weight:700;color:#7c3aed;">
              <?= (int)$ct['total_programas'] ?>
              <div style="font-size:.65rem;color:var(--muted);font-weight:500;"><?= (int)$ct['total_ninos'] ?> ni&ntilde;os</div>
            </td>
            <td style="padding:.7rem .75rem;text-align:right;font-weight:700;">
              $<?= number_format($ct['valor_total'], 0, ',', '.') ?>
            </td>
            <td style="padding:.7rem .75rem;text-align:center;">
              <span style="background:<?= $e[2] ?>;color:<?= $e[1] ?>;font-size:.65rem;font-weight:700;padding:3px 9px;border-radius:10px;white-space:nowrap;">
                <?= $e[0] ?>
              </span>
            </td>
            <td style="padding:.7rem .75rem;text-align:center;white-space:nowrap;">
              <a href="<?= $U ?>modulos/extracurriculares/contratos/ver.php?id=<?= $ct['id'] ?>"
                 class="btn-rsal-primary" style="padding:.35rem .7rem;font-size:.72rem;">
                <i class="bi bi-eye-fill"></i> Ver
              </a>
              <a href="<?= $U ?>modulos/extracurriculares/contratos/form.php?id=<?= $ct['id'] ?>"
                 class="btn-rsal-secondary" style="padding:.35rem .55rem;font-size:.72rem;">
                <i class="bi bi-pencil-fill"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  <?php endif; ?>

</main>
<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
