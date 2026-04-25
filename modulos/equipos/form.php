<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Equipo';
$menu_activo = 'equipos';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$sedes       = $pdo->query("SELECT * FROM sedes WHERE activa=1 ORDER BY nombre")->fetchAll();

$id      = (int)($_GET['id'] ?? 0);
$equipo  = null;
if ($id) {
    $s = $pdo->prepare("SELECT * FROM equipos WHERE id=?");
    $s->execute([$id]); $equipo = $s->fetch();
    if (!$equipo || ($sede_filtro && $equipo['sede_id'] != $sede_filtro)) {
        header('Location: '.$U.'modulos/equipos/index.php'); exit;
    }
}

$titulo  = $equipo ? 'Editar: '.h($equipo['nombre']) : 'Nuevo equipo';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sede_id        = (int)($_POST['sede_id']         ?? ($sede_filtro ?: 0));
    $nombre         = trim($_POST['nombre']            ?? '');
    $descripcion    = trim($_POST['descripcion']       ?? '');
    $cantidad_total = (int)($_POST['cantidad_total']   ?? 1);
    $activo         = isset($_POST['activo']) ? 1 : 0;

    if (!$nombre)         $errores[] = 'El nombre es obligatorio.';
    if (!$sede_id)        $errores[] = 'Selecciona una sede.';
    if ($cantidad_total < 1) $errores[] = 'La cantidad debe ser al menos 1.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE equipos SET sede_id=?,nombre=?,descripcion=?,cantidad_total=?,activo=? WHERE id=?")
                ->execute([$sede_id,$nombre,$descripcion,$cantidad_total,$activo,$id]);
        } else {
            $pdo->prepare("INSERT INTO equipos (sede_id,nombre,descripcion,cantidad_total,activo) VALUES (?,?,?,?,?)")
                ->execute([$sede_id,$nombre,$descripcion,$cantidad_total,$activo]);
        }
        header('Location: '.$U.'modulos/equipos/index.php?msg='.($equipo?'editado':'creado'));
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $equipo ? 'Editar equipo' : 'Nuevo equipo' ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/equipos/index.php">Equipos</a>
      <i class="bi bi-chevron-right"></i>
      <?= $equipo ? h($equipo['nombre']) : 'Nuevo' ?>
    </span></small>
  </div>
</header>
<main class="main-content">
  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger" style="flex-direction:column;align-items:flex-start;">
      <strong><i class="bi bi-exclamation-circle-fill"></i> Errores:</strong>
      <ul style="margin:.4rem 0 0 1.2rem;"><?php foreach($errores as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <div style="max-width:520px;">
    <form method="POST">
      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-cpu-fill"></i> Datos del equipo</div>

        <label class="field-label">Sede <span class="req">*</span></label>
        <select name="sede_id" class="rsal-select" required>
          <option value="">Selecciona sede...</option>
          <?php foreach($sedes as $s):
            // admin_sede solo ve su propia sede
            if ($_SESSION['usuario_rol'] !== 'admin_general' && $sede_filtro && $s['id'] != $sede_filtro) continue;
          ?>
            <option value="<?= $s['id'] ?>"
              <?= ($equipo['sede_id'] ?? $sede_filtro) == $s['id'] ? 'selected' : '' ?>>
              <?= h($s['nombre']) ?> &mdash; <?= h($s['ciudad']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label class="field-label">Nombre del equipo <span class="req">*</span></label>
        <input type="text" name="nombre" class="rsal-input" required
               placeholder="Ej: LEGO Spike Prime, Arduino UNO, ESP32"
               value="<?= h($equipo['nombre'] ?? '') ?>"/>

        <label class="field-label">Descripci&oacute;n</label>
        <input type="text" name="descripcion" class="rsal-input"
               placeholder="Ej: Kit de rob&oacute;tica educativa para 6-12 a&ntilde;os"
               value="<?= h($equipo['descripcion'] ?? '') ?>"/>

        <label class="field-label">Cantidad total disponible <span class="req">*</span></label>
        <input type="number" name="cantidad_total" class="rsal-input" required min="1"
               value="<?= h($equipo['cantidad_total'] ?? 1) ?>"/>

        <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;margin-bottom:.9rem;">
          <div>
            <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Equipo activo</div>
            <div style="font-size:.72rem;color:var(--muted);">Disponible para asignar a grupos</div>
          </div>
          <label style="position:relative;width:44px;height:24px;cursor:pointer;">
            <input type="checkbox" name="activo" style="opacity:0;width:0;height:0;position:absolute;"
                   <?= ($equipo['activo']??1)?'checked':'' ?>
                   onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
            <span style="position:absolute;inset:0;background:<?= ($equipo['activo']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
              <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($equipo['activo']??1)?'translateX(20px)':'' ?>;"></span>
            </span>
          </label>
        </div>

        <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;">
          <i class="bi bi-check-lg"></i> <?= $equipo?'Guardar cambios':'Registrar equipo' ?>
        </button>
        <a href="<?= $U ?>modulos/equipos/index.php" class="btn-rsal-secondary"
           style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
      </div>
    </form>
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
