<?php
// modulos/academico/talleristas.php
// Panel de todos los talleristas con vista cruzada: sedes, cursos, grupos, estudiantes, asistencia
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Talleristas';
$menu_activo = 'talleristas';
$U           = BASE_URL;

$sede_filtro = getSedeFiltro(); // null para coordinador y admin_general

// Filtros
$q_sede     = (int)($_GET['sede']   ?? 0);
$q_buscar   = trim($_GET['buscar']  ?? '');
$q_activo   = $_GET['activo'] ?? 'si'; // si, no, todos

// Sedes para el filtro
$sedes = $pdo->query("SELECT * FROM sedes WHERE activa = 1 ORDER BY nombre")->fetchAll();

// Construir query de talleristas con sus m&eacute;tricas
$where = ["u.rol IN ('docente','coordinador_pedagogico')"];
$params = [];

if ($q_activo === 'si')  { $where[] = 'u.activo = 1'; }
if ($q_activo === 'no')  { $where[] = 'u.activo = 0'; }

if ($q_sede)  { $where[] = 'u.sede_id = ?'; $params[] = $q_sede; }
if ($q_buscar) {
    $where[] = '(u.nombre LIKE ? OR u.email LIKE ?)';
    $params[] = "%$q_buscar%"; $params[] = "%$q_buscar%";
}

$sql = "SELECT u.id, u.nombre, u.email, u.rol, u.activo, u.sede_id,
        s.nombre AS sede_nombre,
        (SELECT COUNT(DISTINCT dg.grupo_id) FROM docente_grupos dg
         JOIN grupos g ON g.id = dg.grupo_id
         WHERE dg.docente_id = u.id AND g.activo = 1) AS total_grupos,
        (SELECT COUNT(DISTINCT g.curso_id) FROM docente_grupos dg
         JOIN grupos g ON g.id = dg.grupo_id
         WHERE dg.docente_id = u.id AND g.activo = 1) AS total_cursos,
        (SELECT COUNT(DISTINCT m.estudiante_id) FROM docente_grupos dg
         JOIN matriculas m ON m.grupo_id = dg.grupo_id
         WHERE dg.docente_id = u.id AND m.estado = 'activa') AS total_estudiantes,
        (SELECT COUNT(*) FROM evaluaciones e WHERE e.docente_id = u.id) AS total_evaluaciones,
        (SELECT COUNT(*) FROM sesiones ses WHERE ses.creado_por = u.id) AS total_sesiones
        FROM usuarios u
        LEFT JOIN sedes s ON s.id = u.sede_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY u.activo DESC, u.nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$talleristas = $stmt->fetchAll();

