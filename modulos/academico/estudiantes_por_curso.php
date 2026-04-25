<?php
// modulos/academico/estudiantes_por_curso.php
// Vista de estudiantes agrupados por curso (con sus grupos y sedes)
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Estudiantes por curso';
$menu_activo = 'estudiantes_curso';
$U           = BASE_URL;

$sede_filtro = getSedeFiltro(); // null para coordinador

// Filtros
$q_curso  = (int)($_GET['curso'] ?? 0);
$q_sede   = (int)($_GET['sede']  ?? 0);

// Sedes y cursos para los filtros
$sedes  = $pdo->query("SELECT * FROM sedes WHERE activa = 1 ORDER BY nombre")->fetchAll();
$cursos_todos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();

// Cursos con sus grupos y conteo de estudiantes
$where_c = ['1=1'];
$params_c = [];
if ($q_curso) { $where_c[] = 'c.id = ?'; $params_c[] = $q_curso; }

$sql = "SELECT c.id AS curso_id, c.nombre AS curso_nombre,
        c.introduccion AS descripcion_corta,
        g.id AS grupo_id, g.nombre AS grupo_nombre, g.dia_semana,
        CONCAT(TIME_FORMAT(g.hora_inicio,'%H:%i'),' - ',TIME_FORMAT(g.hora_fin,'%H:%i')) AS horario,
        g.cupo_real AS cupo_max,
        s.id AS sede_id, s.nombre AS sede_nombre,
        (SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id = g.id AND m.estado = 'activa') AS total_matriculados,
        u.id AS docente_id, u.nombre AS docente_nombre
        FROM cursos c
        LEFT JOIN grupos g ON g.curso_id = c.id AND g.activo = 1
        LEFT JOIN sedes s ON s.id = g.sede_id
        LEFT JOIN docente_grupos dg ON dg.grupo_id = g.id
        LEFT JOIN usuarios u ON u.id = dg.docente_id
        WHERE " . implode(' AND ', $where_c);
if ($q_sede) { $sql .= " AND (g.sede_id = " . (int)$q_sede . " OR g.id IS NULL)"; }
$sql .= " ORDER BY c.nombre, s.nombre, g.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params_c);
$rows = $stmt->fetchAll();

