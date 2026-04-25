<?php
// modulos/extracurriculares/programas/index.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Programas &mdash; Extracurriculares';
$menu_activo = 'ec_programas';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

$q_buscar   = trim($_GET['buscar'] ?? '');
$q_cliente  = (int)($_GET['cliente'] ?? 0);
$q_estado   = $_GET['estado'] ?? '';
$q_dia      = $_GET['dia']    ?? '';

$where = ['1=1'];
$params = [];

if ($q_cliente) { $where[] = 'ct.cliente_id = ?'; $params[] = $q_cliente; }
if ($q_estado)  { $where[] = 'p.estado = ?';      $params[] = $q_estado; }
if ($q_dia)     { $where[] = 'p.dia_semana = ?';  $params[] = $q_dia; }
if ($q_buscar) {
    $where[] = '(p.nombre LIKE ? OR ct.nombre LIKE ? OR cl.nombre LIKE ?)';
    $params[] = "%$q_buscar%"; $params[] = "%$q_buscar%"; $params[] = "%$q_buscar%";
}

$sql = "SELECT p.*, c.nombre AS curso_nombre,
        ct.id AS contrato_id, ct.nombre AS contrato_nombre, ct.codigo AS contrato_codigo,
        cl.nombre AS cliente_nombre, cl.tipo AS cliente_tipo
        FROM ec_programas p
        JOIN ec_contratos ct ON ct.id = p.contrato_id
        JOIN ec_clientes cl  ON cl.id = ct.cliente_id
        LEFT JOIN cursos c ON c.id = p.curso_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY
          FIELD(p.dia_semana,'lunes','martes','miercoles','jueves','viernes','sabado','domingo'),
          p.hora_inicio";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$programas = $stmt->fetchAll();
$total = count($programas);

$clientes = $pdo->query("SELECT DISTINCT cl.id, cl.nombre FROM ec_clientes cl JOIN ec_contratos ct ON ct.cliente_id = cl.id ORDER BY cl.nombre")->fetchAll();

$prog_estados = [
    'planeado'   => ['Planeado',  '#1E4DA1', '#dbeafe'],
    'en_curso'   => ['En curso',  '#0d6e5f', '#d1fae5'],
    'finalizado' => ['Finalizado','#1f2937', '#E5E7EB'],
    'suspendido' => ['Suspendido','#b85f00', '#fff2d6'],
];

