<?php
// modulos/academico/dashboard.php
// Dashboard del coordinador pedagogico con vista transversal academica
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Dashboard Acad&eacute;mico';
$menu_activo = 'dashboard';
$U           = BASE_URL;

// getSedeFiltro() retorna null para coordinador (transversal) y para admin_general
$sede_filtro = getSedeFiltro();

// ==================== M&Eacute;TRICAS CLAVE ====================

// Cursos publicados
$total_cursos = (int)$pdo->query("SELECT COUNT(*) FROM cursos WHERE publicado = 1")->fetchColumn();

// Grupos activos (con matr&iacute;culas activas)
$total_grupos = (int)$pdo->query("
    SELECT COUNT(DISTINCT g.id)
    FROM grupos g
    WHERE g.activo = 1
      AND EXISTS (SELECT 1 FROM matriculas m WHERE m.grupo_id = g.id AND m.estado = 'activa')
")->fetchColumn();

// Talleristas / docentes activos
$total_talleristas = (int)$pdo->query("
    SELECT COUNT(*) FROM usuarios
    WHERE rol IN ('docente','coordinador_pedagogico') AND activo = 1
")->fetchColumn();

// Estudiantes matriculados activamente
$total_estudiantes = (int)$pdo->query("
    SELECT COUNT(DISTINCT m.estudiante_id)
    FROM matriculas m
    WHERE m.estado = 'activa'
")->fetchColumn();

// R&uacute;bricas activas
$total_rubricas = (int)$pdo->query("SELECT COUNT(*) FROM rubricas WHERE activa = 1")->fetchColumn();

// Evaluaciones del mes
$eval_mes = (int)$pdo->query("
    SELECT COUNT(*) FROM evaluaciones
    WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
")->fetchColumn();

// Asistencia promedio del mes
$asistencia_row = $pdo->query("
    SELECT
        SUM(CASE WHEN a.estado = 'presente' THEN 1 ELSE 0 END) AS presentes,
        COUNT(*) AS total
    FROM asistencia a
    JOIN sesiones s ON s.id = a.sesion_id
    WHERE MONTH(s.fecha) = MONTH(CURDATE()) AND YEAR(s.fecha) = YEAR(CURDATE())
")->fetch();
$asistencia_pct = ($asistencia_row && $asistencia_row['total'] > 0)
    ? round(100 * $asistencia_row['presentes'] / $asistencia_row['total'])
    : 0;

// Contenido pedag&oacute;gico
$total_temas       = (int)$pdo->query("SELECT COUNT(*) FROM temas WHERE activo = 1")->fetchColumn();
$total_actividades = (int)$pdo->query("SELECT COUNT(*) FROM actividades WHERE activa = 1")->fetchColumn();

// Estudiantes por sede (para el grafico)
$estudiantes_por_sede = $pdo->query("
    SELECT s.nombre, s.ciudad,
        (SELECT COUNT(DISTINCT m.estudiante_id)
         FROM matriculas m
         WHERE m.sede_id = s.id AND m.estado = 'activa') AS estudiantes
    FROM sedes s
    WHERE s.activa = 1
    ORDER BY s.nombre
")->fetchAll();
$max_est_sede = 0;
foreach ($estudiantes_por_sede as $s) if ($s['estudiantes'] > $max_est_sede) $max_est_sede = $s['estudiantes'];
if (!$max_est_sede) $max_est_sede = 1;

// Cursos con mas matriculas
$top_cursos = $pdo->query("
    SELECT c.id, c.nombre,
        COUNT(DISTINCT m.estudiante_id) AS estudiantes,
        COUNT(DISTINCT g.id) AS grupos
    FROM cursos c
    LEFT JOIN grupos g ON g.curso_id = c.id AND g.activo = 1
    LEFT JOIN matriculas m ON m.grupo_id = g.id AND m.estado = 'activa'
    GROUP BY c.id
    ORDER BY estudiantes DESC
    LIMIT 5
")->fetchAll();

// &Uacute;ltimas evaluaciones registradas
$ultimas_eval = $pdo->query("
    SELECT e.id, e.fecha, e.observaciones,
        est.nombre_completo AS estudiante,
        u.nombre AS docente,
        c.nombre AS curso,
        r.nombre AS rubrica,
        (SELECT SUM(ed.puntaje) FROM evaluacion_detalle ed WHERE ed.evaluacion_id = e.id) AS total_puntaje,
        (SELECT SUM(rc.puntaje_max) FROM rubrica_criterios rc WHERE rc.rubrica_id = e.rubrica_id) AS puntaje_max
    FROM evaluaciones e
    JOIN matriculas m ON m.id = e.matricula_id
    JOIN estudiantes est ON est.id = m.estudiante_id
    JOIN grupos g ON g.id = m.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    JOIN rubricas r ON r.id = e.rubrica_id
    JOIN usuarios u ON u.id = e.docente_id
    ORDER BY e.fecha DESC, e.id DESC
    LIMIT 6
")->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Dashboard Acad&eacute;mico <small>Vista transversal de la operaci&oacute;n pedag&oacute;gica</small></div>
</header>
<main class="main-content">

  <!-- Saludo -->
  <div style="background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;border-radius:16px;padding:1.4rem 1.6rem;margin-bottom:1.4rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div>
      <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:.25rem;">
        Hola, <?= h($_SESSION['usuario_nombre'] ?? 'Coordinador') ?>
      </div>
      <div style="font-size:.9rem;opacity:.9;">
        Vista transversal de todas las sedes &middot; <?= count($estudiantes_por_sede) ?> sede<?= count($estudiantes_por_sede) != 1 ? 's' : '' ?> operando
      </div>
    </div>
    <div style="display:flex;gap:.6rem;">
      <a href="<?= $U ?>modulos/academico/cursos/index.php"
         style="background:rgba(255,255,255,.2);color:#fff;padding:.6rem 1rem;border-radius:10px;text-decoration:none;font-size:.85rem;font-weight:600;">
        <i class="bi bi-journal-richtext"></i> Cursos
      </a>
      <a href="<?= $U ?>modulos/academico/rubricas/index.php"
         style="background:rgba(255,255,255,.2);color:#fff;padding:.6rem 1rem;border-radius:10px;text-decoration:none;font-size:.85rem;font-weight:600;">
        <i class="bi bi-list-check"></i> R&uacute;bricas
      </a>
    </div>
  </div>

  <!-- Metricas principales -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.4rem;">

    <?php
    $cards = [
      ['icon'=>'bi-people-fill',       'color'=>'#7c3aed', 'bg'=>'#ede4fb', 'lbl'=>'Estudiantes activos',   'val'=>$total_estudiantes, 'url'=>$U.'modulos/estudiantes/index.php'],
      ['icon'=>'bi-journal-richtext',  'color'=>'#1DA99A', 'bg'=>'#e1f5f2', 'lbl'=>'Cursos activos',        'val'=>$total_cursos,      'url'=>$U.'modulos/academico/cursos/index.php'],
      ['icon'=>'bi-calendar3',         'color'=>'#3B82F6', 'bg'=>'#dbeafe', 'lbl'=>'Grupos operando',       'val'=>$total_grupos,      'url'=>$U.'modulos/academico/grupos/index.php'],
      ['icon'=>'bi-person-workspace',  'color'=>'#EF9F27', 'bg'=>'#faeeda', 'lbl'=>'Talleristas',           'val'=>$total_talleristas, 'url'=>$U.'modulos/academico/talleristas.php'],
      ['icon'=>'bi-star-fill',         'color'=>'#D85A30', 'bg'=>'#faece7', 'lbl'=>'Evaluaciones del mes',  'val'=>$eval_mes,          'url'=>$U.'modulos/academico/evaluaciones/index.php'],
      ['icon'=>'bi-calendar-check-fill','color'=>'#10B981','bg'=>'#d1fae5', 'lbl'=>'% Asistencia (mes)',    'val'=>$asistencia_pct.'%','url'=>$U.'modulos/academico/asistencia/index.php'],
    ];
    foreach ($cards as $c):
    ?>
    <a href="<?= $c['url'] ?>" style="text-decoration:none;color:inherit;">
      <div class="card-rsal" style="margin:0;transition:all .2s;"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.5rem;">
          <div style="width:44px;height:44px;border-radius:12px;background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
            <i class="bi <?= $c['icon'] ?>"></i>
          </div>
          <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:var(--dark);">
            <?= $c['val'] ?>
          </div>
        </div>
        <div style="font-size:.75rem;color:var(--muted);font-weight:600;">
          <?= $c['lbl'] ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Fila: contenido pedagogico + estudiantes por sede -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.4rem;">

    <!-- Contenido pedagogico -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-bookmark-fill"></i> Contenido pedag&oacute;gico</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.8rem;margin-top:.6rem;">
        <div style="background:#ede4fb;border-radius:10px;padding:.9rem;text-align:center;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:#7c3aed;"><?= $total_rubricas ?></div>
          <div style="font-size:.72rem;color:#6B46C1;font-weight:600;">R&uacute;bricas</div>
        </div>
        <div style="background:#ede4fb;border-radius:10px;padding:.9rem;text-align:center;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:#7c3aed;"><?= $total_temas ?></div>
          <div style="font-size:.72rem;color:#6B46C1;font-weight:600;">Temas</div>
        </div>
        <div style="background:#ede4fb;border-radius:10px;padding:.9rem;text-align:center;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:#7c3aed;"><?= $total_actividades ?></div>
          <div style="font-size:.72rem;color:#6B46C1;font-weight:600;">Actividades</div>
        </div>
      </div>
      <div style="display:flex;gap:.5rem;margin-top:1rem;">
        <a href="<?= $U ?>modulos/academico/temas/index.php" class="btn-rsal-secondary" style="flex:1;justify-content:center;padding:.55rem;font-size:.8rem;">
          <i class="bi bi-bookmark-fill"></i> Gestionar temas
        </a>
        <a href="<?= $U ?>modulos/academico/actividades/index.php" class="btn-rsal-secondary" style="flex:1;justify-content:center;padding:.55rem;font-size:.8rem;">
          <i class="bi bi-puzzle-fill"></i> Actividades
        </a>
      </div>
    </div>

    <!-- Estudiantes por sede -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-building"></i> Estudiantes por sede</div>
      <?php if (empty($estudiantes_por_sede)): ?>
        <div style="color:var(--muted);font-size:.85rem;padding:1rem 0;">Sin datos de sedes.</div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:.4rem;">
          <?php foreach ($estudiantes_por_sede as $s):
            $pct = $max_est_sede ? round(100 * $s['estudiantes'] / $max_est_sede) : 0;
          ?>
          <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem;">
              <span style="font-size:.82rem;font-weight:700;color:var(--dark);"><?= h($s['nombre']) ?></span>
              <span style="font-size:.82rem;font-weight:800;color:#7c3aed;"><?= (int)$s['estudiantes'] ?></span>
            </div>
            <div style="background:var(--gray);border-radius:6px;height:8px;overflow:hidden;">
              <div style="background:linear-gradient(90deg,#7c3aed,#a78bfa);height:100%;width:<?= $pct ?>%;transition:width .6s;"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Fila: Top cursos + ultimas evaluaciones -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

    <!-- Top cursos -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-trophy-fill"></i> Cursos con m&aacute;s matr&iacute;culas</div>
      <?php if (empty($top_cursos)): ?>
        <div style="color:var(--muted);font-size:.85rem;padding:1rem 0;">Sin datos a&uacute;n.</div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.4rem;">
          <?php foreach ($top_cursos as $i => $c): ?>
          <a href="<?= $U ?>modulos/academico/cursos/form.php?id=<?= $c['id'] ?>"
             style="display:flex;align-items:center;gap:.8rem;padding:.6rem .8rem;background:var(--gray);border-radius:10px;text-decoration:none;color:inherit;transition:background .15s;"
             onmouseover="this.style.background='#e8e5f5'"
             onmouseout="this.style.background='var(--gray)'">
            <div style="width:28px;height:28px;border-radius:50%;background:#7c3aed;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.82rem;flex-shrink:0;">
              <?= $i + 1 ?>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:.86rem;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($c['nombre']) ?></div>
              <div style="font-size:.72rem;color:var(--muted);"><?= (int)$c['grupos'] ?> grupo<?= $c['grupos'] != 1 ? 's':'' ?></div>
            </div>
            <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:900;color:#7c3aed;">
              <?= (int)$c['estudiantes'] ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Ultimas evaluaciones -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-star-fill"></i> &Uacute;ltimas evaluaciones</div>
      <?php if (empty($ultimas_eval)): ?>
        <div style="color:var(--muted);font-size:.85rem;padding:1rem 0;">A&uacute;n no hay evaluaciones registradas.</div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.4rem;">
          <?php foreach ($ultimas_eval as $e):
            $pct = $e['puntaje_max'] > 0 ? round(100 * $e['total_puntaje'] / $e['puntaje_max']) : 0;
            $color = $pct >= 80 ? '#10B981' : ($pct >= 60 ? '#EF9F27' : '#EF4444');
          ?>
          <div style="display:flex;align-items:center;gap:.7rem;padding:.55rem .7rem;background:var(--gray);border-radius:10px;">
            <div style="flex:1;min-width:0;">
              <div style="font-size:.82rem;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= h($e['estudiante']) ?>
              </div>
              <div style="font-size:.7rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= h($e['curso']) ?> &middot; <?= date('d/m', strtotime($e['fecha'])) ?>
              </div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
              <div style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:900;color:<?= $color ?>;">
                <?= $pct ?>%
              </div>
              <div style="font-size:.65rem;color:var(--muted);"><?= (int)$e['total_puntaje'] ?>/<?= (int)$e['puntaje_max'] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <a href="<?= $U ?>modulos/academico/evaluaciones/index.php" class="btn-rsal-secondary" style="width:100%;justify-content:center;padding:.55rem;font-size:.8rem;margin-top:.7rem;">
          Ver todas las evaluaciones <i class="bi bi-arrow-right"></i>
        </a>
      <?php endif; ?>
    </div>

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