// Agrupar por curso &rarr; grupos
$cursos_map = [];
foreach ($rows as $r) {
    $cid = $r['curso_id'];
    if (!isset($cursos_map[$cid])) {
        $cursos_map[$cid] = [
            'id'                => $cid,
            'nombre'            => $r['curso_nombre'],
            'descripcion'       => $r['descripcion_corta'],
            'grupos'            => [],
            'total_estudiantes' => 0,
            'total_grupos'      => 0,
        ];
    }
    if ($r['grupo_id']) {
        $cursos_map[$cid]['grupos'][$r['grupo_id']] = [
            'id'                 => $r['grupo_id'],
            'nombre'             => $r['grupo_nombre'],
            'sede_nombre'        => $r['sede_nombre'],
            'dia_semana'         => $r['dia_semana'],
            'horario'            => $r['horario'],
            'cupo_max'           => $r['cupo_max'],
            'total_matriculados' => (int)$r['total_matriculados'],
            'docente_id'         => $r['docente_id'],
            'docente_nombre'     => $r['docente_nombre'],
        ];
        $cursos_map[$cid]['total_estudiantes'] += (int)$r['total_matriculados'];
        $cursos_map[$cid]['total_grupos'] = count($cursos_map[$cid]['grupos']);
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Estudiantes por curso <small>Vista agrupada con sedes y grupos</small></div>
</header>
<main class="main-content">

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Cada tarjeta muestra un curso con todos sus grupos activos, el tallerista asignado y los estudiantes matriculados. Haz clic en un grupo para ver la lista completa.
  </div>

  <!-- Filtros -->
  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="GET" style="display:grid;grid-template-columns:1fr 1fr auto;gap:.8rem;align-items:end;">
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Curso</label>
        <select name="curso" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="0">Todos los cursos</option>
          <?php foreach ($cursos_todos as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $q_curso == $c['id'] ? 'selected' : '' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
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
      <button type="submit" class="btn-rsal-primary" style="padding:.6rem 1rem;">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
    </form>
  </div>

  <?php if (empty($cursos_map)): ?>
    <div class="empty-state">
      <i class="bi bi-search"></i>
      <h3>No hay cursos con estos filtros</h3>
      <p>Ajusta los filtros o crea un curso nuevo.</p>
    </div>
  <?php else: ?>
    <?php foreach ($cursos_map as $c): ?>
    <div class="card-rsal" style="margin-bottom:1rem;">
      <!-- Header del curso -->
      <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding-bottom:.8rem;margin-bottom:.8rem;border-bottom:1px solid var(--border);flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:800;color:var(--dark);display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-journal-richtext" style="color:#1DA99A;"></i>
            <?= h($c['nombre']) ?>
          </div>
          <?php if ($c['descripcion']): ?>
            <div style="font-size:.78rem;color:var(--muted);margin-top:.2rem;"><?= h($c['descripcion']) ?></div>
          <?php endif; ?>
        </div>
        <div style="display:flex;gap:.8rem;align-items:center;">
          <div style="text-align:center;min-width:60px;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.5rem;font-weight:900;color:#1DA99A;"><?= $c['total_grupos'] ?></div>
            <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Grupos</div>
          </div>
          <div style="text-align:center;min-width:60px;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.5rem;font-weight:900;color:#7c3aed;"><?= $c['total_estudiantes'] ?></div>
            <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Estud.</div>
          </div>
          <a href="<?= $U ?>modulos/academico/cursos/form.php?id=<?= $c['id'] ?>" class="btn-rsal-secondary" style="padding:.5rem .8rem;font-size:.78rem;">
            <i class="bi bi-pencil"></i> Editar curso
          </a>
        </div>
      </div>

      <!-- Grupos -->
      <?php if (empty($c['grupos'])): ?>
        <div style="padding:.8rem;text-align:center;color:var(--muted);font-size:.85rem;background:var(--gray);border-radius:10px;">
          <i class="bi bi-exclamation-circle"></i> Este curso a&uacute;n no tiene grupos activos.
        </div>
      <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:.7rem;">
          <?php foreach ($c['grupos'] as $g):
            $pct = $g['cupo_max'] ? round(100 * $g['total_matriculados'] / $g['cupo_max']) : 0;
            $color_cupo = $pct >= 90 ? '#EF4444' : ($pct >= 70 ? '#EF9F27' : '#10B981');
          ?>
          <div style="background:var(--gray);border-radius:10px;padding:.8rem;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">
              <div style="flex:1;min-width:0;">
                <div style="font-size:.85rem;font-weight:700;color:var(--dark);"><?= h($g['nombre']) ?></div>
                <div style="font-size:.72rem;color:var(--muted);margin-top:.15rem;">
                  <i class="bi bi-building"></i> <?= h($g['sede_nombre']) ?>
                </div>
              </div>
              <a href="<?= $U ?>modulos/academico/asistencia/index.php?grupo=<?= $g['id'] ?>"
                 style="background:#fff;color:#7c3aed;padding:.3rem .5rem;border-radius:8px;font-size:.7rem;font-weight:600;text-decoration:none;flex-shrink:0;">
                Ver <i class="bi bi-arrow-right"></i>
              </a>
            </div>
            <div style="font-size:.72rem;color:var(--muted);margin-bottom:.4rem;">
              <i class="bi bi-clock"></i> <?= h(ucfirst($g['dia_semana'] ?? '-')) ?>
              <?php if ($g['horario']): ?> &middot; <?= h($g['horario']) ?><?php endif; ?>
            </div>
            <div style="font-size:.72rem;color:var(--muted);margin-bottom:.5rem;">
              <?php if ($g['docente_nombre']): ?>
                <i class="bi bi-person-workspace"></i> <?= h($g['docente_nombre']) ?>
              <?php else: ?>
                <span style="color:#EF4444;"><i class="bi bi-exclamation-triangle-fill"></i> Sin tallerista asignado</span>
              <?php endif; ?>
            </div>
            <!-- Barra de cupo -->
            <div style="display:flex;justify-content:space-between;font-size:.68rem;color:var(--muted);font-weight:600;margin-bottom:.2rem;">
              <span>Cupo: <?= $g['total_matriculados'] ?>/<?= $g['cupo_max'] ?: '&infin;' ?></span>
              <?php if ($g['cupo_max']): ?><span style="color:<?= $color_cupo ?>;"><?= $pct ?>%</span><?php endif; ?>
            </div>
            <?php if ($g['cupo_max']): ?>
            <div style="background:#fff;border-radius:4px;height:5px;overflow:hidden;">
              <div style="background:<?= $color_cupo ?>;height:100%;width:<?= min($pct,100) ?>%;transition:width .5s;"></div>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
