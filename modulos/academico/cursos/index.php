<?php
// modulos/academico/cursos/index.php
// ROOT y BASE_URL se definen autom&aacute;ticamente en config.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Cursos';
$menu_activo = 'cursos';
$buscar      = trim($_GET['buscar'] ?? '');
$msg         = $_GET['msg'] ?? '';

$sede_filtro = getSedeFiltro();
$where  = ['1=1'];
$params = [];
if ($buscar) { $where[] = 'c.nombre LIKE ?'; $params[] = "%$buscar%"; }
// admin_sede solo ve cursos que tienen al menos un grupo en su sede
if ($sede_filtro) {
    $where[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.curso_id = c.id AND g.sede_id = ?)';
    $params[] = $sede_filtro;
}

$sql = "SELECT c.*,
        (SELECT COUNT(DISTINCT m.id) FROM matriculas m
         JOIN grupos g ON g.id = m.grupo_id
         WHERE g.curso_id = c.id AND m.estado = 'activa'
         " . ($sede_filtro ? "AND g.sede_id = $sede_filtro" : "") . ") AS inscritos,
        (SELECT COUNT(DISTINCT g.sede_id) FROM grupos g WHERE g.curso_id = c.id AND g.activo=1) AS n_sedes
        FROM cursos c
        WHERE " . implode(' AND ', $where) . "
        ORDER BY c.orden ASC, c.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cursos = $stmt->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
$U = BASE_URL;
?>

<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Cursos <small>Gesti&oacute;n de cursos por sede</small>
  </div>
  <a href="<?= $U ?>modulos/academico/cursos/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo curso
  </a>
</header>

