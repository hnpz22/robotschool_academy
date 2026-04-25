<?php
// modulos/academico/informes/index.php
// Listado de estudiantes con opcion de generar informe por periodo
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Informes acad&eacute;micos';
$menu_activo = 'informes';
$U           = BASE_URL;

$sede_filtro = getSedeFiltro(); // null para coordinador y admin_general

// Filtros
$q_buscar = trim($_GET['buscar'] ?? '');
$q_sede   = (int)($_GET['sede']   ?? 0);
$q_curso  = (int)($_GET['curso']  ?? 0);
$q_per    = trim($_GET['periodo'] ?? '');

// Si no hay periodo seleccionado, usar el mas reciente disponible
if (!$q_per) {
    $r = $pdo->query("SELECT periodo FROM grupos WHERE periodo IS NOT NULL AND periodo != '' ORDER BY periodo DESC LIMIT 1")->fetchColumn();
    $q_per = $r ?: date('Y') . '-1';
}

// Periodos disponibles
$periodos = $pdo->query("SELECT DISTINCT periodo FROM grupos WHERE periodo IS NOT NULL AND periodo != '' ORDER BY periodo DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($periodos)) $periodos = [date('Y').'-1', date('Y').'-2'];

// Sedes y cursos para filtros
$sedes  = $pdo->query("SELECT * FROM sedes WHERE activa = 1 ORDER BY nombre")->fetchAll();
$cursos_todos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();

// Construir query de estudiantes con matricula activa en el periodo
$where = ["m.estado IN ('activa','finalizada')", "g.periodo = ?"];
$params = [$q_per];

if ($q_sede)  { $where[] = 'm.sede_id = ?';  $params[] = $q_sede; }
if ($q_curso) { $where[] = 'g.curso_id = ?'; $params[] = $q_curso; }
if ($q_buscar) {
    $where[] = '(e.nombre_completo LIKE ? OR e.numero_doc LIKE ?)';
    $params[] = "%$q_buscar%"; $params[] = "%$q_buscar%";
}

$sql = "SELECT m.id AS matricula_id, m.estado, m.periodo,
        e.id AS estudiante_id, e.nombre_completo, e.numero_doc, e.avatar AS foto,
        c.id AS curso_id, c.nombre AS curso_nombre,
        g.id AS grupo_id, g.nombre AS grupo_nombre,
        s.nombre AS sede_nombre,
        u.nombre AS docente_nombre,
        (SELECT COUNT(*) FROM asistencia a JOIN sesiones ses ON ses.id = a.sesion_id
         WHERE a.matricula_id = m.id AND a.estado = 'presente') AS presentes,
        (SELECT COUNT(*) FROM asistencia a JOIN sesiones ses ON ses.id = a.sesion_id
         WHERE a.matricula_id = m.id) AS total_asist,
        (SELECT COUNT(*) FROM evaluaciones ev WHERE ev.matricula_id = m.id) AS total_eval
        FROM matriculas m
        JOIN estudiantes e ON e.id = m.estudiante_id
        JOIN grupos g ON g.id = m.grupo_id
        JOIN cursos c ON c.id = g.curso_id
        JOIN sedes  s ON s.id = m.sede_id
        LEFT JOIN docente_grupos dg ON dg.grupo_id = g.id
        LEFT JOIN usuarios u ON u.id = dg.docente_id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY m.id
        ORDER BY s.nombre, c.nombre, e.nombre_completo";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$matriculas = $stmt->fetchAll();

$total = count($matriculas);

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Informes acad&eacute;micos <small>Bolet&iacute;n por estudiante y per&iacute;odo</small></div>
</header>
<main class="main-content">

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Selecciona un per&iacute;odo acad&eacute;mico y filtra por sede o curso para generar el informe del estudiante. El informe incluye asistencia, evaluaciones, temas vistos y observaciones del docente &mdash; imprimible como PDF.
  </div>

  <!-- Filtros -->
  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="GET" style="display:grid;grid-template-columns:140px 1fr 200px 200px auto;gap:.8rem;align-items:end;">
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Per&iacute;odo <span class="req">*</span></label>
        <select name="periodo" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <?php foreach ($periodos as $p): ?>
            <option value="<?= h($p) ?>" <?= $q_per === $p ? 'selected' : '' ?>><?= h($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Buscar estudiante</label>
        <input type="text" name="buscar" value="<?= h($q_buscar) ?>"
               placeholder="Nombre o documento..."
               style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;"/>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Sede</label>
        <select name="sede" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="0">Todas</option>
          <?php foreach ($sedes as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $q_sede == $s['id'] ? 'selected' : '' ?>>
              <?= h($s['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Curso</label>
        <select name="curso" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="0">Todos</option>
          <?php foreach ($cursos_todos as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $q_curso == $c['id'] ? 'selected' : '' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-rsal-primary" style="padding:.6rem 1rem;">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
    </form>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
    <div style="font-size:.85rem;color:var(--muted);">
      <strong style="color:var(--dark);"><?= $total ?></strong> matr&iacute;cula<?= $total != 1 ? 's' : '' ?> en el per&iacute;odo <strong style="color:var(--dark);"><?= h($q_per) ?></strong>
    </div>
  </div>

  <?php if (empty($matriculas)): ?>
    <div class="empty-state">
      <i class="bi bi-file-earmark-text"></i>
      <h3>No hay matr&iacute;culas con estos filtros</h3>
      <p>Ajusta los filtros o selecciona otro per&iacute;odo.</p>
    </div>
  <?php else: ?>
    <div class="card-rsal" style="padding:0;overflow:hidden;">
      <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead>
          <tr style="background:var(--gray);">
            <th style="padding:.8rem;text-align:left;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Estudiante</th>
            <th style="padding:.8rem;text-align:left;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Curso</th>
            <th style="padding:.8rem;text-align:left;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Sede / Grupo</th>
            <th style="padding:.8rem;text-align:left;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Tallerista</th>
            <th style="padding:.8rem;text-align:center;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Asistencia</th>
            <th style="padding:.8rem;text-align:center;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Evals.</th>
            <th style="padding:.8rem;text-align:center;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Informe</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($matriculas as $m):
            $ini = strtoupper(substr($m['nombre_completo'], 0, 1) . substr(strrchr($m['nombre_completo'], ' ') ?: $m['nombre_completo'], 1, 1));
            $pct = $m['total_asist'] > 0 ? round(100 * $m['presentes'] / $m['total_asist']) : 0;
            $color_pct = $pct >= 80 ? '#10B981' : ($pct >= 60 ? '#EF9F27' : '#EF4444');
            $foto_src = $m['foto'] ? $U . 'uploads/estudiantes/' . $m['foto'] : '';
          ?>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:.75rem;">
              <div style="display:flex;align-items:center;gap:.6rem;">
                <?php if ($foto_src): ?>
                  <img src="<?= h($foto_src) ?>" alt="" style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;"/>
                <?php else: ?>
                  <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:.72rem;font-weight:800;flex-shrink:0;">
                    <?= h($ini) ?>
                  </div>
                <?php endif; ?>
                <div style="min-width:0;">
                  <div style="font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;">
                    <?= h($m['nombre_completo']) ?>
                  </div>
                  <div style="font-size:.7rem;color:var(--muted);">
                    <?= h($m['numero_doc'] ?? '-') ?>
                  </div>
                </div>
              </div>
            </td>
            <td style="padding:.75rem;font-weight:600;"><?= h($m['curso_nombre']) ?></td>
            <td style="padding:.75rem;color:var(--muted);font-size:.78rem;">
              <?= h($m['sede_nombre']) ?><br>
              <span style="font-size:.7rem;"><?= h($m['grupo_nombre']) ?></span>
            </td>
            <td style="padding:.75rem;color:var(--muted);font-size:.78rem;">
              <?= $m['docente_nombre'] ? h($m['docente_nombre']) : '<em style="color:#EF4444;">Sin asignar</em>' ?>
            </td>
            <td style="padding:.75rem;text-align:center;">
              <?php if ($m['total_asist'] > 0): ?>
                <div style="font-family:'Poppins',sans-serif;font-weight:900;font-size:1rem;color:<?= $color_pct ?>;"><?= $pct ?>%</div>
                <div style="font-size:.65rem;color:var(--muted);"><?= (int)$m['presentes'] ?>/<?= (int)$m['total_asist'] ?></div>
              <?php else: ?>
                <span style="color:var(--muted);font-size:.72rem;">&mdash;</span>
              <?php endif; ?>
            </td>
            <td style="padding:.75rem;text-align:center;font-weight:700;color:#7c3aed;"><?= (int)$m['total_eval'] ?></td>
            <td style="padding:.75rem;text-align:center;">
              <a href="<?= $U ?>modulos/academico/informes/ver.php?matricula=<?= $m['matricula_id'] ?>"
                 target="_blank"
                 class="btn-rsal-primary" style="padding:.4rem .8rem;font-size:.75rem;">
                <i class="bi bi-file-earmark-text-fill"></i> Ver informe
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
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
