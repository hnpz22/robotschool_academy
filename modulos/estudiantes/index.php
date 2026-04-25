<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Estudiantes';
$menu_activo = 'estudiantes';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';
$buscar      = trim($_GET['buscar'] ?? '');
$filtro_sede = $_GET['sede'] ?? '';
$rol_actual  = $_SESSION['usuario_rol'];
$usuario_id  = $_SESSION['usuario_id'];

$where  = ['1=1'];
$params = [];

// Docente: solo estudiantes de sus grupos asignados
if ($rol_actual === 'docente') {
    $where[] = "e.id IN (
        SELECT DISTINCT est.id FROM estudiantes est
        JOIN matriculas m ON m.estudiante_id = est.id
        JOIN docente_grupos dg ON dg.grupo_id = m.grupo_id
        WHERE dg.docente_id = ? AND m.estado = 'activa'
    )";
    $params[] = $usuario_id;
} else {
    if ($sede_filtro)    { $where[] = 'e.sede_id = ?'; $params[] = $sede_filtro; }
    elseif ($filtro_sede){ $where[] = 'e.sede_id = ?'; $params[] = $filtro_sede; }
}

if ($buscar) {
    $where[] = '(e.nombre_completo LIKE ? OR e.numero_doc LIKE ? OR p.nombre_completo LIKE ?)';
    $params  = array_merge($params, ["%$buscar%","%$buscar%","%$buscar%"]);
}

$estudiantes = $pdo->prepare("
    SELECT e.*, s.nombre AS sede_nombre,
        p.nombre_completo AS padre_nombre, p.telefono AS padre_tel,
        (SELECT COUNT(*) FROM matriculas m WHERE m.estudiante_id = e.id AND m.estado='activa') AS matriculas_activas
    FROM estudiantes e
    JOIN sedes  s ON s.id = e.sede_id
    JOIN padres p ON p.id = e.padre_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY e.nombre_completo ASC
");
$estudiantes->execute($params);
$estudiantes = $estudiantes->fetchAll();

$sedes = $pdo->query("SELECT * FROM sedes WHERE activa=1 ORDER BY nombre")->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Estudiantes <small><?= count($estudiantes) ?> registrados</small>
  </div>
  <a href="<?= $U ?>modulos/estudiantes/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo estudiante
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Estudiante registrado.</div>
  <?php elseif ($msg === 'editado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Estudiante actualizado.</div>
  <?php elseif ($msg === 'eliminado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Estudiante eliminado.</div>
  <?php endif; ?>

  <div class="toolbar">
    <div class="toolbar-left">
      <form method="GET" style="display:contents;">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="buscar" placeholder="Buscar por nombre, doc, padre..."
                 value="<?= h($buscar) ?>" onchange="this.form.submit()"/>
        </div>
        <?php if ($_SESSION['usuario_rol']==='admin_general'): ?>
          <select name="sede" class="filter-select" onchange="this.form.submit()">
            <option value="">Todas las sedes</option>
            <?php foreach($sedes as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $filtro_sede==$s['id']?'selected':'' ?>>
                <?= h($s['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </form>
    </div>
    <a href="<?= $U ?>modulos/estudiantes/form.php" class="btn-rsal-primary">
      <i class="bi bi-plus-lg"></i> Nuevo estudiante
    </a>
  </div>

  <?php if (empty($estudiantes)): ?>
    <div class="empty-state">
      <i class="bi bi-person-badge"></i>
      <h3>No hay estudiantes registrados</h3>
      <p>Los estudiantes se crean al vincularlos con un padre de familia.</p>
      <a href="<?= $U ?>modulos/estudiantes/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Registrar estudiante
      </a>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;">
      <?php foreach ($estudiantes as $e):
        $edad = date_diff(date_create($e['fecha_nacimiento']), date_create('today'))->y;
      ?>
      <div class="card-rsal" style="margin:0;transition:all .2s;cursor:pointer;"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''"
           onclick="window.location='<?= $U ?>modulos/estudiantes/ver.php?id=<?= $e['id'] ?>'">
        <div style="display:flex;align-items:center;gap:.9rem;margin-bottom:.9rem;">
          <!-- Avatar -->
          <?php if ($e['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$e['avatar'])): ?>
            <img src="<?= $U ?>uploads/estudiantes/<?= h($e['avatar']) ?>"
                 style="width:52px;height:52px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid var(--teal-l);"/>
          <?php else: ?>
            <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:.9rem;font-weight:800;color:#fff;flex-shrink:0;">
              <?= strtoupper(substr($e['nombre_completo'],0,2)) ?>
            </div>
          <?php endif; ?>
          <div style="flex:1;overflow:hidden;">
            <div style="font-weight:800;font-size:.9rem;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= h($e['nombre_completo']) ?>
            </div>
            <div style="font-size:.72rem;color:var(--muted);">
              <?= $edad ?> a&ntilde;os &middot; <?= h($e['grado'] ?? 'Sin grado') ?>
            </div>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:.3rem;font-size:.78rem;color:var(--muted);">
          <div><i class="bi bi-geo-alt" style="color:var(--teal);width:14px;"></i> <?= h($e['sede_nombre']) ?></div>
          <div><i class="bi bi-people" style="color:var(--teal);width:14px;"></i> <?= h($e['padre_nombre']) ?></div>
          <?php if ($e['colegio']): ?>
            <div><i class="bi bi-building" style="color:var(--teal);width:14px;"></i> <?= h($e['colegio']) ?></div>
          <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.9rem;padding-top:.9rem;border-top:1px solid var(--gray2);">
          <span class="badge-estado <?= $e['activo']?'be-activa':'be-inactiva' ?>">
            <?= $e['activo']?'Activo':'Inactivo' ?>
          </span>
          <?php if ($e['matriculas_activas'] > 0): ?>
            <span style="font-size:.72rem;font-weight:700;color:var(--teal);">
              <i class="bi bi-clipboard2-check-fill"></i> <?= $e['matriculas_activas'] ?> matr&iacute;cula<?= $e['matriculas_activas']>1?'s':'' ?>
            </span>
          <?php endif; ?>
          <div style="display:flex;gap:.4rem;" onclick="event.stopPropagation()">
            <a href="<?= $U ?>modulos/estudiantes/hoja_vida.php?id=<?= $e['id'] ?>"
               target="_blank"
               class="btn-rsal-secondary" style="padding:.3rem .6rem;font-size:.72rem;" title="Hoja de vida PDF">
              <i class="bi bi-file-earmark-person-fill"></i>
            </a>
            <a href="<?= $U ?>modulos/estudiantes/form.php?id=<?= $e['id'] ?>"
               class="btn-rsal-primary" style="padding:.3rem .6rem;font-size:.72rem;">
              <i class="bi bi-pencil-fill"></i>
            </a>
          </div>
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
