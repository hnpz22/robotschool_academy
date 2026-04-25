<?php
// modulos/extracurriculares/contratos/form.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_contratos';
$U           = BASE_URL;

$id       = (int)($_GET['id']      ?? 0);
$cliente_pre = (int)($_GET['cliente'] ?? 0); // Para crear contrato desde la ficha del cliente
$contrato = null;

if ($id) {
    $s = $pdo->prepare("SELECT * FROM ec_contratos WHERE id = ?");
    $s->execute([$id]);
    $contrato = $s->fetch();
    if (!$contrato) { header('Location: ' . $U . 'modulos/extracurriculares/contratos/index.php'); exit; }
}

$titulo  = $contrato ? 'Editar contrato' : 'Nuevo contrato';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id       = (int)($_POST['cliente_id']       ?? 0);
    $codigo           = trim($_POST['codigo']            ?? '');
    $nombre           = trim($_POST['nombre']            ?? '');
    $fecha_inicio     = $_POST['fecha_inicio']           ?? '';
    $fecha_fin        = $_POST['fecha_fin']              ?? '';
    $tipo_duracion    = $_POST['tipo_duracion']          ?? 'semestral';
    $valor_total      = (float)str_replace(['.',','], ['','.'], $_POST['valor_total'] ?? 0);
    $condiciones_pago = trim($_POST['condiciones_pago']  ?? '');
    $estado           = $_POST['estado']                 ?? 'borrador';
    $observaciones    = trim($_POST['observaciones']     ?? '');

    $tipos_validos = ['mensual','bimestral','trimestral','semestral','anual','personalizado'];
    if (!in_array($tipo_duracion, $tipos_validos)) $tipo_duracion = 'semestral';
    $estados_validos = ['borrador','vigente','suspendido','finalizado','cancelado'];
    if (!in_array($estado, $estados_validos)) $estado = 'borrador';

    if (!$cliente_id) $errores[] = 'Debes seleccionar un cliente.';
    if (!$nombre)     $errores[] = 'El nombre del contrato es obligatorio.';
    if (!$fecha_inicio || !$fecha_fin) $errores[] = 'Las fechas de inicio y fin son obligatorias.';
    if ($fecha_inicio && $fecha_fin && $fecha_fin < $fecha_inicio) {
        $errores[] = 'La fecha de fin no puede ser anterior a la de inicio.';
    }

    // Si hay codigo validar unicidad
    if (!empty($codigo)) {
        $chk = $pdo->prepare("SELECT id FROM ec_contratos WHERE codigo = ? AND id != ?");
        $chk->execute([$codigo, $id]);
        if ($chk->fetchColumn()) $errores[] = 'Ese c&oacute;digo ya existe.';
    }

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE ec_contratos SET cliente_id=?, codigo=?, nombre=?, fecha_inicio=?, fecha_fin=?, tipo_duracion=?, valor_total=?, condiciones_pago=?, estado=?, observaciones=? WHERE id=?")
                ->execute([$cliente_id, $codigo ?: null, $nombre, $fecha_inicio, $fecha_fin, $tipo_duracion, $valor_total, $condiciones_pago, $estado, $observaciones, $id]);
            $goto = $id;
        } else {
            $pdo->prepare("INSERT INTO ec_contratos (cliente_id, codigo, nombre, fecha_inicio, fecha_fin, tipo_duracion, valor_total, condiciones_pago, estado, observaciones) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$cliente_id, $codigo ?: null, $nombre, $fecha_inicio, $fecha_fin, $tipo_duracion, $valor_total, $condiciones_pago, $estado, $observaciones]);
            $goto = $pdo->lastInsertId();
        }
        header('Location: ' . $U . 'modulos/extracurriculares/contratos/ver.php?id=' . $goto . '&msg=' . ($contrato ? 'editado' : 'creado'));
        exit;
    }
}

// Generar codigo sugerido si es nuevo
$codigo_sugerido = '';
if (!$contrato) {
    $n = (int)$pdo->query("SELECT COUNT(*) FROM ec_contratos WHERE YEAR(created_at) = YEAR(CURDATE())")->fetchColumn() + 1;
    $codigo_sugerido = 'EC-' . date('Y') . '-' . str_pad($n, 3, '0', STR_PAD_LEFT);
}

