<?php
// modulos/academico/actividades/index.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Actividades';
$menu_activo = 'actividades';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';
$tema_id     = (int)($_GET['tema'] ?? 0);
$curso_id    = (int)($_GET['curso'] ?? 0);

$sede_filtro = getSedeFiltro();

// Si hay tema_id, derivar curso_id automaticamente
if ($tema_id && !$curso_id) {
    $s = $pdo->prepare("SELECT curso_id FROM temas WHERE id = ?");
    $s->execute([$tema_id]);
    $curso_id = (int)$s->fetchColumn();
}

// Cursos para el primer filtro
$sqlC = "SELECT id, nombre FROM cursos";
$paramsC = [];
if ($sede_filtro) {
    $sqlC .= " WHERE EXISTS (SELECT 1 FROM grupos g WHERE g.curso_id = cursos.id AND g.sede_id = ?)";
    $paramsC[] = $sede_filtro;
}
$sqlC .= " ORDER BY orden, nombre";
$stC = $pdo->prepare($sqlC);
$stC->execute($paramsC);
$cursos = $stC->fetchAll();

// Temas del curso seleccionado (para segundo filtro)
$temas_curso = [];
if ($curso_id) {
    $sT = $pdo->prepare("SELECT id, nombre FROM temas WHERE curso_id = ? ORDER BY orden, nombre");
    $sT->execute([$curso_id]);
    $temas_curso = $sT->fetchAll();
}

// Construir WHERE dinamico
$where  = ['1=1'];
$params = [];
if ($tema_id)  { $where[] = 'a.tema_id = ?';  $params[] = $tema_id; }
if ($curso_id) { $where[] = 't.curso_id = ?'; $params[] = $curso_id; }
if ($sede_filtro) {
    $where[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.curso_id = t.curso_id AND g.sede_id = ?)';
    $params[] = $sede_filtro;
}

$sql = "SELECT a.*, t.nombre AS tema_nombre, c.id AS curso_id, c.nombre AS curso_nombre,
        r.nombre AS rubrica_nombre
        FROM actividades a
        JOIN temas t ON t.id = a.tema_id
        JOIN cursos c ON c.id = t.curso_id
        LEFT JOIN rubricas r ON r.id = a.rubrica_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY c.nombre, t.orden, t.nombre, a.orden, a.nombre";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$actividades = $stmt->fetchAll();

