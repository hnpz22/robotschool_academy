<?php
// setup_admin.php &mdash; ROBOTSchool Academy Learning
// &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;
// Ejecuta este archivo UNA SOLA VEZ despu&eacute;s de importar install.sql
// Luego ELIM&Iacute;NALO del servidor por seguridad.
// URL: http://localhost/robotschool_academy/setup_admin.php
// &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552;

require_once 'config/config.php';

// &#9472;&#9472; PROTECCI&Oacute;N: solo funciona si los hashes son 'PENDIENTE' &#9472;&#9472;
$check = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE password_hash = 'PENDIENTE'")->fetchColumn();
if ($check == 0) {
    die('
    <div style="font-family:sans-serif;max-width:500px;margin:4rem auto;padding:2rem;
        background:#fff0f1;border:2px solid #E8192C;border-radius:12px;">
        <h2 style="color:#E8192C;">&#9888; Setup ya ejecutado</h2>
        <p>Las contrase&ntilde;as ya fueron configuradas. Este archivo debe eliminarse del servidor.</p>
        <a href="login.php" style="color:#1DA99A;font-weight:bold;">Ir al login &rarr;</a>
    </div>');
}

$errores = [];
$exito   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pass_admin  = trim($_POST['pass_admin']  ?? '');
    $pass_sedes  = trim($_POST['pass_sedes']  ?? '');

    // Validaciones
    if (strlen($pass_admin) < 8) {
        $errores[] = 'La contrase&ntilde;a del admin general debe tener al menos 8 caracteres.';
    }
    if (strlen($pass_sedes) < 8) {
        $errores[] = 'La contrase&ntilde;a de admins de sede debe tener al menos 8 caracteres.';
    }

    if (empty($errores)) {
        $hash_admin = password_hash($pass_admin, PASSWORD_BCRYPT);
        $hash_sedes = password_hash($pass_sedes, PASSWORD_BCRYPT);

        // Admin general
        $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE rol = 'admin_general'")
            ->execute([$hash_admin]);

        // Admins de sede
        $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE rol = 'admin_sede'")
            ->execute([$hash_sedes]);

        $exito = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Setup inicial &mdash; ROBOTSchool Academy Learning</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Nunito:wght@400;700&display=swap" rel="stylesheet"/>
<style>
:root {
  --teal: #1DA99A; --teal-d: #148a7d; --teal-l: #e6f7f5;
  --red:  #E8192C; --dark: #1a2234;
}
body { font-family:'Nunito',sans-serif; background:#f4f6fb; min-height:100vh;
       display:flex; align-items:center; justify-content:center; padding:1rem; }
.setup-card {
  background:#fff; border-radius:20px; padding:2.5rem 2.2rem;
  width:100%; max-width:480px;
  box-shadow:0 8px 40px rgba(29,169,154,.15);
}
.setup-card h1 {
  font-family:'Poppins',sans-serif; font-size:1.4rem; font-weight:800;
  color:var(--dark); margin-bottom:.2rem;
}
.setup-card h1 span { color:var(--teal); }
.warning-box {
  background:#fff8e1; border:1px solid #ffe082; border-left:4px solid #F59E0B;
  border-radius:10px; padding:.8rem 1rem; font-size:.82rem; color:#7c5c00;
  margin:1.2rem 0;
}
.field-label {
  font-size:.8rem; font-weight:700; color:var(--dark); margin-bottom:.4rem; display:block;
}
.field-sub { font-size:.72rem; color:#6b7a99; margin-bottom:.5rem; display:block; }
.rsal-input {
  width:100%; padding:.72rem 1rem; border:2px solid #dde3ee;
  border-radius:12px; font-family:'Nunito',sans-serif; font-size:.9rem;
  outline:none; transition:all .2s; margin-bottom:1rem;
}
.rsal-input:focus { border-color:var(--teal); box-shadow:0 0 0 4px rgba(29,169,154,.12); }
.btn-setup {
  width:100%; padding:.82rem; background:var(--teal); color:#fff;
  border:none; border-radius:14px; font-family:'Poppins',sans-serif;
  font-size:.95rem; font-weight:700; cursor:pointer; transition:all .25s;
  box-shadow:0 6px 20px rgba(29,169,154,.35);
}
.btn-setup:hover { background:var(--teal-d); transform:translateY(-2px); }
.alert-err {
  background:#fff0f1; border:1px solid #ffc1c6; border-left:4px solid var(--red);
  border-radius:10px; padding:.7rem 1rem; font-size:.84rem; color:#a00; margin-bottom:1rem;
}
.success-box {
  background:var(--teal-l); border:1px solid rgba(29,169,154,.4);
  border-radius:14px; padding:1.5rem; text-align:center;
}
.success-box h2 { font-family:'Poppins',sans-serif; color:var(--teal); font-size:1.2rem; margin-bottom:.5rem; }
.success-box p { font-size:.85rem; color:#0f6e56; margin-bottom:1rem; }
.users-table { width:100%; border-collapse:collapse; font-size:.8rem; margin:1rem 0; text-align:left; }
.users-table th { background:#e6f7f5; padding:.5rem .8rem; color:#0f6e56; font-weight:800; }
.users-table td { padding:.5rem .8rem; border-bottom:1px solid #e6f7f5; }
.btn-ir {
  display:inline-block; background:var(--teal); color:#fff;
  padding:.6rem 1.5rem; border-radius:12px; font-weight:700;
  text-decoration:none; font-size:.9rem;
}
.delete-warning {
  background:#fff0f1; border:1px solid #ffc1c6;
  border-radius:10px; padding:.7rem 1rem; font-size:.78rem;
  color:var(--red); margin-top:1rem; font-weight:700;
}
.delete-warning i { margin-right:.3rem; }
</style>
</head>
<body>

<div class="setup-card">

  <h1>Setup <span>RSAL</span></h1>
  <p style="font-size:.85rem;color:#6b7a99;margin-bottom:0;">ROBOTSchool Academy Learning &mdash; Configuraci&oacute;n inicial</p>

  <?php if ($exito): ?>

    <!-- &#9989; &Eacute;XITO -->
    <div class="success-box" style="margin-top:1.5rem;">
      <h2><i class="bi bi-check-circle-fill"></i> &iexcl;Listo!</h2>
      <p>Las contrase&ntilde;as fueron configuradas correctamente.</p>
      <table class="users-table">
        <thead><tr><th>Correo</th><th>Rol</th><th>Contrase&ntilde;a</th></tr></thead>
        <tbody>
          <tr><td>admin@robotschool.com.co</td><td>Admin general</td><td><code><?= htmlspecialchars($_POST['pass_admin']) ?></code></td></tr>
          <tr><td>sede75@robotschool.com.co</td><td>Admin sede</td><td><code><?= htmlspecialchars($_POST['pass_sedes']) ?></code></td></tr>
          <tr><td>sedenorte@robotschool.com.co</td><td>Admin sede</td><td><code><?= htmlspecialchars($_POST['pass_sedes']) ?></code></td></tr>
          <tr><td>sedecali@robotschool.com.co</td><td>Admin sede</td><td><code><?= htmlspecialchars($_POST['pass_sedes']) ?></code></td></tr>
        </tbody>
      </table>
      <a href="login.php" class="btn-ir"><i class="bi bi-box-arrow-in-right"></i> Ir al login</a>
    </div>

    <div class="delete-warning">
      <i class="bi bi-trash-fill"></i>
      Elimina este archivo del servidor ahora:<br>
      <code>robotschool_academy/setup_admin.php</code>
    </div>

  <?php else: ?>

    <div class="warning-box">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <strong>Solo para uso inicial.</strong> Ejecuta este archivo una sola vez
      y elim&iacute;nalo del servidor despu&eacute;s.
    </div>

    <?php if (!empty($errores)): ?>
      <div class="alert-err">
        <?php foreach ($errores as $e): ?>
          <div><i class="bi bi-x-circle-fill"></i> <?= h($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST">

      <label class="field-label" for="pass_admin">
        Contrase&ntilde;a &mdash; Admin General
      </label>
      <span class="field-sub">
        Para: admin@robotschool.com.co
      </span>
      <input type="password" class="rsal-input" id="pass_admin" name="pass_admin"
             placeholder="M&iacute;nimo 8 caracteres" required minlength="8"/>

      <label class="field-label" for="pass_sedes">
        Contrase&ntilde;a &mdash; Admins de Sede
      </label>
      <span class="field-sub">
        Para: sede75, sedenorte, sedecali @robotschool.com.co
        (pueden cambiarse individualmente despu&eacute;s)
      </span>
      <input type="password" class="rsal-input" id="pass_sedes" name="pass_sedes"
             placeholder="M&iacute;nimo 8 caracteres" required minlength="8"/>

      <button type="submit" class="btn-setup">
        <i class="bi bi-shield-lock-fill"></i> Generar contrase&ntilde;as y activar accesos
      </button>

    </form>

  <?php endif; ?>

</div>

</body>
</html>
