<?php
// modulos/extracurriculares/programas/ver.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_programas';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

$s = $pdo->prepare("SELECT p.*, c.nombre AS curso_nombre,
                    ct.id AS contrato_id, ct.nombre AS contrato_nombre, ct.codigo AS contrato_codigo,
                    ct.fecha_inicio AS contrato_inicio, ct.fecha_fin AS contrato_fin,
                    cl.nombre AS cliente_nombre, cl.tipo AS cliente_tipo
                    FROM ec_programas p
                    JOIN ec_contratos ct ON ct.id = p.contrato_id
                    JOIN ec_clientes cl  ON cl.id = ct.cliente_id
                    LEFT JOIN cursos c ON c.id = p.curso_id
                    WHERE p.id = ?");
$s->execute([$id]);
$P = $s->fetch();
if (!$P) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

// Sesiones del programa
$sesiones = $pdo->prepare("SELECT s.*,
    (SELECT COUNT(*) FROM ec_asistencia a WHERE a.sesion_id = s.id) AS registrados,
    (SELECT COUNT(*) FROM ec_asistencia a WHERE a.sesion_id = s.id AND a.estado IN ('presente','tarde')) AS asistieron,
    (SELECT u.nombre FROM ec_asignaciones asg JOIN usuarios u ON u.id = asg.tallerista_id
       WHERE asg.sesion_id = s.id AND asg.rol = 'principal' LIMIT 1) AS tallerista_principal,
    (SELECT u.nombre FROM ec_asignaciones asg JOIN usuarios u ON u.id = asg.tallerista_id
       WHERE asg.sesion_id = s.id AND asg.rol = 'apoyo' LIMIT 1) AS tallerista_apoyo
    FROM ec_sesiones s WHERE s.programa_id = ? ORDER BY s.numero_sesion");
$sesiones->execute([$id]);
$lista_sesiones = $sesiones->fetchAll();

// Estudiantes del programa
$est = $pdo->prepare("SELECT e.*,
    (SELECT COUNT(*) FROM ec_asistencia a WHERE a.estudiante_id = e.id AND a.estado = 'presente') AS presentes,
    (SELECT COUNT(*) FROM ec_asistencia a WHERE a.estudiante_id = e.id) AS total_asist
    FROM ec_estudiantes e WHERE e.programa_id = ? AND e.activo = 1 ORDER BY e.nombre_completo");
$est->execute([$id]);
$estudiantes = $est->fetchAll();

$titulo = $P['nombre'];
$color_prog = $P['color'] ?: '#7c3aed';

$dias_map = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];
$horario = ($dias_map[$P['dia_semana']] ?? $P['dia_semana'])
         . ' &middot; ' . substr($P['hora_inicio'],0,5) . '&ndash;' . substr($P['hora_fin'],0,5);

$estados_ses = [
    'programada' => ['Programada','#6B7280','#F3F4F6'],
    'dictada'    => ['Dictada','#0d6e5f','#d1fae5'],
    'fallida_justificada' => ['Falla justificada','#b85f00','#fff2d6'],
    'fallida_no_justificada' => ['Falla no justificada','#991b1b','#fde3e4'],
    'recuperada' => ['Recuperada','#1d4ed8','#dbeafe'],
    'cancelada'  => ['Cancelada','#991b1b','#fde3e4'],
];

$valor_total = (float)$P['valor_por_nino'] * (int)$P['cantidad_ninos'];
$bajo_minimo = $P['cantidad_ninos'] < $P['minimo_ninos'];

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= h($P['nombre']) ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/contratos/ver.php?id=<?= $P['contrato_id'] ?>"><?= h($P['contrato_nombre']) ?></a>
      <i class="bi bi-chevron-right"></i>
      <?= h($P['cliente_nombre']) ?>
    </span></small>
  </div>
  <a href="<?= $U ?>modulos/extracurriculares/programas/form.php?id=<?= $id ?>" class="btn-rsal-secondary">
    <i class="bi bi-pencil-fill"></i> Editar
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'sesiones_generadas'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> 4 sesiones generadas autom&aacute;ticamente.</div>
  <?php elseif ($msg === 'extendido'): $n = (int)($_GET['n'] ?? 4); ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> <?= $n ?> sesiones adicionales agregadas al programa.</div>
  <?php elseif ($msg === 'ses_manual_ok'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Sesi&oacute;n creada/actualizada.</div>
  <?php elseif ($msg === 'sin_sesiones'): ?><div class="alert-rsal alert-info"><i class="bi bi-info-circle-fill"></i> A&uacute;n no hay sesiones. Genera las primeras 4 con el bot&oacute;n m&aacute;gico.</div>
  <?php elseif ($msg === 'asignado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Tallerista asignado a la sesi&oacute;n.</div>
  <?php elseif ($msg === 'asignado_masivo'): $n = (int)($_GET['n'] ?? 0); ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> <?= $n ?> sesi&oacute;n<?= $n != 1 ? 'es' : '' ?> asignada<?= $n != 1 ? 's' : '' ?> con el tallerista seleccionado.</div>
  <?php elseif ($msg === 'ya_generadas'): ?><div class="alert-rsal alert-info"><i class="bi bi-info-circle-fill"></i> El programa ya ten&iacute;a sesiones creadas.</div>
  <?php elseif ($msg === 'est_creado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Estudiante agregado.</div>
  <?php elseif ($msg === 'est_masivo'): $n = (int)($_GET['n'] ?? 0); ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> <?= $n ?> estudiantes agregados desde lista.</div>
  <?php elseif ($msg === 'asistencia_ok'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Asistencia registrada.</div>
  <?php elseif ($msg === 'ses_editada'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Sesi&oacute;n actualizada.</div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:1.4rem;align-items:start;">

    <div>

      <div class="card-rsal" style="border-left:4px solid <?= h($color_prog) ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem;gap:1rem;">
          <div>
            <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:700;color:var(--dark);">
              <?= h($P['nombre']) ?>
            </div>
            <?php if ($P['curso_nombre']): ?>
              <div style="font-size:.78rem;color:var(--muted);margin-top:.2rem;">
                <i class="bi bi-journal-check"></i> Curso RSAL: <strong style="color:var(--dark);"><?= h($P['curso_nombre']) ?></strong>
              </div>
            <?php endif; ?>
          </div>
          <?php if ($bajo_minimo): ?>
            <span style="background:#fff2d6;color:#92400e;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:10px;white-space:nowrap;">
              <i class="bi bi-exclamation-triangle-fill"></i> Bajo m&iacute;nimo
            </span>
          <?php endif; ?>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.8rem;padding-top:.8rem;border-top:1px solid var(--border);">
          <div>
            <div style="font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Horario</div>
            <div style="font-weight:700;color:var(--dark);font-size:.85rem;margin-top:.2rem;"><?= $horario ?></div>
          </div>
          <div>
            <div style="font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Ni&ntilde;os / m&iacute;n</div>
            <div style="font-family:'Poppins',sans-serif;font-weight:900;color:<?= $bajo_minimo ? '#92400e' : '#0d6e5f' ?>;font-size:1.05rem;margin-top:.2rem;">
              <?= (int)$P['cantidad_ninos'] ?> <span style="font-weight:500;color:var(--muted);font-size:.75rem;">/ <?= (int)$P['minimo_ninos'] ?></span>
            </div>
          </div>
          <div>
            <div style="font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Tarifa</div>
            <div style="font-weight:700;color:var(--dark);font-size:.85rem;margin-top:.2rem;">$<?= number_format($P['valor_por_nino'], 0, ',', '.') ?><span style="font-size:.7rem;color:var(--muted);">/ni&ntilde;o</span></div>
          </div>
          <div>
            <div style="font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Valor total</div>
            <div style="font-family:'Poppins',sans-serif;font-weight:900;color:#065f46;font-size:1.05rem;margin-top:.2rem;">$<?= number_format($valor_total, 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <div class="card-rsal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.6rem;">
          <div class="card-rsal-title" style="margin:0;"><i class="bi bi-calendar-event"></i> Sesiones (<?= count($lista_sesiones) ?>)</div>
          <?php if (empty($lista_sesiones)): ?>
            <form method="POST" action="<?= $U ?>modulos/extracurriculares/sesiones/generar.php"
                  onsubmit="return confirm('Se generar&aacute;n autom&aacute;ticamente las 4 sesiones del programa a partir del <?= date('d/m/Y', strtotime($P['contrato_inicio'])) ?> los <?= $dias_map[$P['dia_semana']] ?? $P['dia_semana'] ?> a las <?= substr($P['hora_inicio'],0,5) ?>. &iquest;Continuar?');">
              <input type="hidden" name="programa_id" value="<?= $id ?>"/>
              <button type="submit" class="btn-rsal-primary" style="padding:.5rem .9rem;font-size:.82rem;">
                <i class="bi bi-magic"></i> Generar 4 sesiones autom&aacute;ticamente
              </button>
            </form>
          <?php else:
            $ultima_ses = end($lista_sesiones);
            $prox_fecha = (new DateTime($ultima_ses['fecha']))->modify('+7 days')->format('d/m/Y');
            $prox_num   = count($lista_sesiones) + 1;
          ?>
            <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
              <a href="<?= $U ?>modulos/extracurriculares/asignaciones/programa.php?programa=<?= $id ?>"
                 class="btn-rsal-primary" style="padding:.45rem .8rem;font-size:.78rem;">
                <i class="bi bi-people-fill"></i> Asignar tallerista
              </a>
              <form method="POST" action="<?= $U ?>modulos/extracurriculares/sesiones/extender.php"
                    onsubmit="return confirm('Se agregar&aacute;n 4 sesiones m&aacute;s a partir del <?= $prox_fecha ?> (sesiones #<?= $prox_num ?> a #<?= $prox_num+3 ?>). &iquest;Continuar?');">
                <input type="hidden" name="programa_id" value="<?= $id ?>"/>
                <button type="submit" class="btn-rsal-secondary" style="padding:.45rem .8rem;font-size:.78rem;">
                  <i class="bi bi-plus-circle-fill"></i> Extender +4
                </button>
              </form>
              <a href="<?= $U ?>modulos/extracurriculares/sesiones/manual.php?programa=<?= $id ?>"
                 class="btn-rsal-secondary" style="padding:.45rem .8rem;font-size:.78rem;">
                <i class="bi bi-calendar-plus"></i> Sesi&oacute;n manual
              </a>
            </div>
          <?php endif; ?>
        </div>

        <?php if (empty($lista_sesiones)): ?>
          <div style="padding:1.4rem 1rem;text-align:center;color:var(--muted);font-size:.88rem;background:var(--gray);border-radius:10px;border:2px dashed var(--border);">
            <i class="bi bi-calendar-plus" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.5;"></i>
            <div>A&uacute;n no hay sesiones creadas.</div>
            <div style="font-size:.75rem;margin-top:.3rem;">Haz clic en "Generar 4 sesiones" arriba. Las fechas saldr&aacute;n desde <strong><?= date('d/m/Y', strtotime($P['contrato_inicio'])) ?></strong> los <strong><?= $dias_map[$P['dia_semana']] ?? $P['dia_semana'] ?></strong>.</div>
          </div>
        <?php else: ?>
          <div style="display:grid;gap:.6rem;">
            <?php foreach ($lista_sesiones as $ses):
              $e = $estados_ses[$ses['estado']] ?? $estados_ses['programada'];
              $pct_asist = $ses['registrados'] > 0 ? round(100 * $ses['asistieron'] / $ses['registrados']) : 0;
            ?>
              <div style="display:grid;grid-template-columns:60px 1fr auto auto;gap:.8rem;padding:.75rem 1rem;background:var(--gray);border-radius:10px;border:1px solid var(--border);align-items:center;">
                <div style="text-align:center;">
                  <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:900;color:<?= h($color_prog) ?>;line-height:1;">#<?= $ses['numero_sesion'] ?></div>
                </div>
                <div>
                  <div style="font-weight:700;color:var(--dark);font-size:.9rem;">
                    <?= date('D d M Y', strtotime($ses['fecha'])) ?>
                  </div>
                  <div style="font-size:.72rem;color:var(--muted);">
                    <?= substr($ses['hora_inicio'],0,5) ?> &ndash; <?= substr($ses['hora_fin'],0,5) ?>
                    <?php if ($ses['tema_planeado']): ?>
                      &middot; <?= h($ses['tema_planeado']) ?>
                    <?php endif; ?>
                  </div>
                  <?php if ($ses['tallerista_principal']): ?>
                    <div style="font-size:.7rem;color:#1d4ed8;margin-top:.25rem;">
                      <i class="bi bi-person-fill"></i> <?= h($ses['tallerista_principal']) ?>
                      <?php if ($ses['tallerista_apoyo']): ?>
                        <span style="color:var(--muted);"> + <?= h($ses['tallerista_apoyo']) ?></span>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <div style="font-size:.7rem;color:#b85f00;margin-top:.25rem;font-weight:600;">
                      <i class="bi bi-person-exclamation"></i> Sin tallerista asignado
                    </div>
                  <?php endif; ?>
                </div>
                <div style="text-align:center;">
                  <span style="background:<?= $e[2] ?>;color:<?= $e[1] ?>;font-size:.65rem;font-weight:700;padding:3px 9px;border-radius:9px;white-space:nowrap;">
                    <?= $e[0] ?>
                  </span>
                  <?php if ($ses['registrados'] > 0): ?>
                    <div style="font-size:.7rem;color:var(--muted);margin-top:.3rem;">
                      <?= (int)$ses['asistieron'] ?>/<?= (int)$ses['registrados'] ?> (<?= $pct_asist ?>%)
                    </div>
                  <?php endif; ?>
                </div>
                <div style="display:flex;gap:.3rem;">
                  <a href="<?= $U ?>modulos/extracurriculares/asignaciones/sesion.php?sesion=<?= $ses['id'] ?>"
                     class="btn-rsal-secondary" style="padding:.35rem .5rem;font-size:.72rem;"
                     title="Asignar tallerista">
                    <i class="bi bi-person-plus-fill"></i>
                  </a>
                  <a href="<?= $U ?>modulos/extracurriculares/sesiones/manual.php?id=<?= $ses['id'] ?>"
                     class="btn-rsal-secondary" style="padding:.35rem .5rem;font-size:.72rem;"
                     title="Editar fecha u horario">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="<?= $U ?>modulos/extracurriculares/asistencia/tomar.php?sesion=<?= $ses['id'] ?>"
                     class="btn-rsal-primary" style="padding:.35rem .6rem;font-size:.72rem;white-space:nowrap;"
                     title="Tomar o editar asistencia">
                    <i class="bi bi-clipboard-check-fill"></i> Asistencia
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card-rsal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.6rem;">
          <div class="card-rsal-title" style="margin:0;"><i class="bi bi-people-fill"></i> Estudiantes (<?= count($estudiantes) ?>)</div>
          <div style="display:flex;gap:.4rem;">
            <a href="<?= $U ?>modulos/extracurriculares/estudiantes/form.php?programa=<?= $id ?>"
               class="btn-rsal-secondary" style="padding:.4rem .8rem;font-size:.78rem;">
              <i class="bi bi-person-plus-fill"></i> Agregar uno
            </a>
            <a href="<?= $U ?>modulos/extracurriculares/estudiantes/masivo.php?programa=<?= $id ?>"
               class="btn-rsal-primary" style="padding:.4rem .8rem;font-size:.78rem;">
              <i class="bi bi-clipboard-plus-fill"></i> Carga masiva
            </a>
          </div>
        </div>

        <?php if (empty($estudiantes)): ?>
          <div style="padding:1.4rem 1rem;text-align:center;color:var(--muted);font-size:.88rem;background:var(--gray);border-radius:10px;border:2px dashed var(--border);">
            <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.5;"></i>
            <div>A&uacute;n no hay estudiantes inscritos.</div>
            <div style="font-size:.75rem;margin-top:.3rem;">Puedes agregar uno por uno o pegar la lista completa que te env&iacute;e el colegio.</div>
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
            <thead>
              <tr style="background:var(--gray);">
                <th style="padding:.6rem .7rem;text-align:left;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Estudiante</th>
                <th style="padding:.6rem .7rem;text-align:center;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Grado / Edad</th>
                <th style="padding:.6rem .7rem;text-align:center;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Ingreso</th>
                <th style="padding:.6rem .7rem;text-align:center;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Asist.</th>
                <th style="padding:.6rem .7rem;text-align:center;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($estudiantes as $e):
                $pct = $e['total_asist'] > 0 ? round(100 * $e['presentes'] / $e['total_asist']) : 0;
                $col = $pct >= 80 ? '#0d6e5f' : ($pct >= 60 ? '#b85f00' : '#991b1b');
              ?>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:.6rem .7rem;font-weight:600;color:var(--dark);"><?= h($e['nombre_completo']) ?></td>
                <td style="padding:.6rem .7rem;text-align:center;color:var(--muted);font-size:.78rem;">
                  <?= h(trim(($e['grado'] ?? '') . ' &middot; ' . ($e['edad'] ? $e['edad'].' a' : ''), ' &middot;')) ?>
                </td>
                <td style="padding:.6rem .7rem;text-align:center;color:var(--muted);font-size:.78rem;">
                  <?= $e['fecha_ingreso'] ? date('d/m/Y', strtotime($e['fecha_ingreso'])) : '&mdash;' ?>
                </td>
                <td style="padding:.6rem .7rem;text-align:center;">
                  <?php if ($e['total_asist'] > 0): ?>
                    <span style="color:<?= $col ?>;font-weight:700;"><?= $pct ?>%</span>
                    <div style="font-size:.65rem;color:var(--muted);"><?= (int)$e['presentes'] ?>/<?= (int)$e['total_asist'] ?></div>
                  <?php else: ?>
                    <span style="color:var(--muted);font-size:.72rem;">&mdash;</span>
                  <?php endif; ?>
                </td>
                <td style="padding:.6rem .7rem;text-align:center;">
                  <a href="<?= $U ?>modulos/extracurriculares/estudiantes/form.php?id=<?= $e['id'] ?>"
                     class="btn-rsal-secondary" style="padding:.25rem .5rem;font-size:.7rem;">
                    <i class="bi bi-pencil"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <div>

      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-info-circle-fill"></i> Resumen</div>
        <div style="display:grid;gap:.5rem;">
          <div style="display:flex;justify-content:space-between;padding:.5rem .7rem;background:var(--gray);border-radius:8px;">
            <span style="font-size:.78rem;color:var(--muted);">Contrato</span>
            <span style="font-weight:700;color:var(--dark);font-size:.82rem;"><?= h($P['contrato_codigo'] ?: '&mdash;') ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.5rem .7rem;background:var(--gray);border-radius:8px;">
            <span style="font-size:.78rem;color:var(--muted);">Cliente</span>
            <span style="font-weight:700;color:var(--dark);font-size:.82rem;text-align:right;max-width:140px;"><?= h($P['cliente_nombre']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.5rem .7rem;background:var(--gray);border-radius:8px;">
            <span style="font-size:.78rem;color:var(--muted);">Inscritos</span>
            <span style="font-family:'Poppins',sans-serif;font-weight:900;color:#7c3aed;"><?= count($estudiantes) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.5rem .7rem;background:var(--gray);border-radius:8px;">
            <span style="font-size:.78rem;color:var(--muted);">Sesiones</span>
            <span style="font-family:'Poppins',sans-serif;font-weight:900;color:#1E4DA1;"><?= count($lista_sesiones) ?>/4</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.5rem .7rem;background:#d1fae5;border-radius:8px;">
            <span style="font-size:.78rem;color:#065f46;">Valor total</span>
            <span style="font-family:'Poppins',sans-serif;font-weight:900;color:#065f46;font-size:.9rem;">$<?= number_format($valor_total, 0, ',', '.') ?></span>
          </div>
        </div>
      </div>

      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-box-arrow-up-right"></i> Acciones</div>
        <a href="<?= $U ?>modulos/extracurriculares/contratos/ver.php?id=<?= $P['contrato_id'] ?>"
           class="btn-rsal-secondary" style="width:100%;justify-content:center;padding:.55rem;font-size:.8rem;">
          Ver contrato completo
        </a>
        <a href="<?= $U ?>modulos/extracurriculares/programas/form.php?id=<?= $id ?>"
           class="btn-rsal-secondary" style="width:100%;justify-content:center;padding:.55rem;font-size:.8rem;margin-top:.5rem;">
          Editar programa
        </a>
      </div>

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
