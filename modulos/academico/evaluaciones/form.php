<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Evaluaci&oacute;n';
$menu_activo = 'evaluaciones';
$U           = BASE_URL;

$id         = (int)($_GET['id'] ?? 0);
$evaluacion = null;
$detalle    = [];

if ($id) {
    $s = $pdo->prepare("SELECT * FROM evaluaciones WHERE id=?");
    $s->execute([$id]); $evaluacion = $s->fetch();
    if (!$evaluacion) { header('Location:'.$U.'modulos/academico/evaluaciones/index.php'); exit; }
    $ds = $pdo->prepare("SELECT * FROM evaluacion_detalle WHERE evaluacion_id=?");
    $ds->execute([$id]); 
    foreach ($ds->fetchAll() as $d) $detalle[$d['criterio_id']] = $d;
}

$titulo = $evaluacion ? 'Editar evaluaci&oacute;n' : 'Nueva evaluaci&oacute;n';
$errores = [];

// Matr&iacute;culas activas para el select de estudiante
$sede_filtro = getSedeFiltro();
$where_m = $sede_filtro ? "AND m.sede_id = ".(int)$sede_filtro : "";
$matriculas = $pdo->query("
    SELECT m.id, e.nombre_completo AS estudiante, c.nombre AS curso, g.nombre AS grupo
    FROM matriculas m
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN grupos g ON g.id = m.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    WHERE m.estado IN ('activa','pre_inscrito') $where_m
    ORDER BY e.nombre_completo
")->fetchAll();

// R&uacute;bricas activas
$rubricas = $pdo->query("
    SELECT r.id, r.nombre, c.nombre AS curso_nombre
    FROM rubricas r JOIN cursos c ON c.id=r.curso_id
    WHERE r.activa=1
    ORDER BY r.nombre
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula_id  = (int)($_POST['matricula_id']  ?? 0);
    $rubrica_id    = (int)($_POST['rubrica_id']    ?? 0);
    $fecha         = $_POST['fecha']               ?? date('Y-m-d');
    $observaciones = trim($_POST['observaciones']  ?? '');
    $puntajes      = $_POST['puntaje']             ?? [];
    $obs_criterio  = $_POST['obs_criterio']        ?? [];

    if (!$matricula_id) $errores[] = 'Selecciona el estudiante.';
    if (!$rubrica_id)   $errores[] = 'Selecciona la r&uacute;brica.';
    if (!$fecha)        $errores[] = 'La fecha es obligatoria.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE evaluaciones SET matricula_id=?,rubrica_id=?,docente_id=?,fecha=?,observaciones=? WHERE id=?")
                ->execute([$matricula_id,$rubrica_id,$_SESSION['usuario_id'],$fecha,$observaciones,$id]);
            $pdo->prepare("DELETE FROM evaluacion_detalle WHERE evaluacion_id=?")->execute([$id]);
        } else {
            $pdo->prepare("INSERT INTO evaluaciones (matricula_id,rubrica_id,docente_id,fecha,observaciones) VALUES (?,?,?,?,?)")
                ->execute([$matricula_id,$rubrica_id,$_SESSION['usuario_id'],$fecha,$observaciones]);
            $id = $pdo->lastInsertId();
        }
        // Guardar puntajes por criterio
        foreach ($puntajes as $criterio_id => $pts) {
            $criterio_id = (int)$criterio_id;
            $pts = max(0, (int)$pts);
            $obs_c = trim($obs_criterio[$criterio_id] ?? '');
            $pdo->prepare("INSERT INTO evaluacion_detalle (evaluacion_id,criterio_id,puntaje,observacion) VALUES (?,?,?,?)")
                ->execute([$id, $criterio_id, $pts, $obs_c]);
        }
        header('Location:'.$U.'modulos/academico/evaluaciones/index.php?msg='.($evaluacion?'editada':'creada'));
        exit;
    }
}

