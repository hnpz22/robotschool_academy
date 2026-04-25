<?php
// modulos/extracurriculares/sesiones/manual.php
// Crear una sesion individual con fecha y hora libre (recuperaciones, sesiones extra)
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_programas';
$U           = BASE_URL;

$programa_id = (int)($_GET['programa'] ?? 0);
$sesion_id   = (int)($_GET['id']       ?? 0);
$sesion      = null;

if ($sesion_id) {
    $s = $pdo->prepare("SELECT * FROM ec_sesiones WHERE id = ?");
    $s->execute([$sesion_id]);
    $sesion = $s->fetch();
    if (!$sesion) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }
    $programa_id = $sesion['programa_id'];
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

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha         = $_POST['fecha']         ?? '';
    $hora_inicio   = $_POST['hora_inicio']   ?? $programa['hora_inicio'];
    $hora_fin      = $_POST['hora_fin']      ?? $programa['hora_fin'];
    $tema_planeado = trim($_POST['tema_planeado'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $es_recuperacion = isset($_POST['es_recuperacion']);

    if (!$fecha)       $errores[] = 'La fecha es obligatoria.';
    if (!$hora_inicio) $errores[] = 'La hora de inicio es obligatoria.';
    if (!$hora_fin)    $errores[] = 'La hora de fin es obligatoria.';
    if ($hora_fin <= $hora_inicio) $errores[] = 'La hora de fin debe ser posterior al inicio.';

    if (empty($errores)) {
        if ($sesion_id) {
            $pdo->prepare("UPDATE ec_sesiones SET fecha=?, hora_inicio=?, hora_fin=?, tema_planeado=?, observaciones=? WHERE id=?")
                ->execute([$fecha, $hora_inicio, $hora_fin, $tema_planeado, $observaciones, $sesion_id]);
        } else {
            // Numero de sesion = ultima + 1
            $num = $pdo->prepare("SELECT COALESCE(MAX(numero_sesion),0) + 1 FROM ec_sesiones WHERE programa_id = ?");
            $num->execute([$programa_id]);
            $numero_sesion = (int)$num->fetchColumn();

            $pdo->prepare("INSERT INTO ec_sesiones (programa_id, numero_sesion, fecha, hora_inicio, hora_fin, tema_planeado, observaciones, estado) VALUES (?,?,?,?,?,?,?, 'programada')")
                ->execute([$programa_id, $numero_sesion, $fecha, $hora_inicio, $hora_fin, $tema_planeado, $observaciones]);

            $pdo->prepare("UPDATE ec_programas SET total_sesiones = total_sesiones + 1 WHERE id = ?")
                ->execute([$programa_id]);
        }
        header('Location: ' . $U . "modulos/extracurriculares/programas/ver.php?id=$programa_id&msg=ses_manual_ok");
        exit;
    }
}

$titulo = $sesion ? 'Editar sesi&oacute;n' : 'Nueva sesi&oacute;n manual';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $programa_id ?>"><?= h($programa['nombre']) ?></a>
      <i class="bi bi-chevron-right"></i>
      <?= $sesion ? 'Sesi&oacute;n #' . $sesion['numero_sesion'] : 'Nueva' ?>
    </span></small>
  </div>
</header>
<main class="main-content">

  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger">
      <strong><i class="bi bi-exclamation-circle-fill"></i></strong> <?= h($errores[0]) ?>
    </div>
  <?php endif; ?>

  <?php if (!$sesion): ?>
    <div class="alert-rsal alert-info" style="margin-bottom:1rem;">
      <i class="bi bi-info-circle-fill"></i>
      Usa esta opci&oacute;n para crear una sesi&oacute;n puntual en una fecha espec&iacute;fica: recuperaciones,
      sesiones de reposici&oacute;n o cualquier clase extra que no siga el patr&oacute;n semanal del programa.
    </div>
  <?php endif; ?>

  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 280px;gap:1.4rem;align-items:start;">

      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-calendar-plus-fill"></i> Datos de la sesi&oacute;n</div>

        <label class="field-label">Fecha <span class="req">*</span></label>
        <input type="date" name="fecha" class="rsal-input" required style="max-width:220px;"
               value="<?= h($sesion['fecha'] ?? $_POST['fecha'] ?? date('Y-m-d')) ?>"/>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-top:.6rem;">
          <div>
            <label class="field-label">Hora inicio <span class="req">*</span></label>
            <input type="time" name="hora_inicio" class="rsal-input" required
                   value="<?= h(substr($sesion['hora_inicio'] ?? $_POST['hora_inicio'] ?? $programa['hora_inicio'], 0, 5)) ?>"/>
          </div>
          <div>
            <label class="field-label">Hora fin <span class="req">*</span></label>
            <input type="time" name="hora_fin" class="rsal-input" required
                   value="<?= h(substr($sesion['hora_fin'] ?? $_POST['hora_fin'] ?? $programa['hora_fin'], 0, 5)) ?>"/>
          </div>
        </div>

        <label class="field-label" style="margin-top:.6rem;">Tema planeado</label>
        <input type="text" name="tema_planeado" class="rsal-input" maxlength="255"
               placeholder="Ej. Recuperaci&oacute;n sesi&oacute;n #3"
               value="<?= h($sesion['tema_planeado'] ?? $_POST['tema_planeado'] ?? '') ?>"/>

        <label class="field-label" style="margin-top:.6rem;">Observaciones</label>
        <textarea name="observaciones" class="rsal-textarea" style="min-height:60px;"
                  maxlength="255"><?= h($sesion['observaciones'] ?? $_POST['observaciones'] ?? '') ?></textarea>
      </div>

      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-info-circle"></i> Programa</div>
          <div style="font-weight:700;color:var(--dark);font-size:.9rem;"><?= h($programa['nombre']) ?></div>
          <div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;"><?= h($programa['cliente_nombre']) ?></div>
          <div style="font-size:.72rem;color:var(--muted);margin-top:.5rem;padding-top:.5rem;border-top:1px solid var(--border);">
            Horario habitual:<br>
            <strong style="color:var(--dark);"><?= substr($programa['hora_inicio'],0,5) ?> &ndash; <?= substr($programa['hora_fin'],0,5) ?></strong>
          </div>
        </div>

        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.75rem;">
            <i class="bi bi-check-lg"></i> <?= $sesion ? 'Guardar cambios' : 'Crear sesi&oacute;n' ?>
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
