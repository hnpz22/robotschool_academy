<?php
// modulos/extracurriculares/contratos/ver.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_contratos';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php'); exit; }

$s = $pdo->prepare("SELECT ct.*, cl.nombre AS cliente_nombre, cl.tipo AS cliente_tipo,
                    cl.ciudad AS cliente_ciudad, cl.direccion AS cliente_direccion,
                    cl.contacto_nombre, cl.contacto_telefono, cl.contacto_email
                    FROM ec_contratos ct
                    JOIN ec_clientes cl ON cl.id = ct.cliente_id
                    WHERE ct.id = ?");
$s->execute([$id]);
$ct = $s->fetch();
if (!$ct) { header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php'); exit; }

// Traer programas del contrato
$ps = $pdo->prepare("SELECT p.*, c.nombre AS curso_nombre,
                     (SELECT COUNT(*) FROM ec_sesiones ses WHERE ses.programa_id = p.id) AS sesiones_creadas,
                     (SELECT COUNT(*) FROM ec_estudiantes e WHERE e.programa_id = p.id AND e.activo = 1) AS total_estudiantes
                     FROM ec_programas p
                     LEFT JOIN cursos c ON c.id = p.curso_id
                     WHERE p.contrato_id = ?
                     ORDER BY p.dia_semana, p.hora_inicio");
$ps->execute([$id]);
$programas = $ps->fetchAll();

$titulo = $ct['nombre'];

$estado_labels = [
    'borrador'   => ['Borrador',   '#6B7280', '#F3F4F6'],
    'vigente'    => ['Vigente',    '#0d6e5f', '#d1fae5'],
    'suspendido' => ['Suspendido', '#b85f00', '#fff2d6'],
    'finalizado' => ['Finalizado', '#1f2937', '#E5E7EB'],
    'cancelado'  => ['Cancelado',  '#991b1b', '#fde3e4'],
];
$e_lbl = $estado_labels[$ct['estado']] ?? $estado_labels['borrador'];

$prog_estados = [
    'planeado'   => ['Planeado',  '#1E4DA1', '#dbeafe'],
    'en_curso'   => ['En curso',  '#0d6e5f', '#d1fae5'],
    'finalizado' => ['Finalizado','#1f2937', '#E5E7EB'],
    'suspendido' => ['Suspendido','#b85f00', '#fff2d6'],
];

$dias_map = ['lunes'=>'Lun','martes'=>'Mar','miercoles'=>'Mi&eacute;','jueves'=>'Jue','viernes'=>'Vie','sabado'=>'S&aacute;b','domingo'=>'Dom'];

// Totales calculados: cantidad_ninos x valor_por_nino por cada programa
$total_ninos_plan = array_sum(array_column($programas, 'cantidad_ninos'));
$total_valor_prog = 0;
foreach ($programas as $p) {
    $vn = (float)$p['valor_por_nino'];
    $total_valor_prog += $vn * (int)$p['cantidad_ninos'];
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= h($ct['nombre']) ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/contratos/index.php">Contratos</a>
      <i class="bi bi-chevron-right"></i>
      <?= h($ct['cliente_nombre']) ?>
    </span></small>
  </div>
  <a href="<?= $U ?>modulos/extracurriculares/contratos/form.php?id=<?= $id ?>" class="btn-rsal-secondary">
    <i class="bi bi-pencil-fill"></i> Editar
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Contrato creado correctamente.</div>
  <?php elseif ($msg === 'editado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Contrato actualizado.</div>
  <?php elseif ($msg === 'prog_creado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Programa agregado al contrato.</div>
  <?php elseif ($msg === 'prog_editado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Programa actualizado.</div>
  <?php elseif ($msg === 'prog_eliminado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Programa eliminado.</div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 280px;gap:1.4rem;align-items:start;">

    <div>

      <div class="card-rsal">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem;">
          <div>
            <div style="font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:700;color:var(--dark);">
              <?= h($ct['nombre']) ?>
            </div>
            <?php if ($ct['codigo']): ?>
              <div style="font-family:monospace;font-size:.78rem;color:var(--muted);margin-top:.2rem;"><?= h($ct['codigo']) ?></div>
            <?php endif; ?>
          </div>
          <span style="background:<?= $e_lbl[2] ?>;color:<?= $e_lbl[1] ?>;font-size:.7rem;font-weight:700;padding:4px 12px;border-radius:12px;">
            <?= $e_lbl[0] ?>
          </span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;padding-top:.8rem;border-top:1px solid var(--border);">
          <div>
            <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Vigencia</div>
            <div style="font-weight:700;color:var(--dark);font-size:.9rem;">
              <?= date('d/m/Y', strtotime($ct['fecha_inicio'])) ?>
            </div>
            <div style="font-size:.78rem;color:var(--muted);">a <?= date('d/m/Y', strtotime($ct['fecha_fin'])) ?></div>
          </div>
          <div>
            <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Duraci&oacute;n</div>
            <div style="font-weight:700;color:var(--dark);font-size:.9rem;text-transform:capitalize;"><?= h($ct['tipo_duracion']) ?></div>
          </div>
          <div>
            <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Valor total</div>
            <div style="font-family:'Poppins',sans-serif;font-weight:900;color:#0d6e5f;font-size:1.1rem;">
              $<?= number_format($ct['valor_total'], 0, ',', '.') ?>
            </div>
          </div>
        </div>

        <?php if ($ct['observaciones'] || $ct['condiciones_pago']): ?>
        <div style="padding-top:.8rem;margin-top:.8rem;border-top:1px solid var(--border);font-size:.82rem;color:var(--muted);line-height:1.6;">
          <?php if ($ct['condiciones_pago']): ?>
            <div><strong style="color:var(--dark);">Condiciones de pago:</strong> <?= h($ct['condiciones_pago']) ?></div>
          <?php endif; ?>
          <?php if ($ct['observaciones']): ?>
            <div style="margin-top:.4rem;"><?= nl2br(h($ct['observaciones'])) ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="card-rsal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
          <div class="card-rsal-title" style="margin:0;"><i class="bi bi-bookmark-check-fill"></i> Programas del contrato</div>
          <a href="<?= $U ?>modulos/extracurriculares/programas/form.php?contrato=<?= $id ?>" class="btn-rsal-primary" style="padding:.45rem .8rem;font-size:.82rem;">
            <i class="bi bi-plus-lg"></i> Agregar programa
          </a>
        </div>

        <?php if (empty($programas)): ?>
          <div style="padding:2rem 1rem;text-align:center;color:var(--muted);font-size:.88rem;background:var(--gray);border-radius:10px;border:2px dashed var(--border);">
            <i class="bi bi-bookmark-plus" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.5;"></i>
            <div>A&uacute;n no hay programas definidos para este contrato.</div>
            <div style="font-size:.75rem;margin-top:.3rem;">Un programa define un curso espec&iacute;fico con su d&iacute;a, hora y n&uacute;mero de sesiones.</div>
          </div>
        <?php else: ?>
          <div style="display:grid;gap:.7rem;">
            <?php foreach ($programas as $p):
              $pe = $prog_estados[$p['estado']] ?? $prog_estados['planeado'];
              $color_prog = $p['color'] ?: '#7c3aed';
              $horario = ($dias_map[$p['dia_semana']] ?? $p['dia_semana'])
                       . ' &middot; ' . substr($p['hora_inicio'],0,5) . '&ndash;' . substr($p['hora_fin'],0,5);
              $vn = (float)$p['valor_por_nino'];
              $valor_prog = $vn * (int)$p['cantidad_ninos'];
              $bajo_minimo = $p['cantidad_ninos'] < $p['minimo_ninos'];
            ?>
              <div style="display:grid;grid-template-columns:6px 1fr auto;gap:.9rem;padding:.85rem;background:var(--gray);border-radius:10px;border:1px solid var(--border);">
                <div style="background:<?= h($color_prog) ?>;border-radius:4px;"></div>
                <div>
                  <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem;">
                    <span style="font-weight:700;color:var(--dark);font-size:.92rem;"><?= h($p['nombre']) ?></span>
                    <span style="background:<?= $pe[2] ?>;color:<?= $pe[1] ?>;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:8px;">
                      <?= $pe[0] ?>
                    </span>
                    <?php if ($bajo_minimo): ?>
                      <span style="background:#fff2d6;color:#92400e;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:8px;" title="Faltan <?= $p['minimo_ninos'] - $p['cantidad_ninos'] ?> ninos para alcanzar el minimo viable">
                        <i class="bi bi-exclamation-triangle-fill"></i> Bajo m&iacute;nimo
                      </span>
                    <?php endif; ?>
                  </div>
                  <?php if ($p['curso_nombre']): ?>
                    <div style="font-size:.76rem;color:var(--muted);margin-bottom:.2rem;">
                      <i class="bi bi-journal-check"></i> Curso RSAL: <strong style="color:var(--dark);"><?= h($p['curso_nombre']) ?></strong>
                    </div>
                  <?php endif; ?>
                  <div style="display:flex;gap:1rem;flex-wrap:wrap;font-size:.76rem;color:var(--muted);">
                    <span><i class="bi bi-calendar-event"></i> <?= $horario ?></span>
                    <span <?= $bajo_minimo ? 'style="color:#92400e;font-weight:700;"' : '' ?>>
                      <i class="bi bi-people-fill"></i> <?= (int)$p['cantidad_ninos'] ?> / m&iacute;n <?= (int)$p['minimo_ninos'] ?>
                    </span>
                    <?php if ($p['equipos_kit']): ?>
                      <span><i class="bi bi-box-seam"></i> <?= h($p['equipos_kit']) ?></span>
                    <?php endif; ?>
                    <span><i class="bi bi-tag"></i> $<?= number_format($vn, 0, ',', '.') ?>/ni&ntilde;o</span>
                    <span style="color:#0d6e5f;font-weight:700;"><i class="bi bi-cash"></i> $<?= number_format($valor_prog, 0, ',', '.') ?></span>
                  </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:.4rem;align-items:flex-end;">
                  <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $p['id'] ?>" class="btn-rsal-primary" style="padding:.3rem .6rem;font-size:.72rem;">
                    <i class="bi bi-eye-fill"></i> Ver
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div style="margin-top:.9rem;padding:.7rem 1rem;background:#d1fae5;border:1px solid #a7f3d0;border-radius:10px;display:flex;justify-content:space-between;align-items:center;font-size:.82rem;">
            <span style="color:#065f46;"><strong>Total calculado</strong> basado en programas</span>
            <span style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;color:#065f46;">
              $<?= number_format($total_valor_prog, 0, ',', '.') ?>
            </span>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <div>

      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-building-fill"></i> Cliente</div>
        <div style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--dark);font-size:1rem;margin-bottom:.3rem;">
          <?= h($ct['cliente_nombre']) ?>
        </div>
        <div style="font-size:.76rem;color:var(--muted);text-transform:capitalize;margin-bottom:.5rem;">
          <?= h($ct['cliente_tipo']) ?>
          <?php if ($ct['cliente_ciudad']): ?>&middot; <?= h($ct['cliente_ciudad']) ?><?php endif; ?>
        </div>
        <?php if ($ct['cliente_direccion']): ?>
          <div style="font-size:.75rem;color:var(--muted);margin-bottom:.6rem;"><i class="bi bi-geo-alt"></i> <?= h($ct['cliente_direccion']) ?></div>
        <?php endif; ?>
        <?php if ($ct['contacto_nombre']): ?>
          <div style="padding-top:.6rem;border-top:1px solid var(--border);font-size:.76rem;">
            <div style="font-weight:700;color:var(--dark);"><?= h($ct['contacto_nombre']) ?></div>
            <?php if ($ct['contacto_telefono']): ?><div style="color:var(--muted);"><i class="bi bi-telephone"></i> <?= h($ct['contacto_telefono']) ?></div><?php endif; ?>
            <?php if ($ct['contacto_email']):    ?><div style="color:var(--muted);"><i class="bi bi-envelope"></i> <?= h($ct['contacto_email']) ?></div><?php endif; ?>
          </div>
        <?php endif; ?>
        <a href="<?= $U ?>modulos/extracurriculares/clientes/form.php?id=<?= $ct['cliente_id'] ?>"
           class="btn-rsal-secondary" style="width:100%;justify-content:center;padding:.5rem;font-size:.76rem;margin-top:.7rem;">
          <i class="bi bi-box-arrow-up-right"></i> Ver ficha completa
        </a>
      </div>

      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-graph-up"></i> Resumen</div>
        <div style="display:grid;gap:.5rem;">
          <div style="display:flex;justify-content:space-between;padding:.5rem .7rem;background:var(--gray);border-radius:8px;">
            <span style="font-size:.78rem;color:var(--muted);">Programas</span>
            <span style="font-family:'Poppins',sans-serif;font-weight:900;color:#7c3aed;"><?= count($programas) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.5rem .7rem;background:var(--gray);border-radius:8px;">
            <span style="font-size:.78rem;color:var(--muted);">Ni&ntilde;os totales</span>
            <span style="font-family:'Poppins',sans-serif;font-weight:900;color:#1E4DA1;"><?= (int)$total_ninos_plan ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.5rem .7rem;background:#d1fae5;border-radius:8px;">
            <span style="font-size:.78rem;color:#065f46;">Valor total</span>
            <span style="font-family:'Poppins',sans-serif;font-weight:900;color:#065f46;font-size:.95rem;">$<?= number_format($ct['valor_total'], 0, ',', '.') ?></span>
          </div>
        </div>
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