// Criterios de la r&uacute;brica seleccionada (para edici&oacute;n)
$criterios_actuales = [];
$rubrica_actual = null;
if ($evaluacion) {
    $ra = $pdo->prepare("SELECT * FROM rubricas WHERE id=?");
    $ra->execute([$evaluacion['rubrica_id']]); $rubrica_actual = $ra->fetch();
    $ca = $pdo->prepare("SELECT * FROM rubrica_criterios WHERE rubrica_id=? ORDER BY orden");
    $ca->execute([$evaluacion['rubrica_id']]); $criterios_actuales = $ca->fetchAll();
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/academico/evaluaciones/index.php">Evaluaciones</a>
      <i class="bi bi-chevron-right"></i>
      <?= $evaluacion ? '#'.$evaluacion['id'] : 'Nueva' ?>
    </span></small>
  </div>
</header>
<main class="main-content">
  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger" style="flex-direction:column;align-items:flex-start;">
      <strong><i class="bi bi-exclamation-circle-fill"></i> Errores:</strong>
      <ul style="margin:.4rem 0 0 1.2rem;"><?php foreach($errores as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.4rem;align-items:start;">

      <!-- IZQUIERDA: Puntajes -->
      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-person-fill"></i> Estudiante y r&uacute;brica</div>

          <label class="field-label">Estudiante / Matr&iacute;cula <span class="req">*</span></label>
          <select name="matricula_id" class="rsal-select" required onchange="cargarCriterios()">
            <option value="">Selecciona el estudiante...</option>
            <?php foreach ($matriculas as $m): ?>
              <option value="<?= $m['id'] ?>" <?= ($evaluacion['matricula_id']??'')==$m['id']?'selected':'' ?>>
                <?= h($m['estudiante']) ?> &mdash; <?= h($m['curso']) ?> (<?= h($m['grupo']) ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <label class="field-label">R&uacute;brica <span class="req">*</span></label>
          <select name="rubrica_id" id="sel_rubrica" class="rsal-select" required onchange="cargarCriterios()">
            <option value="">Selecciona la r&uacute;brica...</option>
            <?php foreach ($rubricas as $r): ?>
              <option value="<?= $r['id'] ?>" <?= ($evaluacion['rubrica_id']??'')==$r['id']?'selected':'' ?>>
                <?= h($r['nombre']) ?> &mdash; <?= h($r['curso_nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- CRITERIOS Y PUNTAJES -->
        <div class="card-rsal" id="criteriosCard" style="<?= $evaluacion ? '' : 'display:none;' ?>">
          <div class="card-rsal-title"><i class="bi bi-star-fill"></i> Puntajes por criterio</div>
          <div id="criteriosBody">
            <?php if ($evaluacion && !empty($criterios_actuales)): ?>
              <?php foreach ($criterios_actuales as $c):
                $pts_actual = $detalle[$c['id']]['puntaje'] ?? 0;
                $obs_actual = $detalle[$c['id']]['observacion'] ?? '';
                $pct_c = $c['puntaje_max'] > 0 ? round(($pts_actual/$c['puntaje_max'])*100) : 0;
              ?>
              <div style="border:1px solid var(--border);border-radius:10px;padding:.9rem;margin-bottom:.7rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;flex-wrap:wrap;gap:.4rem;">
                  <div>
                    <div style="font-weight:700;font-size:.88rem;color:var(--dark);"><?= h($c['criterio']) ?></div>
                    <?php if ($c['descripcion']): ?>
                      <div style="font-size:.75rem;color:var(--muted);"><?= h($c['descripcion']) ?></div>
                    <?php endif; ?>
                  </div>
                  <div style="display:flex;align-items:center;gap:.5rem;">
                    <span style="font-size:.75rem;color:var(--muted);">0 &ndash;</span>
                    <input type="number" name="puntaje[<?= $c['id'] ?>]"
                           class="rsal-input" style="margin:0;width:70px;text-align:center;font-weight:800;"
                           min="0" max="<?= $c['puntaje_max'] ?>" value="<?= $pts_actual ?>"
                           oninput="actualizarBarra(this, <?= $c['puntaje_max'] ?>)"/>
                    <span style="font-size:.75rem;color:var(--muted);">/ <?= $c['puntaje_max'] ?></span>
                  </div>
                </div>
                <!-- Barra de puntaje -->
                <div style="height:6px;background:var(--gray2);border-radius:3px;margin-bottom:.5rem;">
                  <div class="barra-pts" style="height:100%;width:<?= $pct_c ?>%;background:<?= $pct_c>=80?'#16a34a':($pct_c>=60?'#F59E0B':'var(--red)') ?>;border-radius:3px;transition:width .3s;"></div>
                </div>
                <!-- Estrellas visuales -->
                <div class="estrellas-vis" style="font-size:.9rem;color:#F59E0B;margin-bottom:.4rem;">
                  <?= str_repeat('&#9733;', round($pct_c/20)).str_repeat('&#9734;', 5-round($pct_c/20)) ?>
                </div>
                <input type="text" name="obs_criterio[<?= $c['id'] ?>]" class="rsal-input"
                       style="margin:0;font-size:.78rem;" placeholder="Observaci&oacute;n del criterio (opcional)"
                       value="<?= h($obs_actual) ?>"/>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- DERECHA -->
      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-calendar3"></i> Datos de la evaluaci&oacute;n</div>

          <label class="field-label">Fecha <span class="req">*</span></label>
          <input type="date" name="fecha" class="rsal-input" required
                 value="<?= h($evaluacion['fecha'] ?? date('Y-m-d')) ?>"/>

          <label class="field-label">Observaciones generales</label>
          <textarea name="observaciones" class="rsal-textarea" style="min-height:90px;"
                    placeholder="Comentarios generales sobre el desempe&ntilde;o del estudiante..."><?= h($evaluacion['observaciones'] ?? '') ?></textarea>
        </div>

        <!-- Resumen puntaje -->
        <div class="card-rsal" id="resumenCard" style="<?= $evaluacion ? '' : 'display:none;' ?>">
          <div class="card-rsal-title"><i class="bi bi-bar-chart-fill"></i> Resumen</div>
          <div style="text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:2.5rem;font-weight:900;color:var(--teal);line-height:1;" id="totalObtenido">
              <?php if ($evaluacion): ?>
                <?= array_sum(array_column($detalle, 'puntaje')) ?>
              <?php else: ?>0<?php endif; ?>
            </div>
            <div style="font-size:.8rem;color:var(--muted);margin-bottom:.5rem;">
              puntos de <span id="totalPosible"><?= array_sum(array_column($criterios_actuales, 'puntaje_max')) ?></span>
            </div>
            <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:800;color:var(--orange);" id="pctResumen">
              <?php if ($evaluacion && array_sum(array_column($criterios_actuales,'puntaje_max'))>0):
                $p = round(array_sum(array_column($detalle,'puntaje'))/array_sum(array_column($criterios_actuales,'puntaje_max'))*100);
                echo $p.'%';
              else: echo '&mdash;'; endif; ?>
            </div>
            <div style="height:8px;background:var(--gray2);border-radius:4px;margin:.5rem 0;">
              <div id="barraResumen" style="height:100%;width:0%;background:var(--teal);border-radius:4px;transition:width .4s;"></div>
            </div>
            <div style="font-size:1rem;color:#F59E0B;" id="estrellasResumen">&mdash;</div>
          </div>
        </div>

        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $evaluacion ? 'Guardar cambios' : 'Registrar evaluaci&oacute;n' ?>
          </button>
          <a href="<?= $U ?>modulos/academico/evaluaciones/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
        </div>
      </div>

    </div>
  </form>
</main>

<script>
// Cargar criterios via AJAX al seleccionar r&uacute;brica
function cargarCriterios() {
  const rubId = document.getElementById('sel_rubrica').value;
  if (!rubId) {
    document.getElementById('criteriosCard').style.display = 'none';
    document.getElementById('resumenCard').style.display = 'none';
    return;
  }
  fetch('<?= $U ?>modulos/academico/evaluaciones/criterios_ajax.php?rubrica_id=' + rubId)
    .then(r => r.json())
    .then(data => {
      renderCriterios(data.criterios, data.total);
      document.getElementById('criteriosCard').style.display = 'block';
      document.getElementById('resumenCard').style.display = 'block';
    });
}

function renderCriterios(criterios, totalMax) {
  let html = '';
  criterios.forEach(c => {
    html += `
    <div style="border:1px solid var(--border);border-radius:10px;padding:.9rem;margin-bottom:.7rem;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;flex-wrap:wrap;gap:.4rem;">
        <div>
          <div style="font-weight:700;font-size:.88rem;color:var(--dark);">${c.criterio}</div>
          ${c.descripcion ? `<div style="font-size:.75rem;color:var(--muted);">${c.descripcion}</div>` : ''}
        </div>
        <div style="display:flex;align-items:center;gap:.5rem;">
          <span style="font-size:.75rem;color:var(--muted);">0 &ndash;</span>
          <input type="number" name="puntaje[${c.id}]"
                 class="rsal-input" style="margin:0;width:70px;text-align:center;font-weight:800;"
                 min="0" max="${c.puntaje_max}" value="0"
                 oninput="actualizarBarra(this, ${c.puntaje_max})"/>
          <span style="font-size:.75rem;color:var(--muted);">/ ${c.puntaje_max}</span>
        </div>
      </div>
      <div style="height:6px;background:var(--gray2);border-radius:3px;margin-bottom:.5rem;">
        <div class="barra-pts" style="height:100%;width:0%;background:var(--red);border-radius:3px;transition:width .3s;"></div>
      </div>
      <div class="estrellas-vis" style="font-size:.9rem;color:#F59E0B;margin-bottom:.4rem;">&#9734;&#9734;&#9734;&#9734;&#9734;</div>
      <input type="text" name="obs_criterio[${c.id}]" class="rsal-input"
             style="margin:0;font-size:.78rem;" placeholder="Observaci&oacute;n del criterio (opcional)"/>
    </div>`;
  });
  document.getElementById('criteriosBody').innerHTML = html;
  document.getElementById('totalPosible').textContent = totalMax;
  actualizarResumen();
}

function actualizarBarra(input, max) {
  const pct = max > 0 ? Math.min(100, Math.round((parseInt(input.value)||0) / max * 100)) : 0;
  const row = input.closest('div[style*="border"]');
  const barra = row.querySelector('.barra-pts');
  const estrellas = row.querySelector('.estrellas-vis');
  const col = pct>=80?'#16a34a':(pct>=60?'#F59E0B':'var(--red)');
  if (barra) { barra.style.width = pct+'%'; barra.style.background = col; }
  const s = Math.round(pct/20);
  if (estrellas) estrellas.textContent = '&#9733;'.repeat(s)+'&#9734;'.repeat(5-s);
  actualizarResumen();
}

function actualizarResumen() {
  let obtenido = 0, posible = 0;
  document.querySelectorAll('input[name^="puntaje["]').forEach(i => {
    obtenido += parseInt(i.value)||0;
  });
  const posibleEl = document.getElementById('totalPosible');
  if (posibleEl) posible = parseInt(posibleEl.textContent)||0;
  const pct = posible > 0 ? Math.round(obtenido/posible*100) : 0;
  const col = pct>=80?'#16a34a':(pct>=60?'#F59E0B':'var(--red)');
  const el = document.getElementById('totalObtenido');
  const pe = document.getElementById('pctResumen');
  const br = document.getElementById('barraResumen');
  const sr = document.getElementById('estrellasResumen');
  if (el) el.textContent = obtenido;
  if (pe) { pe.textContent = pct+'%'; pe.style.color = col; }
  if (br) { br.style.width = pct+'%'; br.style.background = col; }
  const s = Math.round(pct/20);
  if (sr) sr.textContent = '&#9733;'.repeat(s)+'&#9734;'.repeat(5-s);
}

// Escuchar cambios en puntajes existentes (modo edici&oacute;n)
document.querySelectorAll('input[name^="puntaje["]').forEach(i => {
  i.addEventListener('input', () => actualizarBarra(i, parseInt(i.max)));
});
document.addEventListener('DOMContentLoaded', actualizarResumen);

document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
