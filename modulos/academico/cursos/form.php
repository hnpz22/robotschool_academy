<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Curso';
$menu_activo = 'cursos';
$U           = BASE_URL;

$id         = (int)($_GET['id'] ?? 0);
$curso      = null;
$modulos    = [];
$materiales = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
    $stmt->execute([$id]);
    $curso = $stmt->fetch();
    if (!$curso) {
        header('Location: ' . $U . 'modulos/academico/cursos/index.php'); exit;
    }
    $sm = $pdo->prepare("SELECT * FROM curso_modulos    WHERE curso_id=? ORDER BY orden");
    $sm->execute([$id]); $modulos = $sm->fetchAll();
    $smm = $pdo->prepare("SELECT * FROM curso_materiales WHERE curso_id=?");
    $smm->execute([$id]); $materiales = $smm->fetchAll();
}

$titulo  = $curso ? 'Editar: ' . $curso['nombre'] : 'Nuevo curso';
$errores = [];

// Si hubo submit con errores en curso nuevo, restaurar m&oacute;dulos/materiales desde POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$curso) {
    $modulos    = [];
    foreach ($_POST['mod_nombre'] ?? [] as $i => $mn) {
        $modulos[] = ['nombre' => $mn, 'descripcion' => $_POST['mod_desc'][$i] ?? ''];
    }
    $materiales = [];
    foreach ($_POST['mat_nombre'] ?? [] as $i => $mn) {
        $materiales[] = ['nombre' => $mn, 'cantidad' => $_POST['mat_cantidad'][$i] ?? 1, 'kit_referencia' => $_POST['mat_kit'][$i] ?? ''];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre']       ?? '');
    $introduccion = trim($_POST['introduccion'] ?? '');
    $objetivos    = trim($_POST['objetivos']    ?? '');
    $edad_min     = (int)($_POST['edad_min']    ?? 0) ?: null;
    $edad_max     = (int)($_POST['edad_max']    ?? 0) ?: null;
    $valor        = (float)str_replace(['.','$',' ','COP'], '', $_POST['valor'] ?? '0');
    $tipo_valor   = in_array($_POST['tipo_valor']??'', ['mensual','semestral']) ? $_POST['tipo_valor'] : 'mensual';
    $cupo_maximo  = (int)($_POST['cupo_maximo'] ?? 20);
    $publicado    = isset($_POST['publicado']) ? 1 : 0;
    $orden        = (int)($_POST['orden']       ?? 0);

    if (!$nombre) $errores[] = 'El nombre del curso es obligatorio.';

    // Imagen
    $imagen_nueva = $curso['imagen'] ?? null;
    if (!empty($_FILES['imagen']['name'])) {
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            $errores[] = 'La imagen debe ser JPG, PNG o WEBP.';
        } elseif ($_FILES['imagen']['size'] > 5*1024*1024) {
            $errores[] = 'La imagen no puede superar 5MB.';
        } else {
            $dir = ROOT . '/uploads/cursos/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $archivo = 'curso_' . time() . '_' . rand(100,999) . '.' . $ext;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dir . $archivo)) {
                if ($imagen_nueva && file_exists($dir . $imagen_nueva)) unlink($dir . $imagen_nueva);
                $imagen_nueva = $archivo;
            } else {
                $errores[] = 'Error al subir imagen. Verifica permisos en uploads/cursos/';
            }
        }
    }

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE cursos SET nombre=?,imagen=?,introduccion=?,objetivos=?,edad_min=?,edad_max=?,valor=?,tipo_valor=?,cupo_maximo=?,publicado=?,orden=? WHERE id=?")
                ->execute([$nombre,$imagen_nueva,$introduccion,$objetivos,$edad_min,$edad_max,$valor,$tipo_valor,$cupo_maximo,$publicado,$orden,$id]);
        } else {
            $pdo->prepare("INSERT INTO cursos (nombre,imagen,introduccion,objetivos,edad_min,edad_max,valor,tipo_valor,cupo_maximo,publicado,orden) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$nombre,$imagen_nueva,$introduccion,$objetivos,$edad_min,$edad_max,$valor,$tipo_valor,$cupo_maximo,$publicado,$orden]);
            $id = $pdo->lastInsertId();
        }
        // M&oacute;dulos
        $pdo->prepare("DELETE FROM curso_modulos WHERE curso_id=?")->execute([$id]);
        foreach ($_POST['mod_nombre'] ?? [] as $i => $mn) {
            if (!trim($mn)) continue;
            $pdo->prepare("INSERT INTO curso_modulos (curso_id,nombre,descripcion,orden) VALUES (?,?,?,?)")
                ->execute([$id, trim($mn), trim($_POST['mod_desc'][$i]??''), $i+1]);
        }
        // Materiales
        $pdo->prepare("DELETE FROM curso_materiales WHERE curso_id=?")->execute([$id]);
        foreach ($_POST['mat_nombre'] ?? [] as $i => $mn) {
            if (!trim($mn)) continue;
            $pdo->prepare("INSERT INTO curso_materiales (curso_id,nombre,cantidad,kit_referencia) VALUES (?,?,?,?)")
                ->execute([$id, trim($mn), (int)($_POST['mat_cantidad'][$i]??1), trim($_POST['mat_kit'][$i]??'')]);
        }
        // Galer&iacute;a adicional
        procesarGaleria($pdo, $id, $U);

        header('Location: ' . $U . 'modulos/academico/cursos/index.php?msg=' . ($curso ? 'editado':'creado'));
        exit;
    }
}

