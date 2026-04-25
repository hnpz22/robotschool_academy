<?php
// modulos/matriculas/avance.php &mdash; Avance de m&oacute;dulo (mismo curso)
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$U           = BASE_URL;
$menu_activo = 'matriculas';
$sede_filtro = getSedeFiltro();
$errores     = [];
$exito       = false;

$matricula_id = (int)($_GET['id'] ?? 0);
if (!$matricula_id) { header('Location: '.$U.'modulos/matriculas/index.php'); exit; }

// Cargar matr&iacute;cula con todos sus datos
$stmt = $pdo->prepare("
    SELECT m.*, e.nombre_completo AS estudiante_nombre, e.id AS est_id,
           g.id AS grupo_id, g.nombre AS grupo_nombre, g.curso_id,
           g.dia_semana, g.hora_inicio, g.hora_fin,
           c.nombre AS curso_nombre, c.valor, c.tipo_valor,
           p.nombre_completo AS padre_nombre,
           s.nombre AS sede_nombre
    FROM matriculas m
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN grupos g ON g.id = m.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    JOIN padres p ON p.id = e.padre_id
    JOIN sedes s ON s.id = m.sede_id
    WHERE m.id = ?
");
$stmt->execute([$matricula_id]);
$matricula = $stmt->fetch();

if (!$matricula) { header('Location: '.$U.'modulos/matriculas/index.php'); exit; }
if ($sede_filtro && $matricula['sede_id'] != $sede_filtro) { header('Location: '.$U.'modulos/matriculas/index.php'); exit; }

// Grupos del MISMO curso (excepto el actual) ordenados por nombre
$where_g = $sede_filtro ? 'AND g.sede_id='.(int)$sede_filtro : '';
$grupos_mismo_curso = $pdo->prepare("
    SELECT g.*,
        (g.cupo_real - COALESCE((SELECT COUNT(*) FROM matriculas m2 WHERE m2.grupo_id=g.id AND m2.estado='activa'),0)) AS disponibles,
        (SELECT GROUP_CONCAT(u.nombre SEPARATOR ', ') FROM docente_grupos dg JOIN usuarios u ON u.id=dg.docente_id WHERE dg.grupo_id=g.id) AS docentes
    FROM grupos g
    WHERE g.curso_id = ? AND g.activo = 1 AND g.id != ? $where_g
    ORDER BY g.nombre ASC
");
$grupos_mismo_curso->execute([$matricula['curso_id'], $matricula['grupo_id']]);
$grupos_mismo_curso = $grupos_mismo_curso->fetchAll();

// Sugerir el siguiente m&oacute;dulo autom&aacute;ticamente
// Busca el primer grupo con cupo disponible ordenado por nombre
$grupo_sugerido_id = null;
foreach ($grupos_mismo_curso as $g) {
    if ($g['disponibles'] > 0) {
        $grupo_sugerido_id = $g['id'];
        break;
    }
}

// Historial de avances del estudiante (todos, no solo esta matr&iacute;cula)
$hist = $pdo->prepare("
    SELECT mh.*, g.nombre AS grupo_nombre, c.nombre AS curso_nombre, mh.fecha
    FROM matricula_historial mh
    JOIN grupos g ON g.id = mh.grupo_id_nuevo
    JOIN cursos c ON c.id = g.curso_id
    JOIN matriculas m ON m.id = mh.matricula_id
    WHERE m.estudiante_id = ?
    ORDER BY mh.fecha DESC
    LIMIT 10
");
$hist->execute([$matricula['est_id']]);
$historial = $hist->fetchAll();

// Per&iacute;odo sugerido
$periodo_sugerido = date('Y') . '-' . (date('n') <= 6 ? '2' : '3');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_grupo_id = (int)($_POST['nuevo_grupo_id'] ?? 0);
    $periodo_nuevo  = trim($_POST['periodo_nuevo'] ?? '');
    $fecha_inicio   = $_POST['fecha_inicio'] ?? null ?: null;
    $fecha_fin      = $_POST['fecha_fin']    ?? null ?: null;
    $motivo         = trim($_POST['motivo']  ?? 'Avance de m&oacute;dulo');
    $generar_pago   = ($_POST['generar_pago'] ?? 'si') === 'si';

    if (!$nuevo_grupo_id) $errores[] = 'Selecciona el grupo de destino.';
    if (!$periodo_nuevo)  $errores[] = 'El per&iacute;odo es obligatorio.';

    // Verificar que sea del mismo curso
    if ($nuevo_grupo_id) {
        $chk = $pdo->prepare("SELECT g.id, g.cupo_real, c.valor, c.tipo_valor, c.nombre AS curso_nombre,
            (g.cupo_real - COALESCE((SELECT COUNT(*) FROM matriculas m2 WHERE m2.grupo_id=g.id AND m2.estado='activa'),0)) AS disponibles
            FROM grupos g JOIN cursos c ON c.id=g.curso_id WHERE g.id=? AND g.curso_id=?");
        $chk->execute([$nuevo_grupo_id, $matricula['curso_id']]);
        $info_nuevo = $chk->fetch();
        if (!$info_nuevo) $errores[] = 'Grupo no v&aacute;lido para este curso.';
        elseif ($info_nuevo['cupo_real'] > 0 && $info_nuevo['disponibles'] <= 0)
            $errores[] = 'El grupo seleccionado no tiene cupos disponibles.';
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            $grupo_anterior_id = $matricula['grupo_id'];

            // 1. Finalizar matr&iacute;cula anterior
            $pdo->prepare("UPDATE matriculas SET estado='finalizada', updated_at=NOW() WHERE id=?")
                ->execute([$matricula_id]);

            // 2. Crear nueva matr&iacute;cula (mismo estudiante, mismo padre)
            $pdo->prepare("
                INSERT INTO matriculas (estudiante_id, grupo_id, sede_id, estado, periodo, observaciones)
                VALUES (?, ?, ?, 'activa', ?, ?)
            ")->execute([
                $matricula['est_id'],
                $nuevo_grupo_id,
                $matricula['sede_id'],
                $periodo_nuevo,
                'Avance desde matr&iacute;cula #'.$matricula_id.'. '.$motivo
            ]);
            $nueva_matricula_id = $pdo->lastInsertId();

            // 3. Registrar historial
            $pdo->prepare("
                INSERT INTO matricula_historial (matricula_id, grupo_id_anterior, grupo_id_nuevo, motivo, usuario_id)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$matricula_id, $grupo_anterior_id, $nuevo_grupo_id, $motivo, $_SESSION['usuario_id']]);

            // 4. Generar pago si se solicit&oacute;
            if ($generar_pago && isset($info_nuevo)) {
                $valor = $info_nuevo['valor'];
                $fecha_venc = $fecha_inicio
                    ? date('Y-m-d', strtotime($fecha_inicio . ' +7 days'))
                    : date('Y-m-d', strtotime('+15 days'));
                $pdo->prepare("
                    INSERT INTO pagos (matricula_id, concepto, valor_total, valor_pagado, estado, fecha_vencimiento)
                    VALUES (?, ?, ?, 0, 'pendiente', ?)
                ")->execute([
                    $nueva_matricula_id,
                    'Pago &mdash; '.$matricula['curso_nombre'].' &mdash; '.$periodo_nuevo,
                    $valor,
                    $fecha_venc
                ]);
            }

            $pdo->commit();
            $exito = true;

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errores[] = 'Error al procesar: '.$ex->getMessage();
        }
    }
}

