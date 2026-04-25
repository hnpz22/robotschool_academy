<?php
// modulos/asistencia/index.php &mdash; Planilla de asistencia directa
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('docente');

$titulo      = 'Asistencia';
$menu_activo = 'asistencia';
$rol         = $_SESSION['usuario_rol'];
$uid         = (int)$_SESSION['usuario_id'];
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

// Grupos visibles segun rol
if ($rol === 'docente') {
    $stmt = $pdo->prepare("
        SELECT g.*, c.nombre AS curso_nombre, s.nombre AS sede_nombre
        FROM grupos g
        JOIN docente_grupos dg ON dg.grupo_id = g.id AND dg.docente_id = ?
        JOIN cursos c ON c.id = g.curso_id
        JOIN sedes  s ON s.id = g.sede_id
        WHERE g.activo = 1
        ORDER BY c.nombre, g.nombre");
    $stmt->execute([$uid]);
} else {
    $where  = ['g.activo = 1'];
    $params = [];
    if ($sede_filtro) { $where[] = 'g.sede_id = ?'; $params[] = $sede_filtro; }
    $stmt = $pdo->prepare("
        SELECT g.*, c.nombre AS curso_nombre, s.nombre AS sede_nombre
        FROM grupos g
        JOIN cursos c ON c.id = g.curso_id
        JOIN sedes  s ON s.id = g.sede_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.nombre, c.nombre, g.nombre");
    $stmt->execute($params);
}
$grupos = $stmt->fetchAll();

$grupo_id  = (int)($_REQUEST['grupo'] ?? 0);
$fecha     = $_REQUEST['fecha'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $fecha = date('Y-m-d');

$grupo_sel = null;
foreach ($grupos as $g) { if ($g['id'] == $grupo_id) { $grupo_sel = $g; break; } }

// Guardar asistencia
$guardado = false;
$errores  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $grupo_id && $grupo_sel) {
    $fecha_post = $_POST['fecha'] ?? $fecha;
    $tema       = trim($_POST['tema'] ?? '');
    $estados    = $_POST['estado'] ?? [];
    $obs_arr    = $_POST['obs']    ?? [];

    if (empty($estados)) {
        $errores[] = 'Debes marcar la asistencia de al menos un estudiante.';
    } else {
        try {
            $pdo->beginTransaction();

            // Crear o recuperar la sesion del dia
            $pdo->prepare("
                INSERT INTO sesiones (grupo_id, fecha, tema, creado_por)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    tema = IF(VALUES(tema) != '', VALUES(tema), tema)
            ")->execute([$grupo_id, $fecha_post, $tema ?: null, $uid]);

            $r = $pdo->prepare("SELECT id FROM sesiones WHERE grupo_id=? AND fecha=?");
            $r->execute([$grupo_id, $fecha_post]);
            $sesion_id = (int)$r->fetchColumn();

            foreach ($estados as $matricula_id => $estado) {
                $matricula_id = (int)$matricula_id;
                if (!in_array($estado, ['presente','tarde','ausente','excusa'])) $estado = 'ausente';
                $obs = trim($obs_arr[$matricula_id] ?? '');
                $pdo->prepare("
                    INSERT INTO asistencia (sesion_id, matricula_id, estado, observacion, registrado_por)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        estado=VALUES(estado), observacion=VALUES(observacion),
                        registrado_por=VALUES(registrado_por), updated_at=NOW()
                ")->execute([$sesion_id, $matricula_id, $estado, $obs ?: null, $uid]);
            }

            $pdo->commit();
            $guardado = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = 'Error al guardar. Intenta de nuevo.';
        }
    }
}

// Cargar planilla
$estudiantes   = [];
$sesion_actual = null;
$tema_actual   = '';
$historial     = [];

if ($grupo_sel) {
    $stSes = $pdo->prepare("SELECT * FROM sesiones WHERE grupo_id=? AND fecha=?");
    $stSes->execute([$grupo_id, $fecha]);
    $sesion_actual = $stSes->fetch();
    $tema_actual   = $sesion_actual['tema'] ?? '';

    $stEst = $pdo->prepare("
        SELECT m.id AS matricula_id,
               e.nombre_completo, e.avatar,
               TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) AS edad,
               a.estado      AS estado_hoy,
               a.observacion AS obs_hoy
        FROM matriculas m
        JOIN estudiantes e ON e.id = m.estudiante_id
        LEFT JOIN sesiones   se ON se.grupo_id = m.grupo_id AND se.fecha = ?
        LEFT JOIN asistencia a  ON a.sesion_id = se.id AND a.matricula_id = m.id
        WHERE m.grupo_id = ? AND m.estado = 'activa'
        ORDER BY e.nombre_completo
    ");
    $stEst->execute([$fecha, $grupo_id]);
    $estudiantes = $stEst->fetchAll();

    $anio = substr($fecha, 0, 4);
    $mes  = substr($fecha, 5, 2);
    $stHist = $pdo->prepare("
        SELECT se.fecha, se.tema,
            SUM(a.estado='presente') AS presentes,
            SUM(a.estado='tarde')    AS tardes,
            SUM(a.estado='excusa')   AS excusas,
            SUM(a.estado='ausente')  AS ausentes
        FROM sesiones se
        LEFT JOIN asistencia a ON a.sesion_id = se.id
        WHERE se.grupo_id=? AND YEAR(se.fecha)=? AND MONTH(se.fecha)=?
        GROUP BY se.id ORDER BY se.fecha DESC
    ");
    $stHist->execute([$grupo_id, $anio, $mes]);
    $historial = $stHist->fetchAll();
}

$DIAS = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miercoles',
         'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'Sabado','domingo'=>'Domingo'];

include ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>

<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Asistencia <small>Planilla por grupo y fecha</small>
  </div>
</header>

<main class="main-content">
<style>
.planilla-table{width:100%;border-collapse:collapse;}
.planilla-table th{background:var(--gray,#f3f4f6);font-size:.75rem;font-weight:700;color:var(--muted);padding:.55rem .8rem;text-align:left;border-bottom:2px solid var(--border);}
.planilla-table td{padding:.55rem .8rem;border-bottom:1px solid var(--border);vertical-align:middle;}
.planilla-table tbody tr:hover{background:var(--gray,#f9fafb);}
.est-group{display:flex;gap:.3rem;flex-wrap:wrap;}
.est-btn{padding:.28rem .6rem;border-radius:20px;border:1.5px solid var(--border);font-size:.74rem;font-weight:700;cursor:pointer;background:transparent;color:var(--muted);transition:.1s;white-space:nowrap;}
.est-btn.sel-presente{background:var(--green-l);color:var(--green);border-color:var(--green);}
.est-btn.sel-tarde{background:#fef3c7;color:#b45309;border-color:#d97706;}
.est-btn.sel-excusa{background:var(--teal-l);color:var(--teal);border-color:var(--teal);}
.est-btn.sel-ausente{background:var(--red-l);color:var(--red);border-color:var(--red);}
.hist-row{display:grid;grid-template-columns:90px 1fr 38px 38px 38px 38px;gap:.4rem;align-items:center;padding:.42rem .8rem;border-bottom:1px solid var(--border);font-size:.8rem;}
.hist-row:hover{background:var(--gray);cursor:pointer;}
.hbadge{text-align:center;font-weight:700;font-size:.73rem;padding:.12rem .3rem;border-radius:10px;}
</style>

<div class="content-wrapper" style="max-width:900px;">
  <div style="margin-bottom:1.2rem;">
    <h2 class="page-title"><i class="bi bi-calendar-check-fill"></i> Asistencia</h2>
    <p class="page-sub">Planilla por grupo y fecha</p>
  </div>

  <?php if ($guardado): ?>
    <div class="alert-rsal alert-ok"><i class="bi bi-check-circle-fill"></i> Asistencia guardada correctamente.</div>
  <?php endif; ?>
  <?php foreach ($errores as $e): ?>
    <div class="alert-rsal alert-err"><i class="bi bi-exclamation-circle-fill"></i> <?= h($e) ?></div>
  <?php endforeach; ?>

  <!-- Selector -->
  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="get" style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end;">
      <div style="flex:2;min-width:220px;">
        <label class="field-label">Grupo</label>
        <select name="grupo" class="rsal-select" onchange="this.form.submit()">
          <option value="">-- Selecciona un grupo --</option>
          <?php foreach ($grupos as $g):
            $dia_label = $DIAS[$g['dia_semana']] ?? $g['dia_semana'];
            $horario   = substr($g['hora_inicio'],0,5).'&ndash;'.substr($g['hora_fin'],0,5);
          ?>
            <option value="<?= $g['id'] ?>" <?= $grupo_id==$g['id']?'selected':'' ?>>
              <?= h($g['curso_nombre']) ?> &middot; <?= $dia_label ?> <?= $horario ?>
              <?php if (!$sede_filtro): ?> &middot; <?= h($g['sede_nombre']) ?><?php endif; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="flex:1;min-width:160px;">
        <label class="field-label">Fecha de la clase</label>
        <input type="date" name="fecha" class="rsal-input" style="margin:0;"
               value="<?= h($fecha) ?>" max="<?= date('Y-m-d') ?>"
               onchange="this.form.submit()">
      </div>
    </form>
  </div>

  <?php if (!$grupo_sel): ?>
    <div class="empty-state">
      <i class="bi bi-people-fill" style="font-size:2.5rem;color:var(--muted);"></i>
      <p style="color:var(--muted);margin:.6rem 0 0;">Selecciona un grupo para ver la planilla.</p>
    </div>

  <?php elseif (empty($estudiantes)): ?>
    <div class="empty-state">
      <i class="bi bi-person-x" style="font-size:2.5rem;color:var(--muted);"></i>
      <p style="color:var(--muted);margin:.6rem 0 0;">Este grupo no tiene estudiantes matriculados activos.</p>
    </div>

  <?php else: ?>

  <!-- Info grupo -->
  <div style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;margin-bottom:.8rem;
              padding:.7rem 1rem;background:var(--teal-l);border-radius:10px;border:1px solid rgba(29,169,154,.25);">
    <i class="bi bi-people-fill" style="color:var(--teal);font-size:1.1rem;"></i>
    <div style="flex:1;">
      <strong style="color:var(--teal);"><?= h($grupo_sel['curso_nombre']) ?></strong>
      <span style="color:var(--muted);font-size:.84rem;">
        &nbsp;&middot;&nbsp;<?= h($grupo_sel['nombre']) ?>
        &nbsp;&middot;&nbsp;<?= $DIAS[$grupo_sel['dia_semana']] ?? $grupo_sel['dia_semana'] ?>
        &nbsp;<?= substr($grupo_sel['hora_inicio'],0,5) ?>--<?= substr($grupo_sel['hora_fin'],0,5) ?>
        &nbsp;&middot;&nbsp;<?= count($estudiantes) ?> estudiantes
      </span>
    </div>
    <div style="font-size:.82rem;font-weight:700;color:var(--teal);">
      <?= date('d/m/Y', strtotime($fecha)) ?>
      <?php if ($sesion_actual): ?>
        <span style="background:var(--green-l);color:var(--green);border-radius:12px;padding:.1rem .5rem;font-size:.72rem;margin-left:.4rem;">Ya registrada</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Planilla -->
  <form method="post">
    <input type="hidden" name="grupo" value="<?= $grupo_id ?>">
    <input type="hidden" name="fecha" value="<?= h($fecha) ?>">

    <div class="card-rsal" style="padding:0;overflow:hidden;margin-bottom:1rem;">

      <!-- Cabecera -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;
                  gap:.6rem;padding:.8rem 1rem;border-bottom:1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:.7rem;flex:1;min-width:200px;">
          <label class="field-label" style="margin:0;white-space:nowrap;flex-shrink:0;">Tema:</label>
          <input type="text" name="tema" class="rsal-input" style="margin:0;flex:1;"
                 placeholder="Tema de la clase (opcional)"
                 value="<?= h($tema_actual) ?>">
        </div>
        <div style="display:flex;gap:.4rem;flex-shrink:0;">
          <button type="button" onclick="marcarTodos('presente')" class="btn-rsal-secondary"
                  style="background:var(--green-l);color:var(--green);border:1.5px solid rgba(22,163,74,.3);padding:.38rem .9rem;font-size:.78rem;">
            <i class="bi bi-check-all"></i> Todos presentes
          </button>
          <button type="button" onclick="marcarTodos('ausente')" class="btn-rsal-secondary"
                  style="background:var(--red-l);color:var(--red);border:1.5px solid rgba(232,25,44,.2);padding:.38rem .9rem;font-size:.78rem;">
            <i class="bi bi-x-lg"></i> Todos ausentes
          </button>
        </div>
      </div>

      <!-- Tabla -->
      <div style="overflow-x:auto;">
        <table class="planilla-table">
          <thead>
            <tr>
              <th style="width:36px;">#</th>
              <th>Estudiante</th>
              <th>Asistencia</th>
              <th>Causa / observacion</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($estudiantes as $i => $est):
              $mid    = $est['matricula_id'];
              $actual = $est['estado_hoy'] ?? 'presente';
            ?>
            <tr>
              <td style="color:var(--muted);font-size:.8rem;"><?= $i+1 ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem;">
                  <?php if ($est['avatar']): ?>
                    <img src="<?= $U ?>uploads/estudiantes/<?= h($est['avatar']) ?>"
                         style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                  <?php else: ?>
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--teal-l);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                      <i class="bi bi-person-fill" style="color:var(--teal);font-size:.9rem;"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <div style="font-weight:700;font-size:.87rem;"><?= h($est['nombre_completo']) ?></div>
                    <div style="font-size:.72rem;color:var(--muted);"><?= $est['edad'] ?> anos</div>
                  </div>
                </div>
              </td>
              <td>
                <input type="hidden" name="estado[<?= $mid ?>]" id="inp-<?= $mid ?>" value="<?= h($actual) ?>">
                <div class="est-group">
                  <button type="button" class="est-btn <?= $actual==='presente'?'sel-presente':'' ?>"
                          data-mid="<?= $mid ?>" data-estado="presente"
                          onclick="sel(<?= $mid ?>,'presente')">Presente</button>
                  <button type="button" class="est-btn <?= $actual==='tarde'?'sel-tarde':'' ?>"
                          data-mid="<?= $mid ?>" data-estado="tarde"
                          onclick="sel(<?= $mid ?>,'tarde')">Tarde</button>
                  <button type="button" class="est-btn <?= $actual==='excusa'?'sel-excusa':'' ?>"
                          data-mid="<?= $mid ?>" data-estado="excusa"
                          onclick="sel(<?= $mid ?>,'excusa')">Excusa</button>
                  <button type="button" class="est-btn <?= $actual==='ausente'?'sel-ausente':'' ?>"
                          data-mid="<?= $mid ?>" data-estado="ausente"
                          onclick="sel(<?= $mid ?>,'ausente')">Ausente</button>
                </div>
              </td>
              <td>
                <input type="text" name="obs[<?= $mid ?>]" id="obs-<?= $mid ?>" class="rsal-input"
                       style="margin:0;font-size:.8rem;padding:.3rem .6rem;<?= $actual==='presente'?'display:none;':'' ?>"
                       placeholder="Causa de inasistencia / nota"
                       value="<?= h($est['obs_hoy'] ?? '') ?>">
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pie -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;
                  gap:.8rem;padding:.8rem 1rem;border-top:1px solid var(--border);background:var(--gray);">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;font-size:.8rem;font-weight:700;">
          <span id="cnt-presente" style="background:var(--green-l);color:var(--green);padding:.2rem .7rem;border-radius:20px;">Presentes: <span>0</span></span>
          <span id="cnt-tarde"    style="background:#fef3c7;color:#b45309;padding:.2rem .7rem;border-radius:20px;">Tarde: <span>0</span></span>
          <span id="cnt-excusa"   style="background:var(--teal-l);color:var(--teal);padding:.2rem .7rem;border-radius:20px;">Excusa: <span>0</span></span>
          <span id="cnt-ausente"  style="background:var(--red-l);color:var(--red);padding:.2rem .7rem;border-radius:20px;">Ausentes: <span>0</span></span>
        </div>
        <button type="submit" class="btn-rsal-primary">
          <i class="bi bi-save-fill"></i> Guardar asistencia
        </button>
      </div>
    </div>
  </form>

  <!-- Historial del mes -->
  <?php if (!empty($historial)): ?>
  <div class="card-rsal" style="padding:0;overflow:hidden;">
    <div style="padding:.65rem 1rem;border-bottom:1px solid var(--border);font-size:.82rem;font-weight:700;color:var(--muted);">
      <i class="bi bi-clock-history"></i> Sesiones del mes -- click para editar
    </div>
    <div class="hist-row" style="background:var(--gray);font-size:.7rem;font-weight:700;color:var(--muted);cursor:default;">
      <div>Fecha</div><div>Tema</div>
      <div style="text-align:center;">Pres.</div>
      <div style="text-align:center;">Tarde</div>
      <div style="text-align:center;">Exc.</div>
      <div style="text-align:center;">Aus.</div>
    </div>
    <?php foreach ($historial as $hr): ?>
    <div class="hist-row"
         onclick="window.location='?grupo=<?= $grupo_id ?>&fecha=<?= $hr['fecha'] ?>'"
         title="Editar <?= date('d/m/Y', strtotime($hr['fecha'])) ?>">
      <div style="font-weight:700;font-size:.8rem;">
        <?= date('d/m/Y', strtotime($hr['fecha'])) ?>
        <?php if ($hr['fecha'] === $fecha): ?>
          <span style="font-size:.63rem;background:var(--teal);color:#fff;border-radius:8px;padding:1px 4px;margin-left:2px;">HOY</span>
        <?php endif; ?>
      </div>
      <div style="color:var(--muted);font-size:.77rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= h($hr['tema'] ?: '--') ?></div>
      <div><span class="hbadge" style="background:var(--green-l);color:var(--green);"><?= $hr['presentes'] ?></span></div>
      <div><span class="hbadge" style="background:#fef3c7;color:#b45309;"><?= $hr['tardes'] ?></span></div>
      <div><span class="hbadge" style="background:var(--teal-l);color:var(--teal);"><?= $hr['excusas'] ?></span></div>
      <div><span class="hbadge" style="background:var(--red-l);color:var(--red);"><?= $hr['ausentes'] ?></span></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<script>
function sel(mid, estado) {
  document.getElementById('inp-' + mid).value = estado;
  document.querySelectorAll('[data-mid="' + mid + '"]').forEach(b => {
    b.className = 'est-btn' + (b.dataset.estado === estado ? ' sel-' + estado : '');
  });
  // Mostrar campo causa solo si no es presente
  const obs = document.getElementById('obs-' + mid);
  if (obs) obs.style.display = (estado === 'presente') ? 'none' : '';
  contadores();
}
function marcarTodos(estado) {
  document.querySelectorAll('[id^="inp-"]').forEach(inp => sel(parseInt(inp.id.replace('inp-','')), estado));
}
function contadores() {
  const c = {presente:0,tarde:0,excusa:0,ausente:0};
  document.querySelectorAll('[id^="inp-"]').forEach(inp => { if(c[inp.value]!==undefined) c[inp.value]++; });
  ['presente','tarde','excusa','ausente'].forEach(e => {
    document.querySelector('#cnt-' + e + ' span').textContent = c[e];
  });
}
document.addEventListener('DOMContentLoaded', contadores);
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</main>
</body>
</html>
