<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Grupo';
$menu_activo = 'grupos';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;

// Datos para selects
$cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();
$sedes  = $pdo->query("SELECT * FROM sedes WHERE activa=1 ORDER BY nombre")->fetchAll();

// Docentes disponibles (filtrados por sede si aplica)
$where_doc = $sede_filtro
    ? "WHERE u.rol IN ('docente','coordinador_pedagogico') AND (u.sede_id=".(int)$sede_filtro." OR u.sede_id IS NULL) AND u.activo=1"
    : "WHERE u.rol IN ('docente','coordinador_pedagogico') AND u.activo=1";
$docentes_disponibles = $pdo->query("
    SELECT u.id, u.nombre, u.rol, s.nombre AS sede_nombre
    FROM usuarios u
    LEFT JOIN sedes s ON s.id = u.sede_id
    $where_doc
    ORDER BY u.nombre
")->fetchAll();

// Modo edici&oacute;n
$id    = (int)($_GET['id'] ?? 0);
$grupo = null;
$grupo_equipos = [];

if ($id) {
    $s = $pdo->prepare("SELECT * FROM grupos WHERE id=?");
    $s->execute([$id]); $grupo = $s->fetch();
    if (!$grupo || ($sede_filtro && $grupo['sede_id'] != $sede_filtro)) {
        header('Location: '.$U.'modulos/academico/grupos/index.php'); exit;
    }
    $ge = $pdo->prepare("SELECT ge.*, e.nombre AS equipo_nombre FROM grupo_equipos ge JOIN equipos e ON e.id=ge.equipo_id WHERE ge.grupo_id=?");
    $ge->execute([$id]); $grupo_equipos = $ge->fetchAll();

    // Docentes asignados al grupo
    $dg = $pdo->prepare("SELECT docente_id FROM docente_grupos WHERE grupo_id=?");
    $dg->execute([$id]);
    $docentes_asignados = array_column($dg->fetchAll(), 'docente_id');
}

$titulo  = $grupo ? 'Editar grupo' : 'Nuevo grupo';
$errores = [];
$docentes_asignados = $docentes_asignados ?? [];

// Sesiones del s&aacute;bado predefinidas
$sesiones_sabado = [
    ['08:00','10:00','S&aacute;bado S1 &mdash; 8:00 a 10:00'],
    ['10:30','12:30','S&aacute;bado S2 &mdash; 10:30 a 12:30'],
    ['13:00','15:00','S&aacute;bado S3 &mdash; 1:00 a 3:00 PM'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id    = (int)($_POST['curso_id']   ?? 0);
    $sede_id     = (int)($_POST['sede_id']    ?? ($sede_filtro ?: 0));
    $nombre      = trim($_POST['nombre']       ?? '');
    $dia_semana  = $_POST['dia_semana']        ?? '';
    $hora_inicio = $_POST['hora_inicio']       ?? '';
    $hora_fin    = $_POST['hora_fin']          ?? '';
    $modalidad   = $_POST['modalidad']         ?? 'presencial';
    $cupo_aula   = (int)($_POST['cupo_aula']  ?? 0) ?: null;
    $cupo_admin  = (int)($_POST['cupo_admin'] ?? 0) ?: null;
    $periodo     = trim($_POST['periodo']      ?? '');
    $fecha_inicio= $_POST['fecha_inicio']      ?? null ?: null;
    $fecha_fin   = $_POST['fecha_fin']         ?? null ?: null;
    $activo      = isset($_POST['activo']) ? 1 : 0;

    // Equipos seleccionados
    $equipo_ids  = $_POST['equipo_id']  ?? [];
    $equipo_cant = $_POST['equipo_cant']?? [];

    if (!$curso_id)   $errores[] = 'Selecciona un curso.';
    if (!$nombre)     $errores[] = 'El nombre del grupo es obligatorio.';
    if (!$dia_semana) $errores[] = 'Selecciona el d&iacute;a de la semana.';
    if (!$hora_inicio || !$hora_fin) $errores[] = 'Define el horario de inicio y fin.';
    if (!$periodo)    $errores[] = 'El per&iacute;odo es obligatorio (Ej: 2026-1).';

    if (empty($errores)) {
        // Calcular cupo_equipos (suma de equipos asignados)
        $cupo_equipos = null;
        if (!empty($equipo_ids)) {
            $cupo_equipos = 0;
            foreach ($equipo_ids as $i => $eid) {
                if ($eid) $cupo_equipos += (int)($equipo_cant[$i] ?? 1);
            }
        }

        // cupo_real = MIN de los valores no nulos
        $valores = array_filter([$cupo_equipos, $cupo_aula, $cupo_admin], fn($v) => $v !== null && $v > 0);
        $cupo_real = !empty($valores) ? min($valores) : 0;

        if ($id) {
            $pdo->prepare("UPDATE grupos SET curso_id=?,sede_id=?,nombre=?,dia_semana=?,hora_inicio=?,hora_fin=?,
                modalidad=?,cupo_equipos=?,cupo_aula=?,cupo_admin=?,cupo_real=?,periodo=?,fecha_inicio=?,fecha_fin=?,activo=?
                WHERE id=?")
                ->execute([$curso_id,$sede_id,$nombre,$dia_semana,$hora_inicio,$hora_fin,
                    $modalidad,$cupo_equipos,$cupo_aula,$cupo_admin,$cupo_real,$periodo,$fecha_inicio,$fecha_fin,$activo,$id]);
        } else {
            $pdo->prepare("INSERT INTO grupos (curso_id,sede_id,nombre,dia_semana,hora_inicio,hora_fin,
                modalidad,cupo_equipos,cupo_aula,cupo_admin,cupo_real,periodo,fecha_inicio,fecha_fin,activo)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$curso_id,$sede_id,$nombre,$dia_semana,$hora_inicio,$hora_fin,
                    $modalidad,$cupo_equipos,$cupo_aula,$cupo_admin,$cupo_real,$periodo,$fecha_inicio,$fecha_fin,$activo]);
            $id = $pdo->lastInsertId();
        }

        // Guardar equipos
        $pdo->prepare("DELETE FROM grupo_equipos WHERE grupo_id=?")->execute([$id]);
        foreach ($equipo_ids as $i => $eid) {
            $eid = (int)$eid;
            if (!$eid) continue;
            $cant = (int)($equipo_cant[$i] ?? 1);
            $pdo->prepare("INSERT INTO grupo_equipos (grupo_id,equipo_id,cantidad_requerida) VALUES (?,?,?)")
                ->execute([$id, $eid, $cant]);
        }

        // Guardar docentes asignados
        $docente_ids = array_filter(array_map('intval', $_POST['docente_ids'] ?? []));
        $pdo->prepare("DELETE FROM docente_grupos WHERE grupo_id=?")->execute([$id]);
        foreach ($docente_ids as $did) {
            $pdo->prepare("INSERT IGNORE INTO docente_grupos (grupo_id, docente_id) VALUES (?,?)")
                ->execute([$id, $did]);
        }

        header('Location: '.$U.'modulos/academico/grupos/index.php?msg='.($grupo?'editado':'creado'));
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>

<header class="main-header">
  <div class="header-title">
    <?= $grupo ? 'Editar grupo' : 'Nuevo grupo' ?>
    <small>
      <span class="breadcrumb-rsal">
        <a href="<?= $U ?>modulos/academico/grupos/index.php">Grupos</a>
        <i class="bi bi-chevron-right"></i>
        <?= $grupo ? h($grupo['nombre']) : 'Nuevo' ?>
      </span>
    </small>
  </div>
</header>

<main class="main-content">

  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger" style="flex-direction:column;align-items:flex-start;">
      <strong><i class="bi bi-exclamation-circle-fill"></i> Corrige los siguientes errores:</strong>
      <ul style="margin:.4rem 0 0 1.2rem;">
        <?php foreach ($errores as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.4rem;align-items:start;">

      <!-- IZQUIERDA -->
      <div>

        <!-- Info b&aacute;sica -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-info-circle-fill"></i> Informaci&oacute;n del grupo</div>

          <label class="field-label">Sede <span class="req">*</span></label>
          <select name="sede_id" class="rsal-select" required onchange="cargarEquipos(this.value)">
            <option value="">Selecciona sede...</option>
            <?php foreach ($sedes as $s):
              if ($_SESSION['usuario_rol'] !== 'admin_general' && $sede_filtro && $s['id'] != $sede_filtro) continue;
            ?>
              <option value="<?= $s['id'] ?>"
                <?= ($grupo['sede_id'] ?? $sede_filtro) == $s['id'] ? 'selected' : '' ?>>
                <?= h($s['nombre']) ?> &mdash; <?= h($s['ciudad']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label class="field-label">Curso <span class="req">*</span></label>
          <select name="curso_id" class="rsal-select" required>
            <option value="">Selecciona el curso...</option>
            <?php foreach ($cursos as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($grupo['curso_id']??'')==$c['id']?'selected':'' ?>>
                <?= h($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label class="field-label">Nombre del grupo <span class="req">*</span></label>
          <input type="text" name="nombre" class="rsal-input" required
                 placeholder="Ej: S&aacute;bado S1 &mdash; Rob&oacute;tica LEGO"
                 value="<?= h($grupo['nombre'] ?? '') ?>"/>

          <label class="field-label">Per&iacute;odo <span class="req">*</span></label>
          <input type="text" name="periodo" class="rsal-input" required
                 placeholder="Ej: 2026-1"
                 value="<?= h($grupo['periodo'] ?? '2026-1') ?>"/>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Fecha inicio</label>
              <input type="date" name="fecha_inicio" class="rsal-input"
                     value="<?= h($grupo['fecha_inicio'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Fecha fin</label>
              <input type="date" name="fecha_fin" class="rsal-input"
                     value="<?= h($grupo['fecha_fin'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <!-- Horario -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-calendar3"></i> Horario</div>

          <!-- Sesiones r&aacute;pidas s&aacute;bado -->
          <label class="field-label">Sesiones r&aacute;pidas (s&aacute;bado)</label>
          <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
            <?php foreach ($sesiones_sabado as $ses): ?>
            <button type="button" onclick="setSesion('sabado','<?= $ses[0] ?>','<?= $ses[1] ?>', '<?= addslashes($ses[2]) ?>')"
                    style="padding:.4rem .8rem;border:1.5px solid var(--border);border-radius:8px;background:#fff;font-size:.78rem;font-weight:700;cursor:pointer;transition:all .2s;color:var(--dark);"
                    onmouseover="this.style.borderColor='var(--teal)';this.style.background='var(--teal-l)'"
                    onmouseout="this.style.borderColor='var(--border)';this.style.background='#fff'">
              <i class="bi bi-clock me-1"></i><?= $ses[2] ?>
            </button>
            <?php endforeach; ?>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">D&iacute;a <span class="req">*</span></label>
              <select name="dia_semana" id="dia_semana" class="rsal-select" required>
                <option value="">Selecciona...</option>
                <?php
                  $dias_opt = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles',
                               'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];
                  foreach ($dias_opt as $val => $lbl):
                ?>
                  <option value="<?= $val ?>" <?= ($grupo['dia_semana']??'')===$val?'selected':'' ?>>
                    <?= $lbl ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="field-label">Hora inicio <span class="req">*</span></label>
              <input type="time" name="hora_inicio" id="hora_inicio" class="rsal-input" required
                     value="<?= h($grupo['hora_inicio'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Hora fin <span class="req">*</span></label>
              <input type="time" name="hora_fin" id="hora_fin" class="rsal-input" required
                     value="<?= h($grupo['hora_fin'] ?? '') ?>"/>
            </div>
          </div>

          <label class="field-label">Modalidad</label>
          <div style="display:flex;gap:.5rem;margin-bottom:.9rem;">
            <?php foreach (['presencial'=>'&#127979; Presencial','virtual'=>'&#128187; Virtual','hibrida'=>'&#128256; H&iacute;brida'] as $val=>$lbl): ?>
            <label style="flex:1;display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.55rem;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;transition:all .2s;" id="mod_<?= $val ?>">
              <input type="radio" name="modalidad" value="<?= $val ?>"
                     <?= ($grupo['modalidad']??'presencial')===$val?'checked':'' ?>
                     onchange="estilizarMod()" style="display:none;"/>
              <?= $lbl ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Equipos -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-cpu-fill"></i> Equipos asignados al grupo</div>
          <p style="font-size:.78rem;color:var(--muted);margin-bottom:.9rem;">
            Los equipos determinan el cupo. Ejemplo: 10 LEGO Spike = m&aacute;ximo 10 estudiantes en este grupo.
          </p>
          <div id="equiposContainer">
            <?php if (!empty($grupo_equipos)):
              foreach ($grupo_equipos as $ge):
                // Cargar equipos de esa sede
                $equips_sede = $pdo->prepare("SELECT * FROM equipos WHERE sede_id=? AND activo=1 ORDER BY nombre");
                $equips_sede->execute([$grupo['sede_id']]);
                $equips_list = $equips_sede->fetchAll();
            ?>
            <div class="equipo-row" style="display:grid;grid-template-columns:1fr auto auto;gap:.5rem;align-items:center;margin-bottom:.5rem;">
              <select name="equipo_id[]" class="rsal-select" style="margin:0;">
                <option value="">Selecciona equipo...</option>
                <?php foreach ($equips_list as $eq): ?>
                  <option value="<?= $eq['id'] ?>" <?= $ge['equipo_id']==$eq['id']?'selected':'' ?>>
                    <?= h($eq['nombre']) ?> (<?= $eq['cantidad_total'] ?> disp.)
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="number" name="equipo_cant[]" class="rsal-input"
                     style="margin:0;width:80px;" min="1" value="<?= $ge['cantidad_requerida'] ?>"
                     placeholder="Cant."/>
              <button type="button" onclick="this.closest('.equipo-row').remove();calcularCupo()"
                      style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
            <?php endforeach; endif; ?>
          </div>
          <button type="button" onclick="addEquipo()"
                  style="display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.5rem;width:100%;background:var(--teal-l);color:var(--teal);border:1.5px dashed rgba(29,169,154,.4);border-radius:10px;font-size:.8rem;font-weight:700;cursor:pointer;">
            <i class="bi bi-plus-lg"></i> Agregar equipo
          </button>
          <!-- Equipos disponibles en JSON para JS -->
          <?php
            $equips_js_sede = $sede_filtro ?: ($grupo['sede_id'] ?? 0);
            $equips_js = [];
            if ($equips_js_sede) {
                $eq_stmt = $pdo->prepare("SELECT * FROM equipos WHERE sede_id=? AND activo=1 ORDER BY nombre");
                $eq_stmt->execute([$equips_js_sede]);
                $equips_js = $eq_stmt->fetchAll();
            }
          ?>
          <script>
          const equiposDisponibles = <?= json_encode($equips_js, JSON_UNESCAPED_UNICODE) ?>;
          </script>
        </div>

        <!-- DOCENTES ASIGNADOS -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-person-workspace"></i> Docentes / Talleristas</div>
          <?php if (empty($docentes_disponibles)): ?>
          <div style="text-align:center;padding:1.2rem;color:var(--muted);font-size:.85rem;">
            <i class="bi bi-exclamation-circle"></i> No hay docentes registrados para esta sede.
            <a href="<?= $U ?>modulos/usuarios/form.php" style="color:var(--teal);">Crear docente</a>
          </div>
          <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:.4rem;">
            <?php foreach ($docentes_disponibles as $doc):
              $checked = in_array($doc['id'], $docentes_asignados) ? 'checked' : '';
              $badge = $doc['rol'] === 'coordinador_pedagogico' ? '#7c3aed' : 'var(--teal)';
              $label_r = $doc['rol'] === 'coordinador_pedagogico' ? 'Coordinador' : 'Docente';
            ?>
            <label style="display:flex;align-items:center;gap:.7rem;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;font-size:.85rem;transition:.15s;"
                   onmouseover="this.style.borderColor='var(--teal)'" onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--teal)':'var(--border)'">
              <input type="checkbox" name="docente_ids[]" value="<?= $doc['id'] ?>" <?= $checked ?>
                     style="accent-color:var(--teal);width:16px;height:16px;flex-shrink:0;"
                     onchange="this.closest('label').style.borderColor=this.checked?'var(--teal)':'var(--border)';this.closest('label').style.background=this.checked?'rgba(0,156,204,.06)':''">
              <div style="flex:1;">
                <strong><?= h($doc['nombre']) ?></strong>
                <?php if ($doc['sede_nombre']): ?>
                <span style="font-size:.75rem;color:var(--muted);"> &mdash; <?= h($doc['sede_nombre']) ?></span>
                <?php endif; ?>
              </div>
              <span style="font-size:.72rem;font-weight:700;padding:.2rem .5rem;border-radius:6px;background:<?= $badge ?>;color:#fff;"><?= $label_r ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <div style="font-size:.75rem;color:var(--muted);margin-top:.6rem;">
            <i class="bi bi-info-circle"></i> Puedes asignar varios docentes al mismo grupo.
          </div>
          <?php endif; ?>
        </div>

      </div><!-- /col izquierda -->

      <!-- DERECHA -->
      <div>

        <!-- Cupos -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-people-fill"></i> Cupos del grupo</div>

          <div style="background:var(--teal-l);border:1px solid rgba(29,169,154,.3);border-radius:12px;padding:1rem;text-align:center;margin-bottom:1.2rem;">
            <div style="font-size:.72rem;font-weight:700;color:var(--teal);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem;">Cupo real calculado</div>
            <div id="cupoRealDisplay" style="font-family:'Poppins',sans-serif;font-size:2.5rem;font-weight:900;color:var(--teal);line-height:1;">
              <?= $grupo ? $grupo['cupo_real'] : '&mdash;' ?>
            </div>
            <div style="font-size:.72rem;color:var(--teal-d);margin-top:.3rem;">MIN(equipos, aula, manual)</div>
          </div>

          <label class="field-label">
            <i class="bi bi-building"></i> Capacidad del aula
            <span style="font-size:.72rem;font-weight:400;color:var(--muted);">(opcional)</span>
          </label>
          <input type="number" name="cupo_aula" id="cupo_aula" class="rsal-input"
                 min="1" placeholder="Ej: 15"
                 value="<?= h($grupo['cupo_aula'] ?? '') ?>"
                 oninput="calcularCupo()"/>

          <label class="field-label">
            <i class="bi bi-person-lock"></i> L&iacute;mite manual
            <span style="font-size:.72rem;font-weight:400;color:var(--muted);">(opcional)</span>
          </label>
          <input type="number" name="cupo_admin" id="cupo_admin" class="rsal-input"
                 min="1" placeholder="Ej: 10"
                 value="<?= h($grupo['cupo_admin'] ?? '') ?>"
                 oninput="calcularCupo()"/>

          <div style="font-size:.75rem;color:var(--muted);background:var(--gray);border-radius:8px;padding:.6rem .8rem;line-height:1.6;">
            &#128161; Si dejas un campo vac&iacute;o, no se considera en el c&aacute;lculo. El cupo real es el <strong>menor</strong> valor ingresado.
          </div>
        </div>

        <!-- Estado -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-toggle-on"></i> Estado</div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Grupo activo</div>
              <div style="font-size:.72rem;color:var(--muted);">Visible para inscripciones</div>
            </div>
            <label style="position:relative;width:44px;height:24px;flex-shrink:0;cursor:pointer;">
              <input type="checkbox" name="activo" id="chkActivo"
                     style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($grupo['activo']??1) ? 'checked':'' ?>
                     onchange="document.getElementById('trackActivo').style.background=this.checked?'var(--teal)':'var(--gray2)';document.getElementById('knobActivo').style.transform=this.checked?'translateX(20px)':'';">
              <span id="trackActivo" style="position:absolute;inset:0;background:<?= ($grupo['activo']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span id="knobActivo" style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($grupo['activo']??1)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <!-- Guardar -->
        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $grupo ? 'Guardar cambios':'Crear grupo' ?>
          </button>
          <a href="<?= $U ?>modulos/academico/grupos/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">
            Cancelar
          </a>
        </div>

      </div><!-- /col derecha -->
    </div>
  </form>
</main>

<script>
// Sesiones r&aacute;pidas s&aacute;bado
function setSesion(dia, inicio, fin, nombre) {
  document.getElementById('dia_semana').value  = dia;
  document.getElementById('hora_inicio').value = inicio;
  document.getElementById('hora_fin').value    = fin;
  // Sugerir nombre si est&aacute; vac&iacute;o
  const nInput = document.querySelector('input[name=nombre]');
  if (!nInput.value) nInput.value = nombre;
  calcularCupo();
}

// Estilizar modalidad
function estilizarMod() {
  ['presencial','virtual','hibrida'].forEach(v => {
    const radio = document.querySelector(`input[name=modalidad][value=${v}]`);
    const lbl   = document.getElementById('mod_'+v);
    lbl.style.borderColor = radio.checked ? 'var(--teal)' : 'var(--border)';
    lbl.style.background  = radio.checked ? 'var(--teal-l)' : '#fff';
    lbl.style.color       = radio.checked ? 'var(--teal)' : 'var(--dark)';
  });
}
document.addEventListener('DOMContentLoaded', estilizarMod);

// Calcular cupo real din&aacute;micamente
function calcularCupo() {
  const valores = [];
  // Sumar equipos
  let totalEquipos = 0;
  document.querySelectorAll('.equipo-row input[name="equipo_cant[]"]').forEach(i => {
    const v = parseInt(i.value);
    if (!isNaN(v) && v > 0) totalEquipos += v;
  });
  if (totalEquipos > 0) valores.push(totalEquipos);

  const aula  = parseInt(document.getElementById('cupo_aula')?.value);
  const admin = parseInt(document.getElementById('cupo_admin')?.value);
  if (!isNaN(aula)  && aula  > 0) valores.push(aula);
  if (!isNaN(admin) && admin > 0) valores.push(admin);

  const display = document.getElementById('cupoRealDisplay');
  if (valores.length > 0) {
    const real = Math.min(...valores);
    display.textContent = real;
    display.style.color = real > 0 ? 'var(--teal)' : 'var(--red)';
  } else {
    display.textContent = '&mdash;';
    display.style.color = 'var(--muted)';
  }
}

// Agregar equipo
function addEquipo() {
  if (!equiposDisponibles.length) {
    alert('No hay equipos registrados para esta sede. Ve a Administraci&oacute;n &#8594; Equipos para agregarlos.');
    return;
  }
  const d = document.createElement('div');
  d.className = 'equipo-row';
  d.style = 'display:grid;grid-template-columns:1fr auto auto;gap:.5rem;align-items:center;margin-bottom:.5rem;';
  let options = '<option value="">Selecciona equipo...</option>';
  equiposDisponibles.forEach(e => {
    options += `<option value="${e.id}">${e.nombre} (${e.cantidad_total} disp.)</option>`;
  });
  d.innerHTML = `
    <select name="equipo_id[]" class="rsal-select" style="margin:0;" onchange="calcularCupo()">${options}</select>
    <input type="number" name="equipo_cant[]" class="rsal-input" style="margin:0;width:80px;" min="1" value="1" placeholder="Cant." oninput="calcularCupo()"/>
    <button type="button" onclick="this.closest('.equipo-row').remove();calcularCupo()"
            style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
      <i class="bi bi-x-lg"></i></button>`;
  document.getElementById('equiposContainer').appendChild(d);
  // Escuchar cambios de cantidad
  d.querySelector('select').addEventListener('change', calcularCupo);
}

document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
