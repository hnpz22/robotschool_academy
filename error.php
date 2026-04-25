<?php
// error.php &mdash; ROBOTSchool Academy Learning
$code = (int)($_GET['code'] ?? 500);
$mensajes = [
    403 => ['titulo' => 'Acceso denegado',            'desc' => 'No tienes permiso para acceder a este recurso.'],
    404 => ['titulo' => 'P&aacute;gina no encontrada','desc' => 'El recurso solicitado no existe o fue movido.'],
    500 => ['titulo' => 'Error del servidor',         'desc' => 'Ocurri&oacute; un error interno. Por favor intenta de nuevo.'],
];
$msg = $mensajes[$code] ?? $mensajes[500];
http_response_code($code);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Error <?= $code ?> &mdash; RSAL</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<style>
body{background:#f4f6fb;font-family:'Segoe UI',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.1);padding:3rem 2.5rem;max-width:480px;width:100%;text-align:center}
.code{font-size:5rem;font-weight:900;color:#E8192C;line-height:1}
.title{font-size:1.4rem;font-weight:700;color:#1a2234;margin:.5rem 0 .8rem}
.desc{color:#6b7a99;margin-bottom:2rem}
.btn-back{background:#1DA99A;color:#fff;border:none;padding:.7rem 2rem;border-radius:12px;font-weight:700;text-decoration:none;display:inline-block}
.btn-back:hover{background:#148a7d;color:#fff}
.debug{margin-top:1.5rem;text-align:left;background:#fff0f1;border-radius:10px;padding:1rem;font-size:.8rem;color:#c00;word-break:break-all}
</style>
</head>
<body>
<div class="card">
  <div class="code"><?= $code ?></div>
  <div class="title"><?= $msg['titulo'] ?></div>
  <p class="desc"><?= $msg['desc'] ?></p>
  <a href="/robotschool_academy/" class="btn-back"><i class="bi bi-house-fill"></i> Volver al inicio</a>
  <?php
  // Info de debug &#8212; solo en localhost
  $is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['::1','127.0.0.1']);
  if ($is_local):
    $php_error = $_SERVER['REDIRECT_ERROR_NOTES'] ?? null;
    $request   = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '?';
  ?>
  <div class="debug">
    <strong>DEBUG (solo visible en localhost)</strong><br>
    <strong>URL:</strong> <?= htmlspecialchars($request) ?><br>
    <?php if ($php_error): ?>
    <strong>Error:</strong> <?= htmlspecialchars($php_error) ?>
    <?php else: ?>
    <strong>Activa display_errors en PHP para ver el detalle.</strong><br>
    Revisa: <code>/Applications/XAMPP/logs/error_log</code>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
