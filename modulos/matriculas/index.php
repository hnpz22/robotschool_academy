<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Matr&iacute;culas';
$menu_activo = 'matriculas';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';
$buscar      = trim($_GET['buscar'] ?? '');
$filtro_est  = $_GET['estado'] ?? '';

$where  = ['1=1'];
$params = [];
if ($sede_filtro) { $where[] = 'm.sede_id = ?'; $params[] = $sede_filtro; }
if ($filtro_est)  { $where[] = 'm.estado = ?';  $params[] = $filtro_est; }
if ($buscar) {
    $where[] = '(e.nombre_completo LIKE ? OR c.nombre LIKE ?)';
    $params  = array_merge($params, ["%$buscar%","%$buscar%"]);
}

$matriculas = $pdo->prepare("
    SELECT m.*, e.nombre_completo AS estudiante, e.avatar,
        c.nombre AS curso, g.nombre AS grupo,
        g.dia_semana, g.hora_inicio, g.hora_fin,
        s.nombre AS sede_nombre,
        p.estado AS pago_estado, p.valor_total, p.valor_pagado
    FROM matriculas m
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN grupos g ON g.id = m.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    JOIN sedes  s ON s.id = m.sede_id
    LEFT JOIN pagos p ON p.matricula_id = m.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY m.created_at DESC
");
$matriculas->execute($params);
$matriculas = $matriculas->fetchAll();

$estados = ['pre_inscrito'=>'Pre-inscrito','activa'=>'Activa','retirada'=>'Retirada','finalizada'=>'Finalizada','suspendida'=>'Suspendida'];
$dias    = ['lunes'=>'Lun','martes'=>'Mar','miercoles'=>'Mi&eacute;','jueves'=>'Jue','viernes'=>'Vie','sabado'=>'S&aacute;b','domingo'=>'Dom'];

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Matr&iacute;culas <small><?= count($matriculas) ?> registros</small>
  </div>
  <a href="<?= $U ?>modulos/matriculas/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nueva matr&iacute;cula
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Matr&iacute;cula creada. Se gener&oacute; el registro de pago.</div>
  <?php elseif ($msg === 'editada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Matr&iacute;cula actualizada.</div>
  <?php elseif ($msg === 'eliminada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Matr&iacute;cula eliminada.</div>
  <?php endif; ?>

  <div class="toolbar">
    <div class="toolbar-left">
      <form method="GET" style="display:contents;">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="buscar" placeholder="Buscar estudiante o curso..."
                 value="<?= h($buscar) ?>" onchange="this.form.submit()"/>
        </div>
        <select name="estado" class="filter-select" onchange="this.form.submit()">
          <option value="">Todos los estados</option>
          <?php foreach($estados as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $filtro_est===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <a href="<?= $U ?>modulos/matriculas/form.php" class="btn-rsal-primary">
      <i class="bi bi-plus-lg"></i> Nueva matr&iacute;cula
    </a>
  </div>

  <?php if (empty($matriculas)): ?>
    <div class="empty-state">
      <i class="bi bi-clipboard2-x"></i>
      <h3>No hay matr&iacute;culas registradas</h3>
      <p>Crea la primera matr&iacute;cula asignando un estudiante a un grupo.</p>
      <a href="<?= $U ?>modulos/matriculas/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Nueva matr&iacute;cula
      </a>
    </div>
  <?php else: ?>
    <div class="card-rsal" style="padding:0;overflow:hidden;margin:0;">
      <table class="table-rsal">
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Curso / Grupo</th>
            <th>Horario</th>
            <th>Per&iacute;odo</th>
            <th>Estado</th>
            <th>Pago</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($matriculas as $m):
            $est_class = ['activa'=>'be-activa','pre_inscrito'=>'be-pre','retirada'=>'be-inactiva',
                          'finalizada'=>'be-inactiva','suspendida'=>'be-vencido'][$m['estado']] ?? 'be-inactiva';
            $pago_class = ['pagado'=>'sem-verde','parcial'=>'sem-amarillo',
                           'vencido'=>'sem-rojo','pendiente'=>'sem-amarillo'][$m['pago_estado']??''] ?? '';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.6rem;">
                <?php if ($m['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$m['avatar'])): ?>
                  <img src="<?= $U ?>uploads/estudiantes/<?= h($m['avatar']) ?>"
                       style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;"/>
                <?php else: ?>
                  <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#fff;flex-shrink:0;">
                    <?= strtoupper(substr($m['estudiante'],0,2)) ?>
                  </div>
                <?php endif; ?>
                <span style="font-size:.84rem;font-weight:600;"><?= h($m['estudiante']) ?></span>
              </div>
            </td>
            <td>
              <div style="font-weight:700;font-size:.84rem;"><?= h($m['curso']) ?></div>
              <div style="font-size:.72rem;color:var(--muted);"><?= h($m['grupo']) ?></div>
            </td>
            <td style="font-size:.82rem;">
              <strong><?= $dias[$m['dia_semana']]??$m['dia_semana'] ?></strong>
              <?= substr($m['hora_inicio'],0,5) ?>&ndash;<?= substr($m['hora_fin'],0,5) ?>
            </td>
            <td style="font-size:.82rem;"><?= h($m['periodo']) ?></td>
            <td><span class="badge-estado <?= $est_class ?>"><?= $estados[$m['estado']]??$m['estado'] ?></span></td>
            <td>
              <?php if ($m['pago_estado']): ?>
                <span class="<?= $pago_class ?>"><?= ucfirst($m['pago_estado']) ?></span>
                <?php if ($m['valor_total'] > 0): ?>
                  <div style="font-size:.7rem;color:var(--muted);">
                    <?= formatCOP($m['valor_pagado']) ?> / <?= formatCOP($m['valor_total']) ?>
                  </div>
                <?php endif; ?>
              <?php else: ?>
                <span style="color:var(--muted);font-size:.75rem;">&mdash;</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <a href="<?= $U ?>modulos/matriculas/form.php?id=<?= $m['id'] ?>"
                   class="btn-rsal-primary" style="padding:.35rem .7rem;font-size:.75rem;" title="Editar">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <?php if ($m['estado'] === 'activa'): ?>
                <a href="<?= $U ?>modulos/matriculas/avance.php?id=<?= $m['id'] ?>"
                   class="btn-rsal" style="padding:.35rem .7rem;font-size:.75rem;background:var(--orange);" title="Avance de m&oacute;dulo">
                  <i class="bi bi-arrow-right-circle-fill"></i>
                </a>
                <?php endif; ?>
                <button onclick="eliminar(<?= $m['id'] ?>)"
                        class="btn-rsal-danger" style="padding:.35rem .7rem;font-size:.75rem;">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<form id="fEl" method="POST" action="<?= $U ?>modulos/matriculas/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>
<script>
function eliminar(id) {
  if (confirm('&iquest;Eliminar esta matr&iacute;cula? Tambi&eacute;n se eliminar&aacute; el registro de pago asociado.')) {
    document.getElementById('elId').value = id;
    document.getElementById('fEl').submit();
  }
}
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
