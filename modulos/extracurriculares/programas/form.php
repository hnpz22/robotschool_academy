<?php
// modulos/extracurriculares/programas/form.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_programas';
$U           = BASE_URL;

$id           = (int)($_GET['id']       ?? 0);
$contrato_pre = (int)($_GET['contrato'] ?? 0);
$programa = null;

if ($id) {
    $s = $pdo->prepare("SELECT * FROM ec_programas WHERE id = ?");
    $s->execute([$id]);
    $programa = $s->fetch();
    if (!$programa) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }
    $contrato_pre = $programa['contrato_id'];
}

if (!$contrato_pre) {
    // Si no hay contrato de origen mostrar lista de contratos
    header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php');
    exit;
}

// Datos del contrato padre
$ch = $pdo->prepare("SELECT ct.*, cl.nombre AS cliente_nombre
                     FROM ec_contratos ct JOIN ec_clientes cl ON cl.id = ct.cliente_id
                     WHERE ct.id = ?");
$ch->execute([$contrato_pre]);
$contrato = $ch->fetch();
if (!$contrato) { header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php'); exit; }

$titulo  = $programa ? 'Editar programa' : 'Nuevo programa';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id       = (int)($_POST['curso_id'] ?? 0) ?: null;
    $nombre         = trim($_POST['nombre']         ?? '');
    $equipos_kit    = trim($_POST['equipos_kit']    ?? '');
    $grado_desde    = trim($_POST['grado_desde']    ?? '');
    $grado_hasta    = trim($_POST['grado_hasta']    ?? '');
    $edad_min       = $_POST['edad_min']       !== '' ? (int)$_POST['edad_min']       : null;
    $edad_max       = $_POST['edad_max']       !== '' ? (int)$_POST['edad_max']       : null;
    $cantidad_ninos = (int)($_POST['cantidad_ninos'] ?? 0);
    $minimo_ninos   = (int)($_POST['minimo_ninos']   ?? 10);
    $dia_semana     = $_POST['dia_semana']     ?? 'lunes';
    $hora_inicio    = $_POST['hora_inicio']    ?? '';
    $hora_fin       = $_POST['hora_fin']       ?? '';
    $total_sesiones = 4; // Fijo por regla de negocio: 1 paquete = 4 sesiones
    $valor_por_nino = (float)str_replace(['.',','], ['','.'], $_POST['valor_por_nino'] ?? 120000);
    $color          = trim($_POST['color']          ?? '#7c3aed');
    $estado         = $_POST['estado']         ?? 'planeado';
    $observaciones  = trim($_POST['observaciones']  ?? '');

    $dias_v = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
    if (!in_array($dia_semana, $dias_v)) $dia_semana = 'lunes';
    $ests_v = ['planeado','en_curso','finalizado','suspendido'];
    if (!in_array($estado, $ests_v)) $estado = 'planeado';

    if (!$nombre)       $errores[] = 'El nombre del programa es obligatorio.';
    if (!$hora_inicio || !$hora_fin) $errores[] = 'Las horas de inicio y fin son obligatorias.';
    if ($hora_inicio && $hora_fin && $hora_fin <= $hora_inicio) $errores[] = 'La hora fin debe ser posterior a la de inicio.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE ec_programas SET curso_id=?, nombre=?, equipos_kit=?, grado_desde=?, grado_hasta=?, edad_min=?, edad_max=?, cantidad_ninos=?, minimo_ninos=?, dia_semana=?, hora_inicio=?, hora_fin=?, total_sesiones=?, valor_por_nino=?, color=?, estado=?, observaciones=? WHERE id=?")
                ->execute([$curso_id, $nombre, $equipos_kit, $grado_desde, $grado_hasta, $edad_min, $edad_max, $cantidad_ninos, $minimo_ninos, $dia_semana, $hora_inicio, $hora_fin, $total_sesiones, $valor_por_nino, $color, $estado, $observaciones, $id]);
        } else {
            $pdo->prepare("INSERT INTO ec_programas (contrato_id, curso_id, nombre, equipos_kit, grado_desde, grado_hasta, edad_min, edad_max, cantidad_ninos, minimo_ninos, dia_semana, hora_inicio, hora_fin, total_sesiones, valor_por_nino, color, estado, observaciones) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$contrato_pre, $curso_id, $nombre, $equipos_kit, $grado_desde, $grado_hasta, $edad_min, $edad_max, $cantidad_ninos, $minimo_ninos, $dia_semana, $hora_inicio, $hora_fin, $total_sesiones, $valor_por_nino, $color, $estado, $observaciones]);
        }
        header('Location: ' . $U . 'modulos/extracurriculares/contratos/ver.php?id=' . $contrato_pre . '&msg=' . ($programa ? 'prog_editado' : 'prog_creado'));
        exit;
    }
}