// Totales
$total_mostrados = count($talleristas);

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Talleristas <small>Docentes y coordinadores asignados a grupos</small></div>
</header>
<main class="main-content">

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Listado de todos los talleristas y docentes. Haz clic en cualquiera para ver el detalle de sus grupos, cursos y estudiantes asignados.
  </div>

  <!-- Filtros -->
  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="GET" style="display:grid;grid-template-columns:1fr 200px 140px auto;gap:.8rem;align-items:end;">
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Buscar por nombre o email</label>
        <input type="text" name="buscar" value="<?= h($q_buscar) ?>"
               placeholder="Buscar tallerista..."
               style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;"/>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Sede</label>
        <select name="sede" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="0">Todas las sedes</option>
          <?php foreach ($sedes as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $q_sede == $s['id'] ? 'selected' : '' ?>>
              <?= h($s['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Estado</label>
        <select name="activo" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="si"    <?= $q_activo === 'si'    ? 'selected' : '' ?>>Activos</option>
          <option value="no"    <?= $q_activo === 'no'    ? 'selected' : '' ?>>Inactivos</option>
          <option value="todos" <?= $q_activo === 'todos' ? 'selected' : '' ?>>Todos</option>
        </select>
      </div>
      <button type="submit" class="btn-rsal-primary" style="padding:.6rem 1rem;">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
    </form>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
    <div style="font-size:.85rem;color:var(--muted);">
      <strong style="color:var(--dark);"><?= $total_mostrados ?></strong> tallerista<?= $total_mostrados != 1 ? 's' : '' ?> encontrado<?= $total_mostrados != 1 ? 's' : '' ?>
    </div>
  </div>

  <?php if (empty($talleristas)): ?>
    <div class="empty-state">
      <i class="bi bi-person-workspace"></i>
      <h3>No hay talleristas con estos filtros</h3>
      <p>Ajusta los filtros o revisa el m&oacute;dulo de Usuarios para crear uno.</p>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
      <?php foreach ($talleristas as $t):
        $iniciales = strtoupper(substr($t['nombre'], 0, 1) . substr(strrchr($t['nombre'], ' ') ?: $t['nombre'], 1, 1));
        $es_coord = $t['rol'] === 'coordinador_pedagogico';
        $badge_color = $es_coord ? '#7c3aed' : '#1DA99A';
        $badge_bg    = $es_coord ? '#ede4fb' : '#e1f5f2';
        $rol_lbl     = $es_coord ? 'Coordinador' : 'Tallerista';
      ?>
      <a href="<?= $U ?>modulos/academico/tallerista_ver.php?id=<?= $t['id'] ?>"
         style="text-decoration:none;color:inherit;">
      <div class="card-rsal" style="margin:0;transition:all .2s;<?= !$t['activo'] ? 'opacity:.55;' : '' ?>"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:flex-start;gap:.8rem;margin-bottom:.8rem;">
          <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,<?= $badge_color ?>,<?= $badge_color ?>99);color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:1rem;font-weight:800;flex-shrink:0;">
            <?= h($iniciales) ?>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= h($t['nombre']) ?>
            </div>
            <div style="font-size:.72rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= h($t['email']) ?>
            </div>
          </div>
          <span style="background:<?= $badge_bg ?>;color:<?= $badge_color ?>;font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:12px;white-space:nowrap;">
            <?= $rol_lbl ?>
          </span>
        </div>

        <?php if ($t['sede_nombre']): ?>
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.7rem;display:flex;align-items:center;gap:.3rem;">
          <i class="bi bi-building"></i> <?= h($t['sede_nombre']) ?>
        </div>
        <?php endif; ?>

        <!-- M&eacute;tricas -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem;margin-bottom:.6rem;">
          <div style="background:var(--gray);border-radius:8px;padding:.5rem;text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;color:<?= $badge_color ?>;"><?= (int)$t['total_cursos'] ?></div>
            <div style="font-size:.62rem;color:var(--muted);font-weight:600;">Cursos</div>
          </div>
          <div style="background:var(--gray);border-radius:8px;padding:.5rem;text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;color:<?= $badge_color ?>;"><?= (int)$t['total_grupos'] ?></div>
            <div style="font-size:.62rem;color:var(--muted);font-weight:600;">Grupos</div>
          </div>
          <div style="background:var(--gray);border-radius:8px;padding:.5rem;text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;color:<?= $badge_color ?>;"><?= (int)$t['total_estudiantes'] ?></div>
            <div style="font-size:.62rem;color:var(--muted);font-weight:600;">Estud.</div>
          </div>
          <div style="background:var(--gray);border-radius:8px;padding:.5rem;text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;color:<?= $badge_color ?>;"><?= (int)$t['total_evaluaciones'] ?></div>
            <div style="font-size:.62rem;color:var(--muted);font-weight:600;">Evals.</div>
          </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:.6rem;border-top:1px solid var(--border);">
          <span style="font-size:.72rem;color:var(--muted);">
            <i class="bi bi-calendar-check"></i> <?= (int)$t['total_sesiones'] ?> sesi&oacute;n<?= $t['total_sesiones'] != 1 ? 'es' : '' ?>
          </span>
          <span style="font-size:.72rem;color:<?= $badge_color ?>;font-weight:600;">
            Ver detalle <i class="bi bi-arrow-right"></i>
          </span>
        </div>
      </div>
      </a>
      <?php endforeach; ?>
    </div>
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
