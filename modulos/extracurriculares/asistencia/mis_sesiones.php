<?php
// modulos/extracurriculares/asistencia/mis_sesiones.php
// Panel donde el tallerista ve sus sesiones EC asignadas y toma asistencia
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireLogin();

$rol_actual = $_SESSION['usuario_rol'] ?? '';
$uid        = (int)$_SESSION['usuario_id'];

if (!in_array($rol_actual, ['docente','admin_general','admin_sede','coordinador_pedagogico'])) {
    http_response_code(403);
    die('Acceso denegado.');
}

$menu_activo = 'ec_mis_sesiones';
$U           = BASE_URL;

// Si es docente: solo las asignadas a el
// Si es coordinador/admin: todas
$es_docente = ($rol_actual === 'docente');

if ($es_docente) {
    $sesiones = $pdo->prepare("SELECT s.*, p.nombre AS programa_nombre, p.color AS programa_color,
        ct.nombre AS contrato_nombre, cl.nombre AS cliente_nombre, cl.ciudad AS cliente_ciudad,
        (SELECT COUNT(*) FROM ec_asistencia a WHERE a.sesion_id = s.id) AS registrados,
        (SELECT COUNT(*) FROM ec_estudiantes e WHERE e.programa_id = p.id AND e.activo = 1
           AND (e.fecha_ingreso IS NULL OR e.fecha_ingreso <= s.fecha)) AS total_estudiantes
        FROM ec_sesiones s
        JOIN ec_programas p  ON p.id = s.programa_id
        JOIN ec_contratos ct ON ct.id = p.contrato_id
        JOIN ec_clientes cl  ON cl.id = ct.cliente_id
        JOIN ec_asignaciones asg ON asg.sesion_id = s.id
        WHERE asg.tallerista_id = ?
        ORDER BY s.fecha DESC, s.hora_inicio");
    $sesiones->execute([$uid]);
} else {
    $sesiones = $pdo->query("SELECT s.*, p.nombre AS programa_nombre, p.color AS programa_color,
        ct.nombre AS contrato_nombre, cl.nombre AS cliente_nombre, cl.ciudad AS cliente_ciudad,
        (SELECT COUNT(*) FROM ec_asistencia a WHERE a.sesion_id = s.id) AS registrados,
        (SELECT COUNT(*) FROM ec_estudiantes e WHERE e.programa_id = p.id AND e.activo = 1
           AND (e.fecha_ingreso IS NULL OR e.fecha_ingreso <= s.fecha)) AS total_estudiantes
        FROM ec_sesiones s
        JOIN ec_programas p  ON p.id = s.programa_id
        JOIN ec_contratos ct ON ct.id = p.contrato_id
        JOIN ec_clientes cl  ON cl.id = ct.cliente_id
        ORDER BY s.fecha DESC, s.hora_inicio
        LIMIT 50");
}
$lista = $sesiones->fetchAll();

$estados_ses = [
    'programada' => ['Programada','#6B7280','#F3F4F6'],
    'dictada'    => ['Dictada','#0d6e5f','#d1fae5'],
    'fallida_justificada' => ['Falla justificada','#b85f00','#fff2d6'],
    'fallida_no_justificada' => ['Falla no justificada','#991b1b','#fde3e4'],
    'recuperada' => ['Recuperada','#1d4ed8','#dbeafe'],
    'cancelada'  => ['Cancelada','#991b1b','#fde3e4'],
];

// Agrupar por fecha
$por_fecha = [];
foreach ($lista as $s) {
    $por_fecha[$s['fecha']][] = $s;
}

$titulo = 'Mis sesiones de extracurriculares';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Mis sesiones <small>Extracurriculares asignadas</small></div>
</header>
<main class="main-content">

  <?php if (empty($lista)): ?>
    <div class="empty-state">
      <i class="bi bi-calendar-event"></i>
      <h3>No tienes sesiones asignadas</h3>
      <p>Cuando el coordinador te asigne sesiones de extracurriculares, aparecer&aacute;n aqu&iacute;.</p>
    </div>
  <?php else: ?>

    <div style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;">
      Mostrando <strong style="color:var(--dark);"><?= count($lista) ?></strong> sesi&oacute;n<?= count($lista) != 1 ? 'es' : '' ?>
      <?php if ($es_docente): ?>asignada<?= count($lista) != 1 ? 's' : '' ?> a ti<?php endif; ?>
    </div>

    <?php foreach ($por_fecha as $fecha => $sesiones_dia):
      $es_hoy = ($fecha === date('Y-m-d'));
      $es_futuro = ($fecha > date('Y-m-d'));
    ?>
      <div style="margin-bottom:1rem;">
        <div style="font-family:'Poppins',sans-serif;font-size:.8rem;font-weight:800;color:var(--dark);text-transform:uppercase;letter-spacing:.5px;padding:.3rem 0;margin-bottom:.5rem;display:flex;align-items:center;gap:.5rem;">
          <?= date('l, d M Y', strtotime($fecha)) ?>
          <?php if ($es_hoy): ?>
            <span style="background:#F26522;color:#fff;font-size:.65rem;padding:2px 8px;border-radius:10px;">HOY</span>
          <?php elseif ($es_futuro): ?>
            <span style="background:#dbeafe;color:#1E4DA1;font-size:.65rem;padding:2px 8px;border-radius:10px;">PR&Oacute;XIMA</span>
          <?php endif; ?>
        </div>

        <div style="display:grid;gap:.6rem;">
          <?php foreach ($sesiones_dia as $s):
            $e = $estados_ses[$s['estado']] ?? $estados_ses['programada'];
            $color_prog = $s['programa_color'] ?: '#7c3aed';
          ?>
          <div style="display:grid;grid-template-columns:6px 1fr auto auto;gap:.9rem;padding:.85rem 1rem;background:#fff;border-radius:10px;border:1px solid var(--border);align-items:center;">
            <div style="background:<?= h($color_prog) ?>;border-radius:4px;align-self:stretch;"></div>
            <div>
              <div style="font-weight:700;color:var(--dark);font-size:.92rem;">
                <?= h($s['programa_nombre']) ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;">
                <i class="bi bi-building"></i> <?= h($s['cliente_nombre']) ?>
                <?php if ($s['cliente_ciudad']): ?> &middot; <?= h($s['cliente_ciudad']) ?><?php endif; ?>
                &middot; <i class="bi bi-clock"></i> <?= substr($s['hora_inicio'],0,5) ?>&ndash;<?= substr($s['hora_fin'],0,5) ?>
                &middot; Sesi&oacute;n #<?= $s['numero_sesion'] ?>
              </div>
            </div>
            <div style="text-align:center;">
              <span style="background:<?= $e[2] ?>;color:<?= $e[1] ?>;font-size:.65rem;font-weight:700;padding:3px 9px;border-radius:9px;white-space:nowrap;">
                <?= $e[0] ?>
              </span>
              <div style="font-size:.7rem;color:var(--muted);margin-top:.3rem;">
                <?= (int)$s['registrados'] ?>/<?= (int)$s['total_estudiantes'] ?> reg.
              </div>
            </div>
            <a href="<?= $U ?>modulos/extracurriculares/asistencia/tomar.php?sesion=<?= $s['id'] ?>" class="btn-rsal-primary" style="padding:.5rem 1rem;font-size:.82rem;white-space:nowrap;">
              <i class="bi bi-clipboard-check-fill"></i>
              <?= $s['registrados'] > 0 ? 'Editar' : 'Tomar' ?>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

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
