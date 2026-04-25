<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Usuario';
$menu_activo = 'usuarios';
$U           = BASE_URL;
$sedes       = $pdo->query("SELECT * FROM sedes WHERE activa=1 ORDER BY nombre")->fetchAll();
$rol_actual  = $_SESSION['usuario_rol'];

$id      = (int)($_GET['id'] ?? 0);
$usuario = null;
if ($id) {
    $s = $pdo->prepare("SELECT * FROM usuarios WHERE id=?");
    $s->execute([$id]); $usuario = $s->fetch();
    if (!$usuario) { header('Location:'.$U.'modulos/usuarios/index.php'); exit; }
}

$titulo  = $usuario ? 'Editar: '.h($usuario['nombre']) : 'Nuevo usuario';
$errores = [];

// Roles disponibles segun quien crea
// admin_general puede crear cualquiera excepto padre
// admin_sede puede crear coordinador y docente (de su sede)
$roles_disponibles = [];
if ($rol_actual === 'admin_general') {
    $roles_disponibles = [
        'admin_general'          => '&#127760; Admin General',
        'admin_sede'             => '&#127&laquo;&#65039; Admin Sede',
        'coordinador_pedagogico' => '&#128218; Coordinador Pedag&oacute;gico',
        'docente'                => '&#9997;&#65039; Docente / Tallerista',
    ];
} else {
    // admin_sede solo crea coordinador y docente
    $roles_disponibles = [
        'coordinador_pedagogico' => '&#128218; Coordinador Pedag&oacute;gico',
        'docente'                => '&#9997;&#65039; Docente / Tallerista',
    ];
}

// Roles que NO necesitan sede propia (la heredan de la sesion del admin)
$roles_sin_sede = ['admin_general'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $rol      = $_POST['rol']           ?? 'docente';
    $sede_id  = in_array($rol, $roles_sin_sede) ? null : ((int)($_POST['sede_id'] ?? 0) ?: null);
    $password = trim($_POST['password'] ?? '');
    $activo   = isset($_POST['activo']) ? 1 : 0;

    // Si admin_sede, forzar su propia sede
    if ($rol_actual === 'admin_sede') {
        $sede_id = $_SESSION['sede_id'] ?? null;
    }

    // Validar rol permitido
    if (!array_key_exists($rol, $roles_disponibles)) $errores[] = 'Rol no permitido.';
    if (!$nombre) $errores[] = 'El nombre es obligatorio.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inv&aacute;lido.';
    if (!$id && !$password) $errores[] = 'La contrase&ntilde;a es obligatoria para usuarios nuevos.';
    if ($password && strlen($password) < 6) $errores[] = 'Contrase&ntilde;a m&iacute;nimo 6 caracteres.';

    $chk = $pdo->prepare("SELECT id FROM usuarios WHERE email=? AND id!=?");
    $chk->execute([$email, $id ?: 0]);
    if ($chk->fetch()) $errores[] = 'Ya existe un usuario con ese email.';

    if (empty($errores)) {
        if ($id) {
            $upd = "UPDATE usuarios SET nombre=?,email=?,rol=?,sede_id=?,activo=?";
            $params = [$nombre,$email,$rol,$sede_id,$activo];
            if ($password) { $upd .= ",password_hash=?"; $params[] = password_hash($password, PASSWORD_BCRYPT); }
            $upd .= " WHERE id=?"; $params[] = $id;
            $pdo->prepare($upd)->execute($params);
        } else {
            $pdo->prepare("INSERT INTO usuarios (nombre,email,password_hash,rol,sede_id,activo) VALUES (?,?,?,?,?,?)")
                ->execute([$nombre,$email,password_hash($password,PASSWORD_BCRYPT),$rol,$sede_id,$activo]);
        }
        header('Location:'.$U.'modulos/usuarios/index.php?msg='.($usuario?'editado':'creado'));
        exit;
    }
}

require_once ROOT . '/includes/head.php';
?>
<body>
<?php require_once ROOT . '/includes/sidebar.php'; ?>
<main class="main-content">
<header class="main-header">
  <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    <h1><?= $titulo ?></h1>
    <div class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/usuarios/index.php">Usuarios</a>
      <i class="bi bi-chevron-right"></i>
      <?= $usuario ? h($usuario['nombre']) : 'Nuevo' ?>
    </div>
  </div>
