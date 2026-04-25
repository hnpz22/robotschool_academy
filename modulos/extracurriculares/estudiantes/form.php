<?php
// modulos/extracurriculares/estudiantes/form.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_programas';
$U           = BASE_URL;

$id          = (int)($_GET['id']       ?? 0);
$programa_id = (int)($_GET['programa'] ?? 0);
$estudiante  = null;

if ($id) {
    $s = $pdo->prepare("SELECT * FROM ec_estudiantes WHERE id = ?");
    $s->execute([$id]);
    $estudiante = $s->fetch();
    if (!$estudiante) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }
    $programa_id = $estudiante['programa_id'];
}

if (!$programa_id) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

$ph = $pdo->prepare("SELECT p.*, ct.nombre AS contrato_nombre, cl.nombre AS cliente_nombre
                     FROM ec_programas p
                     JOIN ec_contratos ct ON ct.id = p.contrato_id
                     JOIN ec_clientes cl  ON cl.id = ct.cliente_id
                     WHERE p.id = ?");
$ph->execute([$programa_id]);
$programa = $ph->fetch();
if (!$programa) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

$titulo  = $estudiante ? 'Editar estudiante' : 'Agregar estudiante';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $grado           = trim($_POST['grado']           ?? '');
    $edad            = $_POST['edad']            !== '' ? (int)$_POST['edad']        : null;
    $fecha_ingreso   = $_POST['fecha_ingreso']   ?: date('Y-m-d');
    $documento       = trim($_POST['documento']       ?? '');
    $observaciones   = trim($_POST['observaciones']   ?? '');
    $activo          = isset($_POST['activo']) ? 1 : 0;

    if (!$nombre_completo) $errores[] = 'El nombre es obligatorio.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE ec_estudiantes SET nombre_completo=?, grado=?, edad=?, fecha_ingreso=?, documento=?, observaciones=?, activo=? WHERE id=?")
                ->execute([$nombre_completo, $grado, $edad, $fecha_ingreso, $documento, $observaciones, $activo, $id]);
        } else {
            $pdo->prepare("INSERT INTO ec_estudiantes (programa_id, nombre_completo, grado, edad, fecha_ingreso, documento, observaciones, activo) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$programa_id, $nombre_completo, $grado, $edad, $fecha_ingreso, $documento, $observaciones, $activo]);
        }
        header('Location: ' . $U . "modulos/extracurriculares/programas/ver.php?id=$programa_id&msg=est_creado");
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $programa_id ?>"><?= h($programa['nombre']) ?></a>
      <i class="bi bi-chevron-right"></i>
      <?= $estudiante ? h($estudiante['nombre_completo']) : 'Nuevo' ?>
    </span></small>
  </div>
</header>
<main class="main-content">

  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger">
      <strong><i class="bi bi-exclamation-circle-fill"></i></strong>
      <?= h($errores[0]) ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 280px;gap:1.4rem;align-items:start;">

      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-person-fill"></i> Datos del estudiante</div>

          <label class="field-label">Nombre completo <span class="req">*</span></label>
          <input type="text" name="nombre_completo" class="rsal-input" required maxlength="150"
                 value="<?= h($estudiante['nombre_completo'] ?? '') ?>"/>

          <div style="display:grid;grid-template-columns:1fr 120px 180px;gap:.8rem;margin-top:.6rem;">
            <div>
              <label class="field-label">Grado</label>
              <input type="text" name="grado" class="rsal-input" maxlength="30"
                     placeholder="Ej 3o"
                     value="<?= h($estudiante['grado'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Edad</label>
              <input type="number" name="edad" class="rsal-input" min="3" max="25"
                     value="<?= h($estudiante['edad'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Documento (opcional)</label>
              <input type="text" name="documento" class="rsal-input" maxlength="30"
                     value="<?= h($estudiante['documento'] ?? '') ?>"/>
            </div>
          </div>

          <label class="field-label" style="margin-top:.6rem;">Fecha de ingreso al programa</label>
          <input type="date" name="fecha_ingreso" class="rsal-input" style="max-width:200px;"
                 value="<?= h($estudiante['fecha_ingreso'] ?? date('Y-m-d')) ?>"/>
          <div style="font-size:.7rem;color:var(--muted);margin-top:.2rem;">
            Se usa para no descontar asistencia de sesiones anteriores a su ingreso.
          </div>

          <label class="field-label" style="margin-top:.6rem;">Observaciones</label>
          <textarea name="observaciones" class="rsal-textarea" style="min-height:60px;"
                    maxlength="255"><?= h($estudiante['observaciones'] ?? '') ?></textarea>
        </div>
      </div>

      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-toggle-on"></i> Estado</div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.7rem 1rem;background:var(--gray);border-radius:10px;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Activo</div>
              <div style="font-size:.72rem;color:var(--muted);">Aparece en asistencia</div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer;">
              <input type="checkbox" name="activo" style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($estudiante['activo']??1) ? 'checked' : '' ?>
                     onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
              <span style="position:absolute;inset:0;background:<?= ($estudiante['activo']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($estudiante['activo']??1)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.75rem;font-size:.9rem;">
            <i class="bi bi-check-lg"></i> <?= $estudiante ? 'Guardar' : 'Agregar estudiante' ?>
          </button>
          <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $programa_id ?>" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.55rem;margin-top:.5rem;">Cancelar</a>
        </div>
      </div>

    </div>
  </form>
</main>
<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
