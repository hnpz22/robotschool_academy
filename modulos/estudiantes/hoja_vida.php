<?php
// modulos/estudiantes/hoja_vida.php
// Hoja de vida en HTML imprimible &mdash; sin librer&iacute;as externas
// El navegador convierte a PDF con Ctrl+P &#8594; Guardar como PDF
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'modulos/estudiantes/index.php'); exit; }

$sede_filtro = getSedeFiltro();

$stmt = $pdo->prepare("
    SELECT e.*,
        s.nombre AS sede_nombre, s.ciudad AS sede_ciudad,
        p.nombre_completo AS padre_nombre,
        p.tipo_doc AS padre_tipo_doc, p.numero_doc AS padre_num_doc,
        p.telefono AS padre_tel, p.telefono_alt AS padre_tel_alt,
        p.email AS padre_email, p.direccion AS padre_direccion,
        p.ocupacion AS padre_ocupacion,
        p.acepta_datos, p.acepta_imagenes, p.fecha_aceptacion,
        m.periodo AS periodo, m.estado AS matricula_estado,
        c.nombre AS curso_nombre,
        g.nombre AS grupo_nombre, g.dia_semana, g.hora_inicio, g.hora_fin
    FROM estudiantes e
    JOIN sedes   s ON s.id = e.sede_id
    JOIN padres  p ON p.id = e.padre_id
    LEFT JOIN matriculas m ON m.estudiante_id = e.id AND m.estado IN ('activa','pre_inscrito')
    LEFT JOIN grupos g ON g.id = m.grupo_id
    LEFT JOIN cursos c ON c.id = g.curso_id
    WHERE e.id = ?
    ORDER BY m.created_at DESC
    LIMIT 1
");
$stmt->execute([$id]);
$e = $stmt->fetch();

if (!$e || ($sede_filtro && $e['sede_id'] != $sede_filtro)) {
    header('Location: ' . BASE_URL . 'modulos/estudiantes/index.php'); exit;
}

$edad = '';
if ($e['fecha_nacimiento']) {
    $edad = (new DateTime($e['fecha_nacimiento']))->diff(new DateTime())->y . ' a&ntilde;os';
}

$dias_map    = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];
$tipo_doc_m  = ['TI'=>'Tarjeta de Identidad','RC'=>'Registro Civil','PP'=>'Pasaporte','CE'=>'C&eacute;dula Extranjer&iacute;a','CC'=>'C&eacute;dula Ciudadan&iacute;a'];
$genero_m    = ['masculino'=>'Masculino','femenino'=>'Femenino','otro'=>'Otro','prefiero_no_decir'=>'No especificado'];

function val($v, $d='&mdash;') { return trim($v ?? '') ?: $d; }
function fd($d) { return $d ? date('d/m/Y', strtotime($d)) : '&mdash;'; }

