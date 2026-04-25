<?php
// login.php &mdash; ROBOTSchool Academy Learning
// Incluir config solo si existe (para preview funciona sin &eacute;l)
if (file_exists('config/config.php')) {
    require_once 'config/config.php';
    require_once 'config/auth.php';
    // Si ya est&aacute; logueado, redirigir
    if (isset($_SESSION['usuario_id'])) {
        $destino = $_SESSION['usuario_rol'] === 'padre' ? 'portal/index.php' : 'dashboard.php';
        header('Location: ' . $destino);
        exit;
    }
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $stmt  = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user  = $stmt->fetch();
        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['usuario_id']     = $user['id'];
            $_SESSION['usuario_rol']    = $user['rol'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['sede_id']        = $user['sede_id'];
            $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$user['id']]);
            // Redirigir seg&uacute;n rol
            if ($user['rol'] === 'padre') {
                header('Location: portal/index.php');
            } elseif ($user['rol'] === 'coordinador_pedagogico') {
                header('Location: modulos/academico/dashboard.php');
            } elseif ($user['rol'] === 'docente') {
                header('Location: docente/index.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Correo o contrase&ntilde;a incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Ingresar &mdash; ROBOTSchool Academy Learning</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<style>
:root {
  --teal:    #1DA99A;
  --teal-d:  #148a7d;
  --teal-l:  #e6f7f5;
  --red:     #E8192C;
  --red-d:   #c41424;
  --dark:    #1a2234;
  --dark2:   #243044;
  --gray:    #f4f6fb;
  --border:  #dde3ee;
  --muted:   #6b7a99;
  --white:   #ffffff;
  --shadow:  0 8px 40px rgba(29,169,154,.15);
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Nunito',sans-serif}

/* &#9472;&#9472; LAYOUT SPLIT &#9472;&#9472; */
.login-wrap {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
}
@media(max-width:768px){
  .login-wrap { grid-template-columns: 1fr; }
  .login-left  { display: none; }
}

/* &#9472;&#9472; LADO IZQUIERDO &mdash; visual brand &#9472;&#9472; */
.login-left {
  background: var(--dark);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 3rem 2.5rem;
}
/* patr&oacute;n de puntos sutil */
.login-left::before {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(29,169,154,.18) 1px, transparent 1px);
  background-size: 28px 28px;
}
/* acento teal esquina superior */
.login-left::after {
  content: '';
  position: absolute;
  top: -120px; right: -120px;
  width: 380px; height: 380px;
  background: radial-gradient(circle, rgba(29,169,154,.22) 0%, transparent 70%);
  border-radius: 50%;
}
/* acento rojo esquina inferior */
.corner-red {
  position: absolute;
  bottom: -80px; left: -80px;
  width: 260px; height: 260px;
  background: radial-gradient(circle, rgba(232,25,44,.14) 0%, transparent 70%);
  border-radius: 50%;
}
.brand-area {
  position: relative; z-index: 2;
  text-align: center;
}
.brand-area img.logo {
  width: 140px;
  margin-bottom: 1.5rem;
  filter: drop-shadow(0 8px 24px rgba(0,0,0,.4));
  animation: floatLogo 4s ease-in-out infinite;
}
@keyframes floatLogo {
  0%,100%{ transform: translateY(0); }
  50%    { transform: translateY(-10px); }
}
.brand-tagline {
  display: flex; gap: .5rem; justify-content: center;
  margin-bottom: 2rem;
}
.tag-pill {
  font-family: 'Poppins', sans-serif;
  font-size: .72rem; font-weight: 700;
  padding: .28rem .9rem; border-radius: 20px;
  letter-spacing: .04em;
}
.tag-pill.red    { background: var(--red);   color: #fff; }
.tag-pill.teal   { background: var(--teal);  color: #fff; }
.tag-pill.yellow { background: #FFCA28;      color: #1a2234; }
.brand-title {
  font-family: 'Poppins', sans-serif;
  font-size: 1.6rem; font-weight: 900;
  color: #fff; line-height: 1.25;
  margin-bottom: .75rem;
}
.brand-title span { color: var(--teal); }
.brand-sub {
  color: rgba(255,255,255,.6);
  font-size: .9rem; line-height: 1.7;
  max-width: 320px; margin: 0 auto 2rem;
}
/* stats row */
.stats-row {
  display: flex; gap: 1.5rem;
  justify-content: center;
  flex-wrap: wrap;
}
.stat-box {
  text-align: center;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 14px;
  padding: .75rem 1.2rem;
  min-width: 90px;
}
.stat-num {
  font-family: 'Poppins',sans-serif;
  font-size: 1.5rem; font-weight: 900;
  color: var(--teal);
  display: block;
}
.stat-label {
  font-size: .7rem; color: rgba(255,255,255,.55);
  text-transform: uppercase; letter-spacing: .06em;
}
/* sedes badges */
.sedes-row {
  display: flex; gap: .5rem; justify-content: center;
  flex-wrap: wrap; margin-top: 1.8rem;
}
.sede-badge {
  font-size: .72rem; font-weight: 700;
  background: rgba(29,169,154,.2);
  border: 1px solid rgba(29,169,154,.4);
  color: rgba(255,255,255,.8);
  padding: .25rem .8rem; border-radius: 20px;
  display: flex; align-items: center; gap: .35rem;
}
.sede-badge i { color: var(--teal); font-size: .65rem; }

/* &#9472;&#9472; LADO DERECHO &mdash; formulario &#9472;&#9472; */
.login-right {
  background: var(--gray);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 2.5rem 2rem;
}
.login-card {
  background: var(--white);
  border-radius: 24px;
  box-shadow: 0 8px 48px rgba(29,169,154,.12), 0 2px 12px rgba(0,0,0,.06);
  padding: 2.5rem 2.2rem;
  width: 100%; max-width: 420px;
}
/* logo m&oacute;vil (solo visible en mobile) */
.mobile-logo {
  display: none;
  text-align: center;
  margin-bottom: 1.5rem;
}
.mobile-logo img { width: 90px; }
@media(max-width:768px){ .mobile-logo{display:block} }

.login-card-header {
  text-align: center;
  margin-bottom: 2rem;
}
.login-card-header h1 {
  font-family: 'Poppins',sans-serif;
  font-size: 1.55rem; font-weight: 800;
  color: var(--dark); margin-bottom: .3rem;
}
.login-card-header h1 span { color: var(--teal); }
.login-card-header p {
  color: var(--muted); font-size: .88rem;
}

/* selector de rol */
.rol-selector {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .5rem;
  margin-bottom: 1.5rem;
}
.rol-btn {
  display: flex; flex-direction: column;
  align-items: center; gap: .3rem;
  padding: .7rem .5rem;
  border: 2px solid var(--border);
  border-radius: 14px;
  background: var(--white);
  cursor: pointer;
  transition: all .2s;
  font-size: .75rem; font-weight: 700;
  color: var(--muted);
}
.rol-btn i { font-size: 1.4rem; }
.rol-btn:hover { border-color: var(--teal); color: var(--teal); }
.rol-btn.active {
  border-color: var(--teal);
  background: var(--teal-l);
  color: var(--teal);
}

/* campos */
.field-wrap {
  margin-bottom: 1.1rem;
}
.field-wrap label {
  display: block;
  font-size: .8rem; font-weight: 700;
  color: var(--dark2); margin-bottom: .4rem;
  letter-spacing: .02em;
}
.input-group-rsal {
  position: relative;
}
.input-group-rsal i.icon-left {
  position: absolute; left: 14px; top: 50%;
  transform: translateY(-50%);
  color: var(--muted); font-size: 1rem;
  pointer-events: none;
}
.input-group-rsal input {
  width: 100%;
  padding: .72rem 1rem .72rem 2.6rem;
  border: 2px solid var(--border);
  border-radius: 12px;
  font-family: 'Nunito',sans-serif;
  font-size: .9rem; color: var(--dark);
  background: var(--gray);
  transition: all .2s; outline: none;
}
.input-group-rsal input:focus {
  border-color: var(--teal);
  background: #fff;
  box-shadow: 0 0 0 4px rgba(29,169,154,.12);
}
.input-group-rsal .toggle-pass {
  position: absolute; right: 12px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none;
  color: var(--muted); cursor: pointer;
  font-size: 1rem; padding: 0;
  transition: color .2s;
}
.input-group-rsal .toggle-pass:hover { color: var(--teal); }

/* error */
.alert-rsal {
  background: #fff0f1; border: 1px solid #ffc1c6;
  border-left: 4px solid var(--red);
  border-radius: 10px; padding: .7rem 1rem;
  font-size: .85rem; color: var(--red-d);
  display: flex; align-items: center; gap: .5rem;
  margin-bottom: 1.1rem;
}

/* bot&oacute;n */
.btn-login {
  width: 100%;
  padding: .82rem;
  background: var(--teal);
  color: #fff; border: none;
  border-radius: 14px;
  font-family: 'Poppins',sans-serif;
  font-size: .95rem; font-weight: 700;
  cursor: pointer; transition: all .25s;
  box-shadow: 0 6px 20px rgba(29,169,154,.35);
  display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.btn-login:hover {
  background: var(--teal-d);
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(29,169,154,.45);
}
.btn-login:active { transform: translateY(0); }

/* footer del card */
.card-footer-link {
  text-align: center; margin-top: 1.4rem;
  font-size: .82rem; color: var(--muted);
}
.card-footer-link a { color: var(--teal); font-weight: 700; text-decoration: none; }
.card-footer-link a:hover { text-decoration: underline; }

/* footer global */
.login-footer {
  text-align: center;
  margin-top: 1.8rem;
  font-size: .75rem; color: #aab2c8;
}
.login-footer strong { color: var(--teal); }

/* loading overlay */
.btn-login .spinner {
  width: 18px; height: 18px;
  border: 2px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
  display: none;
}
@keyframes spin { to { transform: rotate(360deg); } }
.btn-login.loading .spinner { display: block; }
.btn-login.loading .btn-text { display: none; }
</style>
</head>
<body>

<div class="login-wrap">

  <!-- &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552; IZQUIERDA &mdash; BRANDING &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552; -->
  <div class="login-left">
    <div class="corner-red"></div>
    <div class="brand-area">

      <img src="assets/img/RobotSchool.webp" alt="ROBOTSchool" class="logo"/>

      <div class="brand-tagline">
        <span class="tag-pill red">Construye</span>
        <span class="tag-pill teal">Juega</span>
        <span class="tag-pill yellow">Aprende</span>
      </div>

      <div class="brand-title">
        Academy <span>Learning</span><br>Platform
      </div>
      <p class="brand-sub">
        Gestiona cursos, matr&iacute;culas, hojas de vida y el seguimiento acad&eacute;mico de tus estudiantes desde un solo lugar.
      </p>

      <div class="stats-row">
        <div class="stat-box">
          <span class="stat-num">500+</span>
          <span class="stat-label">Estudiantes</span>
        </div>
        <div class="stat-box">
          <span class="stat-num">70+</span>
          <span class="stat-label">Laboratorios</span>
        </div>
        <div class="stat-box">
          <span class="stat-num">3</span>
          <span class="stat-label">Sedes</span>
        </div>
      </div>

      <div class="sedes-row">
        <span class="sede-badge"><i class="bi bi-geo-alt-fill"></i> Sede 75 San Felipe</span>
        <span class="sede-badge"><i class="bi bi-geo-alt-fill"></i> Sede Norte 136</span>
        <span class="sede-badge"><i class="bi bi-geo-alt-fill"></i> Sede Cali</span>
      </div>

    </div>
  </div>

  <!-- &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552; DERECHA &mdash; FORMULARIO &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552; -->
  <div class="login-right">
    
    

    <div class="login-card">

      <div class="login-card-header">
        <div class="brand-area">
          <img src="assets/img/logo R sin fondo azul.png" alt="ROBOTSchool" class="logo"/>
        </div>  
        <h1>Bienvenido a <span>RSAL</span></h1>
        <p>Ingresa con tu cuenta para continuar</p>
      </div>


    <!-- Logo visible solo en m&oacute;vil -->
    
      <!-- Selector visual de rol 
      <div class="rol-selector" id="rolSelector">
        <button type="button" class="rol-btn active" data-rol="admin" onclick="setRol(this)">
          <i class="bi bi-shield-lock-fill"></i>
          Administrador
        </button>
        <button type="button" class="rol-btn" data-rol="padre" onclick="setRol(this)">
          <i class="bi bi-people-fill"></i>
          Padre / Acudiente
        </button>
      </div>-->

      <!-- Error -->
      <?php if (!empty($error)): ?>
      <div class="alert-rsal">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= $error ?>
      </div>
      <?php endif; ?>

      <!-- Formulario -->
      <form method="POST" id="loginForm" onsubmit="handleSubmit(event)">
        <input type="hidden" name="rol_tipo" id="rolInput" value="admin"/>

        <div class="field-wrap">
          <label for="email">Correo electr&oacute;nico</label>
          <div class="input-group-rsal">
            <i class="bi bi-envelope-fill icon-left"></i>
            <input type="email" id="email" name="email"
                   placeholder="tucorreo@robotschool.com.co"
                   required autocomplete="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
          </div>
        </div>

        <div class="field-wrap">
          <label for="password">Contrase&ntilde;a</label>
          <div class="input-group-rsal">
            <i class="bi bi-lock-fill icon-left"></i>
            <input type="password" id="password" name="password"
                   placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
                   required autocomplete="current-password"/>
            <button type="button" class="toggle-pass" onclick="togglePass()">
              <i class="bi bi-eye-fill" id="passIcon"></i>
            </button>
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-bottom:1.3rem;">
          <a href="recuperar.php" style="font-size:.8rem;color:var(--teal);font-weight:700;text-decoration:none;">
            &iquest;Olvidaste tu contrase&ntilde;a?
          </a>
        </div>

        <button type="submit" class="btn-login" id="btnLogin">
          <span class="btn-text"><i class="bi bi-box-arrow-in-right"></i> Ingresar</span>
          <span class="spinner"></span>
        </button>
      </form>

      <div class="card-footer-link">
        &iquest;Eres padre de familia y no tienes cuenta?
        <a href="registro.php">Reg&iacute;strate aqu&iacute;</a>
      </div>

    </div><!-- /login-card -->

    <div class="login-footer">
      &copy; <?= date('Y') ?> <strong>ROBOTSchool Academy Learning</strong> &mdash;
      Bogot&aacute; &amp; Cali, Colombia
    </div>

  </div><!-- /login-right -->

</div><!-- /login-wrap -->

<script>
function setRol(btn) {
  document.querySelectorAll('.rol-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('rolInput').value = btn.dataset.rol;
  // Cambiar placeholder seg&uacute;n rol
  const email = document.getElementById('email');
  if (btn.dataset.rol === 'padre') {
    email.placeholder = 'micorreo@gmail.com';
  } else {
    email.placeholder = 'tucorreo@robotschool.com.co';
  }
}

function togglePass() {
  const inp  = document.getElementById('password');
  const icon = document.getElementById('passIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'bi bi-eye-slash-fill';
  } else {
    inp.type = 'password';
    icon.className = 'bi bi-eye-fill';
  }
}

function handleSubmit(e) {
  const btn = document.getElementById('btnLogin');
  btn.classList.add('loading');
  btn.disabled = true;
  // Si no hay backend, revertir despu&eacute;s de 2s
  setTimeout(() => {
    if (!btn.form.action || btn.form.action === window.location.href) {
      btn.classList.remove('loading');
      btn.disabled = false;
    }
  }, 2000);
}
</script>
</body>
</html>
