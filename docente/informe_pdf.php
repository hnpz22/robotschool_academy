<?php
// docente/informe_pdf.php &mdash; Informe de evaluaci&oacute;n con foto, imprimible como PDF
require_once __DIR__ . '/../config/config.php';
require_once ROOT . '/config/auth.php';
requireLogin();

if (!in_array($_SESSION['usuario_rol'], ['docente','admin_sede','admin_general','padre'])) {
    http_response_code(403); exit;
}

$matricula_id = (int)($_GET['matricula'] ?? 0);
if (!$matricula_id) { header('Location: ' . BASE_URL . 'docente/index.php'); exit; }

// Datos del estudiante y matr&iacute;cula
$stmt = $pdo->prepare("
    SELECT e.*, s.nombre AS sede_nombre, s.ciudad AS sede_ciudad,
        p.nombre_completo AS padre_nombre, p.telefono AS padre_tel, p.email AS padre_email,
        m.periodo, m.estado AS matricula_estado, m.id AS matricula_id,
        c.nombre AS curso_nombre, c.introduccion AS curso_intro,
        g.nombre AS grupo_nombre, g.dia_semana, g.hora_inicio, g.hora_fin
    FROM matriculas m
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN padres      p ON p.id = e.padre_id
    JOIN sedes       s ON s.id = m.sede_id
    JOIN grupos      g ON g.id = m.grupo_id
    JOIN cursos      c ON c.id = g.curso_id
    WHERE m.id = ?
");
$stmt->execute([$matricula_id]);
$est = $stmt->fetch();
if (!$est) { header('Location: ' . BASE_URL . 'docente/index.php'); exit; }

// Todas las evaluaciones de esta matr&iacute;cula
$evals = $pdo->prepare("
    SELECT ev.*, u.nombre AS docente_nombre,
        r.nombre AS rubrica_nombre, r.descripcion AS rubrica_desc,
        (SELECT SUM(ed.puntaje) FROM evaluacion_detalle ed WHERE ed.evaluacion_id=ev.id) AS total_obtenido,
        (SELECT SUM(rc.puntaje_max) FROM evaluacion_detalle ed JOIN rubrica_criterios rc ON rc.id=ed.criterio_id WHERE ed.evaluacion_id=ev.id) AS total_posible
    FROM evaluaciones ev
    JOIN rubricas r ON r.id = ev.rubrica_id
    JOIN usuarios u ON u.id = ev.docente_id
    WHERE ev.matricula_id = ?
    ORDER BY ev.fecha DESC
");
$evals->execute([$matricula_id]);
$evaluaciones = $evals->fetchAll();

// Calcular promedio general
$total_pct = 0; $n_evals = count($evaluaciones);
foreach ($evaluaciones as $ev) {
    if ($ev['total_posible'] > 0)
        $total_pct += round(($ev['total_obtenido'] / $ev['total_posible']) * 100);
}
$promedio = $n_evals > 0 ? round($total_pct / $n_evals) : null;

function fd($d) { return $d ? date('d/m/Y', strtotime($d)) : '&mdash;'; }
function val($v, $d='&mdash;') { return trim($v ?? '') ?: $d; }

$edad = '';
if ($est['fecha_nacimiento']) {
    $edad = (new DateTime($est['fecha_nacimiento']))->diff(new DateTime())->y . ' a&ntilde;os';
}
$dias_map = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles',
             'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];

// Avatar en base64 para PDF
$avatar_b64 = '';
if ($est['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$est['avatar'])) {
    $avatar_path = ROOT.'/uploads/estudiantes/'.$est['avatar'];
    $ext = strtolower(pathinfo($avatar_path, PATHINFO_EXTENSION));
    $mime = in_array($ext,['jpg','jpeg']) ? 'image/jpeg' : 'image/png';
    $avatar_b64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($avatar_path));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width"/>
<title>Informe &mdash; <?= h($est['nombre_completo']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#1a2234;background:#f0f2f5;}
.page{background:#fff;max-width:210mm;margin:0 auto;}

/* ENCABEZADO */
.hdr{background:#0f1623;padding:12px 18px;display:flex;justify-content:space-between;align-items:center;}
.hdr-left{display:flex;align-items:center;gap:10px;}
.hdr-logo{font-size:18px;font-weight:900;color:#F26522;letter-spacing:-.5px;line-height:1.1;}
.hdr-logo small{font-size:8px;font-weight:400;color:#8a99b8;display:block;}
.hdr-right{text-align:right;}
.hdr-right .tipo{font-size:13px;font-weight:900;color:#fff;letter-spacing:1px;}
.hdr-right .sub{font-size:8px;color:#8a99b8;}
.hdr-right .fecha{font-size:7.5px;color:#4a5a7a;margin-top:3px;}

/* PERFIL */
.perfil{background:#1E4DA1;padding:12px 18px;display:flex;align-items:center;gap:14px;}
.perfil-foto{width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.3);flex-shrink:0;}
.perfil-foto-placeholder{width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#1DA99A,#148a7d);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:900;color:#fff;flex-shrink:0;border:3px solid rgba(255,255,255,.3);}
.perfil-info{flex:1;}
.perfil-nombre{font-size:15px;font-weight:900;color:#fff;letter-spacing:.2px;}
.perfil-sub{font-size:8.5px;color:#c8d8ff;margin-top:3px;line-height:1.6;}
.perfil-right{text-align:right;}
.pct-grande{font-family:Arial;font-size:2.2rem;font-weight:900;line-height:1;}
.pct-lbl{font-size:7.5px;color:rgba(255,255,255,.6);margin-top:2px;}

/* BODY */
.body{padding:10px 16px;}
.sec{background:#1DA99A;color:#fff;font-size:7.5px;font-weight:900;text-transform:uppercase;letter-spacing:.9px;padding:3px 8px;margin:9px 0 6px;border-radius:3px;}
.row{display:flex;gap:4px;margin-bottom:4px;flex-wrap:wrap;}
.c{flex:1;min-width:70px;background:#F5F7FA;border:0.4px solid #dde3ef;border-radius:4px;padding:4px 6px;}
.c.full{flex:none;width:100%;}
.c .lbl{font-size:6.5px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:1px;}
.c .val{font-size:9.5px;color:#1a2234;line-height:1.3;}
.c .val.b{font-weight:700;}
.c.alerta{background:#fff0f1;border-left:3px solid #E8192C;}
.c.alerta .lbl{color:#991b1b;}
.c.alerta .val{color:#b91c1c;font-weight:700;}

/* EVALUACIONES */
.eval-card{border:0.5px solid #dde3ef;border-radius:8px;overflow:hidden;margin-bottom:8px;}
.eval-head{display:flex;align-items:center;justify-content:space-between;padding:6px 10px;flex-wrap:wrap;gap:4px;}
.eval-rubrica{font-size:9px;font-weight:700;color:#1a2234;}
.eval-meta{font-size:7.5px;color:#64748b;}
.eval-pct-box{text-align:center;padding:4px 10px;border-radius:6px;min-width:55px;}
.eval-pct-num{font-size:14px;font-weight:900;font-family:Arial;}
.eval-pct-lbl{font-size:6.5px;font-weight:600;}
.criterios-tbl{width:100%;border-collapse:collapse;}
.criterios-tbl th{background:#f1f5f9;font-size:7px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding:4px 8px;text-align:left;color:#64748b;}
.criterios-tbl td{padding:5px 8px;font-size:9px;border-bottom:.4px solid #e8ecf4;}
.criterios-tbl tr:last-child td{border-bottom:none;}
.estrellas{color:#F59E0B;font-size:9px;letter-spacing:1px;}
.barra-crit{height:4px;background:#e5e7eb;border-radius:2px;margin-top:2px;width:80px;display:inline-block;}
.barra-fill{height:100%;border-radius:2px;}
.obs-box{background:#f8fafc;border-top:.4px solid #e0e6f0;padding:5px 10px;font-size:8px;color:#64748b;font-style:italic;}

/* PROGRESO */
.progreso-barra{height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin:4px 0;}
.progreso-fill{height:100%;border-radius:4px;}

/* FIRMA */
.firmas{display:flex;margin-top:18px;border-top:1.5px solid #F26522;padding-top:4px;}
.fbox{flex:1;text-align:center;padding:0 8px;border-right:.5px solid #e0e6f0;}
.fbox:last-child{border-right:none;}
.flinea{border-top:1px solid #0f1623;margin:26px auto 4px;width:80%;}
.fnombre{font-size:8.5px;font-weight:700;}
.fcargo{font-size:7.5px;color:#64748b;margin-top:2px;}

/* PIE */
.pie{margin-top:8px;border-top:1px solid #e0e6f0;padding-top:5px;display:flex;justify-content:space-between;font-size:7px;color:#94a3b8;}

/* BOTONES */
.btn-bar{position:fixed;top:12px;right:16px;z-index:99;display:flex;gap:8px;}
.btn-print{background:#F26522;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:12px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(242,101,34,.4);display:flex;align-items:center;gap:6px;}
.btn-print:hover{background:#d4541a;}
.btn-back{background:#fff;color:#64748b;border:1px solid #dde3ef;border-radius:8px;padding:9px 13px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:5px;}

@media print{
  body{background:#fff;}
  .btn-bar{display:none!important;}
  .page{max-width:none;}
  @page{size:letter portrait;margin:1cm 1.3cm;}
  .sec,.perfil,.hdr{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .eval-head,.c.alerta{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
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
  <div class="hdr-left">
    <div class="hdr-logo">ROBOTSchool<small>Academy Learning &middot; Escuelas STEAM Colombia SAS</small></div>
  </div>
  <div class="hdr-right">
    <div class="tipo">INFORME DE EVALUACI&Oacute;N</div>
    <div class="sub">DESEMPE&Ntilde;O ACAD&Eacute;MICO &middot; DOCUMENTO OFICIAL</div>
    <div class="fecha">Generado: <?= date('d/m/Y H:i') ?></div>
  </div>
</div>

<!-- PERFIL CON FOTO -->
<div class="perfil">
  <?php if ($avatar_b64): ?>
    <img src="<?= $avatar_b64 ?>" class="perfil-foto" alt="Foto estudiante"/>
  <?php else: ?>
    <div class="perfil-foto-placeholder"><?= strtoupper(substr($est['nombre_completo'],0,2)) ?></div>
  <?php endif; ?>
  <div class="perfil-info">
    <div class="perfil-nombre"><?= strtoupper(h($est['nombre_completo'])) ?></div>
    <div class="perfil-sub">
      <?= h($est['tipo_doc']) ?> <?= val($est['numero_doc']) ?> &middot; <?= $edad ?><br>
      <?= h($est['curso_nombre']) ?> &middot; <?= h($est['grupo_nombre']) ?><br>
      <?= ($dias_map[$est['dia_semana']] ?? '') ?> <?= substr($est['hora_inicio'],0,5) ?>&ndash;<?= substr($est['hora_fin'],0,5) ?>
      &middot; <?= h($est['sede_nombre']) ?>
    </div>
  </div>
  <div class="perfil-right">
    <?php if ($promedio !== null):
      $col_p = $promedio>=80?'#dcfce7':($promedio>=60?'#fef9c3':'#fff0f1');
      $col_t = $promedio>=80?'#16a34a':($promedio>=60?'#ca8a04':'#E8192C');
    ?>
    <div class="eval-pct-box" style="background:<?= $col_p ?>;color:<?= $col_t ?>;">
      <div class="eval-pct-num"><?= $promedio ?>%</div>
      <div class="eval-pct-lbl">PROMEDIO<br>GENERAL</div>
    </div>
    <?php endif; ?>
    <div style="font-size:7px;color:rgba(255,255,255,.5);margin-top:4px;"><?= $n_evals ?> evaluaci&oacute;n<?= $n_evals!=1?'es':'' ?></div>
  </div>
</div>

<div class="body">

<!-- 1. DATOS ESTUDIANTE -->
<div class="sec">1. Datos del Estudiante</div>
<div class="row">
  <div class="c"><div class="lbl">Per&iacute;odo</div><div class="val"><?= val($est['periodo']) ?></div></div>
  <div class="c"><div class="lbl">Colegio</div><div class="val"><?= val($est['colegio']) ?></div></div>
  <div class="c"><div class="lbl">Grado</div><div class="val"><?= val($est['grado']) ?></div></div>
  <div class="c"><div class="lbl">Padre / Acudiente</div><div class="val b"><?= h($est['padre_nombre']) ?></div></div>
  <div class="c"><div class="lbl">Tel&eacute;fono</div><div class="val"><?= h($est['padre_tel']) ?></div></div>
</div>
<?php if (trim($est['alergias'] ?? '')): ?>
<div class="row">
  <div class="c full alerta"><div class="lbl">&#9888;&#65039; Alergias / Condiciones especiales</div><div class="val"><?= h($est['alergias']) ?></div></div>
</div>
<?php endif; ?>

<!-- 2. EVALUACIONES -->
<div class="sec">2. Evaluaciones del Per&iacute;odo</div>

<?php if (empty($evaluaciones)): ?>
  <div style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:10px;">
    No hay evaluaciones registradas para este estudiante en este per&iacute;odo.
  </div>
<?php else: ?>
  <?php foreach ($evaluaciones as $ev):
    $pct_ev = $ev['total_posible'] > 0 ? round(($ev['total_obtenido']/$ev['total_posible'])*100) : 0;
    $col_ev = $pct_ev>=80?'#16a34a':($pct_ev>=60?'#ca8a04':'#E8192C');
    $bg_ev  = $pct_ev>=80?'#dcfce7':($pct_ev>=60?'#fef9c3':'#fff0f1');
    $stars  = round($pct_ev/20);

    // Detalle criterios
    $det = $pdo->prepare("
        SELECT ed.puntaje, rc.criterio, rc.descripcion, rc.puntaje_max
        FROM evaluacion_detalle ed
        JOIN rubrica_criterios rc ON rc.id = ed.criterio_id
        WHERE ed.evaluacion_id = ?
        ORDER BY rc.orden
    ");
    $det->execute([$ev['id']]); $criterios = $det->fetchAll();
  ?>
  <div class="eval-card">
    <div class="eval-head" style="background:<?= $bg_ev ?>18;">
      <div>
        <div class="eval-rubrica"><?= h($ev['rubrica_nombre']) ?></div>
        <div class="eval-meta">
          Fecha: <?= fd($ev['fecha']) ?> &middot; Docente: <?= h($ev['docente_nombre']) ?>
        </div>
        <div style="color:#F59E0B;font-size:9px;margin-top:2px;"><?= str_repeat('&#9733;',$stars).str_repeat('&#9734;',5-$stars) ?></div>
      </div>
      <div class="eval-pct-box" style="background:<?= $bg_ev ?>;color:<?= $col_ev ?>;">
        <div class="eval-pct-num"><?= $pct_ev ?>%</div>
        <div class="eval-pct-lbl"><?= $ev['total_obtenido'] ?>/<?= $ev['total_posible'] ?> pts</div>
      </div>
    </div>
    <!-- Barra general -->
    <div style="padding:4px 10px;">
      <div class="progreso-barra"><div class="progreso-fill" style="width:<?= $pct_ev ?>%;background:<?= $col_ev ?>;"></div></div>
    </div>
    <!-- Criterios -->
    <?php if ($criterios): ?>
    <table class="criterios-tbl">
      <thead>
        <tr><th>Criterio</th><th style="text-align:center;">Puntaje</th><th style="text-align:center;">Estrellas</th><th>Progreso</th></tr>
      </thead>
      <tbody>
        <?php foreach ($criterios as $cr):
          $pct_cr = $cr['puntaje_max'] > 0 ? round(($cr['puntaje']/$cr['puntaje_max'])*100) : 0;
          $col_cr = $pct_cr>=80?'#16a34a':($pct_cr>=60?'#F59E0B':'#E8192C');
          $stars_cr = round($cr['puntaje_max']>0?($cr['puntaje']/$cr['puntaje_max'])*5:0);
        ?>
        <tr>
          <td>
            <div style="font-weight:600;"><?= h($cr['criterio']) ?></div>
            <?php if ($cr['descripcion']): ?><div style="font-size:7.5px;color:#64748b;"><?= h($cr['descripcion']) ?></div><?php endif; ?>
          </td>
          <td style="text-align:center;font-weight:700;color:<?= $col_cr ?>;"><?= $cr['puntaje'] ?>/<?= $cr['puntaje_max'] ?></td>
          <td style="text-align:center;color:#F59E0B;"><?= str_repeat('&#9733;',$stars_cr).str_repeat('&#9734;',5-$stars_cr) ?></td>
          <td>
            <span class="barra-crit"><span class="barra-fill" style="display:inline-block;height:4px;width:<?= $pct_cr ?>%;background:<?= $col_cr ?>;border-radius:2px;"></span></span>
            <span style="font-size:7.5px;color:<?= $col_cr ?>;font-weight:700;margin-left:3px;"><?= $pct_cr ?>%</span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
    <?php if (trim($ev['observaciones'] ?? '')): ?>
    <div class="obs-box">&#128172; <?= h($ev['observaciones']) ?></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- 3. OBSERVACIONES GENERALES -->
<div class="sec">3. Observaciones y Recomendaciones</div>
<div style="border:.5px solid #dde3ef;border-radius:6px;padding:8px 10px;min-height:30px;background:#f8fafc;font-size:8.5px;color:#64748b;">
  <?php if ($n_evals > 0): ?>
    <?php if ($promedio >= 80): ?>
      El estudiante demuestra un excelente desempe&ntilde;o acad&eacute;mico con un promedio de <strong><?= $promedio ?>%</strong>. Se recomienda continuar fortaleciendo su desarrollo en rob&oacute;tica y pensamiento computacional.
    <?php elseif ($promedio >= 60): ?>
      El estudiante muestra un desempe&ntilde;o aceptable con un promedio de <strong><?= $promedio ?>%</strong>. Se recomienda reforzar los criterios con menor puntaje para alcanzar un desempe&ntilde;o sobresaliente.
    <?php else: ?>
      El estudiante presenta un desempe&ntilde;o por mejorar con un promedio de <strong><?= $promedio ?>%</strong>. Se recomienda mayor dedicaci&oacute;n y pr&aacute;ctica en casa. Por favor comunicarse con el docente para estrategias de apoyo.
    <?php endif; ?>
  <?php else: ?>
    _____________________________________________________________________________________________________________
  <?php endif; ?>
</div>

<!-- FIRMAS -->
<div class="firmas">
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre"><?= h($est['padre_nombre']) ?></div>
    <div class="fcargo">Padre / Madre / Acudiente</div>
  </div>
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre">Director(a) Acad&eacute;mico(a)</div>
    <div class="fcargo">ROBOTSchool &mdash; <?= h($est['sede_nombre']) ?></div>
  </div>
  <div class="fbox">
    <div class="flinea"></div>
    <div class="fnombre">Docente / Tallerista</div>
    <div class="fcargo">Curso: <?= h($est['curso_nombre']) ?></div>
  </div>
</div>

<!-- PIE -->
<div class="pie">
  <div><strong>ROBOTSchool Academy Learning</strong> &middot; Escuelas STEAM Colombia SAS</div>
  <div>info@robotschool.com.co &middot; 318 654 1859</div>
  <div>Impreso: <?= date('d/m/Y H:i') ?> &middot; Confidencial</div>
</div>

</div><!-- /body -->
</div><!-- /page -->
</body>
</html>