$horario = '';
if ($e['dia_semana'] && $e['hora_inicio']) {
    $horario = ($dias_map[$e['dia_semana']] ?? $e['dia_semana'])
             . ' &middot; ' . substr($e['hora_inicio'],0,5)
             . ' &ndash; ' . substr($e['hora_fin'],0,5);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width"/>
<title>Hoja de Vida &mdash; <?= h($e['nombre_completo']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#1a2234;background:#f0f2f5;}
.page{background:#fff;max-width:210mm;margin:0 auto;padding:0;}

/* ENCABEZADO */
.hdr{background:#0f1623;color:#fff;padding:13px 20px;display:flex;justify-content:space-between;align-items:center;}
.hdr-marca{font-size:20px;font-weight:900;color:#F26522;letter-spacing:-0.5px;line-height:1.1;}
.hdr-marca small{font-size:8.5px;font-weight:400;color:#8a99b8;display:block;}
.hdr-marca .sub{font-size:8px;color:#4a5a7a;margin-top:2px;}
.hdr-right{text-align:right;}
.hdr-right .tipo{font-size:15px;font-weight:900;color:#fff;letter-spacing:1px;}
.hdr-right .sub{font-size:8px;color:#8a99b8;}
.hdr-right .fecha{font-size:7.5px;color:#4a5a7a;margin-top:3px;}

/* NOMBRE */
.nombre-strip{background:#1E4DA1;padding:9px 20px;display:flex;justify-content:space-between;align-items:center;}
.nombre-strip .nombre{font-size:14px;font-weight:900;color:#fff;letter-spacing:.3px;}
.nombre-strip .info{font-size:8.5px;color:#c8d8ff;text-align:right;line-height:1.5;}

/* BODY */
.body{padding:12px 18px;}

/* SECCI&Oacute;N */
.sec{background:#1DA99A;color:#fff;font-size:8px;font-weight:900;text-transform:uppercase;letter-spacing:.9px;padding:4px 10px;margin:10px 0 7px;border-radius:3px;}
.sec:first-child{margin-top:0;}

/* GRIDS */
.row{display:flex;gap:5px;margin-bottom:5px;flex-wrap:wrap;}
.c{flex:1;min-width:80px;background:#F5F7FA;border:0.4px solid #dde3ef;border-radius:4px;padding:5px 7px;}
.c.c2{flex:2;}
.c.c3{flex:3;}
.c.full{flex:none;width:100%;}
.c .lbl{font-size:7px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;}
.c .val{font-size:10px;color:#1a2234;line-height:1.3;}
.c .val.b{font-weight:700;}
.c.alerta{background:#fff0f1;border:0.5px solid #fca5a5;border-left:3px solid #E8192C;}
.c.alerta .lbl{color:#991b1b;}
.c.alerta .val{color:#b91c1c;font-weight:700;}
.c.seguro{background:#e6f7f5;border:0.5px solid #a7ebe5;border-left:3px solid #1DA99A;}
.c.seguro .lbl{color:#0d6e5f;}

/* AUTORIZACIONES */
.auth-wrap{border:0.5px solid #dde3ef;border-radius:5px;overflow:hidden;}
.auth-row{display:flex;align-items:flex-start;gap:9px;padding:6px 10px;border-bottom:.4px solid #e8ecf4;}
.auth-row:last-child{border-bottom:none;}
.auth-ico{font-size:13px;font-weight:900;flex-shrink:0;margin-top:1px;}
.auth-ico.ok{color:#16a34a;} .auth-ico.no{color:#E8192C;}
.auth-txt{font-size:9px;line-height:1.55;color:#1a2234;}
.auth-txt strong{font-size:9.5px;}

/* LEGAL */
.legal{font-size:8px;color:#64748b;line-height:1.7;text-align:justify;border:.5px solid #dde3ef;border-radius:5px;padding:8px 10px;background:#f8fafc;}
.legal strong{color:#1a2234;}

/* FIRMAS */
.firmas{display:flex;margin-top:22px;border-top:1.5px solid #F26522;padding-top:4px;}
.fbox{flex:1;text-align:center;padding:0 10px;border-right:.5px solid #e0e6f0;}
.fbox:last-child{border-right:none;}
.flinea{border-top:1px solid #0f1623;margin:28px auto 5px;width:80%;}
.fnombre{font-size:9px;font-weight:700;color:#1a2234;}
.fcargo{font-size:8px;color:#64748b;margin-top:2px;}
.fdoc{font-size:7.5px;color:#94a3b8;margin-top:1px;}

/* PIE */
.pie{margin-top:10px;border-top:1px solid #e0e6f0;padding-top:6px;display:flex;justify-content:space-between;font-size:7px;color:#94a3b8;}
.pie strong{color:#64748b;}

/* BOT&Oacute;N */
.btn-bar{position:fixed;top:12px;right:16px;z-index:99;display:flex;gap:8px;}
.btn-print{background:#F26522;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:12px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(242,101,34,.4);display:flex;align-items:center;gap:6px;}
.btn-print:hover{background:#d4541a;}
.btn-back{background:#fff;color:#64748b;border:1px solid #dde3ef;border-radius:8px;padding:9px 13px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:5px;}
.btn-back:hover{border-color:#94a3b8;}

@media print{
  body{background:#fff;}
  .btn-bar,.btn-print,.btn-back{display:none!important;}
  .page{max-width:none;box-shadow:none;}
  @page{size:letter;margin:1.2cm 1.5cm;}
  .sec{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .nombre-strip,.hdr{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .c.alerta,.c.seguro{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
}
</style>
</head>
<body>

<div class="btn-bar">
  <a href="javascript:history.back()" class="btn-back">&#8592; Volver</a>
  <button class="btn-print" onclick="window.print()">&#128424;&#65039; Imprimir / PDF</button>
</div>

<div class="page">

<!-- ENCABEZADO -->
<div class="hdr">
  <div class="hdr-marca">
    ROBOTSchool
    <small>Academy Learning</small>
    <div class="sub">Escuelas STEAM Colombia SAS &nbsp;&middot;&nbsp; Bogot&aacute; &middot; Cali &nbsp;&middot;&nbsp; robotschool.com.co</div>
  </div>
  <div class="hdr-right">
    <div class="tipo">HOJA DE VIDA</div>
    <div class="sub">ESTUDIANTE &mdash; DOCUMENTO CONFIDENCIAL</div>
    <div class="fecha">Generado: <?= date('d/m/Y H:i') ?></div>
  </div>
</div>

<!-- NOMBRE -->
<div class="nombre-strip">
  <?php
  // Avatar en base64 para que aparezca en impresi&oacute;n/PDF
  $avatar_b64 = '';
  if ($e['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$e['avatar'])) {
      $ap   = ROOT.'/uploads/estudiantes/'.$e['avatar'];
      $ext  = strtolower(pathinfo($ap, PATHINFO_EXTENSION));
      $mime = in_array($ext,['jpg','jpeg']) ? 'image/jpeg' : 'image/png';
      $avatar_b64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($ap));
  }
  ?>
  <div style="display:flex;align-items:center;gap:12px;">
    <?php if ($avatar_b64): ?>
      <img src="<?= $avatar_b64 ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.3);flex-shrink:0;"/>
    <?php else: ?>
      <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#1DA99A,#148a7d);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:900;color:#fff;flex-shrink:0;border:2px solid rgba(255,255,255,.2);">
        <?= strtoupper(substr($e['nombre_completo'],0,2)) ?>
      </div>
    <?php endif; ?>
    <div>
      <div class="nombre"><?= strtoupper(h($e['nombre_completo'])) ?></div>
      <div class="info" style="text-align:left;font-size:8px;margin-top:2px;">
        <?= h($tipo_doc_m[$e['tipo_doc']] ?? $e['tipo_doc']) ?>: <?= val($e['numero_doc']) ?> &middot; <?= $edad ?>
      </div>
    </div>
  </div>
  <div class="info">
    <?= h($e['sede_nombre']) ?> &mdash; <?= h($e['sede_ciudad']) ?>
  </div>
</div>

<div class="body">

<!-- 1. DATOS PERSONALES -->
<div class="sec">1. Datos Personales del Estudiante</div>
<div class="row">
  <div class="c">
    <div class="lbl">Tipo de documento</div>
    <div class="val"><?= h($tipo_doc_m[$e['tipo_doc']] ?? $e['tipo_doc']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">N&uacute;mero de documento</div>
    <div class="val b"><?= val($e['numero_doc']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">Fecha de nacimiento</div>
    <div class="val"><?= fd($e['fecha_nacimiento']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">Edad</div>
    <div class="val b"><?= $edad ?></div>
  </div>
  <div class="c">
    <div class="lbl">G&eacute;nero</div>
    <div class="val"><?= h($genero_m[$e['genero']] ?? $e['genero']) ?></div>
  </div>
</div>
<div class="row">
  <div class="c c2">
    <div class="lbl">Sede ROBOTSchool</div>
    <div class="val"><?= h($e['sede_nombre']) ?> &mdash; <?= h($e['sede_ciudad']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">Per&iacute;odo</div>
    <div class="val"><?= val($e['periodo'], date('Y').'-1') ?></div>
  </div>
  <div class="c">
    <div class="lbl">Estado matr&iacute;cula</div>
    <div class="val b"><?= strtoupper(val($e['matricula_estado'], 'Sin matr&iacute;cula')) ?></div>
  </div>
</div>

<!-- 2. INFO ACAD&Eacute;MICA -->
<div class="sec">2. Informaci&oacute;n Acad&eacute;mica</div>
<div class="row">
  <div class="c c2">
    <div class="lbl">Colegio / Instituci&oacute;n educativa</div>
    <div class="val"><?= val($e['colegio']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">Grado actual</div>
    <div class="val"><?= val($e['grado']) ?></div>
  </div>
  <div class="c c2">
    <div class="lbl">Curso ROBOTSchool</div>
    <div class="val b"><?= val($e['curso_nombre']) ?></div>
  </div>
</div>
<div class="row">
  <div class="c c2">
    <div class="lbl">Grupo</div>
    <div class="val"><?= val($e['grupo_nombre']) ?></div>
  </div>
  <div class="c c2">
    <div class="lbl">D&iacute;a y horario de clase</div>
    <div class="val b"><?= $horario ?: '&mdash;' ?></div>
  </div>
</div>

<!-- 3. INFO M&Eacute;DICA -->
<div class="sec">3. Informaci&oacute;n M&eacute;dica y de Salud</div>
<div class="row">
  <div class="c c2">
    <div class="lbl">EPS / Entidad de salud</div>
    <div class="val"><?= val($e['eps']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">Grupo sangu&iacute;neo</div>
    <div class="val b"><?= val($e['grupo_sanguineo']) ?></div>
  </div>
  <div class="c c2 seguro">
    <div class="lbl">&#128737;&#65039; Seguro estudiantil</div>
    <div class="val b"><?= val($e['seguro_estudiantil']) ?></div>
  </div>
</div>
<div class="row">
  <?php if (trim($e['alergias'] ?? '')): ?>
    <div class="c full alerta">
      <div class="lbl">&#9888;&#65039; Alergias y condiciones especiales &mdash; ATENCI&Oacute;N</div>
      <div class="val"><?= h($e['alergias']) ?></div>
    </div>
  <?php else: ?>
    <div class="c full">
      <div class="lbl">Alergias y condiciones especiales</div>
      <div class="val">El estudiante no reporta alergias ni condiciones especiales.</div>
    </div>
  <?php endif; ?>
</div>
<?php if (trim($e['observaciones'] ?? '')): ?>
<div class="row">
  <div class="c full">
    <div class="lbl">Observaciones m&eacute;dicas adicionales</div>
    <div class="val"><?= h($e['observaciones']) ?></div>
  </div>
</div>
<?php endif; ?>

<!-- 4. DATOS PADRE -->
<div class="sec">4. Datos del Padre / Madre / Acudiente</div>
<div class="row">
  <div class="c c2">
    <div class="lbl">Nombre completo</div>
    <div class="val b"><?= h($e['padre_nombre']) ?></div>
  </div>
  <div class="c c2">
    <div class="lbl">Tipo y n&uacute;mero de documento</div>
    <div class="val"><?= h($tipo_doc_m[$e['padre_tipo_doc']] ?? $e['padre_tipo_doc']) ?> <?= val($e['padre_num_doc']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">Tel&eacute;fono principal</div>
    <div class="val b"><?= val($e['padre_tel']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">Tel&eacute;fono alternativo</div>
    <div class="val"><?= val($e['padre_tel_alt']) ?></div>
  </div>
</div>
<div class="row">
  <div class="c c2">
    <div class="lbl">Correo electr&oacute;nico</div>
    <div class="val"><?= val($e['padre_email']) ?></div>
  </div>
  <div class="c c2">
    <div class="lbl">Direcci&oacute;n de residencia</div>
    <div class="val"><?= val($e['padre_direccion']) ?></div>
  </div>
  <div class="c">
    <div class="lbl">Ocupaci&oacute;n</div>
    <div class="val"><?= val($e['padre_ocupacion']) ?></div>
  </div>
</div>

<!-- 5. AUTORIZACIONES -->
<div class="sec">5. Autorizaciones Otorgadas</div>
<div class="auth-wrap">
  <div class="auth-row">
    <div class="auth-ico <?= $e['acepta_datos'] ? 'ok':'no' ?>"><?= $e['acepta_datos'] ? '&#10003;':'&#10007;' ?></div>
    <div class="auth-txt">
      <strong>Tratamiento de datos personales &mdash; Ley 1581 de 2012 (Habeas Data)</strong><br>
      Autoriza a ROBOTSchool para recolectar, almacenar y usar datos personales con fines acad&eacute;micos e institucionales.
      <?php if ($e['acepta_datos'] && $e['fecha_aceptacion']): ?>
        <span style="color:#64748b;"> &middot; Aceptado el <?= fd($e['fecha_aceptacion']) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="auth-row">
    <div class="auth-ico <?= $e['acepta_imagenes'] ? 'ok':'no' ?>"><?= $e['acepta_imagenes'] ? '&#10003;':'&#10007;' ?></div>
    <div class="auth-txt">
      <strong>Uso de im&aacute;genes y videos para fines institucionales de ROBOTSchool</strong><br>
      Autoriza capturar y publicar fotograf&iacute;as y videos del estudiante &uacute;nicamente en material institucional, redes sociales y web oficial. Las im&aacute;genes no se ceden a terceros ni se usan fuera del contexto ROBOTSchool.
    </div>
  </div>
</div>

<!-- 6. DECLARACI&Oacute;N -->
<div class="sec">6. Declaraci&oacute;n y Autorizaci&oacute;n de Firma</div>
<div class="legal">
  Yo, el/la firmante en calidad de padre, madre o acudiente legal del estudiante arriba mencionado, declaro que la
  informaci&oacute;n consignada en este documento es <strong>ver&iacute;dica y completa</strong>. Autorizo a
  <strong>Escuelas STEAM Colombia SAS &mdash; ROBOTSchool</strong> para verificar los datos suministrados y para tratar
  la informaci&oacute;n personal conforme a la <strong>Ley 1581 de 2012</strong> y sus decretos reglamentarios.
  Me comprometo a informar oportunamente cualquier cambio en los datos aqu&iacute; registrados, especialmente en lo
  concerniente a informaci&oacute;n m&eacute;dica relevante para la seguridad del estudiante durante las actividades acad&eacute;micas.
  Conozco y acepto el <strong>Reglamento Interno</strong> de ROBOTSchool Academy Learning y las obligaciones
  acad&eacute;micas y de pago del contrato de matr&iacute;cula. Esta hoja de vida hace parte integral del contrato de prestaci&oacute;n
  de servicios educativos suscrito con ROBOTSchool.
</div>

<!-- FIRMAS -->
<div class="firmas">
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre"><?= h($e['padre_nombre']) ?></div>
    <div class="fcargo">Padre / Madre / Acudiente</div>
    <div class="fdoc"><?= h($tipo_doc_m[$e['padre_tipo_doc']] ?? '') ?> <?= val($e['padre_num_doc']) ?></div>
  </div>
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre">Director(a) de Sede</div>
    <div class="fcargo">ROBOTSchool &mdash; <?= h($e['sede_nombre']) ?></div>
    <div class="fdoc">Sello institucional</div>
  </div>
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre"><?= h($e['nombre_completo']) ?></div>
    <div class="fcargo">Estudiante <?= $edad ? '('.$edad.')' : '' ?></div>
    <div class="fdoc">Firma si aplica por edad</div>
  </div>
</div>

<!-- PIE -->
<div class="pie">
  <div><strong>ROBOTSchool Academy Learning</strong> &middot; Escuelas STEAM Colombia SAS</div>
  <div>info@robotschool.com.co &middot; (57) 318 654 1859 &middot; robotschool.com.co</div>
  <div>Impreso: <?= date('d/m/Y H:i') ?> &middot; Documento confidencial</div>
</div>

</div><!-- /body -->
</div><!-- /page -->

</body>
</html>
