<?php
// modulos/academico/temas/index.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Temas';
$menu_activo = 'temas';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';
$curso_id    = (int)($_GET['curso'] ?? 0);

$sede_filtro = getSedeFiltro();

// Cursos para el selector
$sqlC = "SELECT c.id, c.nombre FROM cursos c WHERE 1=1";
$paramsC = [];
if ($sede_filtro) {
    $sqlC .= " AND EXISTS (SELECT 1 FROM grupos g WHERE g.curso_id = c.id AND g.sede_id = ?)";
    $paramsC[] = $sede_filtro;
}
$sqlC .= " ORDER BY c.orden, c.nombre";
$stC = $pdo->prepare($sqlC);
$stC->execute($paramsC);
$cursos = $stC->fetchAll();

// Temas del curso seleccionado (o todos)
$where = ['1=1'];
$params = [];
if ($curso_id) { $where[] = 't.curso_id = ?'; $params[] = $curso_id; }
if ($sede_filtro) {
    $where[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.curso_id = t.curso_id AND g.sede_id = ?)';
    $params[] = $sede_filtro;
}

$sql = "SELECT t.*, c.nombre AS curso_nombre,
        (SELECT COUNT(*) FROM actividades a WHERE a.tema_id = t.id) AS total_actividades
        FROM temas t
        JOIN cursos c ON c.id = t.curso_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY c.nombre, t.orden, t.nombre";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$temas = $stmt->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Temas <small>Unidades pedag&oacute;gicas por curso</small></div>
  <a href="<?= $U ?>modulos/academico/temas/form.php<?= $curso_id ? '?curso='.$curso_id : '' ?>" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo tema
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Tema creado correctamente.</div>
  <?php elseif ($msg === 'editado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Tema actualizado.</div>
  <?php elseif ($msg === 'eliminado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Tema eliminado.</div>
  <?php endif; ?>

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Los temas son las unidades pedag&oacute;gicas que organizan el contenido del curso. Cada tema agrupa actividades que los docentes desarrollar&aacute;n en el aula.
  </div>

  <!-- Filtro por curso -->
  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;">
      <label style="font-size:.85rem;font-weight:700;color:var(--dark);">Curso:</label>
      <select name="curso" onchange="this.form.submit()"
              style="padding:.5rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;min-width:250px;">
        <option value="0">Todos los cursos</option>
        <?php foreach ($cursos as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $curso_id == $c['id'] ? 'selected' : '' ?>>
            <?= h($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($curso_id): ?>
        <a href="?" style="color:var(--muted);font-size:.85rem;"><i class="bi bi-x-circle"></i> Limpiar</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (empty($temas)): ?>
    <div class="empty-state">
      <i class="bi bi-bookmark"></i>
      <h3>No hay temas registrados</h3>
      <p>Crea temas para organizar el contenido pedag&oacute;gico de cada curso.</p>
      <a href="<?= $U ?>modulos/academico/temas/form.php<?= $curso_id ? '?curso='.$curso_id : '' ?>" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Crear primer tema
      </a>
    </div>
  <?php else: ?>
    <?php
    // Agrupar por curso para visualizaci&oacute;n
    $por_curso = [];
    foreach ($temas as $t) { $por_curso[$t['curso_nombre']][] = $t; }
    ?>
    <?php foreach ($por_curso as $nombre_curso => $lista): ?>
      <div style="margin-bottom:1.8rem;">
        <div style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:700;color:var(--teal);margin-bottom:.8rem;display:flex;align-items:center;gap:.5rem;">
          <i class="bi bi-journal-richtext"></i> <?= h($nombre_curso) ?>
          <span style="font-size:.75rem;color:var(--muted);font-weight:500;">(<?= count($lista) ?> tema<?= count($lista) != 1 ? 's':'' ?>)</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;">
          <?php foreach ($lista as $t): ?>
          <div class="card-rsal" style="margin:0;transition:all .2s;<?= !$t['activo'] ? 'opacity:.55;' : '' ?>"
               onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.8rem;">
              <div style="width:42px;height:42px;border-radius:12px;background:#ede4fb;color:#6B46C1;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
                <i class="bi bi-bookmark-fill"></i>
              </div>
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.3rem;">
                <span class="badge-estado <?= $t['activo'] ? 'be-activa':'be-inactiva' ?>">
                  <?= $t['activo'] ? 'Activo':'Inactivo' ?>
                </span>
                <span style="font-size:.7rem;color:var(--muted);">Orden #<?= (int)$t['orden'] ?></span>
              </div>
            </div>
            <div style="font-family:'Poppins',sans-serif;font-size:.98rem;font-weight:700;color:var(--dark);margin-bottom:.5rem;line-height:1.3;">
              <?= h($t['nombre']) ?>
            </div>
            <?php if ($t['descripcion']): ?>
              <div style="font-size:.8rem;color:var(--muted);margin-bottom:.8rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                <?= h($t['descripcion']) ?>
              </div>
            <?php endif; ?>
            <div style="background:var(--gray);border-radius:8px;padding:.6rem;text-align:center;margin-bottom:.9rem;">
              <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:900;color:#6B46C1;"><?= $t['total_actividades'] ?></div>
              <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Actividades</div>
            </div>
            <div style="display:flex;gap:.5rem;">
              <a href="<?= $U ?>modulos/academico/temas/form.php?id=<?= $t['id'] ?>"
                 class="btn-rsal-primary" style="flex:1;justify-content:center;padding:.5rem;">
                <i class="bi bi-pencil-fill"></i> Editar
              </a>
              <a href="<?= $U ?>modulos/academico/actividades/index.php?tema=<?= $t['id'] ?>"
                 class="btn-rsal-secondary" style="flex:1;justify-content:center;padding:.5rem;">
                <i class="bi bi-puzzle-fill"></i> Actividades
              </a>
              <?php if ($t['total_actividades'] == 0): ?>
              <button onclick="eliminar(<?= $t['id'] ?>, '<?= h(addslashes($t['nombre'])) ?>')"
                      class="btn-rsal-danger" style="padding:.5rem .8rem;">
                <i class="bi bi-trash-fill"></i>
              </button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</main>
<form id="fEl" method="POST" action="<?= $U ?>modulos/academico/temas/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>
<script>
function eliminar(id, nombre) {
  if (confirm('&iquest;Eliminar el tema "' + nombre + '"?')) {
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