$dias_map = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Programas <small>Cursos activos en extracurriculares</small></div>
</header>
<main class="main-content">

  <?php if ($msg === 'creado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Programa creado.</div>
  <?php elseif ($msg === 'editado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Programa actualizado.</div>
  <?php elseif ($msg === 'eliminado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Programa eliminado.</div>
  <?php elseif ($msg === 'no_eliminable'): ?><div class="alert-rsal alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> No se puede eliminar: el programa tiene sesiones registradas.</div>
  <?php endif; ?>

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Los programas son los cursos concretos dentro de cada contrato. Para crear un programa nuevo abre el contrato y usa "Agregar programa".
  </div>

  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="GET" style="display:grid;grid-template-columns:1fr 200px 140px 140px auto;gap:.8rem;align-items:end;">
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Buscar</label>
        <input type="text" name="buscar" value="<?= h($q_buscar) ?>"
               placeholder="Programa contrato cliente..."
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
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">D&iacute;a</label>
        <select name="dia" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="">Todos</option>
          <?php foreach ($dias_map as $k => $v): ?>
            <option value="<?= $k ?>" <?= $q_dia === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Estado</label>
        <select name="estado" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="">Todos</option>
          <?php foreach ($prog_estados as $k => $v): ?>
            <option value="<?= $k ?>" <?= $q_estado === $k ? 'selected' : '' ?>><?= $v[0] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-rsal-primary" style="padding:.6rem 1rem;">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
    </form>
  </div>

  <div style="font-size:.85rem;color:var(--muted);margin-bottom:.8rem;">
    <strong style="color:var(--dark);"><?= $total ?></strong> programa<?= $total != 1 ? 's' : '' ?> encontrado<?= $total != 1 ? 's' : '' ?>
  </div>

  <?php if (empty($programas)): ?>
    <div class="empty-state">
      <i class="bi bi-bookmark-plus"></i>
      <h3>No hay programas con estos filtros</h3>
      <p>Los programas se crean desde dentro de cada contrato.</p>
      <a href="<?= $U ?>modulos/extracurriculares/contratos/index.php" class="btn-rsal-primary">
        <i class="bi bi-file-earmark-text-fill"></i> Ir a contratos
      </a>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;">
      <?php foreach ($programas as $p):
        $pe = $prog_estados[$p['estado']] ?? $prog_estados['planeado'];
        $color_prog = $p['color'] ?: '#7c3aed';
        $horario = ($dias_map[$p['dia_semana']] ?? $p['dia_semana'])
                 . ' &middot; ' . substr($p['hora_inicio'],0,5) . '&ndash;' . substr($p['hora_fin'],0,5);
      ?>
      <div class="card-rsal" style="margin:0;border-left:4px solid <?= h($color_prog) ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;gap:.6rem;">
          <div style="flex:1;min-width:0;">
            <div style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--dark);font-size:.95rem;">
              <?= h($p['nombre']) ?>
            </div>
            <?php if ($p['curso_nombre']): ?>
              <div style="font-size:.72rem;color:var(--muted);margin-top:.2rem;">
                <i class="bi bi-journal-check"></i> <?= h($p['curso_nombre']) ?>
              </div>
            <?php endif; ?>
          </div>
          <span style="background:<?= $pe[2] ?>;color:<?= $pe[1] ?>;font-size:.62rem;font-weight:700;padding:3px 8px;border-radius:10px;white-space:nowrap;">
            <?= $pe[0] ?>
          </span>
        </div>

        <div style="font-size:.77rem;color:var(--muted);margin-bottom:.4rem;">
          <i class="bi bi-building"></i> <?= h($p['cliente_nombre']) ?>
        </div>
        <div style="font-size:.77rem;color:var(--muted);margin-bottom:.6rem;">
          <i class="bi bi-calendar-event"></i> <?= $horario ?>
        </div>

        <?php
          $valor_prog = (float)$p['valor_por_nino'] * (int)$p['cantidad_ninos'];
          $bajo_minimo = $p['cantidad_ninos'] < $p['minimo_ninos'];
        ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;padding-top:.6rem;border-top:1px solid var(--border);font-size:.72rem;">
          <div style="text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-weight:900;color:<?= $bajo_minimo ? '#92400e' : h($color_prog) ?>;font-size:1.1rem;">
              <?= (int)$p['cantidad_ninos'] ?><span style="font-size:.65rem;color:var(--muted);font-weight:500;"> / <?= (int)$p['minimo_ninos'] ?></span>
            </div>
            <div style="color:var(--muted);">Ni&ntilde;os / m&iacute;n</div>
          </div>
          <div style="text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-weight:900;color:#0d6e5f;font-size:1rem;">$<?= number_format($valor_prog, 0, ',', '.') ?></div>
            <div style="color:var(--muted);">Valor total</div>
          </div>
        </div>

        <?php if ($bajo_minimo && $p['cantidad_ninos'] > 0): ?>
          <div style="margin-top:.5rem;padding:.35rem .6rem;background:#fff2d6;border:1px solid #fde68a;border-radius:6px;font-size:.68rem;color:#92400e;">
            <i class="bi bi-exclamation-triangle-fill"></i> Faltan <?= $p['minimo_ninos'] - $p['cantidad_ninos'] ?> ni&ntilde;os para el m&iacute;nimo viable
          </div>
        <?php endif; ?>

        <div style="display:flex;gap:.4rem;margin-top:.7rem;">
          <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $p['id'] ?>" class="btn-rsal-primary" style="flex:1;justify-content:center;padding:.4rem;font-size:.72rem;">
            <i class="bi bi-eye-fill"></i> Ver
          </a>
          <a href="<?= $U ?>modulos/extracurriculares/programas/form.php?id=<?= $p['id'] ?>" class="btn-rsal-secondary" style="padding:.4rem .6rem;font-size:.72rem;">
            <i class="bi bi-pencil-fill"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
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
