<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Padre / Acudiente';
$menu_activo = 'padres';
$U           = BASE_URL;

$id     = (int)($_GET['id'] ?? 0);
$padre  = null;

if ($id) {
    $s = $pdo->prepare("SELECT p.*, u.email AS login_email, u.activo AS login_activo FROM padres p JOIN usuarios u ON u.id=p.usuario_id WHERE p.id=?");
    $s->execute([$id]); $padre = $s->fetch();
    if (!$padre) { header('Location: '.$U.'modulos/padres/index.php'); exit; }
}

$titulo  = $padre ? 'Editar: '.h($padre['nombre_completo']) : 'Nuevo padre / acudiente';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre_completo'] ?? '');
    $tipo_doc     = $_POST['tipo_doc']             ?? 'CC';
    $numero_doc   = trim($_POST['numero_doc']      ?? '');
    $telefono     = trim($_POST['telefono']         ?? '');
    $telefono_alt = trim($_POST['telefono_alt']     ?? '');
    $email        = trim($_POST['email']            ?? '');
    $direccion    = trim($_POST['direccion']        ?? '');
    $ocupacion    = trim($_POST['ocupacion']        ?? '');
    $acepta_datos = isset($_POST['acepta_datos'])    ? 1 : 0;
    $acepta_img   = isset($_POST['acepta_imagenes']) ? 1 : 0;
    $activo       = isset($_POST['activo'])          ? 1 : 0;
    $password     = trim($_POST['password']          ?? '');

    if (!$nombre)    $errores[] = 'El nombre es obligatorio.';
    if (!$telefono)  $errores[] = 'El tel&eacute;fono es obligatorio.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inv&aacute;lido.';
    if (!$id && !$password) $errores[] = 'La contrase&ntilde;a es obligatoria para nuevos padres.';
    if ($password && strlen($password) < 6) $errores[] = 'La contrase&ntilde;a debe tener al menos 6 caracteres.';

    // Verificar email &uacute;nico
    if (!$id) {
        $chk = $pdo->prepare("SELECT id FROM usuarios WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) $errores[] = 'Ya existe una cuenta con ese email.';
    }

    if (empty($errores)) {
        if ($id) {
            // Actualizar usuario
            $upd_u = "UPDATE usuarios SET email=?, activo=?";
            $params_u = [$email, $activo];
            if ($password) { $upd_u .= ", password_hash=?"; $params_u[] = password_hash($password, PASSWORD_BCRYPT); }
            $upd_u .= " WHERE id=?"; $params_u[] = $padre['usuario_id'];
            $pdo->prepare($upd_u)->execute($params_u);

            // Actualizar padre
            $pdo->prepare("UPDATE padres SET nombre_completo=?,tipo_doc=?,numero_doc=?,telefono=?,telefono_alt=?,
                email=?,direccion=?,ocupacion=?,acepta_datos=?,acepta_imagenes=? WHERE id=?")
                ->execute([$nombre,$tipo_doc,$numero_doc,$telefono,$telefono_alt,
                    $email,$direccion,$ocupacion,$acepta_datos,$acepta_img,$id]);
        } else {
            // Crear usuario login
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO usuarios (nombre,email,password_hash,rol,activo) VALUES (?,?,?,'padre',?)")
                ->execute([$nombre,$email,$hash,$activo]);
            $usuario_id = $pdo->lastInsertId();

            // Crear padre
            $pdo->prepare("INSERT INTO padres (usuario_id,nombre_completo,tipo_doc,numero_doc,telefono,telefono_alt,
                email,direccion,ocupacion,acepta_datos,acepta_imagenes,fecha_aceptacion,ip_aceptacion)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),?)")
                ->execute([$usuario_id,$nombre,$tipo_doc,$numero_doc,$telefono,$telefono_alt,
                    $email,$direccion,$ocupacion,$acepta_datos,$acepta_img,$_SERVER['REMOTE_ADDR']??'']);
        }
        header('Location: '.$U.'modulos/padres/index.php?msg='.($padre?'editado':'creado'));
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $padre ? 'Editar padre' : 'Nuevo padre / acudiente' ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/padres/index.php">Padres</a>
      <i class="bi bi-chevron-right"></i>
      <?= $padre ? h($padre['nombre_completo']) : 'Nuevo' ?>
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

  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.4rem;align-items:start;">

      <!-- IZQUIERDA -->
      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-person-fill"></i> Datos personales</div>

          <label class="field-label">Nombre completo <span class="req">*</span></label>
          <input type="text" name="nombre_completo" class="rsal-input" required
                 placeholder="Nombre completo del padre o acudiente"
                 value="<?= h($padre['nombre_completo'] ?? '') ?>"/>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Tipo de documento</label>
              <select name="tipo_doc" class="rsal-select">
                <?php foreach(['CC'=>'C&eacute;dula de Ciudadan&iacute;a','CE'=>'C&eacute;dula Extranjer&iacute;a','PP'=>'Pasaporte','NIT'=>'NIT','TI'=>'Tarjeta Identidad'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= ($padre['tipo_doc']??'CC')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="field-label">N&uacute;mero de documento</label>
              <input type="text" name="numero_doc" class="rsal-input"
                     placeholder="N&uacute;mero de documento"
                     value="<?= h($padre['numero_doc'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Tel&eacute;fono principal <span class="req">*</span></label>
              <input type="text" name="telefono" class="rsal-input" required
                     placeholder="Ej: 300 123 4567"
                     value="<?= h($padre['telefono'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Tel&eacute;fono alternativo</label>
              <input type="text" name="telefono_alt" class="rsal-input"
                     placeholder="Opcional"
                     value="<?= h($padre['telefono_alt'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Direcci&oacute;n</label>
              <input type="text" name="direccion" class="rsal-input"
                     placeholder="Direcci&oacute;n de residencia"
                     value="<?= h($padre['direccion'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Ocupaci&oacute;n</label>
              <input type="text" name="ocupacion" class="rsal-input"
                     placeholder="Ej: Docente, Ingeniero..."
                     value="<?= h($padre['ocupacion'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <!-- Pol&iacute;ticas -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-file-earmark-check-fill"></i> Autorizaci&oacute;n y pol&iacute;ticas</div>

          <!-- Pol&iacute;tica 1: Tratamiento datos -->
          <div style="border:1.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:.8rem;">
            <label style="display:flex;align-items:flex-start;gap:.8rem;padding:.9rem 1rem;background:var(--gray);cursor:pointer;">
              <input type="checkbox" name="acepta_datos"
                     style="margin-top:3px;flex-shrink:0;width:16px;height:16px;accent-color:var(--teal);"
                     <?= ($padre['acepta_datos']??0)?'checked':'' ?>/>
              <div style="flex:1;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
                  <strong style="font-size:.85rem;color:var(--dark);">Tratamiento de datos personales &mdash; Ley 1581/2012</strong>
                  <button type="button" onclick="toggleP('datos')"
                          style="background:none;border:none;color:var(--teal);font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                    <i class="bi bi-chevron-down" id="iconDatos"></i> Ver texto
                  </button>
                </div>
                <span style="font-size:.72rem;color:var(--muted);">Habeas Data &middot; Recolecci&oacute;n, almacenamiento y uso de datos</span>
              </div>
            </label>
            <div id="textoDatos" style="display:none;padding:1rem 1.2rem;background:#fff;border-top:1px solid var(--border);font-size:.76rem;color:var(--dark);line-height:1.7;">
              Autoriza a <strong>Escuelas STEAM Colombia SAS &mdash; ROBOTSchool</strong> para recolectar, almacenar, usar y circular sus datos personales y los de su hijo/hija, conforme a la Ley 1581 de 2012 y Decreto 1377 de 2013, con fines de gesti&oacute;n acad&eacute;mica, comunicaci&oacute;n institucional y cobro de servicios. Derechos: conocer, actualizar, rectificar y suprimir datos escribiendo a <strong>info@robotschool.com.co</strong>.
            </div>
          </div>

          <!-- Pol&iacute;tica 2: Im&aacute;genes -->
          <div style="border:1.5px solid var(--border);border-radius:12px;overflow:hidden;">
            <label style="display:flex;align-items:flex-start;gap:.8rem;padding:.9rem 1rem;background:var(--gray);cursor:pointer;">
              <input type="checkbox" name="acepta_imagenes"
                     style="margin-top:3px;flex-shrink:0;width:16px;height:16px;accent-color:var(--teal);"
                     <?= ($padre['acepta_imagenes']??0)?'checked':'' ?>/>
              <div style="flex:1;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
                  <strong style="font-size:.85rem;color:var(--dark);">Uso de im&aacute;genes y videos <span style="font-weight:400;color:var(--muted);font-size:.72rem;">(opcional)</span></strong>
                  <button type="button" onclick="toggleP('imagenes')"
                          style="background:none;border:none;color:var(--teal);font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                    <i class="bi bi-chevron-down" id="iconImagenes"></i> Ver texto
                  </button>
                </div>
                <span style="font-size:.72rem;color:var(--muted);">Solo uso institucional y publicitario de ROBOTSchool</span>
              </div>
            </label>
            <div id="textoImagenes" style="display:none;padding:1rem 1.2rem;background:#fff;border-top:1px solid var(--border);font-size:.76rem;color:var(--dark);line-height:1.7;">
              Autoriza a ROBOTSchool a capturar y publicar fotograf&iacute;as y videos del estudiante <strong>&uacute;nica y exclusivamente</strong> para: material publicitario institucional, redes sociales oficiales (Facebook, Instagram, YouTube), p&aacute;gina web y comunicaciones internas. <strong>Las im&aacute;genes NO ser&aacute;n cedidas a terceros ni usadas fuera del contexto ROBOTSchool.</strong> Esta autorizaci&oacute;n puede revocarse escribiendo a info@robotschool.com.co.
            </div>
          </div>
        </div>
      </div>

      <!-- DERECHA -->
      <div>
        <!-- Cuenta de acceso -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-lock-fill"></i> Cuenta de acceso</div>

          <label class="field-label">Correo electr&oacute;nico <span class="req">*</span></label>
          <input type="email" name="email" class="rsal-input" required
                 placeholder="correo@ejemplo.com"
                 value="<?= h($padre['login_email'] ?? $padre['email'] ?? '') ?>"/>

          <label class="field-label">
            Contrase&ntilde;a <?= $padre ? '' : '<span class="req">*</span>' ?>
          </label>
          <input type="password" name="password" class="rsal-input"
                 placeholder="<?= $padre ? 'Dejar vac&iacute;o para no cambiar' : 'M&iacute;nimo 6 caracteres' ?>"
                 autocomplete="new-password"/>
          <?php if ($padre): ?>
            <div style="font-size:.72rem;color:var(--muted);margin-top:-.5rem;margin-bottom:.9rem;">
              Deja vac&iacute;o para mantener la contrase&ntilde;a actual.
            </div>
          <?php endif; ?>

          <!-- Estado activo -->
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Cuenta activa</div>
              <div style="font-size:.72rem;color:var(--muted);">Puede acceder al portal de padres</div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer;">
              <input type="checkbox" name="activo" style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($padre['login_activo']??1)?'checked':'' ?>
                     onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
              <span style="position:absolute;inset:0;background:<?= ($padre['login_activo']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($padre['login_activo']??1)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <!-- Guardar -->
        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $padre?'Guardar cambios':'Registrar padre' ?>
          </button>
          <a href="<?= $U ?>modulos/padres/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
        </div>
      </div>

    </div>
  </form>
</main>
<script>
function toggleP(cual) {
  const t = document.getElementById('texto'+cual.charAt(0).toUpperCase()+cual.slice(1));
  const i = document.getElementById('icon' +cual.charAt(0).toUpperCase()+cual.slice(1));
  const abierto = t.style.display !== 'none';
  t.style.display  = abierto ? 'none' : 'block';
  i.className = abierto ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
}
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