// Procesar galer&iacute;a aparte (se guarda junto con el curso)
function procesarGaleria($pdo, $curso_id, $U) {
    if (empty($_FILES['galeria']['name'][0])) return;
    $dir = ROOT . '/uploads/cursos/galeria/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    // Obtener orden actual m&aacute;ximo
    $maxOrden = $pdo->prepare("SELECT COALESCE(MAX(orden),0) FROM curso_galeria WHERE curso_id=?");
    $maxOrden->execute([$curso_id]);
    $orden = (int)$maxOrden->fetchColumn();
    foreach ($_FILES['galeria']['tmp_name'] as $i => $tmp) {
        if (!$tmp || $_FILES['galeria']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($_FILES['galeria']['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
        if ($_FILES['galeria']['size'][$i] > 3*1024*1024) continue;
        $archivo = 'gal_' . $curso_id . '_' . time() . '_' . $i . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . $archivo)) {
            $orden++;
            $pdo->prepare("INSERT INTO curso_galeria (curso_id,imagen,orden) VALUES (?,?,?)")
                ->execute([$curso_id, $archivo, $orden]);
        }
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>

<header class="main-header">
  <div class="header-title">
    <?= $curso ? 'Editar curso' : 'Nuevo curso' ?>
    <small>
      <span class="breadcrumb-rsal">
        <a href="<?= $U ?>modulos/academico/cursos/index.php">Cursos</a>
        <i class="bi bi-chevron-right"></i>
        <?= $curso ? h($curso['nombre']) : 'Nuevo' ?>
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

  <form method="POST" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.4rem;align-items:start;">

      <!-- IZQUIERDA -->
      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-info-circle-fill"></i> Informaci&oacute;n del curso</div>

          <div style="background:var(--teal-l);border-radius:10px;padding:.7rem .9rem;margin-bottom:1rem;font-size:.82rem;color:var(--teal);display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-info-circle-fill"></i>
            El curso es &uacute;nico. La sede y los horarios se definen en cada <strong>grupo</strong>.
          </div>

          <label class="field-label">Nombre del curso <span class="req">*</span></label>
          <input type="text" name="nombre" class="rsal-input" required
                 placeholder="Ej: Rob&oacute;tica con Arduino para Principiantes"
                 value="<?= h($curso['nombre'] ?? '') ?>"/>

          <label class="field-label">Introducci&oacute;n</label>
          <textarea name="introduccion" class="rsal-textarea"
                    placeholder="Descripci&oacute;n breve visible en el cat&aacute;logo p&uacute;blico..."><?= h($curso['introduccion'] ?? '') ?></textarea>

          <label class="field-label">Objetivos</label>
          <textarea name="objetivos" class="rsal-textarea" style="min-height:100px;"
                    placeholder="&iquest;Qu&eacute; aprender&aacute; el estudiante al finalizar?"><?= h($curso['objetivos'] ?? '') ?></textarea>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Edad m&iacute;nima</label>
              <input type="number" name="edad_min" class="rsal-input" min="3" max="18"
                     placeholder="6" value="<?= h($curso['edad_min'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Edad m&aacute;xima</label>
              <input type="number" name="edad_max" class="rsal-input" min="3" max="18"
                     placeholder="16" value="<?= h($curso['edad_max'] ?? '') ?>"/>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Valor (COP)</label>
              <input type="text" name="valor" class="rsal-input"
                     placeholder="$ 0" value="<?= $curso ? formatCOP($curso['valor']) : '' ?>"/>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:-.5rem;">
                <label style="display:flex;align-items:center;gap:.5rem;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:all .2s;font-size:.82rem;font-weight:700;"
                       id="lblMensual">
                  <input type="radio" name="tipo_valor" value="mensual"
                         <?= ($curso['tipo_valor']??'mensual')==='mensual'?'checked':'' ?>
                         onchange="estilizarTipoValor()"/>
                  <span>&#128197; Mensual <small style="display:block;font-weight:400;color:var(--muted);font-size:.72rem;">4 sesiones/mes</small></span>
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:all .2s;font-size:.82rem;font-weight:700;"
                       id="lblSemestral">
                  <input type="radio" name="tipo_valor" value="semestral"
                         <?= ($curso['tipo_valor']??'')==='semestral'?'checked':'' ?>
                         onchange="estilizarTipoValor()"/>
                  <span>&#128198; Semestral <small style="display:block;font-weight:400;color:var(--muted);font-size:.72rem;">pago &uacute;nico</small></span>
                </label>
              </div>
            </div>
            <div>
              <label class="field-label">Cupo m&aacute;ximo</label>
              <input type="number" name="cupo_maximo" class="rsal-input" min="1"
                     value="<?= h($curso['cupo_maximo'] ?? 20) ?>"/>
            </div>
          </div>
        </div>

        <!-- M&oacute;dulos -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-collection-fill"></i> M&oacute;dulos del curso</div>
          <div id="mods">
            <?php foreach ($modulos ?: [['nombre'=>'','descripcion'=>'']] as $m): ?>
            <div class="mod-row" style="display:flex;gap:.5rem;align-items:flex-start;margin-bottom:.6rem;background:var(--gray);border-radius:10px;padding:.6rem .8rem;">
              <div style="flex:1;">
                <input type="text" name="mod_nombre[]" class="rsal-input" placeholder="Nombre del m&oacute;dulo" value="<?= h($m['nombre']) ?>"/>
                <textarea name="mod_desc[]" class="rsal-textarea" style="min-height:55px;" placeholder="Descripci&oacute;n (opcional)"><?= h($m['descripcion']) ?></textarea>
              </div>
              <button type="button" onclick="this.closest('.mod-row').remove()"
                      style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;margin-top:2px;">
                <i class="bi bi-x-lg"></i></button>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" onclick="addMod()" style="display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.5rem;width:100%;background:var(--teal-l);color:var(--teal);border:1.5px dashed rgba(29,169,154,.4);border-radius:10px;font-size:.8rem;font-weight:700;cursor:pointer;">
            <i class="bi bi-plus-lg"></i> Agregar m&oacute;dulo
          </button>
        </div>

        <!-- Materiales -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-box-seam-fill"></i> Materiales y kits</div>
          <div style="display:grid;grid-template-columns:2fr 1fr 1.5fr auto;gap:.4rem;margin-bottom:.4rem;">
            <span style="font-size:.7rem;font-weight:700;color:var(--muted);">Material</span>
            <span style="font-size:.7rem;font-weight:700;color:var(--muted);">Cant.</span>
            <span style="font-size:.7rem;font-weight:700;color:var(--muted);">Kit ref.</span>
            <span></span>
          </div>
          <div id="mats">
            <?php foreach ($materiales ?: [['nombre'=>'','cantidad'=>1,'kit_referencia'=>'']] as $m): ?>
            <div class="mat-row" style="display:grid;grid-template-columns:2fr 1fr 1.5fr auto;gap:.4rem;margin-bottom:.4rem;align-items:center;">
              <input type="text"   name="mat_nombre[]"   class="rsal-input" style="margin:0;" placeholder="Ej: Arduino UNO" value="<?= h($m['nombre']) ?>"/>
              <input type="number" name="mat_cantidad[]" class="rsal-input" style="margin:0;" min="1" value="<?= h($m['cantidad']) ?>"/>
              <input type="text"   name="mat_kit[]"      class="rsal-input" style="margin:0;" placeholder="Ecua-03" value="<?= h($m['kit_referencia']) ?>"/>
              <button type="button" onclick="this.closest('.mat-row').remove()"
                      style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                <i class="bi bi-x-lg"></i></button>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" onclick="addMat()" style="display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.5rem;width:100%;background:var(--teal-l);color:var(--teal);border:1.5px dashed rgba(29,169,154,.4);border-radius:10px;font-size:.8rem;font-weight:700;cursor:pointer;margin-top:.4rem;">
            <i class="bi bi-plus-lg"></i> Agregar material / kit
          </button>
        </div>
      </div>

      <!-- DERECHA -->
      <div>
        <!-- Imagen -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-image-fill"></i> Imagen principal</div>
          <?php if ($curso && $curso['imagen'] && file_exists(ROOT.'/uploads/cursos/'.$curso['imagen'])): ?>
            <img src="<?= $U ?>uploads/cursos/<?= h($curso['imagen']) ?>" id="imgPrev"
                 style="width:100%;height:170px;object-fit:cover;border-radius:10px;margin-bottom:.9rem;"/>
          <?php else: ?>
            <img id="imgPrev" style="width:100%;height:170px;object-fit:cover;border-radius:10px;margin-bottom:.9rem;display:none;"/>
          <?php endif; ?>
          <div style="border:2px dashed var(--border);border-radius:12px;padding:1.5rem;text-align:center;cursor:pointer;position:relative;transition:all .2s;"
               onmouseover="this.style.borderColor='var(--teal)';this.style.background='var(--teal-ll)';"
               onmouseout="this.style.borderColor='';this.style.background='';">
            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                   style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                   onchange="prevImg(this)"/>
            <i class="bi bi-cloud-arrow-up-fill" style="font-size:1.8rem;color:var(--muted);display:block;margin-bottom:.4rem;"></i>
            <p style="font-size:.78rem;color:var(--muted);margin:0;"><strong style="color:var(--teal);">Clic o arrastra</strong><br>JPG, PNG, WEBP &middot; m&aacute;x 5MB</p>
          </div>
        </div>

        <!-- Galer&iacute;a adicional -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-images"></i> Galer&iacute;a de fotos adicionales</div>
          <p style="font-size:.78rem;color:var(--muted);margin-bottom:.8rem;">M&aacute;ximo 6 fotos &middot; JPG, PNG, WEBP &middot; m&aacute;x 3MB c/u</p>
          <!-- Fotos existentes -->
          <?php if ($id):
            $gal_existing = $pdo->prepare("SELECT * FROM curso_galeria WHERE curso_id=? ORDER BY orden");
            $gal_existing->execute([$id]);
            $gal_items = $gal_existing->fetchAll();
            if ($gal_items):
          ?>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-bottom:.8rem;" id="galGrid">
            <?php foreach ($gal_items as $gi): ?>
            <div style="position:relative;border-radius:8px;overflow:hidden;" id="gal_<?= $gi['id'] ?>">
              <img src="<?= $U ?>uploads/cursos/galeria/<?= h($gi['imagen']) ?>"
                   style="width:100%;height:80px;object-fit:cover;display:block;"/>
              <a href="<?= $U ?>modulos/academico/cursos/galeria_eliminar.php?id=<?= $gi['id'] ?>&curso=<?= $id ?>"
                 onclick="return confirm('&iquest;Eliminar esta foto?')"
                 style="position:absolute;top:3px;right:3px;width:22px;height:22px;background:var(--red);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;text-decoration:none;">
                &#10005;
              </a>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; endif; ?>
          <!-- Subir nuevas -->
          <div style="border:2px dashed var(--border);border-radius:12px;padding:1.2rem;text-align:center;cursor:pointer;position:relative;transition:all .2s;"
               onmouseover="this.style.borderColor='var(--teal)';this.style.background='var(--teal-ll)';"
               onmouseout="this.style.borderColor='';this.style.background='';">
            <input type="file" name="galeria[]" accept="image/jpeg,image/png,image/webp"
                   multiple style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                   onchange="prevGaleria(this)"/>
            <i class="bi bi-images" style="font-size:1.5rem;color:var(--muted);display:block;margin-bottom:.3rem;"></i>
            <p style="font-size:.75rem;color:var(--muted);margin:0;"><strong style="color:var(--teal);">Clic o arrastra</strong> varias fotos</p>
          </div>
          <div id="galeriaPreview" style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-top:.6rem;"></div>
        </div>

        <!-- Toggle publicado -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-toggle-on"></i> Publicaci&oacute;n</div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Publicar en cat&aacute;logo</div>
              <div style="font-size:.72rem;color:var(--muted);">Visible en la p&aacute;gina web p&uacute;blica</div>
            </div>
            <label style="position:relative;width:44px;height:24px;flex-shrink:0;cursor:pointer;">
              <input type="checkbox" name="publicado" id="chkPub" style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($curso['publicado']??0) ? 'checked':'' ?>
                     onchange="document.getElementById('trackPub').style.background=this.checked?'var(--teal)':'var(--gray2)';document.getElementById('knobPub').style.transform=this.checked?'translateX(20px)':'';">
              <span id="trackPub" style="position:absolute;inset:0;background:<?= ($curso['publicado']??0)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span id="knobPub" style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($curso['publicado']??0)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <!-- Botones -->
        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $curso ? 'Guardar cambios':'Crear curso' ?>
          </button>
          <a href="<?= $U ?>modulos/academico/cursos/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">
            Cancelar
          </a>
        </div>
      </div>

    </div>
  </form>
</main>

<script>
function estilizarTipoValor() {
  const m = document.querySelector('input[name=tipo_valor][value=mensual]');
  const s = document.querySelector('input[name=tipo_valor][value=semestral]');
  if (!m || !s) return;
  document.getElementById('lblMensual').style.cssText   += m.checked ? ';border-color:var(--teal);background:var(--teal-l)' : ';border-color:var(--border);background:';
  document.getElementById('lblSemestral').style.cssText += s.checked ? ';border-color:var(--teal);background:var(--teal-l)' : ';border-color:var(--border);background:';
}
document.addEventListener('DOMContentLoaded', estilizarTipoValor);

function prevGaleria(input) {
  const prev = document.getElementById('galeriaPreview');
  prev.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const r = new FileReader();
    r.onload = e => {
      const d = document.createElement('div');
      d.style.cssText = 'border-radius:8px;overflow:hidden;';
      d.innerHTML = `<img src="${e.target.result}" style="width:100%;height:80px;object-fit:cover;display:block;"/>`;
      prev.appendChild(d);
    };
    r.readAsDataURL(file);
  });
}
function prevImagen(i) {
  if (i.files && i.files[0]) {
    const r = new FileReader();
    r.onload = e => { const p=document.getElementById('imgPrev'); p.src=e.target.result; p.style.display='block'; };
    r.readAsDataURL(i.files[0]);
  }
}
function addMod() {
  const d = document.createElement('div');
  d.className='mod-row';
  d.style='display:flex;gap:.5rem;align-items:flex-start;margin-bottom:.6rem;background:var(--gray);border-radius:10px;padding:.6rem .8rem;';
  d.innerHTML=`<div style="flex:1;"><input type="text" name="mod_nombre[]" class="rsal-input" placeholder="Nombre del m&oacute;dulo"/><textarea name="mod_desc[]" class="rsal-textarea" style="min-height:55px;" placeholder="Descripci&oacute;n (opcional)"></textarea></div><button type="button" onclick="this.closest('.mod-row').remove()" style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;margin-top:2px;"><i class="bi bi-x-lg"></i></button>`;
  document.getElementById('mods').appendChild(d);
}
function addMat() {
  const d = document.createElement('div');
  d.className='mat-row';
  d.style='display:grid;grid-template-columns:2fr 1fr 1.5fr auto;gap:.4rem;margin-bottom:.4rem;align-items:center;';
  d.innerHTML=`<input type="text" name="mat_nombre[]" class="rsal-input" style="margin:0;" placeholder="Material"/><input type="number" name="mat_cantidad[]" class="rsal-input" style="margin:0;" placeholder="1" min="1" value="1"/><input type="text" name="mat_kit[]" class="rsal-input" style="margin:0;" placeholder="Kit ref."/><button type="button" onclick="this.closest('.mat-row').remove()" style="background:var(--red-l);color:var(--red);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><i class="bi bi-x-lg"></i></button>`;
  document.getElementById('mats').appendChild(d);
}
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