// Etiquetas para los tipos
$tipos_label = [
    'armado'        => ['Armado',        'bi-tools',              '#3B82F6'],
    'programacion'  => ['Programaci&oacute;n', 'bi-code-square',        '#10B981'],
    'investigacion' => ['Investigaci&oacute;n', 'bi-search',             '#F59E0B'],
    'reto'          => ['Reto',          'bi-trophy-fill',        '#EF4444'],
    'proyecto'      => ['Proyecto',      'bi-lightbulb-fill',     '#8B5CF6'],
    'exposicion'    => ['Exposici&oacute;n',   'bi-easel-fill',         '#EC4899'],
    'taller'        => ['Taller',        'bi-puzzle-fill',        '#06B6D4'],
    'otro'          => ['Otro',          'bi-three-dots',         '#6B7280'],
];

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Actividades <small>Tareas pedag&oacute;gicas por tema</small></div>
  <a href="<?= $U ?>modulos/academico/actividades/form.php<?= $tema_id ? '?tema='.$tema_id : '' ?>" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nueva actividad
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Actividad creada correctamente.</div>
  <?php elseif ($msg === 'editada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Actividad actualizada.</div>
  <?php elseif ($msg === 'eliminada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Actividad eliminada.</div>
  <?php endif; ?>

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Las actividades son las tareas concretas que los docentes desarrollar&aacute;n en clase. Cada actividad pertenece a un tema y puede asociarse a una r&uacute;brica para evaluarla.
  </div>

  <!-- Filtros: curso + tema -->
  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;">
      <label style="font-size:.85rem;font-weight:700;color:var(--dark);">Curso:</label>
      <select name="curso" onchange="this.form.submit()"
              style="padding:.5rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;min-width:220px;">
        <option value="0">Todos los cursos</option>
        <?php foreach ($cursos as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $curso_id == $c['id'] ? 'selected' : '' ?>>
            <?= h($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <?php if ($curso_id && !empty($temas_curso)): ?>
      <label style="font-size:.85rem;font-weight:700;color:var(--dark);">Tema:</label>
      <select name="tema" onchange="this.form.submit()"
              style="padding:.5rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;min-width:220px;">
        <option value="0">Todos los temas</option>
        <?php foreach ($temas_curso as $t): ?>
          <option value="<?= $t['id'] ?>" <?= $tema_id == $t['id'] ? 'selected' : '' ?>>
            <?= h($t['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>

      <?php if ($curso_id || $tema_id): ?>
        <a href="?" style="color:var(--muted);font-size:.85rem;"><i class="bi bi-x-circle"></i> Limpiar</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (empty($actividades)): ?>
    <div class="empty-state">
      <i class="bi bi-puzzle"></i>
      <h3>No hay actividades registradas</h3>
      <?php if ($tema_id): ?>
        <p>Este tema a&uacute;n no tiene actividades. Crea la primera para definir qu&eacute; har&aacute;n los estudiantes.</p>
      <?php else: ?>
        <p>Las actividades se organizan por tema. Primero selecciona un tema o crea una actividad nueva.</p>
      <?php endif; ?>
      <a href="<?= $U ?>modulos/academico/actividades/form.php<?= $tema_id ? '?tema='.$tema_id : '' ?>" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Crear primera actividad
      </a>
    </div>
  <?php else: ?>
    <?php
    // Agrupar por tema
    $por_tema = [];
    foreach ($actividades as $a) {
        $k = $a['curso_nombre'] . ' &mdash; ' . $a['tema_nombre'];
        $por_tema[$k][] = $a;
    }
    ?>
    <?php foreach ($por_tema as $titulo_grupo => $lista): ?>
      <div style="margin-bottom:1.8rem;">
        <div style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:700;color:#6B46C1;margin-bottom:.8rem;display:flex;align-items:center;gap:.5rem;">
          <i class="bi bi-bookmark-fill"></i> <?= $titulo_grupo ?>
          <span style="font-size:.75rem;color:var(--muted);font-weight:500;">(<?= count($lista) ?> actividad<?= count($lista) != 1 ? 'es':'' ?>)</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;">
          <?php foreach ($lista as $a):
            $t = $tipos_label[$a['tipo']] ?? $tipos_label['otro'];
          ?>
          <div class="card-rsal" style="margin:0;transition:all .2s;<?= !$a['activa'] ? 'opacity:.55;' : '' ?>"
               onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.8rem;">
              <div style="width:42px;height:42px;border-radius:12px;background:<?= $t[2] ?>20;color:<?= $t[2] ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
                <i class="bi <?= $t[1] ?>"></i>
              </div>
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.3rem;">
                <span style="background:<?= $t[2] ?>20;color:<?= $t[2] ?>;font-size:.68rem;font-weight:700;padding:2px 10px;border-radius:12px;">
                  <?= $t[0] ?>
                </span>
                <span style="font-size:.7rem;color:var(--muted);">Orden #<?= (int)$a['orden'] ?></span>
              </div>
            </div>
            <div style="font-family:'Poppins',sans-serif;font-size:.98rem;font-weight:700;color:var(--dark);margin-bottom:.5rem;line-height:1.3;">
              <?= h($a['nombre']) ?>
            </div>
            <?php if ($a['descripcion']): ?>
              <div style="font-size:.8rem;color:var(--muted);margin-bottom:.7rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                <?= h($a['descripcion']) ?>
              </div>
            <?php endif; ?>
            <!-- Meta: duracion + rubrica -->
            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.8rem;">
              <?php if ($a['duracion_min']): ?>
              <span style="background:var(--gray);color:var(--muted);font-size:.7rem;font-weight:600;padding:3px 9px;border-radius:8px;">
                <i class="bi bi-clock"></i> <?= (int)$a['duracion_min'] ?> min
              </span>
              <?php endif; ?>
              <?php if ($a['rubrica_nombre']): ?>
              <span style="background:var(--teal-l);color:var(--teal);font-size:.7rem;font-weight:600;padding:3px 9px;border-radius:8px;" title="<?= h($a['rubrica_nombre']) ?>">
                <i class="bi bi-list-check"></i> R&uacute;brica asociada
              </span>
              <?php else: ?>
              <span style="background:#FEF3C7;color:#92400E;font-size:.7rem;font-weight:600;padding:3px 9px;border-radius:8px;">
                <i class="bi bi-exclamation-circle"></i> Sin r&uacute;brica
              </span>
              <?php endif; ?>
            </div>
            <div style="display:flex;gap:.5rem;">
              <a href="<?= $U ?>modulos/academico/actividades/form.php?id=<?= $a['id'] ?>"
                 class="btn-rsal-primary" style="flex:1;justify-content:center;padding:.5rem;">
                <i class="bi bi-pencil-fill"></i> Editar
              </a>
              <button onclick="eliminar(<?= $a['id'] ?>, '<?= h(addslashes($a['nombre'])) ?>')"
                      class="btn-rsal-danger" style="padding:.5rem .8rem;">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</main>
<form id="fEl" method="POST" action="<?= $U ?>modulos/academico/actividades/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>
<script>
function eliminar(id, nombre) {
  if (confirm('&iquest;Eliminar la actividad "' + nombre + '"?')) {
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
