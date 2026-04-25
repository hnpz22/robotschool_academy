<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'R&uacute;brica';
$menu_activo = 'rubricas';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;

$where_c = $sede_filtro ? 'WHERE sede_id='.(int)$sede_filtro : '';
$cursos  = $pdo->query("SELECT id, nombre FROM cursos $where_c ORDER BY nombre")->fetchAll();

$id      = (int)($_GET['id'] ?? 0);
$rubrica = null;
$criterios = [];

if ($id) {
    $s = $pdo->prepare("SELECT r.* FROM rubricas r JOIN cursos c ON c.id=r.curso_id WHERE r.id=?");
    $s->execute([$id]); $rubrica = $s->fetch();
    if (!$rubrica) { header('Location:'.$U.'modulos/academico/rubricas/index.php'); exit; }
    $cs = $pdo->prepare("SELECT * FROM rubrica_criterios WHERE rubrica_id=? ORDER BY orden");
    $cs->execute([$id]); $criterios = $cs->fetchAll();
}

$titulo  = $rubrica ? 'Editar r&uacute;brica' : 'Nueva r&uacute;brica';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id    = (int)($_POST['curso_id']    ?? 0);
    $nombre      = trim($_POST['nombre']        ?? '');
    $descripcion = trim($_POST['descripcion']   ?? '');
    $periodo     = trim($_POST['periodo']        ?? '');
    $activa      = isset($_POST['activa']) ? 1 : 0;

    $crit_nombres = $_POST['crit_nombre']  ?? [];
    $crit_descs   = $_POST['crit_desc']    ?? [];
    $crit_pts     = $_POST['crit_pts']     ?? [];

    if (!$curso_id) $errores[] = 'Selecciona el curso.';
    if (!$nombre)   $errores[] = 'El nombre es obligatorio.';
    if (empty(array_filter($crit_nombres))) $errores[] = 'Agrega al menos un criterio.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE rubricas SET curso_id=?,nombre=?,descripcion=?,periodo=?,activa=? WHERE id=?")
                ->execute([$curso_id,$nombre,$descripcion,$periodo,$activa,$id]);
            $pdo->prepare("DELETE FROM rubrica_criterios WHERE rubrica_id=?")->execute([$id]);
        } else {
            $pdo->prepare("INSERT INTO rubricas (curso_id,nombre,descripcion,periodo,activa) VALUES (?,?,?,?,?)")
                ->execute([$curso_id,$nombre,$descripcion,$periodo,$activa]);
            $id = $pdo->lastInsertId();
        }
        // Insertar criterios
        foreach ($crit_nombres as $i => $cn) {
            $cn = trim($cn);
            if (!$cn) continue;
            $pdo->prepare("INSERT INTO rubrica_criterios (rubrica_id,criterio,descripcion,puntaje_max,orden) VALUES (?,?,?,?,?)")
                ->execute([$id, $cn, trim($crit_descs[$i]??''), max(1,(int)($crit_pts[$i]??5)), $i+1]);
        }
        header('Location:'.$U.'modulos/academico/rubricas/index.php?msg='.($rubrica?'editada':'creada'));
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
      <a href="<?= $U ?>modulos/academico/rubricas/index.php">R&uacute;bricas</a>
      <i class="bi bi-chevron-right"></i>
      <?= $rubrica ? h($rubrica['nombre']) : 'Nueva' ?>
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
    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.4rem;align-items:start;">

      <!-- IZQUIERDA: Criterios -->
      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-info-circle-fill"></i> Datos de la r&uacute;brica</div>

          <label class="field-label">Curso <span class="req">*</span></label>
          <select name="curso_id" class="rsal-select" required>
            <option value="">Selecciona el curso...</option>
            <?php foreach($cursos as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($rubrica['curso_id']??'')==$c['id']?'selected':'' ?>>
                <?= h($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label class="field-label">Nombre de la r&uacute;brica <span class="req">*</span></label>
          <input type="text" name="nombre" class="rsal-input" required
                 placeholder="Ej: Evaluaci&oacute;n M&oacute;dulo 1 &mdash; Rob&oacute;tica B&aacute;sica"
                 value="<?= h($rubrica['nombre'] ?? '') ?>"/>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Per&iacute;odo</label>
              <input type="text" name="periodo" class="rsal-input"
                     placeholder="Ej: 2026-1"
                     value="<?= h($rubrica['periodo'] ?? date('Y').'-1') ?>"/>
            </div>
          </div>

          <label class="field-label">Descripci&oacute;n</label>
          <textarea name="descripcion" class="rsal-textarea" style="min-height:60px;"
                    placeholder="Descripci&oacute;n opcional de la r&uacute;brica..."><?= h($rubrica['descripcion'] ?? '') ?></textarea>
        </div>

        <!-- CRITERIOS -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-list-check"></i> Criterios de evaluaci&oacute;n</div>
          <p style="font-size:.78rem;color:var(--muted);margin-bottom:1rem;">
            Define cada criterio y su puntaje m&aacute;ximo. El puntaje final ser&aacute; la suma de todos los criterios.
          </p>

          <div id="criteriosContainer">
            <?php if (!empty($criterios)): ?>
              <?php foreach ($criterios as $i => $c): ?>
              <div class="criterio-row" style="border:1px solid var(--border);border-radius:10px;padding:.8rem;margin-bottom:.7rem;background:var(--gray);">
                <div style="display:grid;grid-template-columns:1fr 80px 32px;gap:.5rem;align-items:center;margin-bottom:.5rem;">
                  <input type="text" name="crit_nombre[]" class="rsal-input" style="margin:0;"
                         placeholder="Nombre del criterio *" required
                         value="<?= h($c['criterio']) ?>"/>
                  <div style="text-align:center;">
                    <div style="font-size:.65rem;color:var(--muted);font-weight:600;margin-bottom:2px;">M&Aacute;X</div>
                    <input type="number" name="crit_pts[]" class="rsal-input" style="margin:0;text-align:center;padding:.4rem;"
                           min="1" max="100" value="<?= $c['puntaje_max'] ?>"/>
                  </div>
                  <button type="button" onclick="quitarCriterio(this)"
                          style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
                <input type="text" name="crit_desc[]" class="rsal-input" style="margin:0;font-size:.8rem;"
                       placeholder="Descripci&oacute;n del criterio (opcional)"
                       value="<?= h($c['descripcion'] ?? '') ?>"/>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <!-- Criterios por defecto para nuevas r&uacute;bricas -->
              <?php
              $defaults = [
                ['Participaci&oacute;n y actitud', 'Disposici&oacute;n, atenci&oacute;n y participaci&oacute;n activa durante las sesiones', 5],
                ['Comprensi&oacute;n del tema', 'Entiende los conceptos y principios trabajados en clase', 5],
                ['Construcci&oacute;n y prototipado', 'Habilidad para construir, armar y ajustar prototipos rob&oacute;ticos', 5],
                ['Programaci&oacute;n y l&oacute;gica', 'Capacidad para programar y depurar el comportamiento del robot', 5],
                ['Creatividad e innovaci&oacute;n', 'Propone soluciones originales y mejoras a los dise&ntilde;os', 5],
              ];
              foreach ($defaults as $i => $d):
              ?>
              <div class="criterio-row" style="border:1px solid var(--border);border-radius:10px;padding:.8rem;margin-bottom:.7rem;background:var(--gray);">
                <div style="display:grid;grid-template-columns:1fr 80px 32px;gap:.5rem;align-items:center;margin-bottom:.5rem;">
                  <input type="text" name="crit_nombre[]" class="rsal-input" style="margin:0;"
                         placeholder="Nombre del criterio *"
                         value="<?= h($d[0]) ?>"/>
                  <div style="text-align:center;">
                    <div style="font-size:.65rem;color:var(--muted);font-weight:600;margin-bottom:2px;">M&Aacute;X</div>
                    <input type="number" name="crit_pts[]" class="rsal-input" style="margin:0;text-align:center;padding:.4rem;"
                           min="1" max="100" value="<?= $d[2] ?>"/>
                  </div>
                  <button type="button" onclick="quitarCriterio(this)"
                          style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
                <input type="text" name="crit_desc[]" class="rsal-input" style="margin:0;font-size:.8rem;"
                       placeholder="Descripci&oacute;n del criterio (opcional)"
                       value="<?= h($d[1]) ?>"/>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <button type="button" onclick="agregarCriterio()"
                  style="display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.6rem;width:100%;background:var(--teal-l);color:var(--teal);border:1.5px dashed rgba(29,169,154,.4);border-radius:10px;font-size:.8rem;font-weight:700;cursor:pointer;margin-top:.3rem;">
            <i class="bi bi-plus-lg"></i> Agregar criterio
          </button>

          <!-- Puntaje total calculado -->
          <div style="margin-top:.9rem;background:var(--teal-l);border:1px solid rgba(29,169,154,.3);border-radius:10px;padding:.7rem 1rem;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:.82rem;font-weight:700;color:var(--teal);">Puntaje total de la r&uacute;brica:</span>
            <span id="totalPts" style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:900;color:var(--teal);">&mdash;</span>
          </div>
        </div>
      </div>

      <!-- DERECHA -->
      <div>
        <!-- Estado -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-toggle-on"></i> Estado</div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">R&uacute;brica activa</div>
              <div style="font-size:.72rem;color:var(--muted);">Disponible para evaluar</div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer;">
              <input type="checkbox" name="activa" style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($rubrica['activa']??1)?'checked':'' ?>
                     onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
              <span style="position:absolute;inset:0;background:<?= ($rubrica['activa']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($rubrica['activa']??1)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <!-- Gu&iacute;a r&aacute;pida -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-lightbulb-fill"></i> Gu&iacute;a de criterios</div>
          <div style="font-size:.78rem;color:var(--muted);line-height:1.7;">
            <p style="margin-bottom:.5rem;"><strong style="color:var(--dark);">Criterios sugeridos:</strong></p>
            <div style="display:flex;flex-direction:column;gap:.3rem;">
              <span>&#128203; Participaci&oacute;n y actitud</span>
              <span>&#129504; Comprensi&oacute;n del tema</span>
              <span>&#128295; Construcci&oacute;n / prototipado</span>
              <span>&#128187; Programaci&oacute;n y l&oacute;gica</span>
              <span>&#128161; Creatividad e innovaci&oacute;n</span>
              <span>&#129309; Trabajo en equipo</span>
              <span>&#128221; Presentaci&oacute;n del proyecto</span>
            </div>
            <p style="margin-top:.7rem;font-size:.72rem;">El puntaje m&aacute;ximo por criterio es configurable (1&ndash;100).</p>
          </div>
        </div>

        <!-- Guardar -->
        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $rubrica?'Guardar cambios':'Crear r&uacute;brica' ?>
          </button>
          <a href="<?= $U ?>modulos/academico/rubricas/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
        </div>
      </div>

    </div>
  </form>
</main>
<script>
function agregarCriterio() {
  const c = document.createElement('div');
  c.className = 'criterio-row';
  c.style = 'border:1px solid var(--border);border-radius:10px;padding:.8rem;margin-bottom:.7rem;background:var(--gray);';
  c.innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 80px 32px;gap:.5rem;align-items:center;margin-bottom:.5rem;">
      <input type="text" name="crit_nombre[]" class="rsal-input" style="margin:0;" placeholder="Nombre del criterio *"/>
      <div style="text-align:center;">
        <div style="font-size:.65rem;color:var(--muted);font-weight:600;margin-bottom:2px;">M&Aacute;X</div>
        <input type="number" name="crit_pts[]" class="rsal-input" style="margin:0;text-align:center;padding:.4rem;" min="1" max="100" value="5" oninput="calcTotal()"/>
      </div>
      <button type="button" onclick="quitarCriterio(this)"
              style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
        <i class="bi bi-x-lg"></i></button>
    </div>
    <input type="text" name="crit_desc[]" class="rsal-input" style="margin:0;font-size:.8rem;" placeholder="Descripci&oacute;n (opcional)"/>
  `;
  document.getElementById('criteriosContainer').appendChild(c);
  c.querySelector('input[name="crit_pts[]"]').addEventListener('input', calcTotal);
  calcTotal();
}

function quitarCriterio(btn) {
  btn.closest('.criterio-row').remove();
  calcTotal();
}

function calcTotal() {
  let t = 0;
  document.querySelectorAll('input[name="crit_pts[]"]').forEach(i => {
    const v = parseInt(i.value);
    if (!isNaN(v) && v > 0) t += v;
  });
  document.getElementById('totalPts').textContent = t || '&mdash;';
}

// Escuchar cambios en puntajes existentes
document.querySelectorAll('input[name="crit_pts[]"]').forEach(i => i.addEventListener('input', calcTotal));
document.addEventListener('DOMContentLoaded', calcTotal);
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
