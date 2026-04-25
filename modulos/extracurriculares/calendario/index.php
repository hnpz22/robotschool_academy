<?php
// modulos/extracurriculares/calendario/index.php
// Calendario visual de sesiones de extracurriculares
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
require_once ROOT . '/includes/ec_helpers.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_calendario';
$U           = BASE_URL;

$vista = $_GET['vista'] ?? 'mes'; // 'mes' o 'semana'
if (!in_array($vista, ['mes','semana'])) $vista = 'mes';

// Navegacion por ancla
if ($vista === 'mes') {
    $ym = $_GET['ym'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');
    $fecha_ancla = new DateTime($ym . '-01');

    $primer_dia_mes = clone $fecha_ancla;
    $ultimo_dia_mes = clone $fecha_ancla;
    $ultimo_dia_mes->modify('last day of this month');

    // Primer lunes anterior o igual al primer dia del mes
    $desde = clone $primer_dia_mes;
    $dow = (int)$desde->format('N'); // 1-7
    if ($dow > 1) $desde->modify('-' . ($dow - 1) . ' days');

    // Ultimo domingo posterior o igual al ultimo dia del mes
    $hasta = clone $ultimo_dia_mes;
    $dow = (int)$hasta->format('N');
    if ($dow < 7) $hasta->modify('+' . (7 - $dow) . ' days');

    $label_periodo = ec_nombre_mes((int)$fecha_ancla->format('n')) . ' ' . $fecha_ancla->format('Y');

    $prev = (clone $fecha_ancla)->modify('-1 month')->format('Y-m');
    $next = (clone $fecha_ancla)->modify('+1 month')->format('Y-m');
    $hoy_ancla = date('Y-m');
} else {
    // Vista semana - ancla por fecha especifica
    $fs = $_GET['fs'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fs)) $fs = date('Y-m-d');
    $fecha_ancla = new DateTime($fs);

    // Lunes de esa semana
    $desde = clone $fecha_ancla;
    $dow = (int)$desde->format('N');
    if ($dow > 1) $desde->modify('-' . ($dow - 1) . ' days');

    $hasta = (clone $desde)->modify('+6 days');

    $label_periodo = 'Semana del ' . $desde->format('d M') . ' al ' . $hasta->format('d M Y');

    $prev = (clone $desde)->modify('-7 days')->format('Y-m-d');
    $next = (clone $desde)->modify('+7 days')->format('Y-m-d');
    $hoy_ancla = date('Y-m-d');
}

// Filtros
$filtro_cliente_id     = (int)($_GET['cliente']     ?? 0);
$filtro_tallerista_id  = (int)($_GET['tallerista']  ?? 0);
$filtro_estado         = $_GET['estado']            ?? '';

// Query de sesiones en el rango
$sql = "SELECT s.id AS sesion_id, s.fecha, s.hora_inicio, s.hora_fin, s.numero_sesion, s.estado,
               p.id AS programa_id, p.nombre AS programa_nombre, p.color AS programa_color,
               ct.id AS contrato_id, ct.codigo AS contrato_codigo,
               cl.id AS cliente_id, cl.nombre AS cliente_nombre, cl.tipo AS cliente_tipo,
               cl.latitud AS cl_lat, cl.longitud AS cl_lng,
               GROUP_CONCAT(DISTINCT asg.tallerista_id) AS talleristas_ids,
               GROUP_CONCAT(DISTINCT u.nombre ORDER BY asg.rol SEPARATOR ', ') AS talleristas_nombres
        FROM ec_sesiones s
        JOIN ec_programas p  ON p.id = s.programa_id
        JOIN ec_contratos ct ON ct.id = p.contrato_id
        JOIN ec_clientes cl  ON cl.id = ct.cliente_id
        LEFT JOIN ec_asignaciones asg ON asg.sesion_id = s.id
        LEFT JOIN usuarios u ON u.id = asg.tallerista_id
        WHERE s.fecha BETWEEN ? AND ?";
$params = [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];

