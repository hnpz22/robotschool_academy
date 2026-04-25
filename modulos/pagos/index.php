<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Pagos';
$menu_activo = 'pagos';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';
$buscar      = trim($_GET['buscar'] ?? '');
$filtro_est  = $_GET['estado'] ?? '';

// Actualizar pagos vencidos autom&aacute;ticamente
$pdo->query("UPDATE pagos SET estado='vencido' WHERE estado='pendiente' AND fecha_limite < CURDATE() AND valor_pagado = 0");
$pdo->query("UPDATE pagos SET estado='vencido' WHERE estado='parcial'  AND fecha_limite < CURDATE() AND valor_pagado < valor_total");

$where  = ['1=1'];
$params = [];
if ($sede_filtro) { $where[] = 'm.sede_id = ?'; $params[] = $sede_filtro; }
if ($filtro_est)  { $where[] = 'p.estado = ?';  $params[] = $filtro_est; }
if ($buscar) {
    $where[] = '(e.nombre_completo LIKE ? OR pa.nombre_completo LIKE ?)';
    $params  = array_merge($params, ["%$buscar%","%$buscar%"]);
}

$pagos = $pdo->prepare("
    SELECT p.*, e.nombre_completo AS estudiante, e.avatar,
        pa.nombre_completo AS padre, pa.telefono AS padre_tel,
        c.nombre AS curso, g.nombre AS grupo, m.sede_id,
        (p.valor_total - p.valor_pagado) AS saldo
    FROM pagos p
    JOIN matriculas m  ON m.id = p.matricula_id
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN padres pa     ON pa.id = p.padre_id
    JOIN grupos g      ON g.id = m.grupo_id
    JOIN cursos c      ON c.id = g.curso_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY
        FIELD(p.estado,'vencido','pendiente','parcial','pagado','exonerado'),
        p.fecha_limite ASC
");
$pagos->execute($params);
$pagos = $pagos->fetchAll();

// Totales sem&aacute;foro
$sem = ['pagado'=>0,'parcial'=>0,'pendiente'=>0,'vencido'=>0,'exonerado'=>0];
foreach ($pagos as $pg) { if (isset($sem[$pg['estado']])) $sem[$pg['estado']]++; }

$estados_label = ['pagado'=>'Pagado','parcial'=>'Parcial','pendiente'=>'Pendiente','vencido'=>'Vencido','exonerado'=>'Exonerado'];

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Pagos <small>Seguimiento y sem&aacute;foros</small>
  </div>
</header>
<main class="main-content">

  <?php if ($msg === 'abono'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Abono registrado correctamente.</div>
  <?php endif; ?>

  <!-- Sem&aacute;foros resumen -->
  <div style="display:flex;gap:1rem;margin-bottom:1.6rem;flex-wrap:wrap;">
    <?php
      $sem_config = [
        'vencido'    => ['color'=>'var(--red)',   'bg'=>'var(--red-l)',  'icon'=>'bi-x-circle-fill',       'label'=>'Vencidos'],
        'pendiente'  => ['color'=>'#ca8a04',      'bg'=>'#fef9c3',       'icon'=>'bi-clock-fill',           'label'=>'Pendientes'],
        'parcial'    => ['color'=>'#F59E0B',       'bg'=>'#fff8e1',       'icon'=>'bi-circle-half',          'label'=>'Parciales'],
        'pagado'     => ['color'=>'#16a34a',       'bg'=>'#dcfce7',       'icon'=>'bi-check-circle-fill',    'label'=>'Pagados'],
        'exonerado'  => ['color'=>'var(--muted)',  'bg'=>'var(--gray2)',  'icon'=>'bi-shield-check-fill',   'label'=>'Exonerados'],
      ];
      foreach ($sem_config as $est => $cfg):
    ?>
    <a href="?estado=<?= $est ?><?= $buscar?'&buscar='.urlencode($buscar):'' ?>"
       style="text-decoration:none;flex:1;min-width:120px;">
      <div style="background:<?= $filtro_est===$est?$cfg['bg']:'#fff' ?>;border:1.5px solid <?= $filtro_est===$est?$cfg['color']:' var(--border)' ?>;border-radius:14px;padding:.9rem 1rem;display:flex;align-items:center;gap:.7rem;transition:all .2s;"
           onmouseover="this.style.borderColor='<?= $cfg['color'] ?>'" onmouseout="this.style.borderColor='<?= $filtro_est===$est?$cfg['color']:'var(--border)' ?>'">
        <i class="bi <?= $cfg['icon'] ?>" style="font-size:1.3rem;color:<?= $cfg['color'] ?>;flex-shrink:0;"></i>
        <div>
          <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:900;color:<?= $cfg['color'] ?>;line-height:1;">
            <?= $sem[$est] ?>
          </div>
          <div style="font-size:.7rem;color:var(--muted);font-weight:600;"><?= $cfg['label'] ?></div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
    <?php if ($filtro_est): ?>
      <a href="?" style="display:flex;align-items:center;gap:.3rem;padding:.5rem .9rem;background:var(--gray2);color:var(--muted);border-radius:10px;text-decoration:none;font-size:.8rem;font-weight:700;align-self:center;">
        <i class="bi bi-x-lg"></i> Quitar filtro
      </a>
    <?php endif; ?>
  </div>

  <!-- Toolbar -->
  <div class="toolbar" style="margin-bottom:1rem;">
    <div class="toolbar-left">
      <form method="GET" style="display:contents;">
        <input type="hidden" name="estado" value="<?= h($filtro_est) ?>"/>
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="buscar" placeholder="Buscar estudiante o padre..."
                 value="<?= h($buscar) ?>" onchange="this.form.submit()"/>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($pagos)): ?>
    <div class="empty-state">
      <i class="bi bi-cash-stack"></i>
      <h3>No hay pagos<?= $filtro_est ? ' '.strtolower($estados_label[$filtro_est]) : '' ?></h3>
      <p>Los pagos se crean autom&aacute;ticamente al matricular un estudiante.</p>
    </div>
  <?php else: ?>
    <div class="card-rsal" style="padding:0;overflow:hidden;margin:0;">
      <table class="table-rsal">
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Padre / Acudiente</th>
            <th>Curso</th>
            <th>Total</th>
            <th>Pagado</th>
            <th>Saldo</th>
            <th>Vence</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagos as $p):
            $sem_color = [
              'pagado'    => '#16a34a',
              'parcial'   => '#ca8a04',
              'pendiente' => '#ca8a04',
              'vencido'   => 'var(--red)',
              'exonerado' => 'var(--muted)',
            ][$p['estado']] ?? 'var(--muted)';
            $sem_bg = [
              'pagado'    => '#dcfce7',
              'parcial'   => '#fef9c3',
              'pendiente' => '#fef9c3',
              'vencido'   => 'var(--red-l)',
              'exonerado' => 'var(--gray2)',
            ][$p['estado']] ?? 'var(--gray2)';
            $pct = $p['valor_total'] > 0 ? round(($p['valor_pagado']/$p['valor_total'])*100) : 0;
            $bar_color = $pct>=100?'#16a34a':($pct>=50?'#F59E0B':'var(--red)');
            $vence_hoy = $p['fecha_limite'] && $p['fecha_limite'] <= date('Y-m-d');
          ?>
          <tr style="<?= $p['estado']==='vencido'?'background:rgba(232,25,44,.03)':'' ?>">
            <td>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <?php if ($p['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$p['avatar'])): ?>
                  <img src="<?= $U ?>uploads/estudiantes/<?= h($p['avatar']) ?>"
                       style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;"/>
                <?php else: ?>
                  <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;color:#fff;flex-shrink:0;">
                    <?= strtoupper(substr($p['estudiante'],0,2)) ?>
                  </div>
                <?php endif; ?>
                <span style="font-size:.82rem;font-weight:600;"><?= h($p['estudiante']) ?></span>
              </div>
            </td>
            <td>
              <div style="font-size:.82rem;"><?= h($p['padre']) ?></div>
              <div style="font-size:.72rem;color:var(--muted);">&#128222; <?= h($p['padre_tel']) ?></div>
            </td>
            <td style="font-size:.8rem;"><?= h($p['curso']) ?></td>
            <td style="font-size:.85rem;font-weight:700;"><?= formatCOP($p['valor_total']) ?></td>
            <td>
              <div style="font-size:.85rem;font-weight:700;color:#16a34a;"><?= formatCOP($p['valor_pagado']) ?></div>
              <div style="height:4px;background:var(--gray2);border-radius:2px;margin-top:3px;width:60px;">
                <div style="height:100%;width:<?= min($pct,100) ?>%;background:<?= $bar_color ?>;border-radius:2px;"></div>
              </div>
            </td>
            <td style="font-size:.85rem;font-weight:700;color:<?= (float)$p['saldo']>0?'var(--red)':'#16a34a' ?>;">
              <?= formatCOP($p['saldo']) ?>
            </td>
            <td style="font-size:.8rem;color:<?= $vence_hoy&&$p['estado']!=='pagado'?'var(--red)':'var(--muted)' ?>;font-weight:<?= $vence_hoy?'700':'400' ?>;">
              <?= $p['fecha_limite'] ? formatFecha($p['fecha_limite']) : '&mdash;' ?>
              <?= $vence_hoy && $p['estado']!=='pagado' ? '<br><span style="font-size:.68rem;">&#9888;&#65039; Vencido</span>' : '' ?>
            </td>
            <td>
              <span style="background:<?= $sem_bg ?>;color:<?= $sem_color ?>;font-size:.7rem;font-weight:800;padding:.25rem .6rem;border-radius:20px;white-space:nowrap;">
                <?= $estados_label[$p['estado']] ?? $p['estado'] ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <?php if ($p['estado'] !== 'pagado' && $p['estado'] !== 'exonerado'): ?>
                <button onclick="abrirAbono(<?= $p['id'] ?>, '<?= h(addslashes($p['estudiante'])) ?>', <?= $p['saldo'] ?>)"
                        class="btn-rsal-primary" style="padding:.35rem .7rem;font-size:.75rem;" title="Registrar abono">
                  <i class="bi bi-cash-coin"></i>
                </button>
                <?php endif; ?>
                <button onclick="verAbonos(<?= $p['id'] ?>)"
                        class="btn-rsal-secondary" style="padding:.35rem .7rem;font-size:.75rem;" title="Ver historial">
                  <i class="bi bi-list-ul"></i>
                </button>
                <a href="<?= $U ?>modulos/pagos/recibo.php?id=<?= $p['id'] ?>" target="_blank"
                   style="padding:.35rem .7rem;font-size:.75rem;background:var(--teal);color:#fff;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;" title="Imprimir recibo">
                  <i class="bi bi-receipt"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</main>

<!-- Modal Abono -->
<div id="modalAbono" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,22,35,.6);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:18px;padding:2rem;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.25);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;">
      <h3 style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:800;">Registrar abono</h3>
      <button onclick="cerrarAbono()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--muted);">&#10005;</button>
    </div>
    <div id="abonoEstudiante" style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;"></div>
    <form method="POST" action="<?= $U ?>modulos/pagos/abono.php">
      <input type="hidden" name="pago_id" id="abonoId"/>
      <label class="field-label">Valor del abono <span class="req">*</span></label>
      <input type="number" name="valor" id="abonoValor" class="rsal-input" required min="1" step="1000" placeholder="$ 0"/>
      <label class="field-label">Medio de pago</label>
      <select name="medio_pago" class="rsal-select">
        <option value="efectivo">&#128181; Efectivo</option>
        <option value="transferencia">&#127974; Transferencia</option>
        <option value="nequi">&#128241; Nequi</option>
        <option value="daviplata">&#128241; Daviplata</option>
        <option value="pse">&#128187; PSE</option>
        <option value="tarjeta">&#128179; Tarjeta</option>
        <option value="otro">Otro</option>
      </select>
      <label class="field-label">N&deg; Comprobante / Referencia</label>
      <input type="text" name="comprobante" class="rsal-input" placeholder="Opcional"/>
      <label class="field-label">Observaciones</label>
      <input type="text" name="observaciones" class="rsal-input" placeholder="Opcional"/>
      <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.75rem;margin-top:.5rem;">
        <i class="bi bi-cash-coin"></i> Registrar abono
      </button>
    </form>
  </div>
</div>

<script>
function abrirAbono(id, estudiante, saldo) {
  document.getElementById('abonoId').value    = id;
  document.getElementById('abonoValor').value = Math.round(saldo);
  document.getElementById('abonoEstudiante').textContent = 'Estudiante: ' + estudiante + ' &middot; Saldo: $' + Math.round(saldo).toLocaleString('es-CO');
  const m = document.getElementById('modalAbono');
  m.style.display = 'flex';
}
function cerrarAbono() {
  document.getElementById('modalAbono').style.display = 'none';
}
function verAbonos(id) {
  window.location = '<?= $U ?>modulos/pagos/abonos.php?pago_id=' + id;
}
document.getElementById('modalAbono').addEventListener('click', function(e) {
  if (e.target === this) cerrarAbono();
});
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
