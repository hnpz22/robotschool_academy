<?php
// modulos/academico/tallerista_ver.php
// Detalle de un tallerista: sus grupos, cursos, estudiantes y metricas
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'modulos/academico/talleristas.php'); exit; }

// Datos del tallerista
$s = $pdo->prepare("SELECT u.*, s.nombre AS sede_nombre
                    FROM usuarios u
                    LEFT JOIN sedes s ON s.id = u.sede_id
                    WHERE u.id = ? AND u.rol IN ('docente','coordinador_pedagogico')");
$s->execute([$id]);
$t = $s->fetch();
if (!$t) { header('Location: ' . BASE_URL . 'modulos/academico/talleristas.php?msg=no_existe'); exit; }

$titulo      = 'Tallerista &middot; ' . $t['nombre'];
$menu_activo = 'talleristas';
$U           = BASE_URL;

// Grupos asignados con curso, sede y estudiantes
$grupos = $pdo->prepare("
    SELECT g.id, g.nombre AS grupo_nombre,
        CONCAT(TIME_FORMAT(g.hora_inicio,'%H:%i'),' - ',TIME_FORMAT(g.hora_fin,'%H:%i')) AS horario,
        g.dia_semana, g.activo,
        c.id AS curso_id, c.nombre AS curso_nombre,
        se.id AS sede_id, se.nombre AS sede_nombre,
        (SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id = g.id AND m.estado = 'activa') AS estudiantes,
        (SELECT COUNT(*) FROM sesiones ses WHERE ses.grupo_id = g.id) AS sesiones_dictadas,
        (SELECT COUNT(*) FROM evaluaciones e
         JOIN matriculas m ON m.id = e.matricula_id
         WHERE m.grupo_id = g.id AND e.docente_id = ?) AS evaluaciones_hechas
    FROM docente_grupos dg
    JOIN grupos g ON g.id = dg.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    JOIN sedes se ON se.id = g.sede_id
    WHERE dg.docente_id = ?
    ORDER BY g.activo DESC, se.nombre, c.nombre, g.nombre
");
$grupos->execute([$id, $id]);
$grupos = $grupos->fetchAll();

// Totales agregados
$total_grupos       = 0;
$total_grupos_act   = 0;
$total_estudiantes  = 0;
$cursos_distintos   = [];
$sedes_distintas    = [];
foreach ($grupos as $g) {
    $total_grupos++;
    if ($g['activo']) $total_grupos_act++;
    $total_estudiantes += (int)$g['estudiantes'];
    $cursos_distintos[$g['curso_id']] = $g['curso_nombre'];
    $sedes_distintas[$g['sede_id']]   = $g['sede_nombre'];
}

// Total evaluaciones del tallerista
$total_eval = (int)$pdo->query("SELECT COUNT(*) FROM evaluaciones WHERE docente_id = $id")->fetchColumn();

// Total sesiones creadas por este tallerista
$total_sesiones = (int)$pdo->query("SELECT COUNT(*) FROM sesiones WHERE creado_por = $id")->fetchColumn();

// % asistencia promedio de los grupos de este tallerista
$ast = $pdo->prepare("
    SELECT
        SUM(CASE WHEN a.estado = 'presente' THEN 1 ELSE 0 END) AS presentes,
        COUNT(*) AS total
    FROM asistencia a
    JOIN sesiones ses ON ses.id = a.sesion_id
    WHERE ses.grupo_id IN (SELECT grupo_id FROM docente_grupos WHERE docente_id = ?)
");
$ast->execute([$id]);
$astr = $ast->fetch();
$asistencia_pct = ($astr && $astr['total'] > 0) ? round(100 * $astr['presentes'] / $astr['total']) : 0;

$iniciales = strtoupper(substr($t['nombre'], 0, 1) . substr(strrchr($t['nombre'], ' ') ?: $t['nombre'], 1, 1));
$es_coord = $t['rol'] === 'coordinador_pedagogico';
$color = $es_coord ? '#7c3aed' : '#1DA99A';
$rol_lbl = $es_coord ? 'Coordinador pedag&oacute;gico' : 'Tallerista / Docente';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= h($t['nombre']) ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/academico/talleristas.php">Talleristas</a>
      <i class="bi bi-chevron-right"></i>
      Detalle
    </span></small>
  </div>
</header>
<main class="main-content">

  <!-- Header con datos personales -->
  <div class="card-rsal" style="display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;">
    <div style="width:78px;height:78px;border-radius:50%;background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>99);color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:800;flex-shrink:0;">
      <?= h($iniciales) ?>
    </div>
    <div style="flex:1;min-width:240px;">
      <div style="font-family:'Poppins',sans-serif;font-size:1.3rem;font-weight:800;color:var(--dark);margin-bottom:.3rem;">
        <?= h($t['nombre']) ?>
        <?php if (!$t['activo']): ?>
          <span style="background:#FEE2E2;color:#991B1B;font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:12px;margin-left:.5rem;">Inactivo</span>
        <?php endif; ?>
      </div>
      <div style="font-size:.85rem;color:var(--muted);margin-bottom:.3rem;">
        <i class="bi bi-envelope"></i> <?= h($t['email']) ?>
      </div>
      <div style="display:flex;gap:.8rem;flex-wrap:wrap;font-size:.75rem;color:var(--muted);">
        <span><i class="bi bi-person-badge"></i> <?= $rol_lbl ?></span>
        <?php if ($t['sede_nombre']): ?><span><i class="bi bi-building"></i> Sede base: <?= h($t['sede_nombre']) ?></span><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- M&eacute;tricas -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.8rem;margin:1rem 0;">
    <?php
    $metricas = [
      ['val'=>count($cursos_distintos),  'lbl'=>'Cursos',          'icon'=>'bi-journal-richtext', 'color'=>$color],
      ['val'=>count($sedes_distintas),   'lbl'=>'Sedes',           'icon'=>'bi-building',         'color'=>$color],
      ['val'=>$total_grupos_act,         'lbl'=>'Grupos activos',  'icon'=>'bi-calendar3',        'color'=>$color],
      ['val'=>$total_estudiantes,        'lbl'=>'Estudiantes',     'icon'=>'bi-people',           'color'=>$color],
      ['val'=>$total_sesiones,           'lbl'=>'Sesiones',        'icon'=>'bi-calendar-check',   'color'=>'#10B981'],
      ['val'=>$total_eval,               'lbl'=>'Evaluaciones',    'icon'=>'bi-star-fill',        'color'=>'#EF9F27'],
      ['val'=>$asistencia_pct.'%',       'lbl'=>'Asistencia prom.','icon'=>'bi-graph-up-arrow',   'color'=>'#3B82F6'],
    ];
    foreach ($metricas as $m):
    ?>
    <div class="card-rsal" style="margin:0;">
      <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.3rem;">
        <i class="bi <?= $m['icon'] ?>" style="color:<?= $m['color'] ?>;font-size:1.1rem;"></i>
        <div style="font-family:'Poppins',sans-serif;font-size:1.5rem;font-weight:900;color:var(--dark);"><?= $m['val'] ?></div>
      </div>
      <div style="font-size:.72rem;color:var(--muted);font-weight:600;"><?= $m['lbl'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Grupos asignados -->
  <div class="card-rsal">
    <div class="card-rsal-title"><i class="bi bi-calendar3"></i> Grupos asignados</div>

    <?php if (empty($grupos)): ?>
      <div style="padding:1.5rem;text-align:center;color:var(--muted);">
        <i class="bi bi-inbox" style="font-size:2rem;opacity:.4;"></i>
        <p style="margin-top:.5rem;">Este tallerista no tiene grupos asignados.</p>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead>
          <tr style="background:var(--gray);">
            <th style="padding:.7rem;text-align:left;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Sede</th>
            <th style="padding:.7rem;text-align:left;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Curso</th>
            <th style="padding:.7rem;text-align:left;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Grupo</th>
            <th style="padding:.7rem;text-align:left;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Horario</th>
            <th style="padding:.7rem;text-align:center;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Estud.</th>
            <th style="padding:.7rem;text-align:center;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Sesiones</th>
            <th style="padding:.7rem;text-align:center;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Evals.</th>
            <th style="padding:.7rem;text-align:center;font-weight:700;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grupos as $g): ?>
          <tr style="border-bottom:1px solid var(--border);<?= !$g['activo'] ? 'opacity:.55;' : '' ?>">
            <td style="padding:.7rem;"><?= h($g['sede_nombre']) ?></td>
            <td style="padding:.7rem;font-weight:600;"><?= h($g['curso_nombre']) ?></td>
            <td style="padding:.7rem;"><?= h($g['grupo_nombre']) ?></td>
            <td style="padding:.7rem;color:var(--muted);font-size:.78rem;">
              <?= h(ucfirst($g['dia_semana'] ?? '')) ?>
              <?php if ($g['horario']): ?> &middot; <?= h($g['horario']) ?><?php endif; ?>
            </td>
            <td style="padding:.7rem;text-align:center;font-weight:700;color:#7c3aed;"><?= (int)$g['estudiantes'] ?></td>
            <td style="padding:.7rem;text-align:center;"><?= (int)$g['sesiones_dictadas'] ?></td>
            <td style="padding:.7rem;text-align:center;"><?= (int)$g['evaluaciones_hechas'] ?></td>
            <td style="padding:.7rem;text-align:center;">
              <?php if ($g['activo']): ?>
                <span style="background:#d1fae5;color:#065f46;font-size:.65rem;font-weight:700;padding:3px 8px;border-radius:10px;">Activo</span>
              <?php else: ?>
                <span style="background:#F3F4F6;color:#6B7280;font-size:.65rem;font-weight:700;padding:3px 8px;border-radius:10px;">Inactivo</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Acciones -->
  <div style="display:flex;gap:.6rem;margin-top:1rem;flex-wrap:wrap;">
    <a href="<?= $U ?>modulos/academico/talleristas.php" class="btn-rsal-secondary">
      <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
    <a href="<?= $U ?>modulos/academico/evaluaciones/index.php?docente=<?= $id ?>" class="btn-rsal-secondary">
      <i class="bi bi-star-fill"></i> Ver sus evaluaciones
    </a>
    <a href="<?= $U ?>modulos/usuarios/form.php?id=<?= $id ?>" class="btn-rsal-secondary">
      <i class="bi bi-person-gear"></i> Editar en Usuarios
    </a>
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
