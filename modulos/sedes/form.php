<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_general');

$titulo      = 'Sede';
$menu_activo = 'sedes';
$U           = BASE_URL;

$id    = (int)($_GET['id'] ?? 0);
$sede  = null;
if ($id) {
    $s = $pdo->prepare("SELECT * FROM sedes WHERE id=?");
    $s->execute([$id]); $sede = $s->fetch();
    if (!$sede) { header('Location:'.$U.'modulos/sedes/index.php'); exit; }
}

$titulo  = $sede ? 'Editar: '.h($sede['nombre']) : 'Nueva sede';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']    ?? '');
    $ciudad    = trim($_POST['ciudad']    ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $activa    = isset($_POST['activa']) ? 1 : 0;

    if (!$nombre) $errores[] = 'El nombre es obligatorio.';
    if (!$ciudad) $errores[] = 'La ciudad es obligatoria.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE sedes SET nombre=?,ciudad=?,direccion=?,telefono=?,email=?,activa=? WHERE id=?")
                ->execute([$nombre,$ciudad,$direccion,$telefono,$email,$activa,$id]);
        } else {
            $pdo->prepare("INSERT INTO sedes (nombre,ciudad,direccion,telefono,email,activa) VALUES (?,?,?,?,?,?)")
                ->execute([$nombre,$ciudad,$direccion,$telefono,$email,$activa]);
        }
        header('Location:'.$U.'modulos/sedes/index.php?msg='.($sede?'editada':'creada'));
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title"><?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/sedes/index.php">Sedes</a>
      <i class="bi bi-chevron-right"></i>
      <?= $sede ? h($sede['nombre']) : 'Nueva' ?>
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
        <div class="card-rsal-title"><i class="bi bi-geo-alt-fill"></i> Datos de la sede</div>
        <label class="field-label">Nombre <span class="req">*</span></label>
        <input type="text" name="nombre" class="rsal-input" required placeholder="Ej: Sede Norte 136"
               value="<?= h($sede['nombre'] ?? '') ?>"/>
        <label class="field-label">Ciudad <span class="req">*</span></label>
        <input type="text" name="ciudad" class="rsal-input" required placeholder="Ej: Bogot&aacute;"
               value="<?= h($sede['ciudad'] ?? '') ?>"/>
        <label class="field-label">Direcci&oacute;n</label>
        <input type="text" name="direccion" class="rsal-input" placeholder="Direcci&oacute;n completa"
               value="<?= h($sede['direccion'] ?? '') ?>"/>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
          <div>
            <label class="field-label">Tel&eacute;fono</label>
            <input type="text" name="telefono" class="rsal-input" placeholder="Tel. de la sede"
                   value="<?= h($sede['telefono'] ?? '') ?>"/>
          </div>
          <div>
            <label class="field-label">Email</label>
            <input type="email" name="email" class="rsal-input" placeholder="email@robotschool.com.co"
                   value="<?= h($sede['email'] ?? '') ?>"/>
          </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;margin-bottom:.9rem;">
          <div>
            <div style="font-size:.85rem;font-weight:700;">Sede activa</div>
            <div style="font-size:.72rem;color:var(--muted);">Visible para inscripciones</div>
          </div>
          <label style="position:relative;width:44px;height:24px;cursor:pointer;">
            <input type="checkbox" name="activa" style="opacity:0;width:0;height:0;position:absolute;"
                   <?= ($sede['activa']??1)?'checked':'' ?>
                   onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
            <span style="position:absolute;inset:0;background:<?= ($sede['activa']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
              <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($sede['activa']??1)?'translateX(20px)':'' ?>;"></span>
            </span>
          </label>
        </div>
        <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;">
          <i class="bi bi-check-lg"></i> <?= $sede?'Guardar cambios':'Crear sede' ?>
        </button>
        <a href="<?= $U ?>modulos/sedes/index.php" class="btn-rsal-secondary"
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