// Cursos RSAL disponibles
$cursos = $pdo->query("SELECT id, nombre, edad_min, edad_max FROM cursos ORDER BY nombre")->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/contratos/index.php">Contratos</a>
      <i class="bi bi-chevron-right"></i>
      <a href="<?= $U ?>modulos/extracurriculares/contratos/ver.php?id=<?= $contrato['id'] ?>"><?= h($contrato['nombre']) ?></a>
      <i class="bi bi-chevron-right"></i>
      <?= $programa ? h($programa['nombre']) : 'Nuevo programa' ?>
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

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Este programa pertenece al contrato <strong><?= h($contrato['nombre']) ?></strong> de <strong><?= h($contrato['cliente_nombre']) ?></strong>.
  </div>

  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.4rem;align-items:start;">

      <div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-bookmark-check-fill"></i> Identificaci&oacute;n</div>

          <label class="field-label">Nombre del programa <span class="req">*</span></label>
          <input type="text" name="nombre" class="rsal-input" required maxlength="200"
                 placeholder="Ej LEGO SPIKE Prime &mdash; 3o y 4o"
                 value="<?= h($programa['nombre'] ?? '') ?>"/>

          <label class="field-label" style="margin-top:.6rem;">
            Curso RSAL asociado <small style="font-weight:400;color:var(--muted);">(opcional)</small>
          </label>
          <select name="curso_id" class="rsal-select">
            <option value="">-- Sin curso asociado --</option>
            <?php foreach ($cursos as $c):
              $edades = '';
              if ($c['edad_min'] && $c['edad_max']) $edades = " ({$c['edad_min']}-{$c['edad_max']} a&ntilde;os)";
            ?>
              <option value="<?= $c['id'] ?>" <?= ($programa['curso_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                <?= h($c['nombre']) ?><?= $edades ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div style="font-size:.72rem;color:var(--muted);margin-top:.3rem;line-height:1.5;">
            Al asociar un curso RSAL el programa reutilizar&aacute; sus temas, r&uacute;bricas y contenidos. Esto habilita la evaluaci&oacute;n por r&uacute;brica en la entrega 6.
          </div>

          <label class="field-label" style="margin-top:.6rem;">Equipos / Kit</label>
          <input type="text" name="equipos_kit" class="rsal-input" maxlength="200"
                 placeholder="Ej LEGO SPIKE Prime Arduino UNO Raspberry Pi"
                 value="<?= h($programa['equipos_kit'] ?? '') ?>"/>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-people-fill"></i> Grupo objetivo</div>

          <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Grado desde</label>
              <input type="text" name="grado_desde" class="rsal-input" maxlength="30"
                     placeholder="3&deg;"
                     value="<?= h($programa['grado_desde'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Grado hasta</label>
              <input type="text" name="grado_hasta" class="rsal-input" maxlength="30"
                     placeholder="5&deg;"
                     value="<?= h($programa['grado_hasta'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Edad m&iacute;n</label>
              <input type="number" name="edad_min" class="rsal-input" min="3" max="25"
                     value="<?= h($programa['edad_min'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Edad m&aacute;x</label>
              <input type="number" name="edad_max" class="rsal-input" min="3" max="25"
                     value="<?= h($programa['edad_max'] ?? '') ?>"/>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-top:.6rem;">
            <div>
              <label class="field-label">Cantidad ni&ntilde;os planeada <span class="req">*</span></label>
              <input type="number" name="cantidad_ninos" id="cantidad_ninos" class="rsal-input" min="0" required
                     placeholder="20"
                     value="<?= h($programa['cantidad_ninos'] ?? '') ?>"/>
              <div style="font-size:.7rem;color:var(--muted);margin-top:.2rem;">
                Base del cobro: se cobra por esta cantidad aunque vengan menos.
              </div>
            </div>
            <div>
              <label class="field-label">M&iacute;nimo viable</label>
              <input type="number" name="minimo_ninos" id="minimo_ninos" class="rsal-input" min="0"
                     placeholder="10"
                     value="<?= h($programa['minimo_ninos'] ?? 10) ?>"/>
              <div style="font-size:.7rem;color:var(--muted);margin-top:.2rem;">
                Si no se alcanza, el programa no es rentable (solo alerta).
              </div>
            </div>
          </div>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-calendar-event"></i> Horario</div>

          <div style="display:grid;grid-template-columns:1fr 140px 140px;gap:.8rem;">
            <div>
              <label class="field-label">D&iacute;a de la semana</label>
              <select name="dia_semana" class="rsal-select">
                <?php
                $dias = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];
                $d_sel = $programa['dia_semana'] ?? 'lunes';
                foreach ($dias as $k => $v): ?>
                  <option value="<?= $k ?>" <?= $d_sel === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="field-label">Hora inicio <span class="req">*</span></label>
              <input type="time" name="hora_inicio" class="rsal-input" required
                     value="<?= h($programa['hora_inicio'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Hora fin <span class="req">*</span></label>
              <input type="time" name="hora_fin" class="rsal-input" required
                     value="<?= h($programa['hora_fin'] ?? '') ?>"/>
            </div>
          </div>

          <div style="margin-top:.7rem;padding:.55rem .9rem;background:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;font-size:.78rem;color:#3730a3;">
            <i class="bi bi-info-circle-fill"></i>
            <strong>4 sesiones fijas</strong> &mdash; 1 por semana &mdash; cada programa es un paquete de 4 clases. Las fechas exactas se generan en el calendario (entrega 3).
          </div>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-cash-coin"></i> Tarifa y valor total</div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;align-items:end;">
            <div>
              <label class="field-label">Valor por ni&ntilde;o (COP)</label>
              <div style="position:relative;">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-weight:700;">$</span>
                <input type="number" name="valor_por_nino" id="valor_por_nino" class="rsal-input"
                       step="1000" min="0" style="padding-left:1.8rem;"
                       placeholder="120000"
                       value="<?= h($programa['valor_por_nino'] ?? 120000) ?>"/>
              </div>
              <div style="font-size:.7rem;color:var(--muted);margin-top:.2rem;">
                Tarifa por ni&ntilde;o para el paquete completo de 4 sesiones. Var&iacute;a seg&uacute;n cliente y programa.
              </div>
            </div>
            <div id="preview_valor" style="padding:.7rem 1rem;background:#d1fae5;border:1px solid #a7f3d0;border-radius:10px;font-size:.82rem;color:#065f46;">
              <strong>Valor total del programa:</strong>
              <div id="preview_total" style="font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:900;margin-top:.2rem;">$ 0</div>
              <div id="preview_formula" style="font-size:.7rem;margin-top:.2rem;opacity:.85;">&mdash;</div>
            </div>
          </div>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-journal-text"></i> Observaciones</div>
          <textarea name="observaciones" class="rsal-textarea" style="min-height:70px;"
                    placeholder="Notas metodol&oacute;gicas cronograma detallado condiciones especiales..."><?= h($programa['observaciones'] ?? '') ?></textarea>
        </div>

      </div>

      <div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-palette-fill"></i> Color en calendario</div>
          <p style="font-size:.76rem;color:var(--muted);margin-bottom:.6rem;line-height:1.5;">
            Se usa para diferenciar programas en el calendario visual (entrega 3).
          </p>
          <input type="color" name="color" class="rsal-input" style="height:46px;cursor:pointer;"
                 value="<?= h($programa['color'] ?? '#7c3aed') ?>"/>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-flag-fill"></i> Estado</div>
          <select name="estado" class="rsal-select">
            <?php
            $ests = ['planeado'=>'Planeado','en_curso'=>'En curso','finalizado'=>'Finalizado','suspendido'=>'Suspendido'];
            $e_sel = $programa['estado'] ?? 'planeado';
            foreach ($ests as $k => $v): ?>
              <option value="<?= $k ?>" <?= $e_sel === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.8rem;font-size:.92rem;">
            <i class="bi bi-check-lg"></i> <?= $programa ? 'Guardar cambios' : 'Crear programa' ?>
          </button>
          <a href="<?= $U ?>modulos/extracurriculares/contratos/ver.php?id=<?= $contrato['id'] ?>" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.6rem;margin-top:.6rem;">Cancelar</a>
        </div>

        <?php if ($programa): ?>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-trash"></i> Zona peligrosa</div>
          <form method="POST" action="<?= $U ?>modulos/extracurriculares/programas/eliminar.php"
                onsubmit="return confirm('&iquest;Eliminar este programa? Solo es posible si no tiene sesiones registradas.');">
            <input type="hidden" name="id" value="<?= $programa['id'] ?>"/>
            <button type="submit" class="btn-rsal-danger" style="width:100%;justify-content:center;padding:.6rem;font-size:.82rem;">
              <i class="bi bi-trash-fill"></i> Eliminar programa
            </button>
          </form>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </form>
</main>

<script>
const valorPorNino   = document.getElementById('valor_por_nino');
const cantidadNinos  = document.getElementById('cantidad_ninos');
const minimoNinos    = document.getElementById('minimo_ninos');
const previewTotal   = document.getElementById('preview_total');
const previewFormula = document.getElementById('preview_formula');
const previewValor   = document.getElementById('preview_valor');

function actualizarPreview() {
  const vn = parseFloat(valorPorNino.value) || 0;
  const cn = parseInt(cantidadNinos.value, 10) || 0;
  const mn = parseInt(minimoNinos.value, 10) || 0;
  const total = vn * cn;
  previewTotal.textContent = '$ ' + total.toLocaleString('es-CO');
  previewFormula.textContent = cn + ' ni\u00f1os \u00d7 $' + vn.toLocaleString('es-CO') + ' \u00d7 4 sesiones';

  if (cn > 0 && cn < mn) {
    previewValor.style.background = '#fff2d6';
    previewValor.style.borderColor = '#fde68a';
    previewValor.style.color = '#92400e';
    previewFormula.innerHTML = '\u26a0\ufe0f Faltan ' + (mn - cn) + ' ni\u00f1os para alcanzar el m\u00ednimo viable';
  } else {
    previewValor.style.background = '#d1fae5';
    previewValor.style.borderColor = '#a7f3d0';
    previewValor.style.color = '#065f46';
  }
}

valorPorNino.addEventListener('input', actualizarPreview);
cantidadNinos.addEventListener('input', actualizarPreview);
minimoNinos.addEventListener('input', actualizarPreview);
actualizarPreview();

document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
