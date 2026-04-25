<?php
// modulos/extracurriculares/asignaciones/sesion.php
// Asignar talleristas principal + apoyo a UNA sesion especifica
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
require_once ROOT . '/includes/ec_helpers.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_calendario';
$U           = BASE_URL;

$sesion_id = (int)($_GET['sesion'] ?? $_POST['sesion_id'] ?? 0);
if (!$sesion_id) { header('Location: ' . $U . 'modulos/extracurriculares/calendario/index.php'); exit; }

$ss = $pdo->prepare("SELECT s.*, p.nombre AS programa_nombre, p.color AS programa_color, p.id AS programa_id,
                     ct.nombre AS contrato_nombre, cl.nombre AS cliente_nombre
                     FROM ec_sesiones s
                     JOIN ec_programas p ON p.id = s.programa_id
                     JOIN ec_contratos ct ON ct.id = p.contrato_id
                     JOIN ec_clientes cl ON cl.id = ct.cliente_id
                     WHERE s.id = ?");
$ss->execute([$sesion_id]);
$S = $ss->fetch();
if (!$S) { header('Location: ' . $U . 'modulos/extracurriculares/calendario/index.php'); exit; }

$errores = [];
$confirmar_conflicto = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $principal_id = (int)($_POST['principal_id'] ?? 0);
    $apoyo_id     = (int)($_POST['apoyo_id']     ?? 0);
    $force        = isset($_POST['force_save']);

    if (!$principal_id) {
        $errores[] = 'Debes seleccionar un tallerista principal.';
    } elseif ($apoyo_id && $apoyo_id == $principal_id) {
        $errores[] = 'El tallerista de apoyo no puede ser el mismo que el principal.';
    }

    if (empty($errores)) {
        // Detectar conflictos
        $conflictos = [];
        $ids = array_filter([$principal_id, $apoyo_id]);
        foreach ($ids as $tid) {
            $ch = $pdo->prepare("SELECT s2.id, s2.hora_inicio, s2.hora_fin, s2.fecha,
                                  p2.nombre AS programa_nombre, cl2.nombre AS cliente_nombre,
                                  asg.rol
                                  FROM ec_asignaciones asg
                                  JOIN ec_sesiones s2 ON s2.id = asg.sesion_id
                                  JOIN ec_programas p2 ON p2.id = s2.programa_id
                                  JOIN ec_contratos ct2 ON ct2.id = p2.contrato_id
                                  JOIN ec_clientes cl2 ON cl2.id = ct2.cliente_id
                                  WHERE asg.tallerista_id = ?
                                    AND s2.fecha = ?
                                    AND s2.id != ?
                                    AND s2.hora_inicio < ?
                                    AND s2.hora_fin > ?");
            $ch->execute([$tid, $S['fecha'], $sesion_id, $S['hora_fin'], $S['hora_inicio']]);
            $choques = $ch->fetchAll();
            foreach ($choques as $c) {
                $u = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
                $u->execute([$tid]);
                $conflictos[] = [
                    'tallerista' => $u->fetchColumn(),
                    'detalle'    => $c
                ];
            }
        }

        if (!empty($conflictos) && !$force) {
            $confirmar_conflicto = $conflictos;
        } else {
            // Borrar asignaciones previas
            $pdo->prepare("DELETE FROM ec_asignaciones WHERE sesion_id = ?")->execute([$sesion_id]);

            // Insertar principal
            $pdo->prepare("INSERT INTO ec_asignaciones (sesion_id, tallerista_id, rol) VALUES (?,?,'principal')")
                ->execute([$sesion_id, $principal_id]);

            // Insertar apoyo si existe
            if ($apoyo_id) {
                $pdo->prepare("INSERT INTO ec_asignaciones (sesion_id, tallerista_id, rol) VALUES (?,?,'apoyo')")
                    ->execute([$sesion_id, $apoyo_id]);
            }

            $volver = $_POST['volver'] ?? '';
            if ($volver === 'calendario') {
                header('Location: ' . $U . 'modulos/extracurriculares/calendario/index.php?msg=asignado');
            } else {
                header('Location: ' . $U . 'modulos/extracurriculares/programas/ver.php?id=' . $S['programa_id'] . '&msg=asignado');
            }
            exit;
        }
    }
}

// Cargar asignaciones actuales
$act = $pdo->prepare("SELECT a.rol, a.tallerista_id FROM ec_asignaciones a WHERE a.sesion_id = ?");
$act->execute([$sesion_id]);
$actuales = $act->fetchAll();

$principal_actual = 0;
$apoyo_actual     = 0;
foreach ($actuales as $a) {
    if ($a['rol'] === 'principal') $principal_actual = $a['tallerista_id'];
    if ($a['rol'] === 'apoyo')     $apoyo_actual     = $a['tallerista_id'];
}

$talleristas = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'docente' AND activo = 1 ORDER BY nombre")->fetchAll();

$volver_origen = $_GET['volver'] ?? 'programa';

$titulo = 'Asignar talleristas';
$color_prog = $S['programa_color'] ?: '#7c3aed';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    Asignar talleristas
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $S['programa_id'] ?>"><?= h($S['programa_nombre']) ?></a>
      <i class="bi bi-chevron-right"></i>
      Sesi&oacute;n #<?= $S['numero_sesion'] ?>
    </span></small>
  </div>
</header>
<main class="main-content">

  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger">
      <strong><i class="bi bi-exclamation-circle-fill"></i></strong> <?= h($errores[0]) ?>
    </div>
  <?php endif; ?>

  <?php if ($confirmar_conflicto): ?>
    <div class="alert-rsal alert-danger" style="border-left:4px solid #E24B4A;background:#fde3e4;">
      <div style="font-weight:700;color:#791F1F;margin-bottom:.5rem;font-size:.95rem;">
        <i class="bi bi-exclamation-triangle-fill"></i> Conflicto de horario detectado
      </div>
      <?php foreach ($confirmar_conflicto as $cc):
        $d = $cc['detalle'];
      ?>
        <div style="font-size:.85rem;color:#791F1F;margin-bottom:.3rem;">
          <strong><?= h($cc['tallerista']) ?></strong> ya est&aacute; asignado como <strong><?= h($d['rol']) ?></strong> en:
          <br>
          &raquo; <?= date('d/m/Y', strtotime($d['fecha'])) ?> de <?= substr($d['hora_inicio'],0,5) ?> a <?= substr($d['hora_fin'],0,5) ?>
          &middot; <?= h($d['programa_nombre']) ?> (<?= h($d['cliente_nombre']) ?>)
        </div>
      <?php endforeach; ?>
      <div style="margin-top:.7rem;font-size:.85rem;color:#791F1F;">
        Esta sesi&oacute;n es de <strong><?= date('d/m/Y', strtotime($S['fecha'])) ?> de <?= substr($S['hora_inicio'],0,5) ?> a <?= substr($S['hora_fin'],0,5) ?></strong>.
        Las sesiones se traslapan en el tiempo.
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" id="form-asignacion">
    <input type="hidden" name="sesion_id" value="<?= $sesion_id ?>"/>
    <input type="hidden" name="volver"    value="<?= h($volver_origen) ?>"/>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.4rem;align-items:start;">

      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-person-plus-fill"></i> Asignaci&oacute;n de esta sesi&oacute;n</div>

        <label class="field-label">Tallerista principal <span class="req">*</span></label>
        <select name="principal_id" class="rsal-select" required>
          <option value="0">&mdash; selecciona &mdash;</option>
          <?php foreach ($talleristas as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($_POST['principal_id'] ?? $principal_actual) == $t['id'] ? 'selected' : '' ?>>
              <?= h($t['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div style="font-size:.7rem;color:var(--muted);margin-top:.2rem;">
          Persona responsable de dictar la sesi&oacute;n.
        </div>

        <label class="field-label" style="margin-top:.8rem;">Tallerista de apoyo (opcional)</label>
        <select name="apoyo_id" class="rsal-select">
          <option value="0">Sin apoyo</option>
          <?php foreach ($talleristas as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($_POST['apoyo_id'] ?? $apoyo_actual) == $t['id'] ? 'selected' : '' ?>>
              <?= h($t['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div style="font-size:.7rem;color:var(--muted);margin-top:.2rem;">
          Persona adicional que acompa&ntilde;a ese d&iacute;a.
        </div>

        <?php if ($confirmar_conflicto): ?>
          <div style="margin-top:1.2rem;padding:.8rem 1rem;background:#fff7ed;border-left:4px solid #F26522;border-radius:8px;">
            <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer;font-size:.85rem;color:#7c2d12;">
              <input type="checkbox" name="force_save" value="1" id="chk-force" style="margin-top:.2rem;"/>
              <span><strong>Confirmo que quiero asignar a pesar del conflicto.</strong> El sistema guardar&aacute; la asignaci&oacute;n pero el calendario marcar&aacute; las sesiones en rojo.</span>
            </label>
          </div>
        <?php endif; ?>

      </div>

      <div>
        <div class="card-rsal" style="border-left:4px solid <?= h($color_prog) ?>;">
          <div class="card-rsal-title" style="font-size:.85rem;"><i class="bi bi-calendar-event"></i> Sesi&oacute;n</div>
          <div style="font-weight:700;color:var(--dark);font-size:.85rem;margin-bottom:.3rem;">
            <?= h($S['programa_nombre']) ?> &middot; #<?= $S['numero_sesion'] ?>
          </div>
          <div style="font-size:.75rem;color:var(--muted);">
            <?= h($S['cliente_nombre']) ?>
          </div>
          <div style="margin-top:.5rem;padding-top:.5rem;border-top:1px solid var(--border);font-size:.78rem;">
            <strong style="color:var(--dark);"><?= date('d/m/Y', strtotime($S['fecha'])) ?></strong><br>
            <span style="color:var(--muted);"><?= substr($S['hora_inicio'],0,5) ?> &ndash; <?= substr($S['hora_fin'],0,5) ?></span>
          </div>
        </div>

        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.75rem;" id="btn-guardar">
            <i class="bi bi-check-lg"></i> Guardar asignaci&oacute;n
          </button>
          <a href="<?= $U ?>modulos/extracurriculares/<?= $volver_origen === 'calendario' ? 'calendario/index.php' : 'programas/ver.php?id='.$S['programa_id'] ?>"
             class="btn-rsal-secondary" style="width:100%;justify-content:center;padding:.55rem;margin-top:.5rem;">Cancelar</a>
        </div>
      </div>

    </div>
  </form>

</main>
<script>
<?php if ($confirmar_conflicto): ?>
document.getElementById('btn-guardar').addEventListener('click', function(e) {
  const chk = document.getElementById('chk-force');
  if (!chk.checked) {
    e.preventDefault();
    alert('Debes confirmar que quieres guardar a pesar del conflicto de horario marcando la casilla.');
  }
});
<?php endif; ?>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