</header>

  <?php if (!empty($errores)): ?>
  <div style="background:#fff0f1;border:1.5px solid #fca5a5;border-radius:12px;padding:1rem 1.4rem;margin-bottom:1.5rem;max-width:560px;">
    <strong style="color:#991b1b;"><i class="bi bi-exclamation-circle-fill"></i> Errores:</strong>
    <ul style="margin:.4rem 0 0 1.2rem;"><?php foreach($errores as $e): ?><li style="color:#991b1b;font-size:.87rem;"><?= h($e) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <div style="max-width:560px;">
    <form method="POST">
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:1.6rem;display:flex;flex-direction:column;gap:1rem;">

        <div style="font-size:1rem;font-weight:700;color:var(--dark);display:flex;align-items:center;gap:.5rem;margin-bottom:.2rem;">
          <i class="bi bi-person-gear-fill" style="color:var(--teal);"></i> Datos del usuario
        </div>

        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Nombre completo <span style="color:red;">*</span></label>
          <input type="text" name="nombre" required placeholder="Nombre completo"
                 value="<?= h($usuario['nombre'] ?? '') ?>"
                 style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;box-sizing:border-box;">
        </div>

        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Email <span style="color:red;">*</span></label>
          <input type="email" name="email" required placeholder="correo@robotschool.com.co"
                 value="<?= h($usuario['email'] ?? '') ?>"
                 style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;box-sizing:border-box;">
        </div>

        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">
            Contrase&ntilde;a <?= $usuario ? '' : '<span style="color:red;">*</span>' ?>
          </label>
          <input type="password" name="password" autocomplete="new-password"
                 placeholder="<?= $usuario ? 'Dejar vac&iacute;o para no cambiar' : 'M&iacute;nimo 6 caracteres' ?>"
                 style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;box-sizing:border-box;">
        </div>

        <!-- Roles -->
        <div>
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.5rem;">Rol <span style="color:red;">*</span></label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;" id="rolWrap">
            <?php foreach ($roles_disponibles as $val => $label):
              $checked = ($usuario['rol'] ?? array_key_first($roles_disponibles)) === $val ? 'checked' : '';
            ?>
            <label style="display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;font-size:.82rem;font-weight:600;transition:.15s;"
                   id="lbl_<?= $val ?>"
                   onclick="selectRol('<?= $val ?>')">
              <input type="radio" name="rol" value="<?= $val ?>" <?= $checked ?>
                     onchange="selectRol('<?= $val ?>')"
                     style="flex-shrink:0;accent-color:var(--teal);">
              <?= $label ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Sede (oculta para admin_general) -->
        <?php if ($rol_actual === 'admin_general'): ?>
        <div id="sedeWrap">
          <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.3rem;">Sede asignada</label>
          <select name="sede_id"
                  style="width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;">
            <option value="">&mdash; Todas las sedes &mdash;</option>
            <?php foreach($sedes as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($usuario['sede_id']??'')==$s['id']?'selected':'' ?>>
              <?= h($s['nombre']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php else: ?>
        <input type="hidden" name="sede_id" value="<?= (int)$_SESSION['sede_id'] ?>">
        <?php endif; ?>

        <!-- Toggle activo -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;">
          <span style="font-size:.85rem;font-weight:700;">Cuenta activa</span>
          <label style="position:relative;width:44px;height:24px;cursor:pointer;">
            <input type="checkbox" name="activo" id="togActivo" style="opacity:0;width:0;height:0;position:absolute;"
                   <?= ($usuario['activo']??1)?'checked':'' ?>
                   onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'#ccc';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
            <span id="togSpan" style="position:absolute;inset:0;background:<?= ($usuario['activo']??1)?'var(--teal)':'#ccc' ?>;border-radius:12px;transition:.3s;">
              <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($usuario['activo']??1)?'translateX(20px)':'' ?>;"></span>
            </span>
          </label>
        </div>

        <button type="submit" style="width:100%;padding:.85rem;background:var(--teal);color:#fff;border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;">
          <i class="bi bi-check-lg"></i> <?= $usuario ? 'Guardar cambios' : 'Crear usuario' ?>
        </button>
        <a href="<?= $U ?>modulos/usuarios/index.php"
           style="display:flex;align-items:center;justify-content:center;padding:.68rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.87rem;font-weight:600;color:var(--dark);text-decoration:none;">
          Cancelar
        </a>

      </div>
    </form>
  </div>
</main>

<script>
const rolesConSede = ['admin_sede','coordinador_pedagogico','docente'];

function selectRol(val) {
  document.querySelectorAll('#rolWrap label').forEach(l => {
    l.style.borderColor = 'var(--border)';
    l.style.background  = '#fff';
  });
  const lbl = document.getElementById('lbl_' + val);
  if (lbl) {
    lbl.style.borderColor = 'var(--teal)';
    lbl.style.background  = 'rgba(0,156,204,.07)';
  }
  const sw = document.getElementById('sedeWrap');
  if (sw) sw.style.display = rolesConSede.includes(val) ? 'block' : 'none';
}

// Inicializar selecci&oacute;n visual
const checkedRol = document.querySelector('input[name="rol"]:checked');
if (checkedRol) selectRol(checkedRol.value);

document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
