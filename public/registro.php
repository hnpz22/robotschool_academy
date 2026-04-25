<?php
// public/registro.php &mdash; Registro p&uacute;blico de padre e hijo
require_once __DIR__ . '/../config/config.php';

$titulo    = 'Registro';
$U         = BASE_URL;
$curso_id  = (int)($_GET['curso'] ?? 0);
$lista     = isset($_GET['lista']); // lista de espera

// Cursos publicados para el select
$cursos_pub = $pdo->query("
    SELECT id, nombre FROM cursos WHERE publicado=1 ORDER BY nombre
")->fetchAll();

// Grupos del curso seleccionado
$grupos_curso = [];
if ($curso_id) {
    $gq = $pdo->prepare("
        SELECT g.*,
            (g.cupo_real - COALESCE((SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id=g.id AND m.estado='activa'),0)) AS disponibles
        FROM grupos g WHERE g.curso_id=? AND g.activo=1
        ORDER BY FIELD(g.dia_semana,'lunes','martes','miercoles','jueves','viernes','sabado','domingo'), g.hora_inicio
    ");
    $gq->execute([$curso_id]); $grupos_curso = $gq->fetchAll();
}

$dias = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles',
         'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];

$errores = [];
$exito   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos padre
    $p_nombre     = trim($_POST['p_nombre']      ?? '');
    $p_tipo_doc   = $_POST['p_tipo_doc']          ?? 'CC';
    $p_doc        = trim($_POST['p_doc']          ?? '');
    $p_tel        = trim($_POST['p_tel']          ?? '');
    $p_email      = trim($_POST['p_email']        ?? '');
    $p_pass       = trim($_POST['p_pass']         ?? '');
    $p_pass2      = trim($_POST['p_pass2']        ?? '');
    $p_datos      = isset($_POST['acepta_datos'])    ? 1 : 0;
    $p_img        = isset($_POST['acepta_imagenes']) ? 1 : 0;

    // Datos estudiante
    $e_nombre     = trim($_POST['e_nombre']       ?? '');
    $e_tipo_doc   = $_POST['e_tipo_doc']           ?? 'TI';
    $e_doc        = trim($_POST['e_doc']           ?? '');
    $e_nac        = $_POST['e_nac']                ?? '';
    $e_genero     = $_POST['e_genero']             ?? 'prefiero_no_decir';
    $e_colegio    = trim($_POST['e_colegio']       ?? '');
    $e_grado      = trim($_POST['e_grado']         ?? '');
    $e_eps        = trim($_POST['e_eps']           ?? '');
    $e_alergias   = trim($_POST['e_alergias']      ?? '');
    $e_sede_id    = (int)($_POST['e_sede_id']      ?? 0);

    // Inscripci&oacute;n
    $grupo_id     = (int)($_POST['grupo_id']       ?? 0);
    $periodo      = trim($_POST['periodo']          ?? date('Y').'-1');

    // Validaciones
    if (!$p_nombre)  $errores[] = 'Nombre del padre es obligatorio.';
    if (!$p_tel)     $errores[] = 'Tel&eacute;fono es obligatorio.';
    if (!$p_email || !filter_var($p_email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inv&aacute;lido.';
    if (strlen($p_pass) < 6) $errores[] = 'La contrase&ntilde;a debe tener al menos 6 caracteres.';
    if ($p_pass !== $p_pass2) $errores[] = 'Las contrase&ntilde;as no coinciden.';
    if (!$p_datos)   $errores[] = 'Debes aceptar la pol&iacute;tica de tratamiento de datos.';
    if (!$e_nombre)  $errores[] = 'Nombre del estudiante es obligatorio.';
    if (!$e_nac)     $errores[] = 'Fecha de nacimiento es obligatoria.';
    if (!$e_sede_id) $errores[] = 'Selecciona la sede.';

    // Email &uacute;nico
    if ($p_email) {
        $chk = $pdo->prepare("SELECT id FROM usuarios WHERE email=?");
        $chk->execute([$p_email]);
        if ($chk->fetch()) $errores[] = 'Ya existe una cuenta con ese email. &iquest;Ya est&aacute;s registrado? <a href="../login.php" style="color:var(--orange);font-weight:700;">Inicia sesi&oacute;n</a>';
    }

    // Verificar cupo si seleccion&oacute; grupo
    if ($grupo_id && !$lista) {
        $cq = $pdo->prepare("SELECT cupo_real, (SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id=g.id AND m.estado='activa') AS ins FROM grupos g WHERE g.id=?");
        $cq->execute([$grupo_id]); $ci = $cq->fetch();
        if ($ci && $ci['ins'] >= $ci['cupo_real']) $errores[] = 'Este grupo ya no tiene cupos disponibles.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // 1. Crear usuario padre
            $hash = password_hash($p_pass, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO usuarios (nombre,email,password_hash,rol,activo) VALUES (?,?,?,'padre',1)")
                ->execute([$p_nombre,$p_email,$hash]);
            $usuario_id = $pdo->lastInsertId();

            // 2. Crear padre
            $pdo->prepare("INSERT INTO padres (usuario_id,nombre_completo,tipo_doc,numero_doc,telefono,email,
                acepta_datos,acepta_imagenes,fecha_aceptacion,ip_aceptacion) VALUES (?,?,?,?,?,?,?,?,NOW(),?)")
                ->execute([$usuario_id,$p_nombre,$p_tipo_doc,$p_doc,$p_tel,$p_email,
                    $p_datos,$p_img,$_SERVER['REMOTE_ADDR']??'']);
            $padre_id = $pdo->lastInsertId();

            // 3. Crear estudiante
            $pdo->prepare("INSERT INTO estudiantes (padre_id,sede_id,nombre_completo,tipo_doc,numero_doc,
                fecha_nacimiento,genero,colegio,grado,eps,alergias,activo) VALUES (?,?,?,?,?,?,?,?,?,?,?,1)")
                ->execute([$padre_id,$e_sede_id,$e_nombre,$e_tipo_doc,$e_doc,
                    $e_nac,$e_genero,$e_colegio,$e_grado,$e_eps,$e_alergias]);
            $estudiante_id = $pdo->lastInsertId();

            // 4. Crear matr&iacute;cula si seleccion&oacute; grupo
            if ($grupo_id) {
                $estado_mat = $lista ? 'pre_inscrito' : 'activa';
                $pdo->prepare("INSERT INTO matriculas (estudiante_id,grupo_id,sede_id,estado,periodo) VALUES (?,?,?,?,?)")
                    ->execute([$estudiante_id,$grupo_id,$e_sede_id,$estado_mat,$periodo]);
                $matricula_id = $pdo->lastInsertId();

                // 5. Crear pago si no es lista de espera
                if (!$lista) {
                    $val_q = $pdo->prepare("SELECT c.valor FROM grupos g JOIN cursos c ON c.id=g.curso_id WHERE g.id=?");
                    $val_q->execute([$grupo_id]); $val_info = $val_q->fetch();
                    if ($val_info && $val_info['valor'] > 0) {
                        $pdo->prepare("INSERT INTO pagos (matricula_id,padre_id,valor_total,estado,fecha_limite) VALUES (?,?,?,'pendiente',?)")
                            ->execute([$matricula_id,$padre_id,$val_info['valor'],date('Y-m-d',strtotime('+30 days'))]);
                    }
                }
            }

            $pdo->commit();

            // &#9472;&#9472; Login autom&aacute;tico &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
            $_SESSION['usuario_id']     = $usuario_id;
            $_SESSION['usuario_rol']    = 'padre';
            $_SESSION['usuario_nombre'] = $p_nombre;
            $_SESSION['sede_id']        = null;

            // Actualizar ultimo_login
            $pdo->prepare("UPDATE usuarios SET ultimo_login=NOW() WHERE id=?")->execute([$usuario_id]);

            // Redirigir al portal con mensaje de bienvenida
            header('Location: ' . $U . 'portal/index.php?bienvenida=1');
            exit;

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errores[] = 'Error al procesar el registro. Intenta de nuevo.';
        }
    }
}

$sedes_pub = $pdo->query("SELECT * FROM sedes WHERE activa=1 ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Registro &mdash; ROBOTSchool Academy Learning</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<style>
:root{--orange:#F26522;--orange-d:#d4541a;--blue:#1E4DA1;--dark:#0f1623;--gray:#F5F7FA;--text:#1a2234;--muted:#64748b;--border:#e0e6f0;--teal:#1DA99A;--teal-l:#e6f7f5;--red:#E8192C;--red-l:#fff0f1;}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito',sans-serif;color:var(--text);background:var(--gray);min-height:100vh;}
h1,h2,h3,h4,h5,h6{font-family:'Poppins',sans-serif;font-weight:700}

/* HEADER */
.reg-header{background:var(--dark);padding:1rem 0;border-bottom:3px solid var(--orange);}
.reg-header img{height:44px;}
.reg-header .titulo{font-family:'Poppins',sans-serif;font-size:.85rem;font-weight:700;color:rgba(255,255,255,.7);margin-top:.2rem;}

/* HERO */
.reg-hero{background:linear-gradient(135deg,var(--dark),#1e3a5f);padding:2.5rem 0;text-align:center;}
.reg-hero h1{font-size:clamp(1.4rem,3vw,2rem);font-weight:900;color:#fff;margin-bottom:.5rem;}
.reg-hero h1 span{color:#FFCA28;}
.reg-hero p{color:rgba(255,255,255,.7);font-size:.9rem;}

/* STEPS */
.steps{display:flex;justify-content:center;gap:0;margin:1.5rem 0 0;}
.step{display:flex;align-items:center;gap:.5rem;padding:.5rem 1.2rem;font-size:.78rem;font-weight:700;color:rgba(255,255,255,.4);}
.step.active{color:#fff;}
.step.done{color:var(--teal);}
.step-num{width:24px;height:24px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0;}
.step-sep{width:40px;height:2px;background:rgba(255,255,255,.15);flex-shrink:0;}

/* FORM */
.reg-wrap{max-width:760px;margin:0 auto;padding:2rem 1rem 3rem;}
.reg-card{background:#fff;border-radius:16px;border:1px solid var(--border);padding:1.6rem;margin-bottom:1.2rem;}
.reg-card-title{font-size:.95rem;font-weight:800;color:var(--text);margin-bottom:1.2rem;padding-bottom:.7rem;border-bottom:1px solid var(--gray);display:flex;align-items:center;gap:.5rem;}
.reg-card-title i{color:var(--orange);}
.field-label{display:block;font-size:.8rem;font-weight:700;color:#243044;margin-bottom:.4rem;}
.field-label .req{color:var(--red);}
.rsal-input,.rsal-select,.rsal-textarea{width:100%;padding:.68rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.88rem;color:var(--text);background:#fff;outline:none;transition:all .2s;margin-bottom:.9rem;}
.rsal-input:focus,.rsal-select:focus,.rsal-textarea:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(29,169,154,.1);}
.rsal-textarea{resize:vertical;min-height:80px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;}
@media(max-width:580px){.grid-2{grid-template-columns:1fr;}}
.politica-check{display:flex;align-items:flex-start;gap:.8rem;padding:.9rem;background:var(--gray);border-radius:10px;cursor:pointer;margin-bottom:.7rem;}
.politica-check input{margin-top:3px;flex-shrink:0;width:16px;height:16px;accent-color:var(--teal);}
.politica-check strong{font-size:.85rem;color:var(--text);display:block;}
.politica-check span{font-size:.75rem;color:var(--muted);display:block;margin-top:.2rem;}
.btn-registrar{width:100%;padding:.9rem;background:var(--orange);color:#fff;border:none;border-radius:14px;font-family:'Poppins',sans-serif;font-size:1rem;font-weight:800;cursor:pointer;transition:all .25s;box-shadow:0 6px 20px rgba(242,101,34,.35);display:flex;align-items:center;justify-content:center;gap:.5rem;}
.btn-registrar:hover{background:var(--orange-d);transform:translateY(-2px);}
.alert-err{background:var(--red-l);border:1px solid #fca5a5;border-left:4px solid var(--red);border-radius:10px;padding:.8rem 1rem;font-size:.84rem;color:#b91c1c;margin-bottom:1.2rem;}
.alert-err li{margin-left:1rem;margin-top:.2rem;}
.grupo-item{display:flex;align-items:center;gap:.8rem;padding:.7rem .9rem;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:all .2s;margin-bottom:.5rem;}
.grupo-item:hover{border-color:var(--teal);background:var(--teal-l);}
.grupo-item input[type=radio]{flex-shrink:0;accent-color:var(--teal);width:16px;height:16px;}
.grupo-item.lleno{opacity:.5;cursor:not-allowed;}
.cupos-badge{margin-left:auto;font-size:.72rem;font-weight:800;padding:.2rem .6rem;border-radius:20px;white-space:nowrap;}
/* SUCCESS */
.success-wrap{text-align:center;padding:3rem 2rem;}
.success-wrap .check{width:80px;height:80px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#16a34a;margin:0 auto 1.5rem;}
.success-wrap h2{font-size:1.5rem;font-weight:800;color:var(--text);margin-bottom:.5rem;}
.success-wrap p{color:var(--muted);font-size:.9rem;max-width:400px;margin:0 auto 1.5rem;}
.btn-ir{display:inline-flex;align-items:center;gap:.4rem;padding:.7rem 1.8rem;background:var(--teal);color:#fff;border-radius:12px;font-weight:800;text-decoration:none;font-size:.9rem;}
</style>
</head>
<body>

<!-- HEADER -->
<div class="reg-header">
  <div class="container d-flex align-items-center gap-3">
    <a href="<?= $U ?>public/index.php">
      <img src="<?= $U ?>assets/img/logo_oficial.png" alt="ROBOTSchool"/>
    </a>
    <div class="titulo">Academy Learning &middot; Registro de inscripci&oacute;n</div>
  </div>
</div>

<!-- HERO -->
<div class="reg-hero">
  <div class="container">
    <h1><?= $lista ? 'Lista de <span>espera</span>' : 'Inscribe a tu <span>hijo/hija</span>' ?></h1>
    <p>Completa el formulario para registrarte. Todo el proceso es 100% en l&iacute;nea.</p>
    <?php if (!$exito): ?>
    <div class="steps">
      <div class="step active"><span class="step-num">1</span> Mis datos</div>
      <div class="step-sep"></div>
      <div class="step active"><span class="step-num">2</span> Datos del estudiante</div>
      <div class="step-sep"></div>
      <div class="step active"><span class="step-num">3</span> Curso y horario</div>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="reg-wrap">

  <?php if ($exito): ?>
  <!-- &Eacute;XITO -->
  <div class="reg-card">
    <div class="success-wrap">
      <div class="check"><i class="bi bi-check-lg"></i></div>
      <h2>&iexcl;Registro exitoso!</h2>
      <p>
        <?= $lista
          ? 'Quedaste en la lista de espera. Te contactaremos cuando haya un cupo disponible.'
          : 'Tu hijo/hija ha sido inscrito correctamente. Recibir&aacute;s un correo con los detalles.' ?>
      </p>
      <p style="font-size:.82rem;color:var(--muted);">
        Puedes acceder al portal de padres con tu email y contrase&ntilde;a para hacer seguimiento.
      </p>
      <div style="display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;margin-top:1rem;">
        <a href="<?= $U ?>portal/login.php" class="btn-ir">
          <i class="bi bi-box-arrow-in-right"></i> Ir al portal de padres
        </a>
        <a href="<?= $U ?>public/index.php" style="display:inline-flex;align-items:center;gap:.4rem;padding:.7rem 1.8rem;background:var(--gray);color:var(--muted);border-radius:12px;font-weight:700;text-decoration:none;font-size:.9rem;">
          <i class="bi bi-arrow-left"></i> Volver al inicio
        </a>
      </div>
    </div>
  </div>

  <?php else: ?>

  <?php if (!empty($errores)): ?>
    <div class="alert-err">
      <strong><i class="bi bi-exclamation-circle-fill"></i> Por favor corrige:</strong>
      <ul><?php foreach($errores as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST">

    <!-- PASO 1: DATOS DEL PADRE -->
    <div class="reg-card">
      <div class="reg-card-title"><i class="bi bi-person-fill"></i> 1. Tus datos (padre / acudiente)</div>
      <div class="grid-2">
        <div style="grid-column:1/-1;">
          <label class="field-label">Nombre completo <span class="req">*</span></label>
          <input type="text" name="p_nombre" class="rsal-input" required
                 placeholder="Tu nombre completo"
                 value="<?= h($_POST['p_nombre'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">Tipo documento</label>
          <select name="p_tipo_doc" class="rsal-select">
            <option value="CC" <?= ($_POST['p_tipo_doc']??'CC')==='CC'?'selected':'' ?>>C&eacute;dula de Ciudadan&iacute;a</option>
            <option value="CE" <?= ($_POST['p_tipo_doc']??'')==='CE'?'selected':'' ?>>C&eacute;dula Extranjer&iacute;a</option>
            <option value="PP" <?= ($_POST['p_tipo_doc']??'')==='PP'?'selected':'' ?>>Pasaporte</option>
          </select>
        </div>
        <div>
          <label class="field-label">N&uacute;mero de documento</label>
          <input type="text" name="p_doc" class="rsal-input"
                 placeholder="N&uacute;mero de documento"
                 value="<?= h($_POST['p_doc'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">Tel&eacute;fono / WhatsApp <span class="req">*</span></label>
          <input type="text" name="p_tel" class="rsal-input" required
                 placeholder="300 123 4567"
                 value="<?= h($_POST['p_tel'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">Correo electr&oacute;nico <span class="req">*</span></label>
          <input type="email" name="p_email" class="rsal-input" required
                 placeholder="tucorreo@gmail.com"
                 value="<?= h($_POST['p_email'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">Contrase&ntilde;a <span class="req">*</span></label>
          <input type="password" name="p_pass" class="rsal-input" required
                 placeholder="M&iacute;nimo 6 caracteres" minlength="6"/>
        </div>
        <div>
          <label class="field-label">Confirmar contrase&ntilde;a <span class="req">*</span></label>
          <input type="password" name="p_pass2" class="rsal-input" required
                 placeholder="Repite la contrase&ntilde;a" minlength="6"/>
        </div>
      </div>

      <!-- Pol&iacute;tica 1: Tratamiento de datos -->
      <div style="border:1.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:.8rem;">
        <label style="display:flex;align-items:flex-start;gap:.8rem;padding:.9rem 1rem;background:var(--gray);cursor:pointer;">
          <input type="checkbox" name="acepta_datos"
                 style="margin-top:3px;flex-shrink:0;width:16px;height:16px;accent-color:var(--teal);"
                 <?= isset($_POST['acepta_datos'])?'checked':'' ?> required/>
          <div style="flex:1;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
              <strong style="font-size:.85rem;color:var(--text);">
                &#9989; Autorizaci&oacute;n de tratamiento de datos personales
                <span style="color:var(--red);font-size:.75rem;"> * Obligatorio</span>
              </strong>
              <button type="button" onclick="togglePolitica('datos')"
                      style="background:none;border:none;color:var(--orange);font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                      id="btnDatos">
                <i class="bi bi-chevron-down" id="iconDatos"></i> Leer pol&iacute;tica
              </button>
            </div>
            <span style="font-size:.75rem;color:var(--muted);display:block;margin-top:.2rem;">
              Conforme a la Ley 1581 de 2012 y Decreto 1377 de 2013 &mdash; Habeas Data Colombia
            </span>
          </div>
        </label>
        <!-- Texto legal desplegable -->
        <div id="textoDatos" style="display:none;padding:1rem 1.2rem;background:#fff;border-top:1px solid var(--border);font-size:.78rem;color:var(--text);line-height:1.75;">
          <p style="font-weight:800;color:var(--blue);margin-bottom:.7rem;">
            AUTORIZACI&Oacute;N DE TRATAMIENTO DE DATOS PERSONALES<br>
            <span style="font-weight:400;font-size:.72rem;color:var(--muted);">Ley 1581 de 2012 &middot; Decreto 1377 de 2013 &middot; Habeas Data</span>
          </p>
          <p style="margin-bottom:.7rem;">
            En cumplimiento de la <strong>Ley Estatutaria 1581 de 2012</strong> y su Decreto Reglamentario 1377 de 2013, yo, como titular de los datos personales y representante legal del menor de edad, autorizo de manera libre, expresa, voluntaria, inequ&iacute;voca e informada a <strong>Escuelas STEAM Colombia SAS &mdash; ROBOTSchool</strong>, con domicilio en Bogot&aacute; D.C., para:
          </p>
          <ul style="margin-left:1.2rem;margin-bottom:.7rem;">
            <li style="margin-bottom:.4rem;"><strong>Recolectar, almacenar, usar, circular y suprimir</strong> mis datos personales y los de mi hijo/hija, incluyendo: nombre completo, n&uacute;mero de documento, fecha de nacimiento, direcci&oacute;n, tel&eacute;fono, correo electr&oacute;nico, instituci&oacute;n educativa, grado escolar e informaci&oacute;n de salud relevante.</li>
            <li style="margin-bottom:.4rem;"><strong>Finalidades del tratamiento:</strong> gesti&oacute;n de matr&iacute;culas y procesos acad&eacute;micos, comunicaci&oacute;n institucional, env&iacute;o de informaci&oacute;n sobre cursos y actividades, cobro de servicios educativos, seguimiento del proceso de aprendizaje y cumplimiento de obligaciones legales.</li>
            <li style="margin-bottom:.4rem;"><strong>Conservaci&oacute;n:</strong> los datos ser&aacute;n conservados durante la vigencia de la relaci&oacute;n acad&eacute;mica y por el tiempo exigido por la ley.</li>
            <li style="margin-bottom:.4rem;"><strong>Transferencia:</strong> los datos NO ser&aacute;n compartidos con terceros sin previa autorizaci&oacute;n, salvo obligaci&oacute;n legal.</li>
          </ul>
          <p style="margin-bottom:.7rem;">
            <strong>Derechos del titular (Habeas Data):</strong> Usted tiene derecho a <strong>conocer, actualizar, rectificar y suprimir</strong> sus datos personales, as&iacute; como a <strong>revocar esta autorizaci&oacute;n</strong> en cualquier momento, salvo que exista impedimento legal. Para ejercer estos derechos puede escribir a <strong>info@robotschool.com.co</strong> o llamar al <strong>318 654 1859</strong>.
          </p>
          <p style="background:#e8f0fe;border-radius:8px;padding:.6rem .9rem;font-size:.72rem;color:#1E4DA1;">
            <i class="bi bi-shield-check-fill"></i>
            ROBOTSchool se compromete a tratar sus datos con absoluta confidencialidad, seguridad y &uacute;nicamente para las finalidades descritas, conforme a la Pol&iacute;tica de Privacidad y Tratamiento de Datos disponible en nuestras instalaciones y en <strong>robotschool.com.co</strong>.
          </p>
        </div>
      </div>

      <!-- Pol&iacute;tica 2: Uso de im&aacute;genes -->
      <div style="border:1.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:.7rem;">
        <label style="display:flex;align-items:flex-start;gap:.8rem;padding:.9rem 1rem;background:var(--gray);cursor:pointer;">
          <input type="checkbox" name="acepta_imagenes"
                 style="margin-top:3px;flex-shrink:0;width:16px;height:16px;accent-color:var(--teal);"
                 <?= isset($_POST['acepta_imagenes'])?'checked':'' ?>/>
          <div style="flex:1;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
              <strong style="font-size:.85rem;color:var(--text);">
                &#128248; Autorizaci&oacute;n uso de im&aacute;genes y videos
                <span style="font-size:.72rem;color:var(--muted);font-weight:400;"> (opcional)</span>
              </strong>
              <button type="button" onclick="togglePolitica('imagenes')"
                      style="background:none;border:none;color:var(--orange);font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                      id="btnImagenes">
                <i class="bi bi-chevron-down" id="iconImagenes"></i> Leer autorizaci&oacute;n
              </button>
            </div>
            <span style="font-size:.75rem;color:var(--muted);display:block;margin-top:.2rem;">
              Solo para uso institucional y publicitario de ROBOTSchool
            </span>
          </div>
        </label>
        <!-- Texto legal desplegable -->
        <div id="textoImagenes" style="display:none;padding:1rem 1.2rem;background:#fff;border-top:1px solid var(--border);font-size:.78rem;color:var(--text);line-height:1.75;">
          <p style="font-weight:800;color:var(--blue);margin-bottom:.7rem;">
            AUTORIZACI&Oacute;N USO DE IM&Aacute;GENES, FOTOGRAF&Iacute;AS Y VIDEOS<br>
            <span style="font-weight:400;font-size:.72rem;color:var(--muted);">Uso exclusivo ROBOTSchool &mdash; Escuelas STEAM Colombia SAS</span>
          </p>
          <p style="margin-bottom:.7rem;">
            Como padre, madre o acudiente del menor de edad, <strong>autorizo voluntariamente</strong> a <strong>Escuelas STEAM Colombia SAS &mdash; ROBOTSchool</strong> para capturar, reproducir, publicar y usar las fotograf&iacute;as, im&aacute;genes y videos en los que aparezca mi hijo/hija, <strong>&uacute;nica y exclusivamente</strong> para los siguientes fines institucionales:
          </p>
          <ul style="margin-left:1.2rem;margin-bottom:.7rem;">
            <li style="margin-bottom:.4rem;"><strong>Material publicitario institucional:</strong> folletos, brochures, cat&aacute;logos de cursos y material impreso de ROBOTSchool.</li>
            <li style="margin-bottom:.4rem;"><strong>Redes sociales oficiales:</strong> Facebook, Instagram, YouTube y otras plataformas administradas exclusivamente por ROBOTSchool.</li>
            <li style="margin-bottom:.4rem;"><strong>P&aacute;gina web:</strong> robotschool.com.co y academy.robotschool.com.co.</li>
            <li style="margin-bottom:.4rem;"><strong>Comunicaciones internas:</strong> boletines, correos institucionales y presentaciones corporativas.</li>
          </ul>
          <div style="background:#fff8e1;border:1px solid #fde047;border-radius:8px;padding:.7rem .9rem;margin-bottom:.7rem;">
            <strong style="font-size:.78rem;color:#854d0e;">&#9888;&#65039; Restricciones importantes:</strong>
            <ul style="margin-left:1rem;margin-top:.3rem;font-size:.75rem;color:#92400e;">
              <li>Las im&aacute;genes <strong>NO ser&aacute;n vendidas ni cedidas</strong> a terceros.</li>
              <li>Las im&aacute;genes <strong>NO se usar&aacute;n</strong> con fines ajenos a ROBOTSchool.</li>
              <li>Las im&aacute;genes <strong>NO se usar&aacute;n</strong> de manera denigrante o que afecte la dignidad del menor.</li>
              <li>Esta autorizaci&oacute;n puede <strong>revocarse en cualquier momento</strong> mediante comunicaci&oacute;n escrita a info@robotschool.com.co.</li>
            </ul>
          </div>
          <p style="font-size:.72rem;color:var(--muted);">
            Esta autorizaci&oacute;n se otorga sin contraprestaci&oacute;n econ&oacute;mica y de conformidad con la Ley 1581 de 2012 y el C&oacute;digo de Infancia y Adolescencia (Ley 1098 de 2006).
          </p>
        </div>
      </div>
    </div>

    <!-- PASO 2: DATOS DEL ESTUDIANTE -->
    <div class="reg-card">
      <div class="reg-card-title"><i class="bi bi-person-badge-fill"></i> 2. Datos de tu hijo/hija</div>
      <div class="grid-2">
        <div style="grid-column:1/-1;">
          <label class="field-label">Nombre completo <span class="req">*</span></label>
          <input type="text" name="e_nombre" class="rsal-input" required
                 placeholder="Nombre completo del estudiante"
                 value="<?= h($_POST['e_nombre'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">Tipo documento</label>
          <select name="e_tipo_doc" class="rsal-select">
            <option value="TI" <?= ($_POST['e_tipo_doc']??'TI')==='TI'?'selected':'' ?>>Tarjeta de Identidad</option>
            <option value="RC" <?= ($_POST['e_tipo_doc']??'')==='RC'?'selected':'' ?>>Registro Civil</option>
            <option value="PP" <?= ($_POST['e_tipo_doc']??'')==='PP'?'selected':'' ?>>Pasaporte</option>
          </select>
        </div>
        <div>
          <label class="field-label">N&uacute;mero de documento</label>
          <input type="text" name="e_doc" class="rsal-input"
                 placeholder="N&uacute;mero de documento"
                 value="<?= h($_POST['e_doc'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">Fecha de nacimiento <span class="req">*</span></label>
          <input type="date" name="e_nac" class="rsal-input" required
                 value="<?= h($_POST['e_nac'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">G&eacute;nero</label>
          <select name="e_genero" class="rsal-select">
            <option value="masculino"        <?= ($_POST['e_genero']??'')==='masculino'?'selected':'' ?>>Masculino</option>
            <option value="femenino"         <?= ($_POST['e_genero']??'')==='femenino'?'selected':'' ?>>Femenino</option>
            <option value="otro"             <?= ($_POST['e_genero']??'')==='otro'?'selected':'' ?>>Otro</option>
            <option value="prefiero_no_decir" <?= ($_POST['e_genero']??'prefiero_no_decir')==='prefiero_no_decir'?'selected':'' ?>>Prefiero no decir</option>
          </select>
        </div>
        <div>
          <label class="field-label">Colegio</label>
          <input type="text" name="e_colegio" class="rsal-input"
                 placeholder="Nombre del colegio"
                 value="<?= h($_POST['e_colegio'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">Grado</label>
          <input type="text" name="e_grado" class="rsal-input"
                 placeholder="Ej: 4&deg;, 5&deg;, Preescolar"
                 value="<?= h($_POST['e_grado'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">EPS</label>
          <input type="text" name="e_eps" class="rsal-input"
                 placeholder="Ej: Sura, Nueva EPS"
                 value="<?= h($_POST['e_eps'] ?? '') ?>"/>
        </div>
        <div>
          <label class="field-label">Sede <span class="req">*</span></label>
          <select name="e_sede_id" class="rsal-select" required onchange="cargarGrupos(this.value)">
            <option value="">Selecciona la sede...</option>
            <?php foreach ($sedes_pub as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($_POST['e_sede_id']??$e_sede_id)==$s['id']?'selected':'' ?>>
                <?= h($s['nombre']) ?> &mdash; <?= h($s['ciudad']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="grid-column:1/-1;">
          <label class="field-label">Alergias o condiciones especiales</label>
          <textarea name="e_alergias" class="rsal-textarea"
                    placeholder="Opcional: alergias, asma, diabetes..."><?= h($_POST['e_alergias'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- PASO 3: CURSO Y HORARIO -->
    <div class="reg-card">
      <div class="reg-card-title"><i class="bi bi-calendar3"></i> 3. Curso y horario</div>

      <label class="field-label">Curso de inter&eacute;s</label>
      <select name="curso_select" class="rsal-select" id="sel_curso"
              onchange="cargarGruposPorCurso(this.value)">
        <option value="">Selecciona un curso...</option>
        <?php foreach ($cursos_pub as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $curso_id==$c['id']?'selected':'' ?>>
            <?= h($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label class="field-label">Horario disponible</label>
      <div id="gruposContainer">
        <?php if ($grupos_curso): ?>
          <?php foreach ($grupos_curso as $g):
            $d = (int)$g['disponibles'];
            $lleno = $d <= 0 && !$lista;
          ?>
          <label class="grupo-item <?= $lleno?'lleno':'' ?>">
            <input type="radio" name="grupo_id" value="<?= $g['id'] ?>"
                   <?= $lleno?'disabled':'' ?>
                   <?= ($_POST['grupo_id']??'')==$g['id']?'checked':'' ?>/>
            <div>
              <strong style="font-size:.85rem;"><?= h($g['nombre']) ?></strong>
              <div style="font-size:.75rem;color:var(--muted);">
                <?= $dias[$g['dia_semana']]??$g['dia_semana'] ?> &middot; <?= substr($g['hora_inicio'],0,5) ?>&ndash;<?= substr($g['hora_fin'],0,5) ?>
              </div>
            </div>
            <span class="cupos-badge" style="background:<?= $d>3?'#dcfce7':($d>0?'#fef9c3':'var(--red-l)') ?>;color:<?= $d>3?'#16a34a':($d>0?'#ca8a04':'var(--red)') ?>;">
              <?= $d>0?$d.' cupos':'Lleno' ?>
            </span>
          </label>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="text-align:center;padding:1.5rem;color:var(--muted);font-size:.85rem;background:var(--gray);border-radius:10px;" id="gruposPlaceholder">
            <i class="bi bi-calendar3" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.3;"></i>
            Selecciona un curso para ver los horarios disponibles
          </div>
        <?php endif; ?>
      </div>

      <input type="hidden" name="periodo" value="<?= date('Y') ?>-1"/>

      <div style="margin-top:.8rem;font-size:.78rem;color:var(--muted);background:var(--gray);border-radius:8px;padding:.7rem .9rem;">
        &#128161; Si no seleccionas horario, quedar&aacute;s pre-inscrito y te contactaremos para asignarte uno.
      </div>
    </div>

    <!-- ENVIAR -->
    <button type="submit" class="btn-registrar">
      <i class="bi bi-person-plus-fill"></i>
      <?= $lista ? 'Unirme a lista de espera' : 'Completar inscripci&oacute;n' ?>
    </button>

    <div style="text-align:center;margin-top:1rem;font-size:.82rem;color:var(--muted);">
      &iquest;Ya tienes cuenta? <a href="<?= $U ?>login.php" style="color:var(--orange);font-weight:700;">Inicia sesi&oacute;n</a>
    </div>

  </form>
  <?php endif; ?>

</div>

<script>
function togglePolitica(cual) {
  const texto = document.getElementById('texto' + cual.charAt(0).toUpperCase() + cual.slice(1));
  const icono = document.getElementById('icon'  + cual.charAt(0).toUpperCase() + cual.slice(1));
  const btn   = document.getElementById('btn'   + cual.charAt(0).toUpperCase() + cual.slice(1));
  const abierto = texto.style.display !== 'none';
  texto.style.display = abierto ? 'none' : 'block';
  icono.className = abierto ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
  btn.innerHTML   = (abierto ? '<i class="bi bi-chevron-down" id="icon'+cual.charAt(0).toUpperCase()+cual.slice(1)+'"></i> Leer ' : '<i class="bi bi-chevron-up" id="icon'+cual.charAt(0).toUpperCase()+cual.slice(1)+'"></i> Cerrar ') + (cual==='datos'?'pol&iacute;tica':'autorizaci&oacute;n');
}

function cargarGruposPorCurso(cursoId) {
  if (!cursoId) {
    document.getElementById('gruposContainer').innerHTML =
      '<div style="text-align:center;padding:1.5rem;color:var(--muted);font-size:.85rem;background:var(--gray);border-radius:10px;"><i class="bi bi-calendar3" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.3;"></i>Selecciona un curso para ver los horarios</div>';
    return;
  }
  document.getElementById('gruposContainer').innerHTML =
    '<div style="text-align:center;padding:1rem;color:var(--muted);font-size:.85rem;">Cargando horarios...</div>';

  fetch('curso_detalle.php?id=' + cursoId)
    .then(r => r.json())
    .then(c => {
      const dias = {lunes:'Lunes',martes:'Martes',miercoles:'Mi&eacute;rcoles',jueves:'Jueves',viernes:'Viernes',sabado:'S&aacute;bado',domingo:'Domingo'};
      let html = '';
      if (c.grupos && c.grupos.length) {
        c.grupos.forEach(g => {
          const d = parseInt(g.disponibles);
          const lleno = d <= 0;
          const col = d>3?'#16a34a':(d>0?'#ca8a04':'var(--red)');
          const bg  = d>3?'#dcfce7':(d>0?'#fef9c3':'var(--red-l)');
          html += `<label class="grupo-item ${lleno?'lleno':''}">
            <input type="radio" name="grupo_id" value="${g.id}" ${lleno?'disabled':''} style="flex-shrink:0;accent-color:var(--teal);width:16px;height:16px;"/>
            <div>
              <strong style="font-size:.85rem;">${g.nombre}</strong>
              <div style="font-size:.75rem;color:var(--muted);">${dias[g.dia_semana]||g.dia_semana} &middot; ${g.hora_inicio.substring(0,5)}&ndash;${g.hora_fin.substring(0,5)}</div>
            </div>
            <span style="margin-left:auto;font-size:.72rem;font-weight:800;padding:.2rem .6rem;border-radius:20px;background:${bg};color:${col};white-space:nowrap;">${d>0?d+' cupos':'Lleno'}</span>
          </label>`;
        });
      } else {
        html = '<div style="text-align:center;padding:1.5rem;color:var(--muted);font-size:.85rem;background:var(--gray);border-radius:10px;">No hay grupos disponibles para este curso a&uacute;n.</div>';
      }
      document.getElementById('gruposContainer').innerHTML = html;
    })
    .catch(() => {
      document.getElementById('gruposContainer').innerHTML =
        '<div style="text-align:center;padding:1rem;color:var(--red);font-size:.85rem;">Error al cargar horarios.</div>';
    });
}
</script>
</body>
</html>
