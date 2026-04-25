<?php
// modulos/extracurriculares/asignaciones/programa.php
// Asignar un tallerista principal + apoyo a TODAS las sesiones de un programa
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_programas';
$U           = BASE_URL;

$programa_id = (int)($_GET['programa'] ?? $_POST['programa_id'] ?? 0);
if (!$programa_id) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

$ph = $pdo->prepare("SELECT p.*, ct.nombre AS contrato_nombre, cl.nombre AS cliente_nombre
                     FROM ec_programas p
                     JOIN ec_contratos ct ON ct.id = p.contrato_id
                     JOIN ec_clientes cl  ON cl.id = ct.cliente_id
                     WHERE p.id = ?");
$ph->execute([$programa_id]);
$programa = $ph->fetch();
if (!$programa) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

$sesiones = $pdo->prepare("SELECT id, numero_sesion, fecha, hora_inicio, hora_fin, estado
                           FROM ec_sesiones WHERE programa_id = ? ORDER BY numero_sesion");
$sesiones->execute([$programa_id]);
$lista_ses = $sesiones->fetchAll();

$errores = [];
$conflictos_detectados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $principal_id = (int)($_POST['principal_id'] ?? 0);
    $apoyo_id     = (int)($_POST['apoyo_id']     ?? 0);
    $solo_sin     = isset($_POST['solo_sin_asignar']);
    $force        = isset($_POST['force_save']);

    if (!$principal_id) {
        $errores[] = 'Debes seleccionar un tallerista principal.';
    } elseif ($apoyo_id && $apoyo_id == $principal_id) {
        $errores[] = 'El tallerista de apoyo no puede ser el mismo que el principal.';
    }

    if (empty($errores)) {
        // Detectar conflictos en todas las sesiones objetivo
        $ids = array_filter([$principal_id, $apoyo_id]);
        foreach ($lista_ses as $s) {
            if ($solo_sin) {
                $ya = $pdo->prepare("SELECT COUNT(*) FROM ec_asignaciones WHERE sesion_id = ?");
                $ya->execute([$s['id']]);
                if ($ya->fetchColumn() > 0) continue;
            }
            foreach ($ids as $tid) {
                $ch = $pdo->prepare("SELECT s2.id, s2.hora_inicio, s2.hora_fin, s2.fecha,
                                      p2.nombre AS programa_nombre, cl2.nombre AS cliente_nombre
                                      FROM ec_asignaciones asg
                                      JOIN ec_sesiones s2 ON s2.id = asg.sesion_id
                                      JOIN ec_programas p2 ON p2.id = s2.programa_id
                                      JOIN ec_contratos ct2 ON ct2.id = p2.contrato_id
                                      JOIN ec_clientes cl2 ON cl2.id = ct2.cliente_id
                                      WHERE asg.tallerista_id = ?
                                        AND s2.fecha = ?
                                        AND s2.id != ?
                                        AND s2.hora_inicio < ?
                                        AND s2.hora_fin > ?
                                        AND s2.programa_id != ?");
                $ch->execute([$tid, $s['fecha'], $s['id'], $s['hora_fin'], $s['hora_inicio'], $programa_id]);
                $choques = $ch->fetchAll();
                foreach ($choques as $c) {
                    $u = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
                    $u->execute([$tid]);
                    $conflictos_detectados[] = [
                        'tallerista' => $u->fetchColumn(),
                        'sesion_local' => $s,
                        'sesion_choque' => $c
                    ];
                }
            }
        }

        if (!empty($conflictos_detectados) && !$force) {
            // mostrar advertencia y requerir confirmacion
        } else {
            $n_asignadas = 0;
            foreach ($lista_ses as $s) {
                if ($solo_sin) {
                    $ya = $pdo->prepare("SELECT COUNT(*) FROM ec_asignaciones WHERE sesion_id = ?");
                    $ya->execute([$s['id']]);
                    if ($ya->fetchColumn() > 0) continue;
                }
                // Borrar previas si existian
                $pdo->prepare("DELETE FROM ec_asignaciones WHERE sesion_id = ?")->execute([$s['id']]);

                $pdo->prepare("INSERT INTO ec_asignaciones (sesion_id, tallerista_id, rol) VALUES (?,?,'principal')")
                    ->execute([$s['id'], $principal_id]);

                if ($apoyo_id) {
                    $pdo->prepare("INSERT INTO ec_asignaciones (sesion_id, tallerista_id, rol) VALUES (?,?,'apoyo')")
                        ->execute([$s['id'], $apoyo_id]);
                }
                $n_asignadas++;
            }
            header('Location: ' . $U . 'modulos/extracurriculares/programas/ver.php?id=' . $programa_id . '&msg=asignado_masivo&n=' . $n_asignadas);
            exit;
        }
    }
}

$talleristas = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'docente' AND activo = 1 ORDER BY nombre")->fetchAll();

$titulo = 'Asignar tallerista al programa completo';
$color_prog = $programa['color'] ?: '#7c3aed';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    Asignar al programa
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $programa_id ?>"><?= h($programa['nombre']) ?></a>
      <i class="bi bi-chevron-right"></i>
      Asignaci&oacute;n masiva
    </span></small>
  </div>
</header>
<main class="main-content">

  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger">
      <strong><i class="bi bi-exclamation-circle-fill"></i></strong> <?= h($errores[0]) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($conflictos_detectados)): ?>
    <div class="alert-rsal alert-danger" style="border-left:4px solid #E24B4A;background:#fde3e4;">
      <div style="font-weight:700;color:#791F1F;margin-bottom:.5rem;font-size:.95rem;">
        <i class="bi bi-exclamation-triangle-fill"></i> Conflictos detectados (<?= count($conflictos_detectados) ?>)
      </div>
      <div style="max-height:200px;overflow-y:auto;">
      <?php foreach ($conflictos_detectados as $cc):
        $sl = $cc['sesion_local']; $sc = $cc['sesion_choque'];
      ?>
        <div style="font-size:.8rem;color:#791F1F;margin-bottom:.4rem;padding-bottom:.4rem;border-bottom:1px solid #fca5a5;">
          <strong><?= h($cc['tallerista']) ?></strong> el <?= date('d/m/Y', strtotime($sl['fecha'])) ?>:
          Esta sesi&oacute;n <?= substr($sl['hora_inicio'],0,5) ?>&ndash;<?= substr($sl['hora_fin'],0,5) ?>
          choca con <?= h($sc['programa_nombre']) ?> en <?= h($sc['cliente_nombre']) ?>
          (<?= substr($sc['hora_inicio'],0,5) ?>&ndash;<?= substr($sc['hora_fin'],0,5) ?>).
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (empty($lista_ses)): ?>
    <div class="empty-state">
      <i class="bi bi-calendar-x"></i>
      <h3>No hay sesiones para asignar</h3>
      <p>Primero genera las sesiones del programa.</p>
      <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $programa_id ?>" class="btn-rsal-primary">Volver</a>
    </div>
  <?php else: ?>

  <form method="POST">
    <input type="hidden" name="programa_id" value="<?= $programa_id ?>"/>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.4rem;align-items:start;">

      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-people-fill"></i> Asignaci&oacute;n para todas las sesiones</div>

        <label class="field-label">Tallerista principal <span class="req">*</span></label>
        <select name="principal_id" class="rsal-select" required>
          <option value="0">&mdash; selecciona &mdash;</option>
          <?php foreach ($talleristas as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($_POST['principal_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>>
              <?= h($t['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label class="field-label" style="margin-top:.8rem;">Tallerista de apoyo (opcional)</label>
        <select name="apoyo_id" class="rsal-select">
          <option value="0">Sin apoyo</option>
          <?php foreach ($talleristas as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($_POST['apoyo_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>>
              <?= h($t['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div style="margin-top:1rem;padding:.7rem 1rem;background:var(--gray);border-radius:8px;">
          <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer;font-size:.82rem;">
            <input type="checkbox" name="solo_sin_asignar" value="1" <?= isset($_POST['solo_sin_asignar']) ? 'checked' : '' ?>/>
            <span>Solo asignar a sesiones que <strong>no tienen tallerista</strong> (dejar intactas las que ya est&aacute;n asignadas)</span>
          </label>
        </div>

        <?php if (!empty($conflictos_detectados)): ?>
          <div style="margin-top:1rem;padding:.8rem 1rem;background:#fff7ed;border-left:4px solid #F26522;border-radius:8px;">
            <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer;font-size:.85rem;color:#7c2d12;">
              <input type="checkbox" name="force_save" value="1"/>
              <span><strong>Confirmo los conflictos y quiero guardar de todos modos.</strong> El calendario marcar&aacute; las sesiones en rojo.</span>
            </label>
          </div>
        <?php endif; ?>
      </div>

      <div>
        <div class="card-rsal" style="border-left:4px solid <?= h($color_prog) ?>;">
          <div class="card-rsal-title" style="font-size:.85rem;"><i class="bi bi-bookmark-check"></i> Programa</div>
          <div style="font-weight:700;color:var(--dark);font-size:.88rem;margin-bottom:.3rem;">
            <?= h($programa['nombre']) ?>
          </div>
          <div style="font-size:.75rem;color:var(--muted);">
            <?= h($programa['cliente_nombre']) ?>
          </div>
          <div style="margin-top:.5rem;padding-top:.5rem;border-top:1px solid var(--border);font-size:.78rem;">
            <strong style="color:var(--dark);"><?= count($lista_ses) ?></strong> sesiones <span style="color:var(--muted);">en total</span>
          </div>
        </div>

        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.75rem;">
            <i class="bi bi-check-lg"></i> Asignar a todas
          </button>
          <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $programa_id ?>" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.55rem;margin-top:.5rem;">Cancelar</a>
        </div>
      </div>

    </div>
  </form>

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
