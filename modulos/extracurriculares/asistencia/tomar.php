<?php
// modulos/extracurriculares/asistencia/tomar.php
// Toma o edita asistencia de una sesion. Accesible para coordinador y docentes.
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireLogin();

$rol_actual = $_SESSION['usuario_rol'] ?? '';
$uid        = (int)$_SESSION['usuario_id'];

// Coordinador/admin o docente (que puede ser tallerista de extracurriculares)
if (!in_array($rol_actual, ['admin_general','admin_sede','coordinador_pedagogico','docente'])) {
    http_response_code(403);
    die('Acceso denegado.');
}

$menu_activo = 'ec_programas';
$U           = BASE_URL;

$sesion_id = (int)($_GET['sesion'] ?? 0);
if (!$sesion_id) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

$ss = $pdo->prepare("SELECT s.*, p.id AS programa_id, p.nombre AS programa_nombre, p.color AS programa_color,
                     ct.id AS contrato_id, ct.nombre AS contrato_nombre, cl.nombre AS cliente_nombre
                     FROM ec_sesiones s
                     JOIN ec_programas p ON p.id = s.programa_id
                     JOIN ec_contratos ct ON ct.id = p.contrato_id
                     JOIN ec_clientes cl  ON cl.id = ct.cliente_id
                     WHERE s.id = ?");
$ss->execute([$sesion_id]);
$S = $ss->fetch();
if (!$S) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

// Estudiantes del programa cuyo fecha_ingreso <= fecha de la sesion
$estudiantes = $pdo->prepare("SELECT e.*, a.estado AS estado_prev, a.observacion AS obs_prev, a.id AS asist_id
                              FROM ec_estudiantes e
                              LEFT JOIN ec_asistencia a ON a.estudiante_id = e.id AND a.sesion_id = ?
                              WHERE e.programa_id = ? AND e.activo = 1
                                AND (e.fecha_ingreso IS NULL OR e.fecha_ingreso <= ?)
                              ORDER BY e.nombre_completo");
$estudiantes->execute([$sesion_id, $S['programa_id'], $S['fecha']]);
$lista = $estudiantes->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estados_v = ['presente','tarde','ausente','excusa'];

    $ins = $pdo->prepare("INSERT INTO ec_asistencia (sesion_id, estudiante_id, estado, observacion, registrado_por)
                          VALUES (?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE estado = VALUES(estado), observacion = VALUES(observacion), registrado_por = VALUES(registrado_por)");

    foreach ($lista as $e) {
        $est = $_POST["estado_{$e['id']}"] ?? 'ausente';
        if (!in_array($est, $estados_v)) $est = 'ausente';
        $obs = trim($_POST["obs_{$e['id']}"] ?? '');
        $ins->execute([$sesion_id, $e['id'], $est, $obs, $uid]);
    }

    // Marcar sesion como dictada si no lo esta
    if ($S['estado'] === 'programada') {
        $pdo->prepare("UPDATE ec_sesiones SET estado='dictada', registrado_por=? WHERE id=?")
            ->execute([$uid, $sesion_id]);
    }

    header('Location: ' . $U . "modulos/extracurriculares/programas/ver.php?id={$S['programa_id']}&msg=asistencia_ok");
    exit;
}

$titulo = 'Asistencia sesi&oacute;n #' . $S['numero_sesion'];
$color_prog = $S['programa_color'] ?: '#7c3aed';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?> &mdash; <?= date('d/m/Y', strtotime($S['fecha'])) ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $S['programa_id'] ?>"><?= h($S['programa_nombre']) ?></a>
      <i class="bi bi-chevron-right"></i>
      <?= h($S['cliente_nombre']) ?>
    </span></small>
  </div>
</header>
<main class="main-content">

  <div style="background:<?= h($color_prog) ?>15;border-left:4px solid <?= h($color_prog) ?>;border-radius:10px;padding:.9rem 1.2rem;margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
    <div>
      <div style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--dark);font-size:1rem;">
        Sesi&oacute;n #<?= $S['numero_sesion'] ?> &middot; <?= date('D d M Y', strtotime($S['fecha'])) ?>
      </div>
      <div style="font-size:.78rem;color:var(--muted);margin-top:.2rem;">
        <?= substr($S['hora_inicio'],0,5) ?> &ndash; <?= substr($S['hora_fin'],0,5) ?> &middot;
        <?= h($S['programa_nombre']) ?> &middot; <?= count($lista) ?> estudiante<?= count($lista) != 1 ? 's' : '' ?>
      </div>
    </div>
  </div>

  <?php if (empty($lista)): ?>
    <div class="empty-state">
      <i class="bi bi-people"></i>
      <h3>No hay estudiantes activos para esta sesi&oacute;n</h3>
      <p>Agrega estudiantes al programa primero.</p>
      <a href="<?= $U ?>modulos/extracurriculares/estudiantes/masivo.php?programa=<?= $S['programa_id'] ?>" class="btn-rsal-primary">
        <i class="bi bi-clipboard-plus-fill"></i> Carga masiva
      </a>
    </div>
  <?php else: ?>

  <form method="POST">
    <div class="card-rsal" style="padding:0;overflow:hidden;">

      <div style="padding:1rem 1.2rem;border-bottom:1px solid var(--border);display:flex;gap:.6rem;align-items:center;background:var(--gray);flex-wrap:wrap;">
        <span style="font-size:.78rem;font-weight:700;color:var(--muted);">Marcar todos como:</span>
        <button type="button" onclick="marcarTodos('presente')" style="background:#d1fae5;color:#065f46;border:1px solid #86efac;border-radius:7px;padding:.3rem .7rem;font-size:.72rem;font-weight:700;cursor:pointer;">
          <i class="bi bi-check-circle-fill"></i> Presentes
        </button>
        <button type="button" onclick="marcarTodos('ausente')" style="background:#fde3e4;color:#991b1b;border:1px solid #fca5a5;border-radius:7px;padding:.3rem .7rem;font-size:.72rem;font-weight:700;cursor:pointer;">
          <i class="bi bi-x-circle-fill"></i> Ausentes
        </button>
      </div>

      <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead>
          <tr style="background:var(--gray);">
            <th style="padding:.6rem .8rem;text-align:left;font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;font-weight:700;">#</th>
            <th style="padding:.6rem .8rem;text-align:left;font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;font-weight:700;">Estudiante</th>
            <th style="padding:.6rem .8rem;text-align:center;font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;font-weight:700;">Estado</th>
            <th style="padding:.6rem .8rem;text-align:left;font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;font-weight:700;">Observaci&oacute;n</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista as $i => $e):
            $est_prev = $e['estado_prev'] ?: 'presente';
          ?>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:.55rem .8rem;color:var(--muted);font-size:.78rem;"><?= $i+1 ?></td>
            <td style="padding:.55rem .8rem;">
              <div style="font-weight:600;color:var(--dark);"><?= h($e['nombre_completo']) ?></div>
              <?php if ($e['grado'] || $e['edad']): ?>
                <div style="font-size:.7rem;color:var(--muted);">
                  <?= h(trim(($e['grado'] ?? '') . ($e['edad'] ? ' &middot; ' . $e['edad'] . ' a' : ''))) ?>
                </div>
              <?php endif; ?>
            </td>
            <td style="padding:.55rem .8rem;text-align:center;">
              <div class="btn-estados" data-sid="<?= $e['id'] ?>" style="display:inline-flex;gap:.2rem;background:var(--gray);padding:.2rem;border-radius:9px;">
                <?php
                $estados_btn = [
                    'presente' => ['P', '#d1fae5', '#065f46'],
                    'tarde'    => ['T', '#fff7e6', '#92400e'],
                    'ausente'  => ['A', '#fde3e4', '#991b1b'],
                    'excusa'   => ['E', '#e0e7ff', '#3730a3'],
                ];
                foreach ($estados_btn as $est_v => $b):
                  $is_sel = $est_prev === $est_v;
                ?>
                  <button type="button"
                          onclick="seleccionarEstado(<?= $e['id'] ?>, '<?= $est_v ?>')"
                          data-est="<?= $est_v ?>"
                          style="width:30px;height:30px;border:none;border-radius:6px;font-weight:900;cursor:pointer;
                                 background:<?= $is_sel ? $b[1] : 'transparent' ?>;
                                 color:<?= $is_sel ? $b[2] : '#9ca3af' ?>;"
                          title="<?= ucfirst($est_v) ?>"><?= $b[0] ?></button>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="estado_<?= $e['id'] ?>" id="estado_<?= $e['id'] ?>" value="<?= $est_prev ?>"/>
            </td>
            <td style="padding:.55rem .8rem;">
              <input type="text" name="obs_<?= $e['id'] ?>" class="rsal-input"
                     style="padding:.35rem .6rem;font-size:.78rem;"
                     maxlength="255"
                     placeholder="Opcional"
                     value="<?= h($e['obs_prev'] ?? '') ?>"/>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>

      <div style="padding:1rem 1.2rem;border-top:1px solid var(--border);background:var(--gray);display:flex;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
        <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $S['programa_id'] ?>" class="btn-rsal-secondary" style="padding:.6rem 1rem;">
          Cancelar
        </a>
        <button type="submit" class="btn-rsal-primary" style="padding:.6rem 1.4rem;">
          <i class="bi bi-check-lg"></i> Guardar asistencia
        </button>
      </div>

    </div>
  </form>

  <?php endif; ?>

</main>
<script>
const estadosColor = {
  'presente': {bg:'#d1fae5', fg:'#065f46'},
  'tarde':    {bg:'#fff7e6', fg:'#92400e'},
  'ausente':  {bg:'#fde3e4', fg:'#991b1b'},
  'excusa':   {bg:'#e0e7ff', fg:'#3730a3'}
};

function seleccionarEstado(sid, est) {
  document.getElementById('estado_' + sid).value = est;
  const cont = document.querySelector('.btn-estados[data-sid="' + sid + '"]');
  cont.querySelectorAll('button').forEach(btn => {
    const e = btn.dataset.est;
    if (e === est) {
      btn.style.background = estadosColor[e].bg;
      btn.style.color = estadosColor[e].fg;
    } else {
      btn.style.background = 'transparent';
      btn.style.color = '#9ca3af';
    }
  });
}

function marcarTodos(est) {
  document.querySelectorAll('.btn-estados').forEach(cont => {
    seleccionarEstado(cont.dataset.sid, est);
  });
}

document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
