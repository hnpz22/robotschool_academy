<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Equipos';
$menu_activo = 'equipos';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

$where  = ['1=1'];
$params = [];
if ($sede_filtro) { $where[] = 'e.sede_id = ?'; $params[] = $sede_filtro; }

$equipos = $pdo->prepare("
    SELECT e.*, s.nombre AS sede_nombre,
        (SELECT COALESCE(SUM(ge.cantidad_requerida),0)
         FROM grupo_equipos ge
         JOIN grupos g ON g.id = ge.grupo_id
         WHERE ge.equipo_id = e.id AND g.activo = 1) AS en_uso
    FROM equipos e JOIN sedes s ON s.id = e.sede_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY s.nombre, e.nombre
");
$equipos->execute($params);
$equipos = $equipos->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>

<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Equipos <small>Inventario de equipos por sede</small>
  </div>
  <a href="<?= $U ?>modulos/equipos/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo equipo
  </a>
</header>

<main class="main-content">

  <?php if ($msg === 'creado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Equipo creado.</div>
  <?php elseif ($msg === 'editado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Equipo actualizado.</div>
  <?php elseif ($msg === 'eliminado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Equipo eliminado.</div>
  <?php endif; ?>

  <?php if (empty($equipos)): ?>
    <div class="empty-state">
      <i class="bi bi-cpu"></i>
      <h3>No hay equipos registrados</h3>
      <p>Registra los equipos disponibles en cada sede para calcular cupos autom&aacute;ticamente.</p>
      <a href="<?= $U ?>modulos/equipos/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Registrar primer equipo
      </a>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
      <?php foreach ($equipos as $e):
        $disponibles = $e['cantidad_total'] - $e['en_uso'];
        $pct = $e['cantidad_total'] > 0 ? round(($e['en_uso']/$e['cantidad_total'])*100) : 0;
        $bar_color = $pct>=100?'var(--red)':($pct>=75?'#F59E0B':'var(--teal)');
      ?>
      <div class="card-rsal" style="margin:0;transition:all .2s;"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.9rem;">
          <div style="width:44px;height:44px;border-radius:12px;background:var(--teal-l);color:var(--teal);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
            <i class="bi bi-cpu-fill"></i>
          </div>
          <span class="badge-estado <?= $e['activo']?'be-activa':'be-inactiva' ?>">
            <?= $e['activo']?'Activo':'Inactivo' ?>
          </span>
        </div>
        <div style="font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:700;color:var(--dark);margin-bottom:.2rem;">
          <?= h($e['nombre']) ?>
        </div>
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.9rem;">
          <i class="bi bi-geo-alt"></i> <?= h($e['sede_nombre']) ?>
          <?php if ($e['descripcion']): ?>
            &middot; <?= h($e['descripcion']) ?>
          <?php endif; ?>
        </div>
        <!-- Stats -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;margin-bottom:.9rem;text-align:center;">
          <div style="background:var(--gray);border-radius:8px;padding:.5rem;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:900;color:var(--dark);"><?= $e['cantidad_total'] ?></div>
            <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Total</div>
          </div>
          <div style="background:var(--teal-l);border-radius:8px;padding:.5rem;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:900;color:var(--teal);"><?= $disponibles ?></div>
            <div style="font-size:.65rem;color:var(--teal-d);font-weight:600;">Disponibles</div>
          </div>
          <div style="background:#fff8e1;border-radius:8px;padding:.5rem;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:900;color:#F59E0B;"><?= $e['en_uso'] ?></div>
            <div style="font-size:.65rem;color:#92400e;font-weight:600;">En grupos</div>
          </div>
        </div>
        <!-- Barra -->
        <div style="height:6px;background:var(--gray2);border-radius:3px;margin-bottom:.9rem;">
          <div style="height:100%;width:<?= min($pct,100) ?>%;background:<?= $bar_color ?>;border-radius:3px;transition:width .4s;"></div>
        </div>
        <!-- Acciones -->
        <div style="display:flex;gap:.5rem;">
          <a href="<?= $U ?>modulos/equipos/form.php?id=<?= $e['id'] ?>" class="btn-rsal-primary" style="flex:1;justify-content:center;padding:.5rem;">
            <i class="bi bi-pencil-fill"></i> Editar
          </a>
          <button onclick="eliminar(<?= $e['id'] ?>, '<?= h(addslashes($e['nombre'])) ?>')"
                  class="btn-rsal-danger" style="padding:.5rem .8rem;">
            <i class="bi bi-trash-fill"></i>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<form id="fEl" method="POST" action="<?= $U ?>modulos/equipos/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>
<script>
function eliminar(id, nombre) {
  if (confirm('&iquest;Eliminar "' + nombre + '"?')) {
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