if ($filtro_cliente_id)    { $sql .= ' AND cl.id = ?';              $params[] = $filtro_cliente_id; }
if ($filtro_tallerista_id) { $sql .= ' AND asg.tallerista_id = ?';  $params[] = $filtro_tallerista_id; }
if ($filtro_estado)        { $sql .= ' AND s.estado = ?';           $params[] = $filtro_estado; }

$sql .= ' GROUP BY s.id ORDER BY s.fecha, s.hora_inicio';

$st = $pdo->prepare($sql);
$st->execute($params);
$sesiones = $st->fetchAll();

// Agrupar por fecha
$por_fecha = [];
foreach ($sesiones as $s) {
    $por_fecha[$s['fecha']][] = $s;
}

// Detectar conflictos por tallerista (mismo tallerista, mismo dia, horarios cruzados)
$conflictos = []; // [sesion_id => true]
$por_tall_fecha = [];
foreach ($sesiones as $s) {
    if (!$s['talleristas_ids']) continue;
    $ids = array_filter(explode(',', $s['talleristas_ids']));
    foreach ($ids as $tid) {
        $key = $tid . '|' . $s['fecha'];
        $por_tall_fecha[$key][] = $s;
    }
}
foreach ($por_tall_fecha as $group) {
    if (count($group) < 2) continue;
    for ($i = 0; $i < count($group); $i++) {
        for ($j = $i+1; $j < count($group); $j++) {
            $a = $group[$i]; $b = $group[$j];
            if ($a['hora_inicio'] < $b['hora_fin'] && $b['hora_inicio'] < $a['hora_fin']) {
                $conflictos[$a['sesion_id']] = true;
                $conflictos[$b['sesion_id']] = true;
            }
        }
    }
}

// Clientes y talleristas para filtros
$clientes = $pdo->query("SELECT id, nombre FROM ec_clientes WHERE activo = 1 ORDER BY nombre")->fetchAll();
$talleristas = $pdo->query("SELECT u.id, u.nombre FROM usuarios u WHERE u.rol = 'docente' AND u.activo = 1 ORDER BY u.nombre")->fetchAll();

$estados_ses = [
    'programada' => ['Programada','#6B7280','#F3F4F6'],
    'dictada'    => ['Dictada','#0d6e5f','#d1fae5'],
    'fallida_justificada' => ['Falla justificada','#b85f00','#fff2d6'],
    'fallida_no_justificada' => ['Falla no justificada','#991b1b','#fde3e4'],
    'recuperada' => ['Recuperada','#1d4ed8','#dbeafe'],
    'cancelada'  => ['Cancelada','#991b1b','#fde3e4'],
];

// Calcular distancias entre sesiones consecutivas del mismo tallerista mismo dia
// distancias[sesion_id] = array de km a sesion anterior
$distancias = [];
$por_tall_fecha_orden = [];
foreach ($por_tall_fecha as $key => $g) {
    usort($g, function($a, $b){ return strcmp($a['hora_inicio'], $b['hora_inicio']); });
    for ($k = 1; $k < count($g); $k++) {
        $prev = $g[$k-1]; $curr = $g[$k];
        $km = ec_haversine_km((float)$prev['cl_lat'], (float)$prev['cl_lng'], (float)$curr['cl_lat'], (float)$curr['cl_lng']);
        if ($km !== null && $km > 0.3) { // solo si hay distancia real
            $distancias[$curr['sesion_id']][] = [
                'km'   => round($km * 1.4, 1), // factor vial
                'desde' => $prev['cliente_nombre']
            ];
        }
    }
}