$dias = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles',
         'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];

$titulo = 'Avance de M&oacute;dulo';
require_once ROOT . '/includes/head.php';
?>
<body>
<?php require_once ROOT . '/includes/sidebar.php'; ?>
<main class="main-content">
<header class="main-header">
  <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    <h1><i class="bi bi-arrow-right-circle-fill" style="color:var(--orange);"></i> Avance de M&oacute;dulo</h1>
    <div class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/matriculas/index.php">Matr&iacute;culas</a>
      <i class="bi bi-chevron-right"></i> Avance
    </div>
  </div>
</header>

<div class="content-wrapper" style="max-width:860px;">

  <?php if ($exito): ?>
  <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:1.2rem 1.4rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.8rem;">
    <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:1.6rem;"></i>
    <div>
      <strong style="color:#15803d;font-size:.95rem;">&iexcl;Avance registrado!</strong>
      <p style="margin:.2rem 0 0;font-size:.85rem;color:#166534;">
        <?= h($matricula['estudiante_nombre']) ?> fue inscrito en el nuevo m&oacute;dulo y se gener&oacute; el pago.
      </p>
    </div>
    <a href="<?= $U ?>modulos/matriculas/index.php" class="btn-rsal-secondary" style="margin-left:auto;">
      Volver a matr&iacute;culas
    </a>
  </div>
  <?php endif; ?>

  <?php if (!empty($errores)): ?>
  <div style="background:#fff0f1;border:1.5px solid #fca5a5;border-radius:12px;padding:1rem 1.4rem;margin-bottom:1.5rem;">
    <?php foreach($errores as $e): ?>
    <p style="margin:.2rem 0;color:#991b1b;font-size:.87rem;"><i class="bi bi-exclamation-circle"></i> <?= h($e) ?></p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Tarjeta del estudiante -->
  <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:1.4rem;margin-bottom:1.2rem;">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:.8rem;">Estudiante</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.8rem;">
      <div><small style="color:var(--muted);display:block;font-size:.75rem;">Nombre</small><strong style="font-size:.9rem;"><?= h($matricula['estudiante_nombre']) ?></strong></div>
      <div><small style="color:var(--muted);display:block;font-size:.75rem;">Padre / Acudiente</small><strong style="font-size:.9rem;"><?= h($matricula['padre_nombre']) ?></strong></div>
      <div><small style="color:var(--muted);display:block;font-size:.75rem;">Sede</small><strong style="font-size:.9rem;"><?= h($matricula['sede_nombre']) ?></strong></div>
    </div>
  </div>

  <!-- M&oacute;dulo actual &#8594; nuevo -->
  <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:1rem;align-items:center;margin-bottom:1.2rem;">
    <div style="background:#fff8f0;border:1.5px solid #fed7aa;border-radius:14px;padding:1.2rem;">
      <div style="font-size:.72rem;font-weight:700;color:var(--orange);text-transform:uppercase;margin-bottom:.5rem;">M&oacute;dulo actual</div>
      <div style="font-weight:700;font-size:.95rem;"><?= h($matricula['grupo_nombre']) ?></div>
      <div style="font-size:.8rem;color:var(--muted);"><?= h($matricula['curso_nombre']) ?></div>
      <div style="font-size:.78rem;color:var(--muted);margin-top:.3rem;">
        <?= $dias[$matricula['dia_semana']] ?? '' ?> <?= substr($matricula['hora_inicio'],0,5) ?>&ndash;<?= substr($matricula['hora_fin'],0,5) ?>
      </div>
      <div style="margin-top:.6rem;">
        <span style="font-size:.72rem;padding:.2rem .6rem;background:#fed7aa;color:#c2410c;border-radius:6px;font-weight:700;">Per&iacute;odo: <?= h($matricula['periodo']) ?></span>
      </div>
    </div>
    <div style="text-align:center;font-size:2rem;color:var(--orange);">
      <i class="bi bi-arrow-right-circle-fill"></i>
    </div>
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;padding:1.2rem;">
      <div style="font-size:.72rem;font-weight:700;color:#16a34a;text-transform:uppercase;margin-bottom:.5rem;">Nuevo m&oacute;dulo</div>
      <?php if ($grupo_sugerido_id):
        $gs = array_filter($grupos_mismo_curso, fn($g) => $g['id'] == $grupo_sugerido_id);
        $gs = reset($gs);
      ?>
      <div style="font-weight:700;font-size:.95rem;"><?= h($gs['nombre']) ?></div>
      <div style="font-size:.78rem;color:var(--muted);margin-top:.3rem;">
        <?= $dias[$gs['dia_semana']] ?? '' ?> <?= substr($gs['hora_inicio'],0,5) ?>&ndash;<?= substr($gs['hora_fin'],0,5) ?>
      </div>
      <?php if ($gs['docentes']): ?>
      <div style="font-size:.75rem;color:var(--muted);margin-top:.3rem;"><i class="bi bi-person-workspace"></i> <?= h($gs['docentes']) ?></div>
      <?php endif; ?>
      <div style="margin-top:.5rem;font-size:.78rem;color:#16a34a;font-weight:600;"><i class="bi bi-check-circle"></i> <?= $gs['disponibles'] ?> cupos disponibles</div>
      <?php else: ?>
      <div style="color:var(--muted);font-size:.85rem;font-style:italic;">Selecciona abajo</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Formulario -->
  <?php if ($matricula['estado'] === 'activa' && !$exito): ?>
  <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:1.4rem;">
    <div style="font-size:.9rem;font-weight:700;color:var(--dark);margin-bottom:1.2rem;">
      <i class="bi bi-pencil-fill" style="color:var(--teal);"></i> Configurar avance
    </div>
    <form method="POST">

      <!-- Selector de grupo destino -->
      <div style="margin-bottom:1rem;">
        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.5rem;">
          M&oacute;dulo de destino <span style="color:red;">*</span>
          <span style="font-size:.75rem;font-weight:400;color:var(--muted);"> &mdash; Solo grupos de "<?= h($matricula['curso_nombre']) ?>"</span>
        </label>
        <?php if (empty($grupos_mismo_curso)): ?>
        <div style="background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:.9rem;font-size:.85rem;color:#92400e;">
          <i class="bi bi-exclamation-triangle"></i> No hay otros grupos disponibles en este curso. Crea m&aacute;s grupos en <a href="<?= $U ?>modulos/academico/grupos/form.php">Grupos y Horarios</a>.
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.4rem;">
          <?php foreach ($grupos_mismo_curso as $g):
            $sin_cupo = $g['disponibles'] <= 0;
            $sugerido = $g['id'] == $grupo_sugerido_id;
            $checked  = $sugerido ? 'checked' : '';
          ?>
          <label style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1rem;border:1.5px solid <?= $sin_cupo ? '#e5e7eb' : ($sugerido ? 'var(--teal)' : 'var(--border)') ?>;border-radius:10px;cursor:<?= $sin_cupo ? 'not-allowed' : 'pointer' ?>;background:<?= $sugerido ? 'rgba(0,156,204,.06)' : '#fff' ?>;opacity:<?= $sin_cupo ? '.5' : '1' ?>;"
                 id="lbl_g_<?= $g['id'] ?>">
            <input type="radio" name="nuevo_grupo_id" value="<?= $g['id'] ?>" <?= $checked ?> <?= $sin_cupo ? 'disabled' : '' ?>
                   style="accent-color:var(--teal);flex-shrink:0;"
                   onchange="updateGrupoInfo(<?= $g['id'] ?>, '<?= addslashes(h($g['nombre'])) ?>', <?= $g['disponibles'] ?>)">
            <div style="flex:1;">
              <strong style="font-size:.87rem;"><?= h($g['nombre']) ?></strong>
              <?php if ($sugerido): ?>
              <span style="font-size:.7rem;background:var(--teal);color:#fff;border-radius:4px;padding:.1rem .4rem;margin-left:.4rem;font-weight:700;">Sugerido</span>
              <?php endif; ?>
              <div style="font-size:.75rem;color:var(--muted);margin-top:.1rem;">
                <?= $dias[$g['dia_semana']] ?? '' ?> <?= substr($g['hora_inicio'],0,5) ?>&ndash;<?= substr($g['hora_fin'],0,5) ?>
                <?php if ($g['docentes']): ?> &middot; <i class="bi bi-person-workspace"></i> <?= h($g['docentes']) ?><?php endif; ?>
              </div>
            </div>
            <div style="text-align:right;font-size:.78rem;">
              <?php if ($sin_cupo): ?>
              <span style="color:#ef4444;font-weight:700;">Sin cupos</span>
              <?php else: ?>
              <span style="color:#16a34a;font-weight:700;"><?= $g['disponibles'] ?> cupos</span>
              <?php endif; ?>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Per&iacute;odo y fechas -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;margin-bottom:1rem;">
        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Per&iacute;odo <span style="color:red;">*</span></label>
          <input type="text" name="periodo_nuevo" required placeholder="Ej: 2026-2"
                 value="<?= h($_POST['periodo_nuevo'] ?? $periodo_sugerido) ?>"
                 style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;box-sizing:border-box;">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Fecha inicio</label>
          <input type="date" name="fecha_inicio" value="<?= h($_POST['fecha_inicio'] ?? '') ?>"
                 style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;box-sizing:border-box;">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Fecha fin</label>
          <input type="date" name="fecha_fin" value="<?= h($_POST['fecha_fin'] ?? '') ?>"
                 style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;box-sizing:border-box;">
        </div>
      </div>

      <!-- Pago y motivo -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:1.2rem;">
        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Generar pago</label>
          <select name="generar_pago" id="selPago"
                  style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;"
                  onchange="togglePagoInfo()">
            <option value="si">S&iacute; &mdash; generar cobro por <?= formatCOP($matricula['valor']) ?></option>
            <option value="no">No &mdash; solo registrar el avance</option>
          </select>
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Motivo</label>
          <input type="text" name="motivo" placeholder="Ej: Aprob&oacute; M1, avanza a M2"
                 value="<?= h($_POST['motivo'] ?? 'Avance de m&oacute;dulo') ?>"
                 style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;box-sizing:border-box;">
        </div>
      </div>

      <div id="pagoInfo" style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:.8rem 1.2rem;margin-bottom:1.2rem;font-size:.85rem;display:flex;align-items:center;gap:.6rem;">
        <i class="bi bi-cash-coin" style="color:#16a34a;font-size:1.1rem;"></i>
        <span>Se generar&aacute; un pago de <strong><?= formatCOP($matricula['valor']) ?></strong> con vencimiento 7 d&iacute;as despu&eacute;s de la fecha de inicio.</span>
      </div>

      <div style="display:flex;gap:.8rem;">
        <button type="submit" style="padding:.8rem 1.6rem;background:var(--orange);color:#fff;border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.5rem;">
          <i class="bi bi-arrow-right-circle-fill"></i> Confirmar avance
        </button>
        <a href="<?= $U ?>modulos/matriculas/index.php"
           style="padding:.8rem 1.4rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;font-weight:600;color:var(--dark);text-decoration:none;display:flex;align-items:center;">
          Cancelar
        </a>
      </div>
    </form>
  </div>

  <?php elseif ($matricula['estado'] !== 'activa'): ?>
  <div style="background:#fffbeb;border:1.5px solid #fcd34d;border-radius:12px;padding:1rem 1.4rem;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;"></i>
    <strong>Esta matr&iacute;cula est&aacute; en estado "<?= h($matricula['estado']) ?>".</strong> Solo se pueden hacer avances desde matr&iacute;culas activas.
  </div>
  <?php endif; ?>

  <!-- Historial de avances del estudiante -->
  <?php if (!empty($historial)): ?>
  <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:1.4rem;margin-top:1.2rem;">
    <div style="font-size:.9rem;font-weight:700;color:var(--dark);margin-bottom:1rem;">
      <i class="bi bi-clock-history" style="color:var(--teal);"></i> Historial de avances del estudiante
    </div>
    <table class="table-rsal">
      <thead><tr><th>Fecha</th><th>Curso</th><th>Nuevo grupo</th><th>Motivo</th></tr></thead>
      <tbody>
      <?php foreach($historial as $h): ?>
      <tr>
        <td style="font-size:.82rem;"><?= formatFecha($h['fecha']) ?></td>
        <td style="font-size:.82rem;"><?= h($h['curso_nombre']) ?></td>
        <td style="font-size:.85rem;font-weight:600;"><?= h($h['grupo_nombre']) ?></td>
        <td style="font-size:.8rem;color:var(--muted);"><?= h($h['motivo']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div>
</main>

<script>
function togglePagoInfo() {
  document.getElementById('pagoInfo').style.display =
    document.getElementById('selPago').value === 'si' ? 'flex' : 'none';
}

function updateGrupoInfo(id, nombre, cupos) {
  document.querySelectorAll('[id^="lbl_g_"]').forEach(l => {
    l.style.borderColor = 'var(--border)';
    l.style.background  = '#fff';
  });
  const lbl = document.getElementById('lbl_g_' + id);
  if (lbl) {
    lbl.style.borderColor = 'var(--teal)';
    lbl.style.background  = 'rgba(0,156,204,.06)';
  }
}

// Inicializar selecci&oacute;n visual
document.querySelectorAll('input[name="nuevo_grupo_id"]').forEach(r => {
  if (r.checked) updateGrupoInfo(r.value, '', 0);
  r.addEventListener('change', () => updateGrupoInfo(r.value, '', 0));
});
</script>
</body>
</html>