<main class="main-content">

  <?php if ($msg === 'creado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Curso creado exitosamente.</div>
  <?php elseif ($msg === 'editado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Curso actualizado correctamente.</div>
  <?php elseif ($msg === 'eliminado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Curso eliminado.</div>
  <?php endif; ?>

  <!-- Stats -->
  <?php
    $total           = count($cursos);
    $publicados      = count(array_filter($cursos, fn($c) => $c['publicado']));
    $total_inscritos = array_sum(array_column($cursos, 'inscritos'));
    $stats = [
      ['icon'=>'bi-journal-richtext','bg'=>'var(--teal-l)','color'=>'var(--teal)','num'=>$total,'lbl'=>'Total cursos'],
      ['icon'=>'bi-eye-fill','bg'=>'#e8f0fe','color'=>'#1E4DA1','num'=>$publicados,'lbl'=>'Publicados'],
      ['icon'=>'bi-person-fill','bg'=>'#fff8e1','color'=>'#F59E0B','num'=>$total_inscritos,'lbl'=>'Inscritos activos'],
    ];
  ?>
  <div style="display:flex;gap:1rem;margin-bottom:1.4rem;flex-wrap:wrap;">
    <?php foreach ($stats as $st): ?>
    <div class="card-rsal" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;margin:0;min-width:140px;">
      <div style="width:36px;height:36px;border-radius:10px;background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;display:flex;align-items:center;justify-content:center;">
        <i class="bi <?= $st['icon'] ?>"></i>
      </div>
      <div>
        <div style="font-family:'Poppins',sans-serif;font-size:1.3rem;font-weight:900;color:var(--dark);line-height:1;"><?= $st['num'] ?></div>
        <div style="font-size:.7rem;color:var(--muted);font-weight:600;"><?= $st['lbl'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="toolbar-left">
      <form method="GET" style="display:contents;">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="buscar" placeholder="Buscar curso..."
                 value="<?= h($buscar) ?>" onchange="this.form.submit()"/>
        </div>
      </form>
    </div>
    <a href="<?= $U ?>modulos/academico/cursos/form.php" class="btn-rsal-primary">
      <i class="bi bi-plus-lg"></i> Nuevo curso
    </a>
  </div>

  <!-- Grid de cursos -->
  <?php if (empty($cursos)): ?>
    <div class="empty-state">
      <i class="bi bi-journal-x"></i>
      <h3>No hay cursos registrados</h3>
      <p>Crea el primer curso para comenzar a gestionar matr&iacute;culas.</p>
      <a href="<?= $U ?>modulos/academico/cursos/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Crear primer curso
      </a>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.2rem;">
      <?php foreach ($cursos as $c):
        $pct         = $c['cupo_maximo'] > 0 ? round(($c['inscritos']/$c['cupo_maximo'])*100) : 0;
        $bar_color   = $pct>=100 ? 'var(--red)' : ($pct>=75 ? '#F59E0B' : 'var(--teal)');
        $disponibles = max(0, $c['cupo_maximo'] - $c['inscritos']);
      ?>
      <div style="background:#fff;border-radius:18px;border:1px solid var(--border);overflow:hidden;display:flex;flex-direction:column;transition:transform .2s,box-shadow .2s;"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,.1)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">

        <?php if ($c['imagen'] && file_exists(ROOT.'/uploads/cursos/'.$c['imagen'])): ?>
          <img src="<?= $U ?>uploads/cursos/<?= h($c['imagen']) ?>"
               style="width:100%;height:170px;object-fit:cover;" alt="<?= h($c['nombre']) ?>"/>
        <?php else: ?>
          <div style="width:100%;height:170px;background:linear-gradient(135deg,var(--dark),#1e3a5f);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.15);font-size:3rem;">
            <i class="bi bi-robot"></i>
          </div>
        <?php endif; ?>

        <div style="padding:1.1rem 1.2rem;flex:1;display:flex;flex-direction:column;">
          <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.7rem;">
            <span style="font-size:.65rem;font-weight:800;padding:.2rem .6rem;border-radius:20px;background:var(--teal-l);color:var(--teal);">
              <i class="bi bi-geo-alt"></i>
              <?= $c['n_sedes'] > 0 ? $c['n_sedes'].' sede'.($c['n_sedes']>1?'s':'') : 'Sin grupos' ?>
            </span>
            <span style="font-size:.65rem;font-weight:800;padding:.2rem .6rem;border-radius:20px;background:<?= $c['publicado']?'#dcfce7':'var(--gray2)' ?>;color:<?= $c['publicado']?'#15803d':'var(--muted)' ?>;">
              <?= $c['publicado'] ? 'Publicado' : 'Borrador' ?>
            </span>
          </div>
          <div style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:700;color:var(--dark);margin-bottom:.4rem;line-height:1.3;"><?= h($c['nombre']) ?></div>
          <?php if ($c['introduccion']): ?>
            <div style="font-size:.8rem;color:var(--muted);line-height:1.6;margin-bottom:.9rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= h($c['introduccion']) ?></div>
          <?php endif; ?>
          <div style="display:flex;gap:1rem;margin-bottom:.9rem;flex-wrap:wrap;">
            <?php if ($c['edad_min'] || $c['edad_max']): ?>
              <span style="display:flex;align-items:center;gap:.3rem;font-size:.75rem;color:var(--muted);font-weight:600;">
                <i class="bi bi-person" style="color:var(--teal)"></i><?= $c['edad_min'] ?>&ndash;<?= $c['edad_max'] ?> a&ntilde;os
              </span>
            <?php endif; ?>
            <span style="display:flex;align-items:center;gap:.3rem;font-size:.75rem;color:var(--muted);font-weight:600;">
              <i class="bi bi-cash" style="color:var(--teal)"></i><?= formatCOP($c['valor']) ?>
            </span>
          </div>
          <div style="margin-bottom:.9rem;">
            <div style="display:flex;justify-content:space-between;font-size:.72rem;font-weight:700;margin-bottom:.3rem;">
              <span style="color:var(--muted);">Cupos</span>
              <span style="color:var(--dark);"><?= $c['inscritos'] ?>/<?= $c['cupo_maximo'] ?> &middot; <?= $disponibles ?> libres</span>
            </div>
            <div style="height:6px;background:var(--gray2);border-radius:3px;overflow:hidden;">
              <div style="height:100%;width:<?= min($pct,100) ?>%;background:<?= $bar_color ?>;border-radius:3px;"></div>
            </div>
          </div>
          <div style="display:flex;gap:.5rem;margin-top:auto;">
            <a href="<?= $U ?>modulos/academico/cursos/form.php?id=<?= $c['id'] ?>" class="btn-rsal-primary" style="flex:1;justify-content:center;padding:.5rem;">
              <i class="bi bi-pencil-fill"></i> Editar
            </a>
            <a href="<?= $U ?>modulos/academico/cursos/toggle.php?id=<?= $c['id'] ?>"
               onclick="return confirm('&iquest;Cambiar estado?')"
               class="btn-rsal-secondary" style="flex:1;justify-content:center;padding:.5rem;">
              <i class="bi bi-eye<?= $c['publicado']?'-slash':'' ?>-fill"></i>
              <?= $c['publicado'] ? 'Ocultar' : 'Publicar' ?>
            </a>
            <button onclick="eliminar(<?= $c['id'] ?>, '<?= h(addslashes($c['nombre'])) ?>')"
                    class="btn-rsal-danger" style="padding:.5rem .8rem;">
              <i class="bi bi-trash-fill"></i>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<form id="fEliminar" method="POST" action="<?= $U ?>modulos/academico/cursos/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>

<script>
function eliminar(id, nombre) {
  if (confirm('&iquest;Eliminar "' + nombre + '"?\nEsta acci&oacute;n no se puede deshacer.')) {
    document.getElementById('elId').value = id;
    document.getElementById('fEliminar').submit();
  }
}
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