$titulo = 'Calendario EC';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Calendario Extracurriculares <small><?= count($sesiones) ?> sesi&oacute;n<?= count($sesiones) != 1 ? 'es' : '' ?> en <?= h($label_periodo) ?></small></div>
</header>
<main class="main-content">

  <?php if (($_GET['msg'] ?? '') === 'asignado'): ?>
    <div class="alert-rsal alert-success" style="margin-bottom:1rem;">
      <i class="bi bi-check-circle-fill"></i> Tallerista asignado correctamente.
    </div>
  <?php endif; ?>

  <div style="display:flex;justify-content:space-between;align-items:center;gap:.8rem;flex-wrap:wrap;margin-bottom:1rem;">

    <div style="display:flex;align-items:center;gap:.5rem;">
      <a href="?vista=<?= $vista ?>&<?= $vista === 'mes' ? 'ym='.$prev : 'fs='.$prev ?>" class="btn-rsal-secondary" style="padding:.4rem .7rem;">
        <i class="bi bi-chevron-left"></i>
      </a>
      <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:700;color:var(--dark);padding:0 .6rem;min-width:220px;text-align:center;">
        <?= h($label_periodo) ?>
      </div>
      <a href="?vista=<?= $vista ?>&<?= $vista === 'mes' ? 'ym='.$next : 'fs='.$next ?>" class="btn-rsal-secondary" style="padding:.4rem .7rem;">
        <i class="bi bi-chevron-right"></i>
      </a>
      <a href="?vista=<?= $vista ?>" class="btn-rsal-secondary" style="padding:.4rem .8rem;font-size:.8rem;">
        Hoy
      </a>
    </div>

    <div style="display:flex;gap:.3rem;background:var(--gray);padding:.2rem;border-radius:8px;">
      <a href="?vista=mes&ym=<?= date('Y-m', $vista === 'mes' ? $fecha_ancla->getTimestamp() : $desde->getTimestamp()) ?>"
         style="padding:.35rem .9rem;font-size:.78rem;font-weight:700;border-radius:6px;text-decoration:none;
                background:<?= $vista === 'mes' ? '#fff' : 'transparent' ?>;
                color:<?= $vista === 'mes' ? 'var(--dark)' : 'var(--muted)' ?>;">Mes</a>
      <a href="?vista=semana&fs=<?= $vista === 'semana' ? $desde->format('Y-m-d') : date('Y-m-d') ?>"
         style="padding:.35rem .9rem;font-size:.78rem;font-weight:700;border-radius:6px;text-decoration:none;
                background:<?= $vista === 'semana' ? '#fff' : 'transparent' ?>;
                color:<?= $vista === 'semana' ? 'var(--dark)' : 'var(--muted)' ?>;">Semana</a>
    </div>
  </div>

  <form method="GET" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem;align-items:end;background:var(--gray);padding:.75rem 1rem;border-radius:10px;">
    <input type="hidden" name="vista" value="<?= h($vista) ?>"/>
    <input type="hidden" name="<?= $vista === 'mes' ? 'ym' : 'fs' ?>" value="<?= $vista === 'mes' ? $fecha_ancla->format('Y-m') : $desde->format('Y-m-d') ?>"/>
    <div>
      <label class="field-label" style="margin:0 0 .2rem;">Cliente</label>
      <select name="cliente" class="rsal-select" style="min-width:180px;padding:.4rem .6rem;font-size:.82rem;">
        <option value="0">Todos</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filtro_cliente_id == $c['id'] ? 'selected' : '' ?>><?= h($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="field-label" style="margin:0 0 .2rem;">Tallerista</label>
      <select name="tallerista" class="rsal-select" style="min-width:180px;padding:.4rem .6rem;font-size:.82rem;">
        <option value="0">Todos</option>
        <?php foreach ($talleristas as $t): ?>
          <option value="<?= $t['id'] ?>" <?= $filtro_tallerista_id == $t['id'] ? 'selected' : '' ?>><?= h($t['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="field-label" style="margin:0 0 .2rem;">Estado</label>
      <select name="estado" class="rsal-select" style="min-width:140px;padding:.4rem .6rem;font-size:.82rem;">
        <option value="">Todos</option>
        <?php foreach ($estados_ses as $k => $e): ?>
          <option value="<?= $k ?>" <?= $filtro_estado === $k ? 'selected' : '' ?>><?= $e[0] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-rsal-primary" style="padding:.4rem .9rem;font-size:.82rem;">
      <i class="bi bi-funnel-fill"></i> Filtrar
    </button>
    <?php if ($filtro_cliente_id || $filtro_tallerista_id || $filtro_estado): ?>
      <a href="?vista=<?= $vista ?>&<?= $vista === 'mes' ? 'ym='.$fecha_ancla->format('Y-m') : 'fs='.$desde->format('Y-m-d') ?>" class="btn-rsal-secondary" style="padding:.4rem .7rem;font-size:.82rem;">Limpiar</a>
    <?php endif; ?>
  </form>

  <?php if ($vista === 'mes'): ?>

    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:10px;overflow:hidden;">
      <?php foreach (['Lunes','Martes','Mi&eacute;rcoles','Jueves','Viernes','S&aacute;bado','Domingo'] as $d): ?>
        <div style="background:var(--gray);padding:.5rem;text-align:center;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;"><?= $d ?></div>
      <?php endforeach; ?>

      <?php
      $cur = clone $desde;
      $mes_activo = (int)$fecha_ancla->format('n');
      while ($cur <= $hasta):
        $fstr = $cur->format('Y-m-d');
        $dia_mes = (int)$cur->format('j');
        $es_otro_mes = ((int)$cur->format('n') !== $mes_activo);
        $es_hoy = ($fstr === date('Y-m-d'));
        $ses_dia = $por_fecha[$fstr] ?? [];
      ?>
        <div style="background:<?= $es_otro_mes ? 'var(--gray)' : '#fff' ?>;padding:.4rem;min-height:110px;position:relative;<?= $es_hoy ? 'box-shadow:inset 0 0 0 2px #F26522;' : '' ?>">
          <div style="font-weight:<?= $es_hoy ? '900' : '700' ?>;font-size:.8rem;color:<?= $es_hoy ? '#F26522' : ($es_otro_mes ? 'var(--muted)' : 'var(--dark)') ?>;margin-bottom:.25rem;">
            <?= $dia_mes ?><?= $es_hoy ? ' hoy' : '' ?>
          </div>
          <?php foreach ($ses_dia as $s):
            $color_prog = $s['programa_color'] ?: '#7c3aed';
            $tiene_conflicto = isset($conflictos[$s['sesion_id']]);
            $sin_asignar = empty($s['talleristas_ids']);
            $destino = $sin_asignar
                ? $U . 'modulos/extracurriculares/asignaciones/sesion.php?sesion=' . $s['sesion_id'] . '&volver=calendario'
                : $U . 'modulos/extracurriculares/asistencia/tomar.php?sesion=' . $s['sesion_id'];
          ?>
            <a href="<?= $destino ?>"
               style="display:block;background:<?= $tiene_conflicto ? '#fde3e4' : $color_prog . '22' ?>;
                      border-left:3px solid <?= $tiene_conflicto ? '#E24B4A' : h($color_prog) ?>;
                      color:<?= $tiene_conflicto ? '#791F1F' : '#333' ?>;
                      font-size:.65rem;padding:2px 5px;border-radius:4px;margin-bottom:2px;
                      text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               title="<?= h($s['cliente_nombre']) ?> &middot; <?= h($s['programa_nombre']) ?> &middot; <?= substr($s['hora_inicio'],0,5) ?>&ndash;<?= substr($s['hora_fin'],0,5) ?><?= $s['talleristas_nombres'] ? ' &middot; ' . h($s['talleristas_nombres']) : '' ?><?= $tiene_conflicto ? ' &middot; CONFLICTO' : '' ?>">
              <?php if ($tiene_conflicto): ?><i class="bi bi-exclamation-triangle-fill"></i> <?php endif; ?>
              <strong><?= substr($s['hora_inicio'],0,5) ?></strong> <?= h(mb_substr($s['cliente_nombre'],0,14)) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php
        $cur->modify('+1 day');
      endwhile;
      ?>
    </div>

  <?php else: ?>

    <div style="display:grid;grid-template-columns:60px repeat(7,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:10px;overflow:hidden;">
      <div style="background:var(--gray);padding:.5rem;"></div>
      <?php
      $cur = clone $desde;
      for ($i = 0; $i < 7; $i++):
        $es_hoy = ($cur->format('Y-m-d') === date('Y-m-d'));
      ?>
        <div style="background:var(--gray);padding:.5rem;text-align:center;<?= $es_hoy ? 'background:#fff7ed;' : '' ?>">
          <div style="font-size:.65rem;font-weight:700;color:var(--muted);text-transform:uppercase;"><?= ec_nombre_dia((int)$cur->format('N')) ?></div>
          <div style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:<?= $es_hoy ? '900' : '700' ?>;color:<?= $es_hoy ? '#F26522' : 'var(--dark)' ?>;"><?= $cur->format('j') ?></div>
        </div>
      <?php
        $cur->modify('+1 day');
      endfor;
      ?>

      <?php
      // Grid por horas 7am a 7pm
      for ($hora = 7; $hora <= 19; $hora++):
      ?>
        <div style="background:var(--gray);padding:.3rem;text-align:right;font-size:.68rem;font-weight:700;color:var(--muted);min-height:55px;">
          <?= str_pad($hora,2,'0',STR_PAD_LEFT) ?>:00
        </div>
        <?php
        $cur = clone $desde;
        for ($i = 0; $i < 7; $i++):
          $fstr = $cur->format('Y-m-d');
          $ses_hora = array_filter($por_fecha[$fstr] ?? [], function($s) use ($hora) {
            $h_ini = (int)substr($s['hora_inicio'], 0, 2);
            return $h_ini === $hora;
          });
        ?>
          <div style="background:#fff;padding:.15rem;min-height:55px;position:relative;">
            <?php foreach ($ses_hora as $s):
              $color_prog = $s['programa_color'] ?: '#7c3aed';
              $tiene_conflicto = isset($conflictos[$s['sesion_id']]);
              $km_info = $distancias[$s['sesion_id']] ?? [];
            ?>
              <a href="<?= $U ?>modulos/extracurriculares/asistencia/tomar.php?sesion=<?= $s['sesion_id'] ?>"
                 style="display:block;background:<?= $tiene_conflicto ? '#fde3e4' : $color_prog . '22' ?>;
                        border-left:3px solid <?= $tiene_conflicto ? '#E24B4A' : h($color_prog) ?>;
                        color:<?= $tiene_conflicto ? '#791F1F' : '#333' ?>;
                        font-size:.68rem;padding:3px 5px;border-radius:4px;margin-bottom:2px;text-decoration:none;"
                 title="<?= h($s['programa_nombre']) ?> &middot; <?= h($s['cliente_nombre']) ?>">
                <?php if ($tiene_conflicto): ?><i class="bi bi-exclamation-triangle-fill"></i> <?php endif; ?>
                <strong><?= substr($s['hora_inicio'],0,5) ?></strong><br>
                <?= h(mb_substr($s['cliente_nombre'],0,16)) ?>
                <?php if (!empty($km_info)): ?>
                  <div style="font-size:.6rem;color:var(--muted);margin-top:1px;">
                    <i class="bi bi-geo-alt"></i> <?= $km_info[0]['km'] ?> km
                  </div>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php
          $cur->modify('+1 day');
        endfor;
      endfor;
      ?>
    </div>

  <?php endif; ?>

  <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem;padding:.75rem 1rem;background:var(--gray);border-radius:10px;font-size:.75rem;">
    <div style="font-weight:700;color:var(--muted);">Leyenda:</div>
    <div style="display:flex;align-items:center;gap:.3rem;">
      <span style="width:12px;height:12px;background:#E24B4A;border-radius:3px;"></span>
      <span>Conflicto de horario</span>
    </div>
    <div style="display:flex;align-items:center;gap:.3rem;">
      <i class="bi bi-geo-alt" style="font-size:.9rem;color:var(--muted);"></i>
      <span>Distancia desde sesi&oacute;n anterior del tallerista</span>
    </div>
    <div style="display:flex;align-items:center;gap:.3rem;">
      <span style="width:12px;height:12px;background:#F26522;border-radius:3px;"></span>
      <span>D&iacute;a de hoy</span>
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
