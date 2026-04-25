<?php
// modulos/academico/informes/ver.php
// Informe acad&eacute;mico del estudiante por periodo &mdash; HTML imprimible como PDF
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireLogin();

// Acceso: coordinador, admin_sede, admin_general, docente de ese grupo, o padre de ese estudiante
$rol_actual = $_SESSION['usuario_rol'] ?? '';
$uid        = (int)$_SESSION['usuario_id'];

$matricula_id = (int)($_GET['matricula'] ?? 0);
if (!$matricula_id) { die('Matricula no especificada.'); }

// Datos completos de la matricula
$s = $pdo->prepare("
    SELECT m.id AS matricula_id, m.estado AS matricula_estado, m.periodo,
        m.fecha_matricula, m.observaciones AS matricula_obs,
        e.id AS estudiante_id, e.nombre_completo, e.numero_doc, e.tipo_doc,
        e.fecha_nacimiento, e.genero, e.colegio, e.grado, e.avatar,
        e.eps, e.grupo_sanguineo, e.alergias,
        p.id AS padre_id, p.nombre_completo AS padre_nombre,
        p.telefono AS padre_tel, p.email AS padre_email,
        c.id AS curso_id, c.nombre AS curso_nombre,
        c.introduccion AS curso_intro, c.objetivos AS curso_objetivos,
        c.tipo_valor, c.edad_min, c.edad_max,
        g.id AS grupo_id, g.nombre AS grupo_nombre, g.dia_semana,
        g.hora_inicio, g.hora_fin, g.modalidad, g.fecha_inicio, g.fecha_fin,
        s.nombre AS sede_nombre, s.ciudad AS sede_ciudad, s.direccion AS sede_direccion,
        s.telefono AS sede_tel
        FROM matriculas m
        JOIN estudiantes e ON e.id = m.estudiante_id
        JOIN padres      p ON p.id = e.padre_id
        JOIN grupos      g ON g.id = m.grupo_id
        JOIN cursos      c ON c.id = g.curso_id
        JOIN sedes       s ON s.id = m.sede_id
        WHERE m.id = ?
        LIMIT 1
");
$s->execute([$matricula_id]);
$M = $s->fetch();

if (!$M) { die('Matricula no encontrada.'); }

// Control de acceso
$acceso_ok = false;
if (in_array($rol_actual, ['admin_general','admin_sede','coordinador_pedagogico'])) {
    $acceso_ok = true;
}
if ($rol_actual === 'docente') {
    // Verificar que el docente tenga asignado este grupo
    $chk = $pdo->prepare("SELECT 1 FROM docente_grupos WHERE docente_id = ? AND grupo_id = ?");
    $chk->execute([$uid, $M['grupo_id']]);
    if ($chk->fetchColumn()) $acceso_ok = true;
}
if ($rol_actual === 'padre') {
    // Verificar que sea el padre del estudiante
    $chk = $pdo->prepare("SELECT 1 FROM estudiantes WHERE id = ? AND padre_id IN
                         (SELECT id FROM padres WHERE usuario_id = ?)");
    $chk->execute([$M['estudiante_id'], $uid]);
    if ($chk->fetchColumn()) $acceso_ok = true;
}
if (!$acceso_ok) { http_response_code(403); die('Acceso denegado.'); }

// Docente(s) del grupo
$docs = $pdo->prepare("
    SELECT u.id, u.nombre, u.email
    FROM docente_grupos dg
    JOIN usuarios u ON u.id = dg.docente_id
    WHERE dg.grupo_id = ?
");
$docs->execute([$M['grupo_id']]);
$docentes = $docs->fetchAll();

// ==================== ASISTENCIA ====================
$ast = $pdo->prepare("
    SELECT a.estado, COUNT(*) AS cnt
    FROM asistencia a
    JOIN sesiones ses ON ses.id = a.sesion_id
    WHERE a.matricula_id = ?
    GROUP BY a.estado
");
$ast->execute([$matricula_id]);
$asis_counts = ['presente'=>0,'tarde'=>0,'ausente'=>0,'excusa'=>0];
foreach ($ast->fetchAll() as $r) { $asis_counts[$r['estado']] = (int)$r['cnt']; }
$total_sesiones_registradas = array_sum($asis_counts);
$pct_asistencia = $total_sesiones_registradas > 0
    ? round(100 * ($asis_counts['presente'] + $asis_counts['tarde']) / $total_sesiones_registradas)
    : 0;

// Detalle de sesiones (opcional, ultimas 15)
$sds = $pdo->prepare("
    SELECT ses.fecha, ses.tema, a.estado, a.observacion
    FROM asistencia a
    JOIN sesiones ses ON ses.id = a.sesion_id
    WHERE a.matricula_id = ?
    ORDER BY ses.fecha DESC
    LIMIT 15
");
$sds->execute([$matricula_id]);
$sesiones_detalle = $sds->fetchAll();
$sesiones_detalle = array_reverse($sesiones_detalle); // mostrar cronologico

// ==================== EVALUACIONES ====================
$eval = $pdo->prepare("
    SELECT e.id AS eval_id, e.fecha, e.observaciones,
        r.id AS rubrica_id, r.nombre AS rubrica_nombre
    FROM evaluaciones e
    JOIN rubricas r ON r.id = e.rubrica_id
    WHERE e.matricula_id = ?
    ORDER BY e.fecha ASC
");
$eval->execute([$matricula_id]);
$evaluaciones = $eval->fetchAll();

// Para cada evaluacion traer sus detalles con criterios
$eval_detalle = [];
$promedios_criterio = []; // criterio_nombre => [suma_pct, n]
foreach ($evaluaciones as $ev) {
    $ds = $pdo->prepare("
        SELECT rc.criterio, rc.puntaje_max, ed.puntaje
        FROM evaluacion_detalle ed
        JOIN rubrica_criterios rc ON rc.id = ed.criterio_id
        WHERE ed.evaluacion_id = ?
        ORDER BY rc.orden
    ");
    $ds->execute([$ev['eval_id']]);
    $det = $ds->fetchAll();
    $eval_detalle[$ev['eval_id']] = $det;

    foreach ($det as $d) {
        $crit = $d['criterio'];
        $pct  = $d['puntaje_max'] > 0 ? 100 * $d['puntaje'] / $d['puntaje_max'] : 0;
        if (!isset($promedios_criterio[$crit])) $promedios_criterio[$crit] = ['suma'=>0,'n'=>0];
        $promedios_criterio[$crit]['suma'] += $pct;
        $promedios_criterio[$crit]['n']++;
    }
}

// Calcular promedios finales por criterio
$prom_final = [];
foreach ($promedios_criterio as $crit => $data) {
    $prom_final[$crit] = $data['n'] > 0 ? round($data['suma'] / $data['n']) : 0;
}
// Promedio general
$prom_general = !empty($prom_final) ? round(array_sum($prom_final) / count($prom_final)) : 0;

// ==================== TEMAS Y ACTIVIDADES DEL CURSO ====================
$temas = $pdo->prepare("
    SELECT t.id, t.nombre, t.descripcion,
        (SELECT COUNT(*) FROM actividades a WHERE a.tema_id = t.id AND a.activa = 1) AS total_actividades
    FROM temas t
    WHERE t.curso_id = ? AND t.activo = 1
    ORDER BY t.orden, t.nombre
");
$temas->execute([$M['curso_id']]);
$lista_temas = $temas->fetchAll();

// ==================== HELPERS Y MAPAS ====================
$dias_map   = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles','jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];
$genero_m   = ['masculino'=>'Masculino','femenino'=>'Femenino','otro'=>'Otro','prefiero_no_decir'=>'No especificado'];
$tipo_doc_m = ['TI'=>'Tarjeta de Identidad','RC'=>'Registro Civil','PP'=>'Pasaporte','CE'=>'C&eacute;dula Extranjer&iacute;a'];

function val($v, $d='&mdash;') { return trim($v ?? '') ?: $d; }
function fd($d) { return $d ? date('d/m/Y', strtotime($d)) : '&mdash;'; }

$edad = '';
if ($M['fecha_nacimiento']) {
    $edad = (new DateTime($M['fecha_nacimiento']))->diff(new DateTime())->y . ' a&ntilde;os';
}

$horario_str = '';
if ($M['dia_semana'] && $M['hora_inicio']) {
    $horario_str = ($dias_map[$M['dia_semana']] ?? $M['dia_semana'])
                 . ' &middot; ' . substr($M['hora_inicio'],0,5)
                 . ' &ndash; ' . substr($M['hora_fin'],0,5);
}

// Escala de valoracion (sobre 100%)
function calificar($pct) {
    if ($pct >= 90) return ['Superior', '#0B7A3E'];
    if ($pct >= 75) return ['Alto',     '#1DA99A'];
    if ($pct >= 60) return ['B&aacute;sico',   '#F2A623'];
    return ['Bajo',     '#E8192C'];
}
$escala_info = calificar($prom_general);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width"/>
<title>Informe Acad&eacute;mico &mdash; <?= h($M['nombre_completo']) ?></title>
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
.nombre-strip{background:#1E4DA1;padding:9px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px;}
.nombre-strip .avatar{width:52px;height:52px;border-radius:50%;background:#fff;flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:900;color:#1E4DA1;}
.nombre-strip .avatar img{width:100%;height:100%;object-fit:cover;}
.nombre-strip .nombre-data{flex:1;}
.nombre-strip .nombre{font-size:14px;font-weight:900;color:#fff;letter-spacing:.3px;}
.nombre-strip .subinfo{font-size:8.5px;color:#c8d8ff;margin-top:2px;}
.nombre-strip .info{font-size:8.5px;color:#c8d8ff;text-align:right;line-height:1.5;}
.nombre-strip .info strong{color:#fff;}

/* BODY */
.body{padding:12px 18px;}

/* SECCION */
.sec{background:#1DA99A;color:#fff;font-size:8px;font-weight:900;text-transform:uppercase;letter-spacing:.9px;padding:4px 10px;margin:10px 0 7px;border-radius:3px;}
.sec:first-child{margin-top:0;}

/* GRIDS */
.row{display:flex;gap:5px;margin-bottom:5px;flex-wrap:wrap;}
.c{flex:1;min-width:80px;background:#F5F7FA;border:0.4px solid #dde3ef;border-radius:4px;padding:5px 7px;}
.c.c2{flex:2;} .c.c3{flex:3;} .c.full{flex:none;width:100%;}
.c .lbl{font-size:7px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;}
.c .val{font-size:10px;color:#1a2234;line-height:1.3;}
.c .val.b{font-weight:700;}

/* ASISTENCIA */
.asis-grid{display:flex;gap:5px;margin-bottom:6px;}
.asis-box{flex:1;text-align:center;background:#F5F7FA;border:0.4px solid #dde3ef;border-radius:4px;padding:6px;}
.asis-box.presente{background:#e6f7f5;border-color:#a7ebe5;}
.asis-box.tarde   {background:#fff7e0;border-color:#ffe29c;}
.asis-box.ausente {background:#fff0f1;border-color:#fca5a5;}
.asis-box.excusa  {background:#eef2ff;border-color:#c7d2fe;}
.asis-box .num{font-size:18px;font-weight:900;line-height:1;}
.asis-box.presente .num{color:#0d6e5f;}
.asis-box.tarde    .num{color:#b85f00;}
.asis-box.ausente  .num{color:#991b1b;}
.asis-box.excusa   .num{color:#3730a3;}
.asis-box .lbl-a{font-size:8px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:3px;}

.barra-asis-wrap{margin-top:4px;background:#F5F7FA;border:0.4px solid #dde3ef;border-radius:4px;padding:6px 8px;}
.barra-asis-wrap .tp{display:flex;justify-content:space-between;font-size:8px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;}
.barra-asis-wrap .tp strong{color:#1a2234;font-size:10px;}
.barra-asis{background:#fff;border-radius:3px;height:9px;overflow:hidden;border:.5px solid #dde3ef;}
.barra-asis .fill{height:100%;border-radius:2px;}

/* EVALUACIONES */
.crit-wrap{background:#F5F7FA;border:.4px solid #dde3ef;border-radius:5px;padding:7px 10px;margin-bottom:4px;}
.crit-line{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:3px;}
.crit-line .nom{font-size:10px;font-weight:700;color:#1a2234;flex:1;}
.crit-line .pct{font-size:10.5px;font-weight:900;min-width:38px;text-align:right;}
.crit-bar{background:#fff;border:.4px solid #dde3ef;height:6px;border-radius:3px;overflow:hidden;}
.crit-bar .fill{height:100%;}

/* PROMEDIO GENERAL */
.prom-general{display:flex;align-items:center;gap:10px;background:#1DA99A;color:#fff;padding:9px 13px;border-radius:5px;margin-top:6px;}
.prom-general .num{font-size:26px;font-weight:900;line-height:1;}
.prom-general .lbl{font-size:8px;text-transform:uppercase;letter-spacing:.5px;opacity:.9;}
.prom-general .lvl{background:rgba(255,255,255,.22);padding:3px 9px;border-radius:10px;font-size:9px;font-weight:800;}

/* TABLA SESIONES */
.tbl{width:100%;border-collapse:collapse;font-size:9px;margin-top:2px;}
.tbl th{background:#F5F7FA;border:.4px solid #dde3ef;padding:3px 6px;text-align:left;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;font-size:7.5px;}
.tbl td{border:.4px solid #e8ecf4;padding:3px 6px;color:#1a2234;}
.tbl .est-p{color:#0d6e5f;font-weight:700;}
.tbl .est-t{color:#b85f00;font-weight:700;}
.tbl .est-a{color:#991b1b;font-weight:700;}
.tbl .est-e{color:#3730a3;font-weight:700;}

/* TEMAS */
.tema-item{background:#F5F7FA;border:.4px solid #dde3ef;border-left:2.5px solid #7c3aed;border-radius:4px;padding:5px 9px;margin-bottom:3px;}
.tema-item .nm{font-size:9.5px;font-weight:700;color:#1a2234;}
.tema-item .desc{font-size:8.5px;color:#64748b;margin-top:1px;line-height:1.4;}
.tema-item .act-count{font-size:7.5px;color:#7c3aed;font-weight:700;margin-top:2px;}

/* ESCALA DE VALORACION */
.escala-wrap{display:grid;grid-template-columns:repeat(4,1fr);gap:3px;}
.escala-box{padding:5px 8px;border-radius:4px;text-align:center;}
.escala-box .rango{font-size:11px;font-weight:900;line-height:1;}
.escala-box .etq{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;}
.esc-sup{background:#d1f5e3;color:#0B7A3E;}
.esc-alt{background:#d0f3ed;color:#0d6e5f;}
.esc-bas{background:#fff2d6;color:#8e5a0c;}
.esc-baj{background:#fde3e4;color:#991b1b;}

/* OBSERVACIONES */
.obs-box{background:#fefce8;border:.5px solid #fde047;border-left:3px solid #ca8a04;border-radius:5px;padding:9px 12px;margin-top:3px;}
.obs-box .txt{font-size:10px;color:#1a2234;line-height:1.55;}
.obs-box .meta{font-size:7.5px;color:#a16207;margin-top:4px;font-style:italic;}

/* FIRMAS */
.firmas{display:flex;margin-top:25px;border-top:1.5px solid #F26522;padding-top:4px;}
.fbox{flex:1;text-align:center;padding:0 10px;border-right:.5px solid #e0e6f0;}
.fbox:last-child{border-right:none;}
.flinea{border-top:1px solid #0f1623;margin:28px auto 5px;width:80%;}
.fnombre{font-size:9px;font-weight:700;color:#1a2234;}
.fcargo{font-size:8px;color:#64748b;margin-top:2px;}

/* PIE */
.pie{margin-top:10px;border-top:1px solid #e0e6f0;padding-top:6px;display:flex;justify-content:space-between;font-size:7px;color:#94a3b8;}
.pie strong{color:#64748b;}

/* BOTON */
.btn-bar{position:fixed;top:12px;right:16px;z-index:99;display:flex;gap:8px;}
.btn-print{background:#F26522;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:12px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(242,101,34,.4);}
.btn-print:hover{background:#d4541a;}
.btn-back{background:#fff;color:#64748b;border:1px solid #dde3ef;border-radius:8px;padding:9px 13px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;}

@media print{
  body{background:#fff;}
  .btn-bar{display:none!important;}
  .page{max-width:none;box-shadow:none;}
  @page{size:letter;margin:1.2cm 1.5cm;}
  .sec,.nombre-strip,.hdr,.prom-general,
  .asis-box,.obs-box,.tema-item,.crit-wrap,
  .esc-sup,.esc-alt,.esc-bas,.esc-baj{
    -webkit-print-color-adjust:exact;print-color-adjust:exact;
  }
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
    <div class="tipo">INFORME ACAD&Eacute;MICO</div>
    <div class="sub">PER&Iacute;ODO <?= h($M['periodo']) ?> &mdash; CONFIDENCIAL</div>
    <div class="fecha">Generado: <?= date('d/m/Y H:i') ?></div>
  </div>
</div>

<!-- NOMBRE + FOTO -->
<div class="nombre-strip">
  <div class="avatar">
    <?php if ($M['avatar'] && file_exists(ROOT . '/uploads/estudiantes/' . $M['avatar'])): ?>
      <img src="<?= BASE_URL ?>uploads/estudiantes/<?= h($M['avatar']) ?>" alt=""/>
    <?php else:
      $ini = strtoupper(substr($M['nombre_completo'], 0, 1));
      echo h($ini);
    endif; ?>
  </div>
  <div class="nombre-data">
    <div class="nombre"><?= h($M['nombre_completo']) ?></div>
    <div class="subinfo">
      <?= h($M['curso_nombre']) ?> &middot; <?= h($M['grupo_nombre']) ?> &middot; <?= h($M['sede_nombre']) ?>
    </div>
  </div>
  <div class="info">
    <strong>Matr&iacute;cula:</strong> <?= strtoupper(h($M['matricula_estado'])) ?><br>
    <strong>Per&iacute;odo:</strong> <?= h($M['periodo']) ?><br>
    Doc: <?= h(($tipo_doc_m[$M['tipo_doc']] ?? '') . ' ' . val($M['numero_doc'], '&mdash;')) ?>
  </div>
</div>

<div class="body">

<!-- DATOS DEL ESTUDIANTE Y CURSO -->
<div class="sec">1. Informaci&oacute;n del estudiante y curso</div>
<div class="row">
  <div class="c c2"><div class="lbl">Nombre completo</div><div class="val b"><?= h($M['nombre_completo']) ?></div></div>
  <div class="c"><div class="lbl">Edad</div><div class="val"><?= val($edad) ?></div></div>
  <div class="c"><div class="lbl">G&eacute;nero</div><div class="val"><?= h($genero_m[$M['genero']] ?? '&mdash;') ?></div></div>
</div>
<div class="row">
  <div class="c"><div class="lbl">Fecha nacimiento</div><div class="val"><?= fd($M['fecha_nacimiento']) ?></div></div>
  <div class="c c2"><div class="lbl">Colegio</div><div class="val"><?= h(val($M['colegio'])) ?></div></div>
  <div class="c"><div class="lbl">Grado</div><div class="val"><?= h(val($M['grado'])) ?></div></div>
</div>
<div class="row">
  <div class="c c2"><div class="lbl">Acudiente</div><div class="val b"><?= h($M['padre_nombre']) ?></div></div>
  <div class="c"><div class="lbl">Tel&eacute;fono acudiente</div><div class="val"><?= h(val($M['padre_tel'])) ?></div></div>
</div>
<div class="row">
  <div class="c c2"><div class="lbl">Curso</div><div class="val b"><?= h($M['curso_nombre']) ?></div></div>
  <div class="c c2"><div class="lbl">Horario</div><div class="val"><?= val($horario_str) ?></div></div>
</div>
<div class="row">
  <div class="c"><div class="lbl">Sede</div><div class="val"><?= h($M['sede_nombre']) ?></div></div>
  <div class="c c2"><div class="lbl">Tallerista(s)</div><div class="val b">
    <?php if (empty($docentes)): ?>&mdash;<?php else:
      echo h(implode(', ', array_map(fn($d) => $d['nombre'], $docentes)));
    endif; ?>
  </div></div>
  <div class="c"><div class="lbl">Modalidad</div><div class="val"><?= h(ucfirst($M['modalidad'] ?? '&mdash;')) ?></div></div>
</div>

<!-- ASISTENCIA -->
<div class="sec">2. Asistencia</div>

<div class="asis-grid">
  <div class="asis-box presente">
    <div class="num"><?= $asis_counts['presente'] ?></div>
    <div class="lbl-a">Presente</div>
  </div>
  <div class="asis-box tarde">
    <div class="num"><?= $asis_counts['tarde'] ?></div>
    <div class="lbl-a">Tardanza</div>
  </div>
  <div class="asis-box ausente">
    <div class="num"><?= $asis_counts['ausente'] ?></div>
    <div class="lbl-a">Ausente</div>
  </div>
  <div class="asis-box excusa">
    <div class="num"><?= $asis_counts['excusa'] ?></div>
    <div class="lbl-a">Excusa</div>
  </div>
</div>

<div class="barra-asis-wrap">
  <div class="tp">
    <span>Porcentaje de asistencia</span>
    <span><strong><?= $pct_asistencia ?>%</strong> &middot; <?= $total_sesiones_registradas ?> sesiones</span>
  </div>
  <div class="barra-asis">
    <?php
    $col_asis = $pct_asistencia >= 80 ? '#0d6e5f' : ($pct_asistencia >= 60 ? '#b85f00' : '#991b1b');
    ?>
    <div class="fill" style="width:<?= min($pct_asistencia, 100) ?>%;background:<?= $col_asis ?>;"></div>
  </div>
</div>

<?php if (!empty($sesiones_detalle)): ?>
<table class="tbl" style="margin-top:6px;">
  <thead>
    <tr>
      <th style="width:80px;">Fecha</th>
      <th>Tema de la sesi&oacute;n</th>
      <th style="width:70px;">Estado</th>
      <th>Observaci&oacute;n</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($sesiones_detalle as $sd):
      $clase = 'est-' . substr($sd['estado'], 0, 1);
      $est_txt = ucfirst($sd['estado']);
    ?>
    <tr>
      <td><?= fd($sd['fecha']) ?></td>
      <td><?= h(val($sd['tema'], '&mdash;')) ?></td>
      <td class="<?= $clase ?>"><?= h($est_txt) ?></td>
      <td style="color:#64748b;"><?= h(val($sd['observacion'], '')) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<!-- EVALUACIONES -->
<div class="sec">3. Evaluaciones por r&uacute;brica</div>

<?php if (empty($prom_final)): ?>
  <div class="c full"><div class="val">No hay evaluaciones registradas en este per&iacute;odo.</div></div>
<?php else: ?>

  <?php foreach ($prom_final as $crit => $pct):
    $col = $pct >= 75 ? '#0d6e5f' : ($pct >= 60 ? '#b85f00' : '#991b1b');
  ?>
  <div class="crit-wrap">
    <div class="crit-line">
      <div class="nom"><?= h($crit) ?></div>
      <div class="pct" style="color:<?= $col ?>;"><?= $pct ?>%</div>
    </div>
    <div class="crit-bar"><div class="fill" style="width:<?= min($pct, 100) ?>%;background:<?= $col ?>;"></div></div>
  </div>
  <?php endforeach; ?>

  <div class="prom-general">
    <div style="flex:1;">
      <div class="lbl">Promedio general del per&iacute;odo</div>
      <div style="display:flex;align-items:baseline;gap:8px;margin-top:3px;">
        <div class="num"><?= $prom_general ?>%</div>
        <div class="lvl"><?= $escala_info[0] ?></div>
      </div>
    </div>
    <div style="text-align:right;font-size:9px;opacity:.9;">
      Basado en <?= count($evaluaciones) ?> evaluaci&oacute;n<?= count($evaluaciones) != 1 ? 'es' : '' ?><br>
      <?= count($prom_final) ?> criterio<?= count($prom_final) != 1 ? 's' : '' ?> distinto<?= count($prom_final) != 1 ? 's' : '' ?>
    </div>
  </div>

<?php endif; ?>

<!-- OBSERVACIONES DEL DOCENTE -->
<?php
// Observaciones consolidadas: todas las evaluaciones con texto
$obs_list = array_filter($evaluaciones, fn($e) => trim($e['observaciones'] ?? '') !== '');
?>
<?php if (!empty($obs_list)): ?>
<div class="sec">4. Observaciones del docente</div>
<?php foreach ($obs_list as $ob): ?>
<div class="obs-box">
  <div class="txt"><?= nl2br(h($ob['observaciones'])) ?></div>
  <div class="meta">Evaluaci&oacute;n del <?= fd($ob['fecha']) ?> &middot; <?= h($ob['rubrica_nombre']) ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- TEMAS Y ACTIVIDADES -->
<?php if (!empty($lista_temas)): ?>
<div class="sec">5. Temas y actividades desarrolladas</div>
<?php foreach ($lista_temas as $t): ?>
<div class="tema-item">
  <div class="nm"><?= h($t['nombre']) ?></div>
  <?php if ($t['descripcion']): ?><div class="desc"><?= h($t['descripcion']) ?></div><?php endif; ?>
  <div class="act-count">&#9679; <?= (int)$t['total_actividades'] ?> actividad<?= $t['total_actividades'] != 1 ? 'es' : '' ?> en el tema</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ESCALA DE VALORACION -->
<div class="sec">6. Escala de valoraci&oacute;n</div>
<div class="escala-wrap">
  <div class="escala-box esc-sup">
    <div class="rango">90&ndash;100%</div>
    <div class="etq">Superior</div>
  </div>
  <div class="escala-box esc-alt">
    <div class="rango">75&ndash;89%</div>
    <div class="etq">Alto</div>
  </div>
  <div class="escala-box esc-bas">
    <div class="rango">60&ndash;74%</div>
    <div class="etq">B&aacute;sico</div>
  </div>
  <div class="escala-box esc-baj">
    <div class="rango">0&ndash;59%</div>
    <div class="etq">Bajo</div>
  </div>
</div>
<div style="font-size:8px;color:#64748b;margin-top:5px;text-align:justify;line-height:1.5;">
  <strong>Nota interpretativa:</strong> Los puntajes representan el desempe&ntilde;o del estudiante en los criterios de cada r&uacute;brica de evaluaci&oacute;n aplicada durante el per&iacute;odo acad&eacute;mico. El <strong>promedio general</strong> es el promedio aritm&eacute;tico de los porcentajes obtenidos por criterio.
</div>

<!-- FIRMAS -->
<div class="firmas">
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre"><?= h(!empty($docentes) ? $docentes[0]['nombre'] : 'Tallerista') ?></div>
    <div class="fcargo">Tallerista &middot; <?= h($M['curso_nombre']) ?></div>
  </div>
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre">Coordinaci&oacute;n pedag&oacute;gica</div>
    <div class="fcargo">ROBOTSchool Academy Learning</div>
  </div>
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre"><?= h($M['padre_nombre']) ?></div>
    <div class="fcargo">Recibido por acudiente</div>
  </div>
</div>

<!-- PIE -->
<div class="pie">
  <div>
    <strong>ROBOTSchool</strong> &middot; Escuelas STEAM Colombia SAS &middot;
    <?= h($M['sede_direccion'] ?? '&mdash;') ?>
  </div>
  <div>
    Matr&iacute;cula #<?= (int)$M['matricula_id'] ?> &middot;
    P&aacute;gina 1
  </div>
</div>

</div><!-- /body -->
</div><!-- /page -->

</body>
</html>
