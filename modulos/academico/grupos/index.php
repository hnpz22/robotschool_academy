<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Grupos y Horarios';
$menu_activo = 'grupos';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

$filtro_curso = (int)($_GET['curso'] ?? 0);

// Cursos disponibles para filtro
// Cursos disponibles para filtro: los que tienen grupos en esta sede
$where_c = $sede_filtro ? "WHERE EXISTS (SELECT 1 FROM grupos gx WHERE gx.curso_id = id AND gx.sede_id = ".(int)$sede_filtro.")" : '';

$cursos  = $pdo->query("SELECT id, nombre FROM cursos $where_c ORDER BY nombre")->fetchAll();

// Query grupos
$where  = ['1=1'];
$params = [];
if ($sede_filtro)   { $where[] = 'g.sede_id = ?';  $params[] = $sede_filtro; }
if ($filtro_curso)  { $where[] = 'g.curso_id = ?'; $params[] = $filtro_curso; }

$grupos = $pdo->prepare("
    SELECT g.*, c.nombre AS curso_nombre, s.nombre AS sede_nombre,
        (SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id = g.id AND m.estado = 'activa') AS inscritos,
        (g.cupo_real - (SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id = g.id AND m.estado = 'activa')) AS disponibles,
        (SELECT GROUP_CONCAT(u.nombre ORDER BY u.nombre SEPARATOR ', ')
         FROM docente_grupos dg JOIN usuarios u ON u.id = dg.docente_id
         WHERE dg.grupo_id = g.id) AS docentes_nombres
    FROM grupos g
    JOIN cursos c ON c.id = g.curso_id
    JOIN sedes  s ON s.id = g.sede_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.nombre, FIELD(g.dia_semana,'lunes','martes','miercoles','jueves','viernes','sabado','domingo'), g.hora_inicio
");
$grupos->execute($params);
$grupos = $grupos->fetchAll();

$dias = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles',
         'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>

<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Grupos y Horarios <small>Sesiones por curso y sede</small>
  </div>
  <a href="<?= $U ?>modulos/academico/grupos/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo grupo
  </a>
</header>

<main class="main-content">

  <?php if ($msg === 'creado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Grupo creado correctamente.</div>
  <?php elseif ($msg === 'editado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Grupo actualizado.</div>
  <?php elseif ($msg === 'eliminado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Grupo eliminado.</div>
  <?php endif; ?>

  <!-- Info -->
  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    El cupo real de cada grupo se calcula como el <strong>m&iacute;nimo</strong> entre: equipos asignados, capacidad del aula y l&iacute;mite del admin.
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="toolbar-left">
      <form method="GET" style="display:contents;">
        <select name="curso" class="filter-select" onchange="this.form.submit()">
          <option value="">Todos los cursos</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filtro_curso==$c['id']?'selected':'' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <a href="<?= $U ?>modulos/academico/grupos/form.php" class="btn-rsal-primary">
      <i class="bi bi-plus-lg"></i> Nuevo grupo
    </a>
  </div>

  <?php if (empty($grupos)): ?>
    <div class="empty-state">
      <i class="bi bi-calendar-x"></i>
      <h3>No hay grupos registrados</h3>
      <p>Crea el primer grupo para definir horarios y cupos.</p>
      <a href="<?= $U ?>modulos/academico/grupos/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Crear primer grupo
      </a>
    </div>
  <?php else: ?>
    <div class="card-rsal" style="padding:0;overflow:hidden;margin:0;">
      <table class="table-rsal">
        <thead>
          <tr>
            <th>Curso</th>
            <th>Grupo</th>
            <th>D&iacute;a y horario</th>
            <th>Modalidad</th>
            <th>Sede</th>
            <th>Cupo</th>
            <th>Inscritos</th>
            <th>Docentes</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grupos as $g):
            $pct = $g['cupo_real'] > 0 ? round(($g['inscritos']/$g['cupo_real'])*100) : 0;
            $bar_color = $pct>=100?'var(--red)':($pct>=75?'#F59E0B':'var(--teal)');
          ?>
          <tr>
            <td>
              <div style="font-weight:700;font-size:.85rem;color:var(--dark);"><?= h($g['curso_nombre']) ?></div>
              <div style="font-size:.72rem;color:var(--muted);"><?= h($g['sede_nombre']) ?></div>
            </td>
            <td style="font-weight:700;font-size:.85rem;"><?= h($g['nombre']) ?></td>
            <td>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">
                <?= $dias[$g['dia_semana']] ?? $g['dia_semana'] ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted);">
                <?= substr($g['hora_inicio'],0,5) ?> &ndash; <?= substr($g['hora_fin'],0,5) ?>
              </div>
            </td>
            <td>
              <?php
                $mod_color = ['presencial'=>'be-activa','virtual'=>'be-pre','hibrida'=>'be-pendiente'];
              ?>
              <span class="badge-estado <?= $mod_color[$g['modalidad']]??'be-inactiva' ?>">
                <?= ucfirst($g['modalidad']) ?>
              </span>
            </td>
            <td style="font-size:.82rem;"><?= h($g['sede_nombre']) ?></td>
            <td>
              <div style="font-size:.85rem;font-weight:700;"><?= $g['cupo_real'] ?></div>
              <div style="font-size:.68rem;color:var(--muted);">
                <?php if ($g['cupo_equipos']): ?>E:<?= $g['cupo_equipos'] ?> <?php endif; ?>
                <?php if ($g['cupo_aula']): ?>A:<?= $g['cupo_aula'] ?> <?php endif; ?>
                <?php if ($g['cupo_admin']): ?>M:<?= $g['cupo_admin'] ?> <?php endif; ?>
              </div>
              <!-- Barra progreso -->
              <div style="height:4px;background:var(--gray2);border-radius:2px;margin-top:3px;width:60px;">
                <div style="height:100%;width:<?= min($pct,100) ?>%;background:<?= $bar_color ?>;border-radius:2px;"></div>
              </div>
            </td>
            <td style="font-size:.85rem;font-weight:700;"><?= $g['inscritos'] ?></td>
            <td>
              <?php if ($g['docentes_nombres']): ?>
              <div style="font-size:.78rem;color:var(--dark);"><?= h($g['docentes_nombres']) ?></div>
              <?php else: ?>
              <span style="font-size:.75rem;color:var(--muted);font-style:italic;">Sin asignar</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge-estado <?= $g['activo'] ? 'be-activa' : 'be-inactiva' ?>">
                <?= $g['activo'] ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <a href="<?= $U ?>modulos/academico/grupos/form.php?id=<?= $g['id'] ?>"
                   class="btn-rsal-primary" style="padding:.35rem .7rem;font-size:.75rem;">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <button onclick="eliminar(<?= $g['id'] ?>, '<?= h(addslashes($g['nombre'])) ?>')"
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

<form id="fEl" method="POST" action="<?= $U ?>modulos/academico/grupos/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>

<script>
function eliminar(id, nombre) {
  if (confirm('&iquest;Eliminar el grupo "' + nombre + '"?')) {
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
