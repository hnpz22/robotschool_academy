<?php
// portal/index.php &mdash; Portal de Padres ROBOTSchool Academy Learning
require_once __DIR__ . '/../config/config.php';

// Verificar que sea un padre logueado
if (empty($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'padre') {
    header('Location: ' . BASE_URL . 'portal/login.php');
    exit;
}

$U = BASE_URL;

// Obtener datos del padre
$stmt = $pdo->prepare("SELECT p.* FROM padres p WHERE p.usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$padre = $stmt->fetch();
if (!$padre) { session_destroy(); header('Location: '.$U.'portal/login.php'); exit; }

// Hijos del padre con sus matr&iacute;culas activas
$hijos = $pdo->prepare("
    SELECT e.*,
        s.nombre AS sede_nombre,
        (SELECT COUNT(*) FROM matriculas m WHERE m.estudiante_id=e.id AND m.estado='activa') AS matriculas_activas
    FROM estudiantes e
    JOIN sedes s ON s.id = e.sede_id
    WHERE e.padre_id = ? AND e.activo = 1
    ORDER BY e.nombre_completo
");
$hijos->execute([$padre['id']]);
$hijos = $hijos->fetchAll();

// Matr&iacute;culas activas de todos los hijos
$matriculas = $pdo->prepare("
    SELECT m.*, e.nombre_completo AS estudiante, e.avatar,
        c.nombre AS curso, c.introduccion,
        g.nombre AS grupo, g.dia_semana, g.hora_inicio, g.hora_fin, g.modalidad,
        s.nombre AS sede_nombre,
        p.estado AS pago_estado, p.valor_total, p.valor_pagado,
        (p.valor_total - p.valor_pagado) AS saldo,
        p.fecha_limite
    FROM matriculas m
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN grupos g ON g.id = m.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    JOIN sedes s ON s.id = m.sede_id
    LEFT JOIN pagos p ON p.matricula_id = m.id
    WHERE e.padre_id = ? AND m.estado IN ('activa','pre_inscrito')
    ORDER BY e.nombre_completo, m.created_at DESC
");
$matriculas->execute([$padre['id']]);
$matriculas = $matriculas->fetchAll();

// Pagos pendientes/vencidos
$alertas_pago = array_filter($matriculas, fn($m) =>
    in_array($m['pago_estado'] ?? '', ['pendiente','vencido','parcial'])
);

$dias = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Mi&eacute;rcoles',
         'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'S&aacute;bado','domingo'=>'Domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Portal Padres &mdash; ROBOTSchool Academy</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<style>
:root{--orange:#F26522;--orange-d:#d4541a;--blue:#1E4DA1;--dark:#0f1623;--gray:#F5F7FA;--text:#1a2234;--muted:#64748b;--border:#e0e6f0;--teal:#1DA99A;--teal-d:#148a7d;--teal-l:#e6f7f5;--red:#E8192C;--red-l:#fff0f1;--white:#fff;}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito',sans-serif;color:var(--text);background:var(--gray);min-height:100vh;}
h1,h2,h3,h4,h5,h6{font-family:'Poppins',sans-serif;font-weight:700}

/* NAVBAR */
.portal-nav{background:var(--dark);padding:.7rem 0;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.2);}
.portal-nav img{height:38px;}
.nav-user{display:flex;align-items:center;gap:.6rem;color:rgba(255,255,255,.8);font-size:.82rem;min-width:0;overflow:hidden;}
.nav-user strong{color:#fff;font-size:.85rem;}
.btn-nav-out{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.7);border-radius:8px;padding:.3rem .8rem;font-size:.75rem;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;}
.btn-nav-out:hover{background:var(--red);border-color:var(--red);color:#fff;}

/* TABS */
.portal-tabs{background:#fff;border-bottom:1px solid var(--border);padding:0 1rem;}
.tab-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.9rem 1.2rem;font-size:.84rem;font-weight:700;color:var(--muted);border:none;background:none;cursor:pointer;border-bottom:3px solid transparent;transition:all .2s;white-space:nowrap;}
.tab-btn:hover{color:var(--orange);}
.tab-btn.active{color:var(--orange);border-bottom-color:var(--orange);}
.tab-content{display:none;padding:1.5rem 1rem;max-width:900px;margin:0 auto;}
.tab-content.active{display:block;}

/* CARDS */
.pcard{background:#fff;border-radius:16px;border:1px solid var(--border);padding:1.3rem;margin-bottom:1rem;transition:box-shadow .2s;}
.pcard:hover{box-shadow:0 4px 20px rgba(0,0,0,.07);}
.pcard-title{font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:.9rem;display:flex;align-items:center;gap:.4rem;}
.pcard-title i{color:var(--orange);}

/* HIJO CARD */
.hijo-card{display:flex;align-items:center;gap:1rem;padding:1rem;background:var(--gray);border-radius:12px;margin-bottom:.7rem;}
.hijo-avatar{width:54px;height:54px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid var(--teal-l);}
.hijo-avatar-placeholder{width:54px;height:54px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:800;color:#fff;flex-shrink:0;}

/* MATR&Iacute;CULA CARD */
.mat-card{border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:1rem;}
.mat-card-head{background:linear-gradient(135deg,var(--dark),#1e3a5f);padding:1rem 1.2rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;flex-wrap:wrap;}
.mat-card-head h4{font-size:.95rem;font-weight:800;color:#fff;margin:0;}
.mat-card-head small{font-size:.72rem;color:rgba(255,255,255,.6);}
.mat-card-body{padding:1.1rem 1.2rem;}
.info-row{display:flex;align-items:center;gap:.5rem;font-size:.82rem;margin-bottom:.5rem;}
.info-row i{color:var(--orange);width:16px;flex-shrink:0;}

/* SEM&Aacute;FORO */
.sem{font-size:.72rem;font-weight:800;padding:.25rem .7rem;border-radius:20px;white-space:nowrap;}
.sem-verde   {background:#dcfce7;color:#16a34a;}
.sem-amarillo{background:#fef9c3;color:#ca8a04;}
.sem-rojo    {background:var(--red-l);color:var(--red);}
.sem-gris    {background:var(--gray);color:var(--muted);}

/* PAGO CARD */
.pago-barra{height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin:.5rem 0;}
.pago-fill{height:100%;border-radius:4px;transition:width .4s;}

/* ALERTA */
.alerta-pago{background:#fff7ed;border:1px solid #fed7aa;border-left:4px solid var(--orange);border-radius:10px;padding:.8rem 1rem;font-size:.82rem;display:flex;align-items:center;gap:.6rem;margin-bottom:1rem;}

/* BADGE */
.badge-mod{font-size:.68rem;font-weight:800;padding:.2rem .55rem;border-radius:20px;}
.bm-presencial{background:var(--teal-l);color:var(--teal);}
.bm-virtual{background:#e8f0fe;color:var(--blue);}
.bm-hibrida{background:#f3e8ff;color:#7c3aed;}

/* RUBRICA */
.rubrica-criterio{display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--gray);}
.rubrica-criterio:last-child{border-bottom:none;}
.estrellas{color:#F59E0B;font-size:1rem;}

/* WELCOME BANNER */
.welcome-banner{background:linear-gradient(135deg,var(--dark),#1e3a5f);border-radius:18px;padding:1.5rem 1.8rem;margin-bottom:1.2rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.welcome-banner h2{font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:.25rem;}
.welcome-banner h2 span{color:#FFCA28;}
.welcome-banner p{font-size:.82rem;color:rgba(255,255,255,.65);margin:0;}

@media(max-width:600px){
  .tab-btn{padding:.7rem .6rem;font-size:.75rem;}
  .tab-btn span.label{display:none;}
  .mat-card-head{flex-direction:column;align-items:flex-start;}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="portal-nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= $U ?>public/index.php" style="flex-shrink:0;">
      <img src="<?= $U ?>assets/img/RobotSchool.webp" alt="ROBOTSchool"/>
    </a>
    <div class="nav-user" style="min-width:0;">
      <div style="text-align:right;min-width:0;overflow:hidden;">
        <strong style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;">
          <?= h($padre['nombre_completo']) ?>
        </strong>
        <div style="font-size:.7rem;color:rgba(255,255,255,.5);">Portal de padres</div>
      </div>
      <a href="<?= $U ?>portal/logout.php" class="btn-nav-out" style="flex-shrink:0;white-space:nowrap;">
        <i class="bi bi-box-arrow-right"></i> Salir
      </a>
    </div>
  </div>
</nav>

<!-- TABS -->
<div class="portal-tabs">
  <div class="container" style="display:flex;gap:0;overflow-x:auto;">
    <button class="tab-btn active" onclick="showTab('inicio')">
      <i class="bi bi-grid-fill"></i> <span class="label">Inicio</span>
    </button>
    <button class="tab-btn" onclick="showTab('hijos')">
      <i class="bi bi-person-badge-fill"></i> <span class="label">Mis hijos</span>
    </button>
    <button class="tab-btn" onclick="showTab('cursos')">
      <i class="bi bi-journal-richtext"></i> <span class="label">Cursos</span>
    </button>
    <button class="tab-btn" onclick="showTab('pagos')">
      <i class="bi bi-cash-stack"></i> <span class="label">Pagos</span>
      <?php if (count($alertas_pago) > 0): ?>
        <span style="background:var(--red);color:#fff;border-radius:50%;width:16px;height:16px;font-size:.6rem;display:flex;align-items:center;justify-content:center;font-weight:800;"><?= count($alertas_pago) ?></span>
      <?php endif; ?>
    </button>
    <button class="tab-btn" onclick="showTab('horarios')">
      <i class="bi bi-calendar3"></i> <span class="label">Horarios</span>
    </button>
    <button class="tab-btn" onclick="showTab('informes')">
      <i class="bi bi-star-fill"></i> <span class="label">Informes</span>
    </button>
    <button class="tab-btn" onclick="showTab('observaciones')">
      <i class="bi bi-journal-text"></i> <span class="label">Observaciones</span>
    </button>
  </div>
</div>

<!-- &#9552;&#9552;&#9552; TAB INICIO &#9552;&#9552;&#9552; -->
<div id="tab-inicio" class="tab-content active">

  <?php if (isset($_GET['bienvenida']) && $_GET['bienvenida'] == '1'): ?>
  <div style="background:linear-gradient(135deg,#065f46,#047857);border-radius:16px;padding:1.4rem 1.6rem;margin-bottom:1.2rem;display:flex;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
    <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.4rem;">&#127881;</div>
    <div style="flex:1;">
      <div style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:800;color:#fff;margin-bottom:.3rem;">
        &iexcl;Inscripci&oacute;n completada con &eacute;xito!
      </div>
      <div style="font-size:.84rem;color:rgba(255,255,255,.85);line-height:1.6;">
        Ya hiciste el registro de tu hijo/hija. Desde aqu&iacute; puedes hacer seguimiento a su matr&iacute;cula, ver los horarios y estar al d&iacute;a con los pagos.<br>
        <span style="opacity:.7;font-size:.78rem;">Si tienes dudas escr&iacute;benos a <strong>robotschoolcol@gmail.com</strong> o al WhatsApp <strong>318 654 1859</strong>.</span>
      </div>
    </div>
    <a href="portal/index.php" style="color:rgba(255,255,255,.5);font-size:.8rem;text-decoration:none;flex-shrink:0;align-self:flex-start;" title="Cerrar">&#10005;</a>
  </div>
  <?php endif; ?>

  <div class="welcome-banner">
    <div>
      <h2>Hola, <span><?= h(explode(' ', $padre['nombre_completo'])[0]) ?></span> &#128075;</h2>
      <p>Aqu&iacute; puedes ver todo sobre el proceso acad&eacute;mico de tu<?= count($hijos) > 1 ? 's hijos' : ' hijo/hija' ?>.</p>
    </div>
    <div style="text-align:right;">
      <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:#fff;line-height:1;"><?= date('d') ?></div>
      <div style="font-size:.72rem;color:rgba(255,255,255,.5);"><?= date('M Y') ?></div>
    </div>
  </div>

  <!-- Alertas de pago -->
  <?php foreach ($alertas_pago as $ap): ?>
  <div class="alerta-pago">
    <i class="bi bi-exclamation-triangle-fill" style="color:var(--orange);font-size:1.1rem;flex-shrink:0;"></i>
    <div>
      <strong><?= h($ap['estudiante']) ?></strong> tiene un pago
      <strong><?= $ap['pago_estado'] === 'vencido' ? 'vencido' : 'pendiente' ?></strong>
      por <?= h($ap['curso']) ?> &mdash;
      Saldo: <strong><?= formatCOP($ap['saldo']) ?></strong>
      <?php if ($ap['fecha_limite']): ?>
        &middot; Vence: <?= formatFecha($ap['fecha_limite']) ?>
      <?php endif; ?>
    </div>
    <button onclick="showTab('pagos')" style="margin-left:auto;background:var(--orange);color:#fff;border:none;border-radius:8px;padding:.3rem .8rem;font-size:.75rem;font-weight:700;cursor:pointer;flex-shrink:0;">Ver pagos</button>
  </div>
  <?php endforeach; ?>

  <!-- Resumen hijos -->
  <div class="pcard">
    <div class="pcard-title"><i class="bi bi-people-fill"></i> Mis hijos inscritos</div>
    <?php if (empty($hijos)): ?>
      <div style="text-align:center;padding:1.5rem;color:var(--muted);font-size:.85rem;">
        No tienes hijos registrados a&uacute;n.
        <a href="<?= $U ?>public/index.php#academias" style="color:var(--orange);font-weight:700;">Ver cursos disponibles</a>
      </div>
    <?php else: ?>
      <?php foreach ($hijos as $h):
        $edad = date_diff(date_create($h['fecha_nacimiento']), date_create('today'))->y;
      ?>
      <div class="hijo-card">
        <?php if ($h['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$h['avatar'])): ?>
          <img src="<?= $U ?>uploads/estudiantes/<?= h($h['avatar']) ?>" class="hijo-avatar"/>
        <?php else: ?>
          <div class="hijo-avatar-placeholder"><?= strtoupper(substr($h['nombre_completo'],0,2)) ?></div>
        <?php endif; ?>
        <div style="flex:1;">
          <div style="font-weight:800;font-size:.9rem;color:var(--text);"><?= h($h['nombre_completo']) ?></div>
          <div style="font-size:.75rem;color:var(--muted);"><?= $edad ?> a&ntilde;os &middot; <?= h($h['grado']??'Sin grado') ?> &middot; <?= h($h['sede_nombre']) ?></div>
        </div>
        <?php if ($h['matriculas_activas'] > 0): ?>
          <span class="sem sem-verde"><i class="bi bi-check-circle-fill"></i> Inscrito</span>
        <?php else: ?>
          <a href="<?= $U ?>public/index.php#academias" class="sem sem-amarillo" style="text-decoration:none;">Ver cursos</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pr&oacute;ximas clases -->
  <?php if (!empty($matriculas)): ?>
  <div class="pcard">
    <div class="pcard-title"><i class="bi bi-calendar3"></i> Pr&oacute;ximas clases</div>
    <?php foreach ($matriculas as $m): ?>
    <div style="display:flex;align-items:center;gap:.8rem;padding:.7rem .9rem;background:var(--gray);border-radius:10px;margin-bottom:.5rem;">
      <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--orange),#ff8c42);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-family:'Poppins',sans-serif;font-size:.7rem;font-weight:800;flex-shrink:0;text-align:center;line-height:1.2;">
        <?= strtoupper(substr($dias[$m['dia_semana']]??'',0,3)) ?>
      </div>
      <div style="flex:1;">
        <div style="font-size:.85rem;font-weight:700;"><?= h($m['curso']) ?></div>
        <div style="font-size:.75rem;color:var(--muted);"><?= h($m['estudiante']) ?> &middot; <?= substr($m['hora_inicio'],0,5) ?>&ndash;<?= substr($m['hora_fin'],0,5) ?></div>
      </div>
      <span class="badge-mod bm-<?= $m['modalidad'] ?>"><?= ucfirst($m['modalidad']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<!-- &#9552;&#9552;&#9552; TAB MIS HIJOS &#9552;&#9552;&#9552; -->
<div id="tab-hijos" class="tab-content">
  <?php foreach ($hijos as $h):
    $edad = date_diff(date_create($h['fecha_nacimiento']), date_create('today'))->y;
  ?>
  <div class="pcard">
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
      <?php if ($h['avatar'] && file_exists(ROOT.'/uploads/estudiantes/'.$h['avatar'])): ?>
        <img src="<?= $U ?>uploads/estudiantes/<?= h($h['avatar']) ?>"
             style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid var(--teal-l);flex-shrink:0;"/>
      <?php else: ?>
        <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:800;color:#fff;flex-shrink:0;">
          <?= strtoupper(substr($h['nombre_completo'],0,2)) ?>
        </div>
      <?php endif; ?>
      <div>
        <h3 style="font-size:1.1rem;margin-bottom:.2rem;"><?= h($h['nombre_completo']) ?></h3>
        <div style="font-size:.78rem;color:var(--muted);"><?= $edad ?> a&ntilde;os &middot; <?= h($h['sede_nombre']) ?></div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.6rem;font-size:.82rem;">
      <?php if ($h['colegio']): ?>
        <div><i class="bi bi-building" style="color:var(--orange);"></i> <?= h($h['colegio']) ?> &mdash; <?= h($h['grado']??'') ?></div>
      <?php endif; ?>
      <?php if ($h['eps']): ?>
        <div><i class="bi bi-heart-pulse" style="color:var(--orange);"></i> <?= h($h['eps']) ?></div>
      <?php endif; ?>
      <?php if ($h['grupo_sanguineo']): ?>
        <div><i class="bi bi-droplet-fill" style="color:var(--red);"></i> Grupo <?= h($h['grupo_sanguineo']) ?></div>
      <?php endif; ?>
      <?php if ($h['alergias']): ?>
        <div style="grid-column:1/-1;background:var(--red-l);border-radius:8px;padding:.5rem .8rem;font-size:.78rem;color:var(--red);">
          <i class="bi bi-exclamation-triangle-fill"></i> <?= h($h['alergias']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($hijos)): ?>
    <div class="pcard" style="text-align:center;padding:2rem;color:var(--muted);">
      <i class="bi bi-person-badge" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.8rem;"></i>
      No tienes hijos registrados.
      <a href="<?= $U ?>public/registro.php" style="color:var(--orange);font-weight:700;display:block;margin-top:.5rem;">Inscribir hijo/hija</a>
    </div>
  <?php endif; ?>
</div>

<!-- &#9552;&#9552;&#9552; TAB CURSOS &#9552;&#9552;&#9552; -->
<div id="tab-cursos" class="tab-content">
  <?php if (empty($matriculas)): ?>
    <div class="pcard" style="text-align:center;padding:2rem;color:var(--muted);">
      <i class="bi bi-journal-x" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.8rem;"></i>
      No hay cursos activos.
      <a href="<?= $U ?>public/index.php#academias" style="color:var(--orange);font-weight:700;display:block;margin-top:.5rem;">Ver cursos disponibles</a>
    </div>
  <?php else: ?>
    <?php foreach ($matriculas as $m): ?>
    <div class="mat-card">
      <div class="mat-card-head">
        <div>
          <h4><?= h($m['curso']) ?></h4>
          <small><?= h($m['estudiante']) ?> &middot; <?= h($m['sede_nombre']) ?> &middot; <?= h($m['periodo']) ?></small>
        </div>
        <span class="badge-mod bm-<?= $m['modalidad'] ?>"><?= ucfirst($m['modalidad']) ?></span>
      </div>
      <div class="mat-card-body">
        <?php if ($m['introduccion']): ?>
          <p style="font-size:.82rem;color:var(--muted);margin-bottom:.8rem;line-height:1.6;"><?= h($m['introduccion']) ?></p>
        <?php endif; ?>
        <div class="info-row"><i class="bi bi-calendar3"></i> <?= $dias[$m['dia_semana']]??$m['dia_semana'] ?> &middot; <?= substr($m['hora_inicio'],0,5) ?> &ndash; <?= substr($m['hora_fin'],0,5) ?></div>
        <div class="info-row"><i class="bi bi-geo-alt-fill"></i> <?= h($m['sede_nombre']) ?></div>
        <div class="info-row">
          <i class="bi bi-clipboard2-check"></i>
          Estado:
          <?php
            $est_col = ['activa'=>'sem-verde','pre_inscrito'=>'sem-amarillo','suspendida'=>'sem-rojo'][$m['estado']]??'sem-gris';
            $est_lab = ['activa'=>'Activo','pre_inscrito'=>'Pre-inscrito','suspendida'=>'Suspendido'][$m['estado']]??$m['estado'];
          ?>
          <span class="sem <?= $est_col ?>"><?= $est_lab ?></span>
        </div>
        <div style="margin-top:.9rem;">
          <a href="<?= $U ?>docente/informe_pdf.php?matricula=<?= $m['id'] ?>" target="_blank"
             style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;background:var(--orange);color:#fff;border-radius:10px;font-size:.8rem;font-weight:700;text-decoration:none;box-shadow:0 3px 10px rgba(242,101,34,.3);">
            <i class="bi bi-file-earmark-person-fill"></i> Descargar informe PDF
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- &#9552;&#9552;&#9552; TAB PAGOS &#9552;&#9552;&#9552; -->
<div id="tab-pagos" class="tab-content">
  <?php if (empty($matriculas)): ?>
    <div class="pcard" style="text-align:center;padding:2rem;color:var(--muted);">
      <i class="bi bi-cash-stack" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.8rem;"></i>
      No hay pagos registrados.
    </div>
  <?php else: ?>
    <?php foreach ($matriculas as $m):
      if (!$m['pago_estado']) continue;
      $pct = $m['valor_total'] > 0 ? round(($m['valor_pagado']/$m['valor_total'])*100) : 0;
      $bar_col = $pct>=100?'#16a34a':($pct>=50?'#F59E0B':'var(--red)');
      $sem_class = ['pagado'=>'sem-verde','parcial'=>'sem-amarillo','pendiente'=>'sem-amarillo','vencido'=>'sem-rojo','exonerado'=>'sem-gris'][$m['pago_estado']]??'sem-gris';
    ?>
    <div class="pcard">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.8rem;flex-wrap:wrap;gap:.5rem;">
        <div>
          <div style="font-weight:800;font-size:.9rem;"><?= h($m['curso']) ?></div>
          <div style="font-size:.75rem;color:var(--muted);"><?= h($m['estudiante']) ?> &middot; <?= h($m['periodo']) ?></div>
        </div>
        <span class="sem <?= $sem_class ?>"><?= ucfirst($m['pago_estado']) ?></span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;text-align:center;margin-bottom:.8rem;">
        <div style="background:var(--gray);border-radius:8px;padding:.6rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:900;color:var(--text);"><?= formatCOP($m['valor_total']) ?></div>
          <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Total</div>
        </div>
        <div style="background:#dcfce7;border-radius:8px;padding:.6rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:900;color:#16a34a;"><?= formatCOP($m['valor_pagado']) ?></div>
          <div style="font-size:.65rem;color:#166534;font-weight:600;">Pagado</div>
        </div>
        <div style="background:<?= (float)$m['saldo']>0?'var(--red-l)':'#dcfce7' ?>;border-radius:8px;padding:.6rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:900;color:<?= (float)$m['saldo']>0?'var(--red)':'#16a34a' ?>;"><?= formatCOP($m['saldo']) ?></div>
          <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Saldo</div>
        </div>
      </div>
      <div class="pago-barra"><div class="pago-fill" style="width:<?= $pct ?>%;background:<?= $bar_col ?>;"></div></div>
      <?php if ($m['fecha_limite']): ?>
        <div style="font-size:.75rem;color:<?= $m['pago_estado']==='vencido'?'var(--red)':'var(--muted)' ?>;margin-top:.4rem;">
          <i class="bi bi-calendar-event"></i>
          Fecha l&iacute;mite: <?= formatFecha($m['fecha_limite']) ?>
          <?= $m['pago_estado']==='vencido' ? ' <strong>&mdash; VENCIDO</strong>' : '' ?>
        </div>
      <?php endif; ?>
      <?php if (in_array($m['pago_estado'], ['pendiente','parcial','vencido'])): ?>
        <div style="margin-top:.9rem;padding:.8rem;background:var(--gray);border-radius:10px;font-size:.78rem;color:var(--muted);">
          <i class="bi bi-info-circle-fill" style="color:var(--orange);"></i>
          Para realizar tu pago comun&iacute;cate con la sede o escr&iacute;benos al
          <a href="https://wa.link/ktfv4o" target="_blank" style="color:var(--orange);font-weight:700;">
            <i class="bi bi-whatsapp"></i> WhatsApp
          </a>
        </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- &#9552;&#9552;&#9552; TAB HORARIOS &#9552;&#9552;&#9552; -->
<div id="tab-horarios" class="tab-content">
  <?php if (empty($matriculas)): ?>
    <div class="pcard" style="text-align:center;padding:2rem;color:var(--muted);">
      <i class="bi bi-calendar3" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.8rem;"></i>
      No hay horarios registrados.
    </div>
  <?php else: ?>
    <?php
      // Agrupar por d&iacute;a
      $por_dia = [];
      foreach ($matriculas as $m) {
        $por_dia[$m['dia_semana']][] = $m;
      }
      $orden_dias = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
      uksort($por_dia, fn($a,$b) => array_search($a,$orden_dias) - array_search($b,$orden_dias));
    ?>
    <?php foreach ($por_dia as $dia => $clases): ?>
    <div class="pcard">
      <div class="pcard-title">
        <i class="bi bi-calendar-week-fill"></i>
        <?= $dias[$dia] ?? ucfirst($dia) ?>
      </div>
      <?php foreach ($clases as $c): ?>
      <div style="display:flex;align-items:center;gap:.8rem;padding:.7rem .9rem;background:var(--gray);border-radius:10px;margin-bottom:.5rem;">
        <div style="text-align:center;min-width:60px;flex-shrink:0;">
          <div style="font-family:'Poppins',sans-serif;font-size:.85rem;font-weight:900;color:var(--dark);"><?= substr($c['hora_inicio'],0,5) ?></div>
          <div style="font-size:.65rem;color:var(--muted);">a <?= substr($c['hora_fin'],0,5) ?></div>
        </div>
        <div style="width:3px;height:40px;background:var(--orange);border-radius:2px;flex-shrink:0;"></div>
        <div style="flex:1;">
          <div style="font-size:.85rem;font-weight:700;"><?= h($c['curso']) ?></div>
          <div style="font-size:.75rem;color:var(--muted);"><?= h($c['estudiante']) ?> &middot; <?= h($c['sede_nombre']) ?></div>
        </div>
        <span class="badge-mod bm-<?= $c['modalidad'] ?>"><?= ucfirst($c['modalidad']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- &#9552;&#9552;&#9552; TAB INFORMES / R&Uacute;BRICAS &#9552;&#9552;&#9552; -->
<div id="tab-informes" class="tab-content">
  <?php
    // Obtener evaluaciones con r&uacute;bricas
    $evaluaciones = $pdo->prepare("
        SELECT ev.*, r.nombre AS rubrica_nombre, r.periodo,
            e.nombre_completo AS estudiante,
            c.nombre AS curso,
            u.nombre AS docente,
            (SELECT SUM(ed.puntaje) FROM evaluacion_detalle ed WHERE ed.evaluacion_id=ev.id) AS total_obtenido,
            (SELECT SUM(rc.puntaje_max) FROM evaluacion_detalle ed JOIN rubrica_criterios rc ON rc.id=ed.criterio_id WHERE ed.evaluacion_id=ev.id) AS total_posible
        FROM evaluaciones ev
        JOIN rubricas r ON r.id=ev.rubrica_id
        JOIN matriculas m ON m.id=ev.matricula_id
        JOIN estudiantes e ON e.id=m.estudiante_id
        JOIN grupos g ON g.id=m.grupo_id
        JOIN cursos c ON c.id=g.curso_id
        JOIN usuarios u ON u.id=ev.docente_id
        WHERE e.padre_id=?
        ORDER BY ev.fecha DESC
    ");
    $evaluaciones->execute([$padre['id']]);
    $evaluaciones = $evaluaciones->fetchAll();
  ?>
  <?php if (empty($evaluaciones)): ?>
    <div class="pcard" style="text-align:center;padding:2rem;color:var(--muted);">
      <i class="bi bi-star" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.8rem;"></i>
      <h4 style="font-size:1rem;margin-bottom:.4rem;color:var(--text);">A&uacute;n no hay informes disponibles</h4>
      <p style="font-size:.84rem;">Los informes aparecer&aacute;n aqu&iacute; cuando el instructor registre las evaluaciones.</p>
    </div>
  <?php else: ?>
    <?php foreach ($evaluaciones as $ev):
      $pct_ev = $ev['total_posible'] > 0 ? round(($ev['total_obtenido']/$ev['total_posible'])*100) : 0;
      $color_ev = $pct_ev>=80?'#16a34a':($pct_ev>=60?'#F59E0B':'var(--red)');
      // Obtener detalle criterios
      $detalle = $pdo->prepare("
          SELECT ed.puntaje, rc.criterio, rc.puntaje_max
          FROM evaluacion_detalle ed
          JOIN rubrica_criterios rc ON rc.id=ed.criterio_id
          WHERE ed.evaluacion_id=?
          ORDER BY rc.orden
      ");
      $detalle->execute([$ev['id']]); $detalle = $detalle->fetchAll();
    ?>
    <div class="pcard">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:.9rem;">
        <div>
          <div style="font-weight:800;font-size:.9rem;"><?= h($ev['rubrica_nombre']) ?></div>
          <div style="font-size:.75rem;color:var(--muted);"><?= h($ev['estudiante']) ?> &middot; <?= h($ev['curso']) ?> &middot; <?= formatFecha($ev['fecha']) ?></div>
          <div style="font-size:.72rem;color:var(--muted);">Evaluado por: <?= h($ev['docente']) ?></div>
        </div>
        <div style="text-align:center;background:var(--gray);border-radius:12px;padding:.6rem 1rem;">
          <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:900;color:<?= $color_ev ?>;"><?= $pct_ev ?>%</div>
          <div style="font-size:.65rem;color:var(--muted);font-weight:600;"><?= $ev['total_obtenido'] ?>/<?= $ev['total_posible'] ?> pts</div>
        </div>
      </div>
      <!-- Barra general -->
      <div style="height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin-bottom:1rem;">
        <div style="height:100%;width:<?= $pct_ev ?>%;background:<?= $color_ev ?>;border-radius:4px;"></div>
      </div>
      <!-- Criterios -->
      <?php if ($detalle): ?>
      <div style="background:var(--gray);border-radius:10px;padding:.8rem 1rem;">
        <?php foreach ($detalle as $d):
          $pct_d  = $d['puntaje_max'] > 0 ? round(($d['puntaje']/$d['puntaje_max'])*100) : 0;
          $stars  = round($d['puntaje_max'] > 0 ? ($d['puntaje']/$d['puntaje_max'])*5 : 0);
        ?>
        <div class="rubrica-criterio">
          <div style="flex:1;">
            <div style="font-size:.82rem;font-weight:600;"><?= h($d['criterio']) ?></div>
            <div class="estrellas"><?= str_repeat('&#9733;',$stars).str_repeat('&#9734;',5-$stars) ?></div>
          </div>
          <div style="text-align:right;font-size:.8rem;font-weight:700;color:<?= $pct_d>=60?'#16a34a':'var(--red)' ?>;">
            <?= $d['puntaje'] ?>/<?= $d['puntaje_max'] ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($ev['observaciones']): ?>
        <div style="margin-top:.8rem;padding:.7rem .9rem;background:#fff7ed;border-radius:8px;font-size:.78rem;color:var(--text);">
          <i class="bi bi-chat-quote-fill" style="color:var(--orange);"></i>
          <?= h($ev['observaciones']) ?>
        </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- TAB OBSERVACIONES -->
<div id="tab-observaciones" class="tab-content">
  <?php
    $obs_padre = $pdo->prepare("
        SELECT o.*, u.nombre AS docente,
               e.nombre_completo AS estudiante,
               c.nombre AS curso
        FROM observaciones o
        JOIN usuarios u ON u.id = o.registrado_por
        LEFT JOIN matriculas m ON m.id = o.matricula_id
        LEFT JOIN estudiantes e ON e.id = m.estudiante_id
        LEFT JOIN grupos g ON g.id = o.grupo_id
        LEFT JOIN cursos c ON c.id = g.curso_id
        WHERE o.visible_padre = 1
          AND (
            m.estudiante_id IN (SELECT id FROM estudiantes WHERE padre_id = ?)
            OR o.grupo_id IN (
                SELECT m2.grupo_id FROM matriculas m2
                JOIN estudiantes e2 ON e2.id = m2.estudiante_id
                WHERE e2.padre_id = ?
            )
          )
        ORDER BY o.fecha DESC, o.created_at DESC
    ");
    $obs_padre->execute([$padre['id'], $padre['id']]);
    $obs_padre = $obs_padre->fetchAll();
  ?>
  <?php if (empty($obs_padre)): ?>
    <div class="pcard" style="text-align:center;padding:2rem;color:var(--muted);">
      <i class="bi bi-journal-x" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.8rem;"></i>
      <h4 style="font-size:1rem;margin-bottom:.4rem;color:var(--text);">Sin observaciones aun</h4>
      <p style="font-size:.84rem;">Las observaciones del instructor apareceran aqui cuando sean registradas.</p>
    </div>
  <?php else: ?>
    <?php foreach ($obs_padre as $ob): ?>
    <div class="pcard" style="margin-bottom:.8rem;border-left:4px solid <?= $ob['tipo']==='general'?'var(--orange)':'var(--teal)' ?>;">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem;">
        <div>
          <?php if ($ob['tipo']==='general'): ?>
            <span style="font-size:.7rem;font-weight:800;background:var(--orange);color:#fff;padding:.12rem .5rem;border-radius:8px;display:inline-block;">
              Clase completa
            </span>
          <?php else: ?>
            <span style="font-size:.7rem;font-weight:800;background:#e6f7f5;color:var(--teal);padding:.12rem .5rem;border-radius:8px;display:inline-block;">
              <i class="bi bi-person-fill"></i> <?= h($ob['estudiante']) ?>
            </span>
          <?php endif; ?>
          <?php if ($ob['curso']): ?>
            <span style="font-size:.7rem;color:var(--muted);margin-left:.4rem;"><?= h($ob['curso']) ?></span>
          <?php endif; ?>
        </div>
        <div style="font-size:.72rem;color:var(--muted);text-align:right;flex-shrink:0;">
          <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($ob['fecha'])) ?><br>
          <i class="bi bi-person-fill"></i> <?= h($ob['docente']) ?>
        </div>
      </div>
      <p style="font-size:.86rem;line-height:1.68;color:var(--text);margin:0;"><?= nl2br(h($ob['texto'])) ?></p>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-'+tab).classList.add('active');
  event.currentTarget.classList.add('active');
  window.scrollTo({top:0,behavior:'smooth'});
}
</script>
</body>
</html>