$clientes = $pdo->query("SELECT id, nombre, tipo, ciudad FROM ec_clientes WHERE activo = 1 ORDER BY nombre")->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/contratos/index.php">Contratos</a>
      <i class="bi bi-chevron-right"></i>
      <?= $contrato ? h($contrato['nombre']) : 'Nuevo' ?>
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

      <div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-file-earmark-text-fill"></i> Datos del contrato</div>

          <label class="field-label">Cliente <span class="req">*</span></label>
          <select name="cliente_id" class="rsal-select" required>
            <option value="">-- Seleccionar --</option>
            <?php
            $cliente_sel = $contrato['cliente_id'] ?? $cliente_pre;
            foreach ($clientes as $c):
            ?>
              <option value="<?= $c['id'] ?>" <?= $cliente_sel == $c['id'] ? 'selected' : '' ?>>
                <?= h($c['nombre']) ?> &middot; <?= h(ucfirst($c['tipo'])) ?>
                <?= $c['ciudad'] ? ' &middot; ' . h($c['ciudad']) : '' ?>
              </option>
            <?php endforeach; ?>
          </select>

          <div style="display:grid;grid-template-columns:180px 1fr;gap:.8rem;">
            <div>
              <label class="field-label">C&oacute;digo</label>
              <input type="text" name="codigo" class="rsal-input" maxlength="30"
                     placeholder="<?= h($codigo_sugerido) ?>"
                     value="<?= h($contrato['codigo'] ?? $codigo_sugerido) ?>"/>
            </div>
            <div>
              <label class="field-label">Nombre del contrato <span class="req">*</span></label>
              <input type="text" name="nombre" class="rsal-input" required maxlength="200"
                     placeholder="Ej Rob&oacute;tica 2026-1 Gimnasio Moderno"
                     value="<?= h($contrato['nombre'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-calendar3"></i> Vigencia</div>

          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Fecha inicio <span class="req">*</span></label>
              <input type="date" name="fecha_inicio" class="rsal-input" required
                     value="<?= h($contrato['fecha_inicio'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Fecha fin <span class="req">*</span></label>
              <input type="date" name="fecha_fin" class="rsal-input" required
                     value="<?= h($contrato['fecha_fin'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Tipo duraci&oacute;n</label>
              <select name="tipo_duracion" class="rsal-select">
                <?php
                $durs = [
                    'mensual'       => 'Mensual',
                    'bimestral'     => 'Bimestral',
                    'trimestral'    => 'Trimestral',
                    'semestral'     => 'Semestral',
                    'anual'         => 'Anual',
                    'personalizado' => 'Personalizado',
                ];
                $d_sel = $contrato['tipo_duracion'] ?? 'semestral';
                foreach ($durs as $k => $v): ?>
                  <option value="<?= $k ?>" <?= $d_sel === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-cash-coin"></i> Valor total del contrato</div>

          <div style="background:#fff7e6;border:1px solid #fde68a;border-radius:10px;padding:.7rem 1rem;margin-bottom:.9rem;font-size:.8rem;color:#92400e;line-height:1.6;">
            <i class="bi bi-info-circle-fill"></i>
            <strong>Modelo de facturaci&oacute;n:</strong> el valor total se calcula sumando los programas.
            Cada programa cobra <strong>cantidad de ni&ntilde;os &times; valor por ni&ntilde;o</strong> (paquete de 4 sesiones &mdash; 1 por semana). Este campo abajo es un valor de referencia manual; el total real lo ver&aacute;s en la vista detalle del contrato sumado autom&aacute;ticamente.
          </div>

          <label class="field-label">Valor total pactado (COP) <small style="font-weight:400;color:var(--muted);">referencia manual</small></label>
          <div style="position:relative;max-width:260px;">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-weight:700;">$</span>
            <input type="number" name="valor_total" id="valor_total" class="rsal-input"
                   step="1000" min="0" style="padding-left:1.8rem;"
                   value="<?= h($contrato['valor_total'] ?? '') ?>"/>
          </div>

          <label class="field-label" style="margin-top:.9rem;">Condiciones de pago</label>
          <input type="text" name="condiciones_pago" class="rsal-input" maxlength="200"
                 placeholder="Ej Pago mensual contra factura a 30 d&iacute;as"
                 value="<?= h($contrato['condiciones_pago'] ?? '') ?>"/>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-journal-text"></i> Observaciones</div>
          <textarea name="observaciones" class="rsal-textarea" style="min-height:80px;"
                    placeholder="Acuerdos especiales condiciones t&eacute;cnicas cl&aacute;usulas..."><?= h($contrato['observaciones'] ?? '') ?></textarea>
        </div>

      </div>

      <div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-flag-fill"></i> Estado</div>
          <select name="estado" class="rsal-select">
            <?php
            $ests = [
                'borrador'   => 'Borrador',
                'vigente'    => 'Vigente',
                'suspendido' => 'Suspendido',
                'finalizado' => 'Finalizado',
                'cancelado'  => 'Cancelado',
            ];
            $e_sel = $contrato['estado'] ?? 'borrador';
            foreach ($ests as $k => $v): ?>
              <option value="<?= $k ?>" <?= $e_sel === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
          <div style="font-size:.72rem;color:var(--muted);margin-top:.5rem;line-height:1.5;">
            Marca como <strong>Vigente</strong> cuando el contrato est&eacute; firmado y en ejecuci&oacute;n.
          </div>
        </div>

        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $contrato ? 'Guardar cambios' : 'Crear contrato' ?>
          </button>
          <a href="<?= $U ?>modulos/extracurriculares/contratos/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
        </div>

        <?php if ($contrato): ?>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-trash"></i> Zona peligrosa</div>
          <form method="POST" action="<?= $U ?>modulos/extracurriculares/contratos/eliminar.php"
                onsubmit="return confirm('&iquest;Eliminar este contrato? Esta acci&oacute;n es irreversible y solo es posible si no tiene programas asociados.');">
            <input type="hidden" name="id" value="<?= $contrato['id'] ?>"/>
            <button type="submit" class="btn-rsal-danger" style="width:100%;justify-content:center;padding:.65rem;font-size:.85rem;">
              <i class="bi bi-trash-fill"></i> Eliminar contrato
            </button>
          </form>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </form>
</main>

<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
