<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Matr&iacute;cula';
$menu_activo = 'matriculas';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;

$id         = (int)($_GET['id'] ?? 0);
$matricula  = null;

if ($id) {
    $s = $pdo->prepare("SELECT * FROM matriculas WHERE id=?");
    $s->execute([$id]); $matricula = $s->fetch();
    if (!$matricula || ($sede_filtro && $matricula['sede_id'] != $sede_filtro)) {
        header('Location: '.$U.'modulos/matriculas/index.php'); exit;
    }
}

$titulo  = $matricula ? 'Editar matr&iacute;cula' : 'Nueva matr&iacute;cula';
$errores = [];

// Estudiantes por sede
$where_e = $sede_filtro ? 'WHERE e.sede_id='.(int)$sede_filtro.' AND e.activo=1' : 'WHERE e.activo=1';
$estudiantes = $pdo->query("SELECT e.id, e.nombre_completo, s.nombre AS sede FROM estudiantes e JOIN sedes s ON s.id=e.sede_id $where_e ORDER BY e.nombre_completo")->fetchAll();

// Grupos activos con cupos disponibles
$where_g = $sede_filtro ? 'WHERE g.sede_id='.(int)$sede_filtro.' AND g.activo=1' : 'WHERE g.activo=1';
$grupos  = $pdo->query("
    SELECT g.*, c.nombre AS curso_nombre, c.valor, c.tipo_valor,
        (g.cupo_real - COALESCE((SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id=g.id AND m.estado='activa'),0)) AS disponibles
    FROM grupos g JOIN cursos c ON c.id=g.curso_id $where_g
    ORDER BY c.nombre, g.dia_semana, g.hora_inicio
")->fetchAll();

$estados = ['pre_inscrito'=>'Pre-inscrito','activa'=>'Activa','retirada'=>'Retirada','finalizada'=>'Finalizada','suspendida'=>'Suspendida'];
$dias    = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estudiante_id = (int)($_POST['estudiante_id'] ?? 0);
    $grupo_id      = (int)($_POST['grupo_id']      ?? 0);
    $estado        = $_POST['estado']               ?? 'activa';
    $periodo       = trim($_POST['periodo']          ?? '');
    $observaciones = trim($_POST['observaciones']    ?? '');

    // Obtener sede del grupo seleccionado (m&aacute;s confiable que el hidden)
    $sede_id = $sede_filtro ?: 0;
    if ($grupo_id) {
        $sg = $pdo->prepare("SELECT sede_id FROM grupos WHERE id=?");
        $sg->execute([$grupo_id]);
        $sede_id = (int)($sg->fetchColumn() ?: $sede_id);
    }

    if (!$estudiante_id) $errores[] = 'Selecciona el estudiante.';
    if (!$grupo_id)      $errores[] = 'Selecciona el grupo.';
    if (!$periodo)       $errores[] = 'El per&iacute;odo es obligatorio.';

    // Verificar cupo disponible (solo en nueva matr&iacute;cula)
    if (!$id && $grupo_id) {
        $cupo_q = $pdo->prepare("SELECT g.cupo_real, (SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id=g.id AND m.estado='activa') AS inscritos FROM grupos g WHERE g.id=?");
        $cupo_q->execute([$grupo_id]); $cupo_info = $cupo_q->fetch();
        if ($cupo_info && $cupo_info['cupo_real'] > 0 && $cupo_info['inscritos'] >= $cupo_info['cupo_real']) {
            $errores[] = 'Este grupo no tiene cupos disponibles.';
        }
        // Verificar que no est&eacute; ya inscrito
        $dup = $pdo->prepare("SELECT id FROM matriculas WHERE estudiante_id=? AND grupo_id=? AND estado='activa'");
        $dup->execute([$estudiante_id, $grupo_id]);
        if ($dup->fetch()) $errores[] = 'Este estudiante ya est&aacute; inscrito en este grupo.';
    }

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE matriculas SET estudiante_id=?,grupo_id=?,sede_id=?,estado=?,periodo=?,observaciones=? WHERE id=?")
                ->execute([$estudiante_id,$grupo_id,$sede_id,$estado,$periodo,$observaciones,$id]);
        } else {
            $pdo->prepare("INSERT INTO matriculas (estudiante_id,grupo_id,sede_id,estado,periodo,observaciones) VALUES (?,?,?,?,?,?)")
                ->execute([$estudiante_id,$grupo_id,$sede_id,$estado,$periodo,$observaciones]);
            $matricula_id = $pdo->lastInsertId();

            // Obtener padre del estudiante
            $padre_q = $pdo->prepare("SELECT e.padre_id, c.valor FROM estudiantes e JOIN grupos g ON g.id=? JOIN cursos c ON c.id=g.curso_id WHERE e.id=?");
            $padre_q->execute([$grupo_id, $estudiante_id]);
            $padre_info = $padre_q->fetch();

            // Crear registro de pago autom&aacute;ticamente
            if ($padre_info && $padre_info['valor'] > 0) {
                $fecha_limite = date('Y-m-d', strtotime('+30 days'));
                $pdo->prepare("INSERT INTO pagos (matricula_id,padre_id,valor_total,valor_pagado,estado,fecha_limite) VALUES (?,?,?,0,'pendiente',?)")
                    ->execute([$matricula_id, $padre_info['padre_id'], $padre_info['valor'], $fecha_limite]);
            }
        }
        header('Location: '.$U.'modulos/matriculas/index.php?msg='.($matricula?'editada':'creada'));
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/matriculas/index.php">Matr&iacute;culas</a>
      <i class="bi bi-chevron-right"></i> <?= $matricula ? '#'.$matricula['id'] : 'Nueva' ?>
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

  <div style="max-width:640px;">
    <form method="POST">
      <div class="card-rsal">
        <div class="card-rsal-title"><i class="bi bi-clipboard2-check-fill"></i> Datos de la matr&iacute;cula</div>

        <label class="field-label">Estudiante <span class="req">*</span></label>
        <select name="estudiante_id" class="rsal-select" required>
          <option value="">Selecciona el estudiante...</option>
          <?php foreach ($estudiantes as $e): ?>
            <option value="<?= $e['id'] ?>" <?= ($matricula['estudiante_id']??'')==$e['id']?'selected':'' ?>>
              <?= h($e['nombre_completo']) ?> &mdash; <?= h($e['sede']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label class="field-label">Grupo / Horario <span class="req">*</span></label>
        <select name="grupo_id" id="sel_grupo" class="rsal-select" required onchange="mostrarInfoGrupo(this)">
          <option value="">Selecciona el grupo...</option>
          <?php foreach ($grupos as $g):
            $disp = (int)$g['disponibles'];
            $label = h($g['curso_nombre']) . ' &mdash; ' . h($g['nombre']) . ' &middot; ' .
                     ($dias[$g['dia_semana']]??$g['dia_semana']) . ' ' .
                     substr($g['hora_inicio'],0,5) . '&ndash;' . substr($g['hora_fin'],0,5) .
                     ' [' . $disp . ' cupos]';
          ?>
            <option value="<?= $g['id'] ?>"
                    data-disp="<?= $disp ?>"
                    data-valor="<?= $g['valor'] ?>"
                    data-tipo="<?= h($g['tipo_valor']) ?>"
                    data-horario="<?= $dias[$g['dia_semana']]??$g['dia_semana'] ?> <?= substr($g['hora_inicio'],0,5) ?>&ndash;<?= substr($g['hora_fin'],0,5) ?>"
                    <?= $disp <= 0 ? 'style="color:#999"' : '' ?>
                    <?= ($matricula['grupo_id']??'')==$g['id']?'selected':'' ?>>
              <?= $label ?>
            </option>
          <?php endforeach; ?>
        </select>

        <!-- Info del grupo seleccionado -->
        <div id="grupoInfo" style="display:none;background:var(--teal-l);border:1px solid rgba(29,169,154,.3);border-radius:10px;padding:.8rem 1rem;margin-bottom:.9rem;font-size:.82rem;">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
          <div>
            <label class="field-label">Estado <span class="req">*</span></label>
            <select name="estado" class="rsal-select" required>
              <?php foreach($estados as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($matricula['estado']??'activa')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="field-label">Per&iacute;odo <span class="req">*</span></label>
            <input type="text" name="periodo" class="rsal-input" required
                   placeholder="Ej: 2026-1"
                   value="<?= h($matricula['periodo'] ?? '2026-1') ?>"/>
          </div>
        </div>

        <input type="hidden" name="sede_id" value="<?= $sede_filtro ?: 0 ?>"/>

        <label class="field-label">Observaciones</label>
        <textarea name="observaciones" class="rsal-textarea" style="min-height:70px;"
                  placeholder="Notas adicionales..."><?= h($matricula['observaciones'] ?? '') ?></textarea>

        <?php if (!$id): ?>
          <div class="alert-rsal alert-info">
            <i class="bi bi-info-circle-fill"></i>
            Al crear la matr&iacute;cula se generar&aacute; autom&aacute;ticamente el registro de pago pendiente.
          </div>
        <?php endif; ?>

        <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
          <i class="bi bi-check-lg"></i> <?= $matricula?'Guardar cambios':'Crear matr&iacute;cula' ?>
        </button>
        <a href="<?= $U ?>modulos/matriculas/index.php" class="btn-rsal-secondary"
           style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
      </div>
    </form>
  </div>
</main>
<script>
function mostrarInfoGrupo(sel) {
  const opt   = sel.options[sel.selectedIndex];
  const info  = document.getElementById('grupoInfo');
  if (!opt.value) { info.style.display='none'; return; }
  const disp  = parseInt(opt.dataset.disp);
  const valor = parseFloat(opt.dataset.valor);
  const tipo  = opt.dataset.tipo;
  const hor   = opt.dataset.horario;
  const col   = disp > 0 ? '#16a34a' : 'var(--red)';
  const tipoLabel = tipo === 'semestral' ? 'semestre' : 'mes (4 sesiones)';
  info.style.display = 'block';
  info.innerHTML = `
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
      <div><strong>Horario:</strong> ${hor}</div>
      <div><strong>Cupos disponibles:</strong> <span style="color:${col};font-weight:800;">${disp}</span></div>
      ${valor > 0 ? `<div><strong>Valor:</strong> $${parseInt(valor).toLocaleString('es-CO')} / ${tipoLabel}</div>` : '<div><span style="color:#16a34a;font-weight:700;">Gratuito</span></div>'}
    </div>`;
}
// Inicializar si hay valor seleccionado
document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('sel_grupo');
  if (sel.value) mostrarInfoGrupo(sel);
});
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
