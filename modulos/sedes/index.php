<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_general');

$titulo      = 'Sedes';
$menu_activo = 'sedes';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

$sedes = $pdo->query("
    SELECT s.*,
        (SELECT COUNT(DISTINCT g.curso_id) FROM grupos g WHERE g.sede_id=s.id AND g.activo=1) AS total_cursos,
        (SELECT COUNT(*) FROM estudiantes e WHERE e.sede_id=s.id) AS total_estudiantes,
        (SELECT COUNT(*) FROM grupos g WHERE g.sede_id=s.id AND g.activo=1) AS grupos_activos
    FROM sedes s ORDER BY s.nombre
")->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">Sedes <small>Ubicaciones ROBOTSchool</small></div>
  <a href="<?= $U ?>modulos/sedes/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nueva sede
  </a>
</header>
<main class="main-content">
  <?php if ($msg==='creada'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Sede creada.</div>
  <?php elseif($msg==='editada'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Sede actualizada.</div><?php endif; ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:1rem;">
    <?php foreach ($sedes as $s): ?>
    <div class="card-rsal" style="margin:0;">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.8rem;">
        <div style="width:44px;height:44px;border-radius:12px;background:<?= $s['activa']?'var(--teal-l)':'var(--gray2)' ?>;color:<?= $s['activa']?'var(--teal)':'var(--muted)' ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
          <i class="bi bi-geo-alt-fill"></i>
        </div>
        <span class="badge-estado <?= $s['activa']?'be-activa':'be-inactiva' ?>"><?= $s['activa']?'Activa':'Inactiva' ?></span>
      </div>
      <div style="font-family:'Poppins',sans-serif;font-size:.97rem;font-weight:700;color:var(--dark);margin-bottom:.2rem;"><?= h($s['nombre']) ?></div>
      <div style="font-size:.78rem;color:var(--muted);margin-bottom:.8rem;">
        <i class="bi bi-pin-map-fill"></i> <?= h($s['ciudad']) ?>
        <?php if ($s['direccion']): ?> &middot; <?= h($s['direccion']) ?><?php endif; ?>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.4rem;margin-bottom:.9rem;text-align:center;">
        <div style="background:var(--gray);border-radius:8px;padding:.5rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;color:var(--teal);"><?= $s['total_cursos'] ?></div>
          <div style="font-size:.62rem;color:var(--muted);font-weight:600;">Cursos</div>
        </div>
        <div style="background:var(--gray);border-radius:8px;padding:.5rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;color:var(--orange);"><?= $s['grupos_activos'] ?></div>
          <div style="font-size:.62rem;color:var(--muted);font-weight:600;">Grupos</div>
        </div>
        <div style="background:var(--gray);border-radius:8px;padding:.5rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;color:var(--blue);"><?= $s['total_estudiantes'] ?></div>
          <div style="font-size:.62rem;color:var(--muted);font-weight:600;">Alumnos</div>
        </div>
      </div>
      <a href="<?= $U ?>modulos/sedes/form.php?id=<?= $s['id'] ?>" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.5rem;">
        <i class="bi bi-pencil-fill"></i> Editar sede
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</main>
<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
