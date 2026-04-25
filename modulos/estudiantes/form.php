<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Estudiante';
$menu_activo = 'estudiantes';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;
$sedes       = $pdo->query("SELECT * FROM sedes WHERE activa=1 ORDER BY nombre")->fetchAll();
$padres_list = $pdo->query("SELECT p.id, p.nombre_completo, p.telefono FROM padres p ORDER BY p.nombre_completo")->fetchAll();

$id         = (int)($_GET['id'] ?? 0);
$estudiante = null;

if ($id) {
    $s = $pdo->prepare("SELECT * FROM estudiantes WHERE id=?");
    $s->execute([$id]); $estudiante = $s->fetch();
    if (!$estudiante || ($sede_filtro && $estudiante['sede_id'] != $sede_filtro)) {
        header('Location: '.$U.'modulos/estudiantes/index.php'); exit;
    }
}

$titulo  = $estudiante ? 'Editar: '.h($estudiante['nombre_completo']) : 'Nuevo estudiante';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $padre_id        = (int)($_POST['padre_id']       ?? 0);
    $sede_id         = (int)($_POST['sede_id']        ?? ($sede_filtro ?: 0));
    $nombre          = trim($_POST['nombre_completo'] ?? '');
    $tipo_doc        = $_POST['tipo_doc']              ?? 'TI';
    $numero_doc      = trim($_POST['numero_doc']       ?? '');
    $fecha_nac       = $_POST['fecha_nacimiento']      ?? '';
    $genero          = $_POST['genero']                ?? 'prefiero_no_decir';
    $colegio         = trim($_POST['colegio']          ?? '');
    $grado           = trim($_POST['grado']            ?? '');
    $eps             = trim($_POST['eps']              ?? '');
    $grupo_sang      = trim($_POST['grupo_sanguineo']  ?? '');
    $seguro          = trim($_POST['seguro_estudiantil']?? '');
    $alergias        = trim($_POST['alergias']         ?? '');
    $observaciones   = trim($_POST['observaciones']    ?? '');
    $activo          = isset($_POST['activo']) ? 1 : 0;

    if (!$nombre)   $errores[] = 'El nombre es obligatorio.';
    if (!$padre_id) $errores[] = 'Selecciona el padre o acudiente.';
    if (!$sede_id)  $errores[] = 'Selecciona la sede.';
    if (!$fecha_nac) $errores[] = 'La fecha de nacimiento es obligatoria.';

    // Avatar
    $avatar_actual = $estudiante['avatar'] ?? null;
    $avatar_nuevo  = $avatar_actual;
    if (!empty($_FILES['avatar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            $errores[] = 'El avatar debe ser JPG, PNG o WEBP.';
        } elseif ($_FILES['avatar']['size'] > 3*1024*1024) {
            $errores[] = 'El avatar no puede superar 3MB.';
        } else {
            $dir = ROOT . '/uploads/estudiantes/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $archivo = 'est_' . time() . '_' . rand(100,999) . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir.$archivo)) {
                if ($avatar_actual && file_exists($dir.$avatar_actual)) unlink($dir.$avatar_actual);
                $avatar_nuevo = $archivo;
            } else {
                $errores[] = 'Error al subir el avatar. Verifica permisos.';
            }
        }
    }

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE estudiantes SET padre_id=?,sede_id=?,nombre_completo=?,tipo_doc=?,numero_doc=?,
                fecha_nacimiento=?,genero=?,colegio=?,grado=?,eps=?,grupo_sanguineo=?,seguro_estudiantil=?,alergias=?,observaciones=?,avatar=?,activo=?
                WHERE id=?")
                ->execute([$padre_id,$sede_id,$nombre,$tipo_doc,$numero_doc,$fecha_nac,$genero,
                    $colegio,$grado,$eps,$grupo_sang,$seguro,$alergias,$observaciones,$avatar_nuevo,$activo,$id]);
        } else {
            $pdo->prepare("INSERT INTO estudiantes (padre_id,sede_id,nombre_completo,tipo_doc,numero_doc,
                fecha_nacimiento,genero,colegio,grado,eps,grupo_sanguineo,seguro_estudiantil,alergias,observaciones,avatar,activo)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$padre_id,$sede_id,$nombre,$tipo_doc,$numero_doc,$fecha_nac,$genero,
                    $colegio,$grado,$eps,$grupo_sang,$seguro,$alergias,$observaciones,$avatar_nuevo,$activo]);
        }
        header('Location: '.$U.'modulos/estudiantes/index.php?msg='.($estudiante?'editado':'creado'));
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $estudiante ? 'Editar estudiante' : 'Nuevo estudiante' ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/estudiantes/index.php">Estudiantes</a>
      <i class="bi bi-chevron-right"></i>
      <?= $estudiante ? h($estudiante['nombre_completo']) : 'Nuevo' ?>
    </span></small>
  </div>
