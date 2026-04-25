<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Evaluaciones';
$menu_activo = 'evaluaciones';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';
$filtro_rub  = (int)($_GET['rubrica'] ?? 0);
$filtro_cur  = (int)($_GET['curso']   ?? 0);
$rol_actual  = $_SESSION['usuario_rol'];
$usuario_id  = $_SESSION['usuario_id'];

$where  = ['1=1'];
$params = [];

// Docente: solo evaluaciones de sus grupos
if ($rol_actual === 'docente') {
    $where[] = "m.grupo_id IN (SELECT grupo_id FROM docente_grupos WHERE docente_id = ?)";
    $params[] = $usuario_id;
} else {
    if ($sede_filtro) { $where[] = 'm_s.id = ?'; $params[] = $sede_filtro; }
}
if ($filtro_rub) { $where[] = 'ev.rubrica_id = ?'; $params[] = $filtro_rub; }
if ($filtro_cur) { $where[] = 'c.id = ?'; $params[] = $filtro_cur; }

$evaluaciones = $pdo->prepare("
    SELECT ev.*,
        e.nombre_completo AS estudiante, e.avatar,
        p.nombre_completo AS padre,
        r.nombre AS rubrica_nombre,
        c.nombre AS curso_nombre,
        g.nombre AS grupo_nombre,
        u.nombre AS docente_nombre,
        m_s.nombre AS sede_nombre,
        (SELECT SUM(ed.puntaje) FROM evaluacion_detalle ed WHERE ed.evaluacion_id=ev.id) AS total_obtenido,
        (SELECT SUM(rc.puntaje_max) FROM evaluacion_detalle ed JOIN rubrica_criterios rc ON rc.id=ed.criterio_id WHERE ed.evaluacion_id=ev.id) AS total_posible
    FROM evaluaciones ev
    JOIN matriculas m ON m.id = ev.matricula_id
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN padres p ON p.id = e.padre_id
    JOIN rubricas r ON r.id = ev.rubrica_id
    JOIN grupos g ON g.id = m.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    JOIN sedes m_s ON m_s.id = m.sede_id
    JOIN usuarios u ON u.id = ev.docente_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY ev.fecha DESC, ev.created_at DESC
");
$evaluaciones->execute($params);
$evaluaciones = $evaluaciones->fetchAll();

// Para filtros
$cursos_f   = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();
$rubricas_f = $pdo->query("SELECT r.id, r.nombre FROM rubricas r ORDER BY r.nombre")->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Evaluaciones <small><?= count($evaluaciones) ?> registros</small></div>
  <a href="<?= $U ?>modulos/academico/evaluaciones/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nueva evaluaci&oacute;n
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Evaluaci&oacute;n registrada correctamente.</div>
  <?php elseif ($msg === 'editada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Evaluaci&oacute;n actualizada.</div>
  <?php elseif ($msg === 'eliminada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Evaluaci&oacute;n eliminada.</div>
  <?php endif; ?>

  <div class="toolbar">
    <div class="toolbar-left">
      <form method="GET" style="display:contents;">
        <select name="curso" class="filter-select" onchange="this.form.submit()">
          <option value="">Todos los cursos</option>
          <?php foreach($cursos_f as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filtro_cur==$c['id']?'selected':'' ?>><?= h($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="rubrica" class="filter-select" onchange="this.form.submit()">
          <option value="">Todas las r&uacute;bricas</option>
          <?php foreach($rubricas_f as $r): ?>
            <option value="<?= $r['id'] ?>" <?= $filtro_rub==$r['id']?'selected':'' ?>><?= h($r['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <a href="<?= $U ?>modulos/academico/evaluaciones/form.php" class="btn-rsal-primary">
      <i class="bi bi-plus-lg"></i> Nueva evaluaci&oacute;n
    </a>
  </div>

  <?php if (empty($evaluaciones)): ?>
    <div class="empty-state">
      <i class="bi bi-star"></i>
      <h3>No hay evaluaciones registradas</h3>
      <p>Registra la primera evaluaci&oacute;n aplicando una r&uacute;brica a un estudiante.</p>
      <a href="<?= $U ?>modulos/academico/evaluaciones/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Registrar evaluaci&oacute;n
      </a>
    </div>
  <?php else: ?>
    <div class="card-rsal" style="padding:0;overflow:hidden;margin:0;">
      <table class="table-rsal">
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Curso / R&uacute;brica</th>
            <th>Fecha</th>
            <th>Puntaje</th>
            <th>%</th>
            <th>Docente</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($evaluaciones as $ev):
            $pct = $ev['total_posible'] > 0 ? round(($ev['total_obtenido']/$ev['total_posible'])*100) : 0;
            $col = $pct>=80?'#16a34a':($pct>=60?'#ca8a04':'var(--red)');
            $stars = round($pct / 20); // 0-5 estrellas
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.6rem;">
                <?php if ($ev['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$ev['avatar'])): ?>
                  <img src="<?= $U ?>uploads/estudiantes/<?= h($ev['avatar']) ?>"
                       style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;"/>
                <?php else: ?>
                  <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:800;color:#fff;flex-shrink:0;">
                    <?= strtoupper(substr($ev['estudiante'],0,2)) ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div style="font-size:.84rem;font-weight:600;"><?= h($ev['estudiante']) ?></div>
                  <div style="font-size:.7rem;color:var(--muted);"><?= h($ev['grupo_nombre']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-size:.83rem;font-weight:700;"><?= h($ev['curso_nombre']) ?></div>
              <div style="font-size:.72rem;color:var(--muted);"><?= h($ev['rubrica_nombre']) ?></div>
            </td>
            <td style="font-size:.82rem;"><?= formatFecha($ev['fecha']) ?></td>
            <td>
              <div style="font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:800;color:<?= $col ?>;">
                <?= $ev['total_obtenido'] ?><span style="font-size:.7rem;color:var(--muted);font-weight:400;">/<?= $ev['total_posible'] ?></span>
              </div>
              <!-- Barra mini -->
              <div style="height:3px;background:var(--gray2);border-radius:2px;margin-top:2px;width:60px;">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $col ?>;border-radius:2px;"></div>
              </div>
            </td>
            <td>
              <span style="font-family:'Poppins',sans-serif;font-size:.9rem;font-weight:800;color:<?= $col ?>;"><?= $pct ?>%</span>
              <div style="font-size:.72rem;color:#F59E0B;"><?= str_repeat('&#9733;',$stars).str_repeat('&#9734;',5-$stars) ?></div>
            </td>
            <td style="font-size:.8rem;"><?= h($ev['docente_nombre']) ?></td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <a href="<?= $U ?>modulos/academico/evaluaciones/form.php?id=<?= $ev['id'] ?>"
                   class="btn-rsal-primary" style="padding:.35rem .7rem;font-size:.75rem;">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <button onclick="eliminar(<?= $ev['id'] ?>)"
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
<form id="fEl" method="POST" action="<?= $U ?>modulos/academico/evaluaciones/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>
<script>
function eliminar(id) {
  if (confirm('&iquest;Eliminar esta evaluaci&oacute;n?')) {
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
