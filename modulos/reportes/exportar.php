<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$tipo    = $_GET['tipo']    ?? '';
$formato = $_GET['formato'] ?? 'html';
$sede    = (int)($_GET['sede'] ?? getSedeFiltro());

$tipos_validos = ['estudiantes','pagos','matriculas','evaluaciones','grupos','padres'];
if (!in_array($tipo, $tipos_validos)) {
    header('Location: ' . BASE_URL . 'modulos/reportes/index.php'); exit;
}

// &#9472;&#9472; CONSULTAS &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
$where_s = $sede ? 'AND s.id='.$sede : '';
$where_m = $sede ? 'AND m.sede_id='.$sede : '';
$where_e = $sede ? 'AND e.sede_id='.$sede : '';
$where_g = $sede ? 'AND g.sede_id='.$sede : '';

switch ($tipo) {

    case 'estudiantes':
        $titulo_rep = 'Reporte de Estudiantes';
        $columnas   = ['Nombre','Doc','Fecha Nac.','Edad','Colegio','Grado','Sede','Curso','Grupo','Horario','EPS','G. Sangu&iacute;neo','Seguro','Alergias','Estado'];
        $datos_raw  = $pdo->query("
            SELECT e.nombre_completo, CONCAT(e.tipo_doc,' ',COALESCE(e.numero_doc,'')) AS doc,
                DATE_FORMAT(e.fecha_nacimiento,'%d/%m/%Y') AS fecha_nac,
                TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) AS edad,
                COALESCE(e.colegio,'&mdash;') AS colegio,
                COALESCE(e.grado,'&mdash;') AS grado,
                s.nombre AS sede,
                COALESCE(c.nombre,'Sin matr&iacute;cula') AS curso,
                COALESCE(g.nombre,'&mdash;') AS grupo,
                CASE WHEN g.dia_semana IS NOT NULL THEN CONCAT(g.dia_semana,' ',SUBSTRING(g.hora_inicio,1,5),'&ndash;',SUBSTRING(g.hora_fin,1,5)) ELSE '&mdash;' END AS horario,
                COALESCE(e.eps,'&mdash;') AS eps,
                COALESCE(e.grupo_sanguineo,'&mdash;') AS grupo_sang,
                COALESCE(e.seguro_estudiantil,'&mdash;') AS seguro,
                COALESCE(e.alergias,'&mdash;') AS alergias,
                IF(e.activo,'Activo','Inactivo') AS estado
            FROM estudiantes e
            JOIN sedes s ON s.id = e.sede_id
            LEFT JOIN matriculas m ON m.estudiante_id=e.id AND m.estado='activa'
            LEFT JOIN grupos g ON g.id=m.grupo_id
            LEFT JOIN cursos c ON c.id=g.curso_id
            WHERE e.activo=1 $where_e
            ORDER BY s.nombre, e.nombre_completo
        ")->fetchAll(PDO::FETCH_NUM);
        break;

    case 'pagos':
        $titulo_rep = 'Reporte de Pagos y Cartera';
        $columnas   = ['Estudiante','Padre','Tel&eacute;fono','Email','Curso','Grupo','Total','Pagado','Saldo','Vencimiento','Estado'];
        $datos_raw  = $pdo->query("
            SELECT e.nombre_completo, pa.nombre_completo AS padre,
                pa.telefono, pa.email,
                c.nombre AS curso, g.nombre AS grupo,
                FORMAT(p.valor_total,0,'es_CO') AS total,
                FORMAT(p.valor_pagado,0,'es_CO') AS pagado,
                FORMAT(p.valor_total-p.valor_pagado,0,'es_CO') AS saldo,
                COALESCE(DATE_FORMAT(p.fecha_limite,'%d/%m/%Y'),'&mdash;') AS vencimiento,
                UPPER(p.estado) AS estado
            FROM pagos p
            JOIN matriculas m ON m.id=p.matricula_id
            JOIN estudiantes e ON e.id=m.estudiante_id
            JOIN padres pa ON pa.id=p.padre_id
            JOIN grupos g ON g.id=m.grupo_id
            JOIN cursos c ON c.id=g.curso_id
            WHERE 1=1 $where_m
            ORDER BY FIELD(p.estado,'vencido','pendiente','parcial','pagado'), e.nombre_completo
        ")->fetchAll(PDO::FETCH_NUM);
        break;

    case 'matriculas':
        $titulo_rep = 'Reporte de Matr&iacute;culas por Grupo';
        $columnas   = ['Estudiante','Edad','Colegio','Grado','Padre','Tel&eacute;fono','Curso','Grupo','D&iacute;a','Horario','Sede','Per&iacute;odo','Estado'];
        $datos_raw  = $pdo->query("
            SELECT e.nombre_completo,
                TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) AS edad,
                COALESCE(e.colegio,'&mdash;'), COALESCE(e.grado,'&mdash;'),
                pa.nombre_completo, pa.telefono,
                c.nombre AS curso, g.nombre AS grupo,
                g.dia_semana,
                CONCAT(SUBSTRING(g.hora_inicio,1,5),'&ndash;',SUBSTRING(g.hora_fin,1,5)) AS horario,
                s.nombre AS sede, m.periodo,
                UPPER(m.estado) AS estado
            FROM matriculas m
            JOIN estudiantes e ON e.id=m.estudiante_id
            JOIN padres pa ON pa.id=e.padre_id
            JOIN grupos g ON g.id=m.grupo_id
            JOIN cursos c ON c.id=g.curso_id
            JOIN sedes s ON s.id=m.sede_id
            WHERE m.estado='activa' $where_m
            ORDER BY c.nombre, g.dia_semana, e.nombre_completo
        ")->fetchAll(PDO::FETCH_NUM);
        break;

    case 'evaluaciones':
        $titulo_rep = 'Reporte de Evaluaciones';
        $columnas   = ['Estudiante','Curso','R&uacute;brica','Fecha','Puntaje','Total posible','%','Docente','Observaciones'];
        $datos_raw  = $pdo->query("
            SELECT e.nombre_completo,
                c.nombre AS curso, r.nombre AS rubrica,
                DATE_FORMAT(ev.fecha,'%d/%m/%Y') AS fecha,
                (SELECT SUM(ed.puntaje) FROM evaluacion_detalle ed WHERE ed.evaluacion_id=ev.id) AS obtenido,
                (SELECT SUM(rc.puntaje_max) FROM evaluacion_detalle ed JOIN rubrica_criterios rc ON rc.id=ed.criterio_id WHERE ed.evaluacion_id=ev.id) AS posible,
                CONCAT(ROUND(COALESCE((SELECT SUM(ed.puntaje) FROM evaluacion_detalle ed WHERE ed.evaluacion_id=ev.id),0) /
                    NULLIF((SELECT SUM(rc.puntaje_max) FROM evaluacion_detalle ed JOIN rubrica_criterios rc ON rc.id=ed.criterio_id WHERE ed.evaluacion_id=ev.id),0)*100,1),'%') AS porcentaje,
                u.nombre AS docente,
                COALESCE(ev.observaciones,'&mdash;') AS obs
            FROM evaluaciones ev
            JOIN matriculas m ON m.id=ev.matricula_id
            JOIN estudiantes e ON e.id=m.estudiante_id
            JOIN rubricas r ON r.id=ev.rubrica_id
            JOIN grupos g ON g.id=m.grupo_id
            JOIN cursos c ON c.id=g.curso_id
            JOIN usuarios u ON u.id=ev.docente_id
            WHERE 1=1 $where_m
            ORDER BY ev.fecha DESC, e.nombre_completo
        ")->fetchAll(PDO::FETCH_NUM);
        break;

    case 'grupos':
        $titulo_rep = 'Reporte de Ocupaci&oacute;n de Grupos';
        $columnas   = ['Curso','Grupo','Sede','D&iacute;a','Horario','Cupo real','Inscritos','Disponibles','% Ocupaci&oacute;n','Estado'];
        $datos_raw  = $pdo->query("
            SELECT c.nombre AS curso, g.nombre AS grupo, s.nombre AS sede,
                g.dia_semana,
                CONCAT(SUBSTRING(g.hora_inicio,1,5),'&ndash;',SUBSTRING(g.hora_fin,1,5)) AS horario,
                g.cupo_real,
                COUNT(m.id) AS inscritos,
                g.cupo_real - COUNT(m.id) AS disponibles,
                CONCAT(ROUND(COUNT(m.id)/NULLIF(g.cupo_real,0)*100,1),'%') AS ocupacion,
                IF(g.activo,'Activo','Inactivo') AS estado
            FROM grupos g
            JOIN cursos c ON c.id=g.curso_id
            JOIN sedes s ON s.id=g.sede_id
            LEFT JOIN matriculas m ON m.grupo_id=g.id AND m.estado='activa'
            WHERE 1=1 $where_g
            GROUP BY g.id
            ORDER BY c.nombre, g.dia_semana, g.hora_inicio
        ")->fetchAll(PDO::FETCH_NUM);
        break;

    case 'padres':
        $titulo_rep = 'Directorio de Padres y Contactos';
        $columnas   = ['Nombre','Tipo Doc','N&deg; Doc','Tel&eacute;fono','Tel. Alt.','Email','Direcci&oacute;n','Ocupaci&oacute;n','Datos','Im&aacute;genes','Hijos','Registro'];
        $datos_raw  = $pdo->query("
            SELECT pa.nombre_completo, pa.tipo_doc, COALESCE(pa.numero_doc,'&mdash;'),
                pa.telefono, COALESCE(pa.telefono_alt,'&mdash;'), pa.email,
                COALESCE(pa.direccion,'&mdash;'), COALESCE(pa.ocupacion,'&mdash;'),
                IF(pa.acepta_datos,'S&iacute;','No') AS datos,
                IF(pa.acepta_imagenes,'S&iacute;','No') AS imagenes,
                COUNT(e.id) AS hijos,
                DATE_FORMAT(pa.created_at,'%d/%m/%Y') AS registro
            FROM padres pa
            LEFT JOIN estudiantes e ON e.padre_id=pa.id AND e.activo=1
            WHERE 1=1
            GROUP BY pa.id
            ORDER BY pa.nombre_completo
        ")->fetchAll(PDO::FETCH_NUM);
        break;
}

// &#9472;&#9472; EXPORTAR CSV &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
if ($formato === 'csv') {
    $nombre_archivo = $tipo . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    // BOM para Excel
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $columnas, ';');
    foreach ($datos_raw as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}

// &#9472;&#9472; REPORTE HTML &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
$sede_nombre = '';
if ($sede) {
    $sn = $pdo->prepare("SELECT nombre FROM sedes WHERE id=?");
    $sn->execute([$sede]); $sede_nombre = $sn->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width"/>
<title><?= h($titulo_rep) ?> &mdash; ROBOTSchool</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:11px;background:#f0f2f5;color:#1a2234;}
.page{background:#fff;max-width:1200px;margin:1rem auto;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1);}
.rep-header{background:#0f1623;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;}
.rep-header .titulo{font-size:16px;font-weight:900;color:#fff;}
.rep-header .sub{font-size:9px;color:#8a99b8;margin-top:2px;}
.rep-header .meta{text-align:right;font-size:9px;color:#64748b;}
.toolbar{padding:10px 16px;background:#f8fafc;border-bottom:1px solid #e0e6f0;display:flex;gap:8px;flex-wrap:wrap;}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;text-decoration:none;border:none;}
.btn-orange{background:#F26522;color:#fff;box-shadow:0 3px 10px rgba(242,101,34,.3);}
.btn-gray{background:#fff;color:#64748b;border:1px solid #dde3ef;}
.btn:hover{opacity:.9;}
.rep-table{width:100%;border-collapse:collapse;}
.rep-table thead th{background:#1E4DA1;color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:8px 10px;text-align:left;white-space:nowrap;}
.rep-table tbody tr:nth-child(even){background:#f8fafc;}
.rep-table tbody tr:hover{background:#e6f7f5;}
.rep-table tbody td{padding:7px 10px;font-size:10.5px;border-bottom:.4px solid #e0e6f0;vertical-align:middle;}
.total-row{background:#f0f7ff!important;font-weight:700;}
.badge{font-size:8px;font-weight:800;padding:2px 7px;border-radius:20px;}
.b-verde{background:#dcfce7;color:#16a34a;}
.b-rojo{background:#fff0f1;color:#E8192C;}
.b-amari{background:#fef9c3;color:#ca8a04;}
.b-gris{background:#f1f5f9;color:#64748b;}
.summary{padding:10px 16px;background:#f8fafc;border-top:1px solid #e0e6f0;font-size:10px;color:#64748b;display:flex;justify-content:space-between;}
@media print{
  body{background:#fff;}
  .toolbar,.btn{display:none!important;}
  .page{box-shadow:none;border-radius:0;max-width:none;}
  @page{size:landscape;margin:1cm;}
}
</style>
</head>
<body>
<div class="page">
  <div class="rep-header">
    <div>
      <div class="titulo"><?= h($titulo_rep) ?></div>
      <div class="sub">
        ROBOTSchool Academy Learning
        <?= $sede_nombre ? ' &middot; ' . h($sede_nombre) : ' &middot; Todas las sedes' ?>
      </div>
    </div>
    <div class="meta">
      Generado: <?= date('d/m/Y H:i') ?><br>
      <?= count($datos_raw) ?> registros
    </div>
  </div>
  <div class="toolbar">
    <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=<?= $tipo ?>&formato=csv<?= $sede?"&sede=$sede":'' ?>"
       class="btn btn-orange"><i class="bi bi-file-earmark-spreadsheet-fill"></i> Descargar CSV</a>
    <button onclick="window.print()" class="btn btn-gray"><i class="bi bi-printer-fill"></i> Imprimir</button>
    <a href="<?= $U ?>modulos/reportes/index.php" class="btn btn-gray"><i class="bi bi-arrow-left"></i> Volver</a>
    <span style="margin-left:auto;font-size:10px;color:#94a3b8;align-self:center;">
      <?= count($datos_raw) ?> registros encontrados
    </span>
  </div>

  <?php if (empty($datos_raw)): ?>
    <div style="text-align:center;padding:3rem;color:#94a3b8;">
      <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:.8rem;opacity:.3;"></i>
      No hay datos para mostrar con los filtros actuales.
    </div>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table class="rep-table">
      <thead>
        <tr>
          <th>#</th>
          <?php foreach ($columnas as $col): ?>
            <th><?= h($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($datos_raw as $i => $fila): ?>
        <tr>
          <td style="color:#94a3b8;font-size:9px;"><?= $i+1 ?></td>
          <?php foreach ($fila as $j => $celda):
            // Colorear sem&aacute;foros
            $cel = h($celda);
            if ($tipo === 'pagos' && $j === 10) { // estado pago
              $map = ['PAGADO'=>'b-verde','VENCIDO'=>'b-rojo','PARCIAL'=>'b-amari','PENDIENTE'=>'b-amari','EXONERADO'=>'b-gris'];
              $cls = $map[$cel] ?? 'b-gris';
              $cel = "<span class='badge $cls'>$cel</span>";
            }
            if ($tipo === 'evaluaciones' && $j === 6) { // porcentaje
              $pct = (float)$celda;
              $col_e = $pct>=80?'#16a34a':($pct>=60?'#ca8a04':'#E8192C');
              $cel = "<strong style='color:$col_e;'>$cel</strong>";
            }
            if (($tipo === 'padres') && in_array($j, [8,9])) {
              $cls = $celda === 'S&iacute;' ? 'b-verde' : 'b-gris';
              $cel = "<span class='badge $cls'>$cel</span>";
            }
            if ($tipo === 'grupos' && $j === 6) { // disponibles
              $disp = (int)$celda;
              $col_d = $disp>3?'#16a34a':($disp>0?'#ca8a04':'#E8192C');
              $cel = "<strong style='color:$col_d;'>$cel</strong>";
            }
          ?>
            <td><?= $cel ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="summary">
    <span>Total: <strong><?= count($datos_raw) ?> registros</strong></span>
    <span>ROBOTSchool Academy Learning &middot; <?= date('d/m/Y H:i') ?></span>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
