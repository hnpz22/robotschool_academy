<?php
require_once __DIR__ . '/../config/config.php';

// Si ya est&aacute; logueado como padre, ir al portal
if (!empty($_SESSION['usuario_id']) && $_SESSION['usuario_rol'] === 'padre') {
    header('Location: ' . BASE_URL . 'portal/index.php'); exit;
}

$U      = BASE_URL;
$error  = '';
$curso  = (int)($_GET['curso'] ?? 0); // para redirigir tras login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']  ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email=? AND rol='padre' AND activo=1");
    $stmt->execute([$email]); $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['usuario_id']     = $user['id'];
        $_SESSION['usuario_rol']    = 'padre';
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $pdo->prepare("UPDATE usuarios SET ultimo_login=NOW() WHERE id=?")->execute([$user['id']]);
        header('Location: ' . $U . 'portal/index.php');
        exit;
    } else {
        $error = 'Correo o contrase&ntilde;a incorrectos. &iquest;A&uacute;n no tienes cuenta?';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Acceso padres &mdash; ROBOTSchool Academy</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<style>
:root{--orange:#F26522;--orange-d:#d4541a;--dark:#0f1623;--teal:#1DA99A;--teal-l:#e6f7f5;--red:#E8192C;--border:#dde3ee;--muted:#6b7a99;--gray:#f4f6fb;}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito',sans-serif;min-height:100vh;display:flex;flex-direction:column;background:var(--gray);}
.login-wrap{flex:1;display:grid;grid-template-columns:1fr 1fr;}
@media(max-width:768px){.login-wrap{grid-template-columns:1fr}.login-left{display:none}}
.login-left{background:var(--dark);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:3rem 2rem;position:relative;overflow:hidden;}
.login-left::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(29,169,154,.15) 1px,transparent 1px);background-size:28px 28px;}
.login-left::after{content:'';position:absolute;top:-100px;right:-100px;width:350px;height:350px;background:radial-gradient(circle,rgba(242,101,34,.15) 0%,transparent 70%);border-radius:50%;}
.brand-area{position:relative;z-index:2;text-align:center;}
.brand-area img{width:130px;margin-bottom:1.5rem;filter:drop-shadow(0 8px 24px rgba(0,0,0,.4));animation:float 4s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.brand-title{font-family:'Poppins',sans-serif;font-size:1.5rem;font-weight:900;color:#fff;line-height:1.25;margin-bottom:.5rem;}
.brand-title span{color:var(--teal);}
.brand-sub{color:rgba(255,255,255,.55);font-size:.88rem;max-width:300px;margin:0 auto 1.8rem;}
.feature-list{list-style:none;padding:0;text-align:left;}
.feature-list li{display:flex;align-items:center;gap:.6rem;color:rgba(255,255,255,.7);font-size:.82rem;margin-bottom:.6rem;}
.feature-list li i{color:var(--teal);font-size:.9rem;flex-shrink:0;}
.login-right{display:flex;flex-direction:column;justify-content:center;align-items:center;padding:2rem;}
.login-card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.1);padding:2.2rem;width:100%;max-width:400px;}
.login-card h1{font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:800;color:#1a2234;margin-bottom:.3rem;}
.login-card h1 span{color:var(--teal);}
.login-card p{font-size:.85rem;color:var(--muted);margin-bottom:1.5rem;}
.field-label{display:block;font-size:.78rem;font-weight:700;color:#243044;margin-bottom:.4rem;}
.rsal-input{width:100%;padding:.7rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.9rem;outline:none;transition:all .2s;margin-bottom:.9rem;}
.rsal-input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(29,169,154,.1);}
.btn-login{width:100%;padding:.8rem;background:var(--teal);color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;transition:all .25s;box-shadow:0 5px 16px rgba(29,169,154,.35);}
.btn-login:hover{background:#148a7d;transform:translateY(-1px);}
.alert-err{background:#fff0f1;border:1px solid #ffc1c6;border-left:3px solid var(--red);border-radius:8px;padding:.65rem .9rem;font-size:.82rem;color:#b91c1c;margin-bottom:1rem;}
.divider{text-align:center;font-size:.78rem;color:var(--muted);margin:1rem 0;position:relative;}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:40%;height:1px;background:var(--border);}
.divider::before{left:0}.divider::after{right:0}
.btn-register{width:100%;padding:.72rem;background:#fff;color:var(--orange);border:1.5px solid var(--orange);border-radius:12px;font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .2s;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:.4rem;}
.btn-register:hover{background:var(--orange);color:#fff;}
</style>
</head>
<body>
<div class="login-wrap">
  <!-- IZQUIERDA -->
  <div class="login-left">
    <div class="brand-area">
      <img src="<?= $U ?>assets/img/logo_oficial.png" alt="ROBOTSchool"/>
      <div class="brand-title">Portal de <span>Padres</span></div>
      <p class="brand-sub">Sigue el proceso acad&eacute;mico de tu hijo/hija desde un solo lugar.</p>
      <ul class="feature-list">
        <li><i class="bi bi-journal-richtext"></i> Cursos e inscripciones</li>
        <li><i class="bi bi-calendar3"></i> Horarios y fechas</li>
        <li><i class="bi bi-cash-stack"></i> Estado de pagos</li>
        <li><i class="bi bi-star-fill"></i> Informes y r&uacute;bricas</li>
        <li><i class="bi bi-person-badge-fill"></i> Perfil del estudiante</li>
      </ul>
    </div>
  </div>
  <!-- DERECHA -->
  <div class="login-right">
    <div class="login-card">
      <h1>Acceso <span>Portal</span></h1>
      <p>Ingresa con tu correo y contrase&ntilde;a de padre/acudiente</p>

      <?php if ($error): ?>
        <div class="alert-err">
          <i class="bi bi-exclamation-circle-fill"></i> <?= $error ?>
          <a href="<?= $U ?>public/registro.php" style="color:var(--orange);font-weight:700;"> Reg&iacute;strate aqu&iacute;</a>
        </div>
      <?php endif; ?>

      <form method="POST">
        <label class="field-label">Correo electr&oacute;nico</label>
        <input type="email" name="email" class="rsal-input" required
               placeholder="tucorreo@gmail.com"
               value="<?= h($_POST['email'] ?? '') ?>"/>

        <label class="field-label">Contrase&ntilde;a</label>
        <input type="password" name="password" class="rsal-input" required
               placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"/>

        <div style="text-align:right;margin-top:-.5rem;margin-bottom:1rem;">
          <a href="<?= $U ?>public/registro.php" style="font-size:.78rem;color:var(--teal);font-weight:700;text-decoration:none;">&iquest;Olvidaste tu contrase&ntilde;a?</a>
        </div>

        <button type="submit" class="btn-login">
          <i class="bi bi-box-arrow-in-right"></i> Ingresar al portal
        </button>
      </form>

      <div class="divider">&iquest;A&uacute;n no tienes cuenta?</div>

      <a href="<?= $U ?>public/registro.php<?= $curso?'?curso='.$curso:'' ?>" class="btn-register">
        <i class="bi bi-person-plus-fill"></i> Registrarme e inscribir a mi hijo/hija
      </a>

      <div style="text-align:center;margin-top:1.2rem;font-size:.75rem;color:var(--muted);">
        <a href="<?= $U ?>public/index.php" style="color:var(--muted);text-decoration:none;">
          <i class="bi bi-arrow-left"></i> Volver al sitio web
        </a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
