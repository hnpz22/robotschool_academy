<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'R&uacute;bricas';
$menu_activo = 'rubricas';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

$sede_filtro = getSedeFiltro();
$where  = ['1=1'];
$params = [];
if ($sede_filtro) {
    $where[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.curso_id = r.curso_id AND g.sede_id = ?)';
    $params[] = $sede_filtro;
}

$rubricas = $pdo->prepare("
    SELECT r.*, c.nombre AS curso_nombre,
        (SELECT COUNT(*) FROM rubrica_criterios rc WHERE rc.rubrica_id = r.id) AS total_criterios,
        (SELECT COUNT(*) FROM evaluaciones ev WHERE ev.rubrica_id = r.id) AS total_evaluaciones
    FROM rubricas r
    JOIN cursos c ON c.id = r.curso_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.nombre, r.nombre
");
$rubricas->execute($params);
$rubricas = $rubricas->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">R&uacute;bricas <small>Instrumentos de evaluaci&oacute;n</small></div>
  <a href="<?= $U ?>modulos/academico/rubricas/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nueva r&uacute;brica
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> R&uacute;brica creada correctamente.</div>
  <?php elseif ($msg === 'editada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> R&uacute;brica actualizada.</div>
  <?php elseif ($msg === 'eliminada'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> R&uacute;brica eliminada.</div>
  <?php endif; ?>

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Las r&uacute;bricas definen los criterios de evaluaci&oacute;n por curso. Cada criterio tiene un puntaje m&aacute;ximo configurable.
  </div>

  <?php if (empty($rubricas)): ?>
    <div class="empty-state">
      <i class="bi bi-clipboard2-check"></i>
      <h3>No hay r&uacute;bricas registradas</h3>
      <p>Crea r&uacute;bricas para poder evaluar a los estudiantes.</p>
      <a href="<?= $U ?>modulos/academico/rubricas/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Crear primera r&uacute;brica
      </a>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;">
      <?php foreach ($rubricas as $r): ?>
      <div class="card-rsal" style="margin:0;transition:all .2s;"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.8rem;">
          <div style="width:42px;height:42px;border-radius:12px;background:var(--teal-l);color:var(--teal);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
            <i class="bi bi-clipboard2-check-fill"></i>
          </div>
          <span class="badge-estado <?= $r['activa'] ? 'be-activa':'be-inactiva' ?>">
            <?= $r['activa'] ? 'Activa':'Inactiva' ?>
          </span>
        </div>
        <div style="font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:700;color:var(--dark);margin-bottom:.2rem;">
          <?= h($r['nombre']) ?>
        </div>
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.8rem;">
          <i class="bi bi-journal-richtext" style="color:var(--teal);"></i> <?= h($r['curso_nombre']) ?>
          <?php if ($r['periodo']): ?> &middot; <?= h($r['periodo']) ?><?php endif; ?>
        </div>
        <?php if ($r['descripcion']): ?>
          <div style="font-size:.8rem;color:var(--muted);margin-bottom:.8rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
            <?= h($r['descripcion']) ?>
          </div>
        <?php endif; ?>
        <!-- Stats -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.9rem;">
          <div style="background:var(--gray);border-radius:8px;padding:.5rem;text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.3rem;font-weight:900;color:var(--teal);"><?= $r['total_criterios'] ?></div>
            <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Criterios</div>
          </div>
          <div style="background:var(--gray);border-radius:8px;padding:.5rem;text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.3rem;font-weight:900;color:var(--orange);"><?= $r['total_evaluaciones'] ?></div>
            <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Evaluaciones</div>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;">
          <a href="<?= $U ?>modulos/academico/rubricas/form.php?id=<?= $r['id'] ?>"
             class="btn-rsal-primary" style="flex:1;justify-content:center;padding:.5rem;">
            <i class="bi bi-pencil-fill"></i> Editar
          </a>
          <a href="<?= $U ?>modulos/academico/evaluaciones/index.php?rubrica=<?= $r['id'] ?>"
             class="btn-rsal-secondary" style="flex:1;justify-content:center;padding:.5rem;">
            <i class="bi bi-star-fill"></i> Evaluar
          </a>
          <?php if ($r['total_evaluaciones'] == 0): ?>
          <button onclick="eliminar(<?= $r['id'] ?>, '<?= h(addslashes($r['nombre'])) ?>')"
                  class="btn-rsal-danger" style="padding:.5rem .8rem;">
            <i class="bi bi-trash-fill"></i>
          </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>
<form id="fEl" method="POST" action="<?= $U ?>modulos/academico/rubricas/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>
<script>
function eliminar(id, nombre) {
  if (confirm('&iquest;Eliminar la r&uacute;brica "' + nombre + '"?')) {
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