</header>
<main class="main-content">
  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger" style="flex-direction:column;align-items:flex-start;">
      <strong><i class="bi bi-exclamation-circle-fill"></i> Corrige los errores:</strong>
      <ul style="margin:.4rem 0 0 1.2rem;"><?php foreach($errores as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.4rem;align-items:start;">

      <!-- IZQUIERDA -->
      <div>
        <!-- Datos personales -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-person-badge-fill"></i> Datos del estudiante</div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div style="grid-column:1/-1;">
              <label class="field-label">Nombre completo <span class="req">*</span></label>
              <input type="text" name="nombre_completo" class="rsal-input" required
                     placeholder="Nombre completo del estudiante"
                     value="<?= h($estudiante['nombre_completo'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Tipo de documento</label>
              <select name="tipo_doc" class="rsal-select">
                <?php foreach(['TI'=>'Tarjeta Identidad','RC'=>'Registro Civil','PP'=>'Pasaporte','CE'=>'C&eacute;dula Extranjer&iacute;a'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= ($estudiante['tipo_doc']??'TI')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="field-label">N&uacute;mero de documento</label>
              <input type="text" name="numero_doc" class="rsal-input"
                     placeholder="N&uacute;mero de documento"
                     value="<?= h($estudiante['numero_doc'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Fecha de nacimiento <span class="req">*</span></label>
              <input type="date" name="fecha_nacimiento" class="rsal-input" required
                     value="<?= h($estudiante['fecha_nacimiento'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">G&eacute;nero</label>
              <select name="genero" class="rsal-select">
                <?php foreach(['masculino'=>'Masculino','femenino'=>'Femenino','otro'=>'Otro','prefiero_no_decir'=>'Prefiero no decir'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= ($estudiante['genero']??'prefiero_no_decir')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Info acad&eacute;mica -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-building-fill"></i> Informaci&oacute;n acad&eacute;mica</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Colegio / Instituci&oacute;n</label>
              <input type="text" name="colegio" class="rsal-input"
                     placeholder="Nombre del colegio"
                     value="<?= h($estudiante['colegio'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Grado</label>
              <input type="text" name="grado" class="rsal-input"
                     placeholder="Ej: 4&deg;, 5&deg;, Preescolar"
                     value="<?= h($estudiante['grado'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <!-- Info m&eacute;dica -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-heart-pulse-fill"></i> Informaci&oacute;n m&eacute;dica</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">EPS</label>
              <input type="text" name="eps" class="rsal-input"
                     placeholder="Ej: Sura, Nueva EPS, Famisanar"
                     value="<?= h($estudiante['eps'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Grupo sangu&iacute;neo</label>
              <select name="grupo_sanguineo" class="rsal-select">
                <option value="">No especificado</option>
                <?php foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $gs): ?>
                  <option value="<?= $gs ?>" <?= ($estudiante['grupo_sanguineo']??'')===$gs?'selected':'' ?>><?= $gs ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="grid-column:1/-1;">
              <label class="field-label"><i class="bi bi-shield-check" style="color:var(--teal);"></i> Seguro estudiantil</label>
              <input type="text" name="seguro_estudiantil" class="rsal-input"
                     placeholder="Nombre de la aseguradora y/o N&deg; de p&oacute;liza"
                     value="<?= h($estudiante['seguro_estudiantil'] ?? '') ?>"/>
            </div>
            <div style="grid-column:1/-1;">
              <label class="field-label">Alergias / Condiciones especiales</label>
              <textarea name="alergias" class="rsal-textarea" style="min-height:70px;"
                        placeholder="Ej: Alergia al polvo, asma, diabetes..."><?= h($estudiante['alergias'] ?? '') ?></textarea>
            </div>
            <div style="grid-column:1/-1;">
              <label class="field-label">Observaciones</label>
              <textarea name="observaciones" class="rsal-textarea" style="min-height:70px;"
                        placeholder="Notas adicionales sobre el estudiante..."><?= h($estudiante['observaciones'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- DERECHA -->
      <div>
        <!-- Avatar -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-person-circle"></i> Foto / Avatar</div>
          <div style="text-align:center;margin-bottom:.9rem;">
            <?php if ($estudiante && $estudiante['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$estudiante['avatar'])): ?>
              <img src="<?= $U ?>uploads/estudiantes/<?= h($estudiante['avatar']) ?>" id="avatarPrev"
                   style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--teal-l);"/>
            <?php else: ?>
              <div id="avatarPrevPlaceholder" style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:1.5rem;font-weight:800;color:#fff;margin:0 auto;">
                <?= $estudiante ? strtoupper(substr($estudiante['nombre_completo'],0,2)) : '?' ?>
              </div>
              <img id="avatarPrev" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--teal-l);display:none;margin:0 auto;"/>
            <?php endif; ?>
          </div>
          <div style="border:2px dashed var(--border);border-radius:12px;padding:1rem;text-align:center;cursor:pointer;position:relative;transition:all .2s;"
               onmouseover="this.style.borderColor='var(--teal)';this.style.background='var(--teal-ll)';"
               onmouseout="this.style.borderColor='';this.style.background='';">
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                   style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                   onchange="prevAvatar(this)"/>
            <i class="bi bi-camera-fill" style="font-size:1.4rem;color:var(--muted);display:block;margin-bottom:.3rem;"></i>
            <p style="font-size:.75rem;color:var(--muted);margin:0;"><strong style="color:var(--teal);">Subir foto</strong><br>JPG, PNG &middot; m&aacute;x 3MB</p>
          </div>
        </div>

        <!-- Padre / sede -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-people-fill"></i> Familia y sede</div>

          <label class="field-label">Padre / Acudiente <span class="req">*</span></label>
          <select name="padre_id" class="rsal-select" required>
            <option value="">Selecciona el padre...</option>
            <?php foreach ($padres_list as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($estudiante['padre_id']??'')==$p['id']?'selected':'' ?>>
                <?= h($p['nombre_completo']) ?> &mdash; <?= h($p['telefono']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div style="font-size:.75rem;color:var(--muted);margin-top:-.5rem;margin-bottom:.9rem;">
            &iquest;No est&aacute;? <a href="<?= $U ?>modulos/padres/form.php" style="color:var(--teal);font-weight:700;" target="_blank">Registrar padre primero</a>
          </div>

          <?php if ($_SESSION['usuario_rol']==='admin_general'): ?>
            <label class="field-label">Sede <span class="req">*</span></label>
            <select name="sede_id" class="rsal-select" required>
              <option value="">Selecciona sede...</option>
              <?php foreach($sedes as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($estudiante['sede_id']??'')==$s['id']?'selected':'' ?>><?= h($s['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input type="hidden" name="sede_id" value="<?= $sede_filtro ?>"/>
          <?php endif; ?>

          <!-- Estado activo -->
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Estudiante activo</div>
              <div style="font-size:.72rem;color:var(--muted);">Puede inscribirse a cursos</div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer;">
              <input type="checkbox" name="activo" style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($estudiante['activo']??1)?'checked':'' ?>
                     onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
              <span style="position:absolute;inset:0;background:<?= ($estudiante['activo']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($estudiante['activo']??1)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <!-- Guardar -->
        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $estudiante?'Guardar cambios':'Registrar estudiante' ?>
          </button>
          <a href="<?= $U ?>modulos/estudiantes/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
        </div>
      </div>

    </div>
  </form>
</main>
<script>
function prevAvatar(input) {
  if (input.files && input.files[0]) {
    const r = new FileReader();
    r.onload = e => {
      const prev = document.getElementById('avatarPrev');
      const ph   = document.getElementById('avatarPrevPlaceholder');
      prev.src = e.target.result;
      prev.style.display = 'block';
      if (ph) ph.style.display = 'none';
    };
    r.readAsDataURL(input.files[0]);
  }
}
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
