<?php
// docente/index.php &mdash; Portal del Docente / Tallerista
require_once __DIR__ . '/../config/config.php';
require_once ROOT . '/config/auth.php';
requireLogin();

$roles_ok = ['docente','coordinador_pedagogico','admin_sede','admin_general'];
if (!in_array($_SESSION['usuario_rol'], $roles_ok)) {
    header('Location: ' . BASE_URL . 'login.php'); exit;
}

$U    = BASE_URL;
$uid  = (int)$_SESSION['usuario_id'];
$rol  = $_SESSION['usuario_rol'];
$tab  = $_GET['tab'] ?? 'estudiantes';
$gid  = (int)($_GET['grupo'] ?? 0);
$msg  = $_GET['msg'] ?? '';

// Grupos visibles
if (in_array($rol, ['admin_general','admin_sede'])) {
    $sf = getSedeFiltro();
    $w  = $sf ? 'AND g.sede_id='.(int)$sf : '';
    $grupos = $pdo->query("
        SELECT g.*, c.nombre AS cnombre, s.nombre AS snombre
        FROM grupos g JOIN cursos c ON c.id=g.curso_id JOIN sedes s ON s.id=g.sede_id
        WHERE g.activo=1 $w ORDER BY c.nombre, g.hora_inicio
    ")->fetchAll();
} else {
    $st = $pdo->prepare("
        SELECT g.*, c.nombre AS cnombre, s.nombre AS snombre
        FROM grupos g JOIN cursos c ON c.id=g.curso_id JOIN sedes s ON s.id=g.sede_id
        JOIN docente_grupos dg ON dg.grupo_id=g.id
        WHERE dg.docente_id=? AND g.activo=1 ORDER BY c.nombre, g.hora_inicio
    ");
    $st->execute([$uid]);
    $grupos = $st->fetchAll();
}

if (!$gid && !empty($grupos)) $gid = $grupos[0]['id'];
$grupo_sel = null;
foreach ($grupos as $g) { if ($g['id']==$gid) { $grupo_sel=$g; break; } }

// Estudiantes y rubricas
$estudiantes = []; $rubricas = [];
if ($grupo_sel) {
    $st = $pdo->prepare("
        SELECT e.*, p.nombre_completo AS pnombre, p.telefono AS ptel,
               m.id AS mid, TIMESTAMPDIFF(YEAR,e.fecha_nacimiento,CURDATE()) AS edad,
               (SELECT DATE_FORMAT(ev.fecha,'%d/%m/%Y') FROM evaluaciones ev
                WHERE ev.matricula_id=m.id ORDER BY ev.fecha DESC LIMIT 1) AS uf,
               (SELECT ROUND(COALESCE(SUM(ed.puntaje),0)/NULLIF(
                   (SELECT SUM(rc.puntaje_max) FROM evaluacion_detalle ed2
                    JOIN rubrica_criterios rc ON rc.id=ed2.criterio_id WHERE ed2.evaluacion_id=ev2.id),0)*100)
                FROM evaluaciones ev2 JOIN evaluacion_detalle ed ON ed.evaluacion_id=ev2.id
                WHERE ev2.matricula_id=m.id ORDER BY ev2.fecha DESC LIMIT 1) AS upct
        FROM matriculas m JOIN estudiantes e ON e.id=m.estudiante_id JOIN padres p ON p.id=e.padre_id
        WHERE m.grupo_id=? AND m.estado='activa' ORDER BY e.nombre_completo
    ");
    $st->execute([$gid]);
    $estudiantes = $st->fetchAll();

    $st2 = $pdo->prepare("SELECT * FROM rubricas WHERE curso_id=? AND activa=1 ORDER BY nombre");
    $st2->execute([$grupo_sel['curso_id']]);
    $rubricas = $st2->fetchAll();
}

// Guardar observacion
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']??'')==='obs') {
    $g   = (int)($_POST['gid'] ?? 0);
    $mid = (int)($_POST['mid'] ?? 0) ?: null;
    $tipo= $mid ? 'estudiante' : 'general';
    $fecha= $_POST['fecha'] ?? date('Y-m-d');
    $txt = trim($_POST['texto'] ?? '');
    $vis = isset($_POST['vis']) ? 1 : 0;
    if ($g && $txt) {
        $pdo->prepare("INSERT INTO observaciones (grupo_id,matricula_id,tipo,fecha,texto,visible_padre,registrado_por) VALUES (?,?,?,?,?,?,?)")
            ->execute([$g, $mid, $tipo, $fecha, $txt, $vis, $uid]);
    }
    header('Location: ?grupo='.$g.'&tab=observaciones&msg=ok'); exit;
}

// Observaciones del grupo
$obs_list = [];
if ($grupo_sel && $tab==='observaciones') {
    $st = $pdo->prepare("
        SELECT o.*, u.nombre AS unombre,
               e.nombre_completo AS enombre
        FROM observaciones o
        JOIN usuarios u ON u.id=o.registrado_por
        LEFT JOIN matriculas m ON m.id=o.matricula_id
        LEFT JOIN estudiantes e ON e.id=m.estudiante_id
        WHERE o.grupo_id=? ORDER BY o.fecha DESC, o.created_at DESC
    ");
    $st->execute([$gid]);
    $obs_list = $st->fetchAll();
}

$DIAS=['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miercoles',
       'jueves'=>'Jueves','viernes'=>'Viernes','sabado'=>'Sabado','domingo'=>'Domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Portal Docente &mdash; ROBOTSchool</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;800;900&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<style>
:root{--or:#F26522;--bl:#1E4DA1;--dk:#0f1623;--tl:#1DA99A;--tld:#148a7d;--tll:#e6f7f5;--re:#E8192C;--rel:#fff0f1;--gr:#16a34a;--grl:#dcfce7;--gy:#F5F7FA;--bd:#e0e6f0;--mu:#64748b;--tx:#1a2234;--wh:#fff;}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito',sans-serif;background:var(--gy);color:var(--tx);min-height:100vh;}
h1,h2,h3,h4,h5{font-family:'Poppins',sans-serif;font-weight:700}
.nav{background:var(--dk);padding:.6rem 0;position:sticky;top:0;z-index:200;box-shadow:0 2px 14px rgba(0,0,0,.22);}
.nav img{height:36px;}
.nbadge{background:var(--or);color:#fff;font-size:.62rem;font-weight:800;padding:.12rem .48rem;border-radius:20px;}
.nout{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);color:rgba(255,255,255,.75);border-radius:8px;padding:.27rem .72rem;font-size:.74rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;}
.nout:hover{background:var(--re);border-color:var(--re);color:#fff;}
.layout{display:grid;grid-template-columns:235px 1fr;min-height:calc(100vh - 53px);}
@media(max-width:768px){.layout{grid-template-columns:1fr;}.gpanel{display:none;}}
.gpanel{background:var(--wh);border-right:1px solid var(--bd);overflow-y:auto;}
.gptitle{font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--mu);padding:.85rem 1.1rem .3rem;}
.gbtn{display:block;padding:.62rem 1.1rem;border-left:3px solid transparent;text-decoration:none;color:var(--tx);transition:.16s;}
.gbtn:hover,.gbtn.on{background:var(--tll);border-left-color:var(--tl);}
.gbtn .gn{font-size:.81rem;font-weight:700;}
.gbtn .gi{font-size:.69rem;color:var(--mu);margin-top:.1rem;}
.dmain{display:flex;flex-direction:column;}
.ghead{background:linear-gradient(135deg,var(--dk),#1a3a60);padding:.95rem 1.3rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;flex-wrap:wrap;}
.ghead h2{font-size:.98rem;font-weight:800;color:#fff;margin:0;}
.ghead p{font-size:.73rem;color:rgba(255,255,255,.52);margin:.13rem 0 0;}
.tabs{display:flex;border-bottom:2px solid var(--bd);background:var(--wh);padding:0 1.1rem;}
.tab{display:flex;align-items:center;gap:.32rem;padding:.62rem .95rem;font-size:.81rem;font-weight:700;color:var(--mu);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:.16s;white-space:nowrap;}
.tab:hover{color:var(--tl);}
.tab.on{color:var(--tl);border-bottom-color:var(--tl);}
.tabbody{padding:1.3rem;flex:1;}
.ecard{background:var(--wh);border:1.5px solid var(--bd);border-radius:12px;padding:.82rem .95rem;display:flex;align-items:center;gap:.75rem;margin-bottom:.55rem;cursor:pointer;transition:.16s;}
.ecard:hover{border-color:var(--tl);background:var(--tll);}
.eavt{width:42px;height:42px;border-radius:50%;object-fit:cover;flex-shrink:0;}
.eini{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--tl),var(--tld));display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:.8rem;font-weight:800;color:#fff;flex-shrink:0;}
.ppill{font-family:'Poppins',sans-serif;font-size:.8rem;font-weight:800;padding:.2rem .55rem;border-radius:8px;}
.ocard{background:var(--wh);border:1px solid var(--bd);border-left:4px solid var(--tl);border-radius:10px;padding:.78rem .95rem;margin-bottom:.65rem;}
.ocard.gen{border-left-color:var(--or);}
.ometa{font-size:.7rem;color:var(--mu);margin-top:.3rem;display:flex;gap:.75rem;flex-wrap:wrap;}
.ovl{display:none;position:fixed;inset:0;z-index:9000;background:rgba(15,22,35,.72);backdrop-filter:blur(4px);overflow-y:auto;padding:1.4rem 1rem;}
.obox{background:var(--wh);border-radius:18px;max-width:600px;margin:0 auto;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.28);}
.ohead{background:linear-gradient(135deg,var(--dk),#1e3a5f);padding:1rem 1.3rem;display:flex;align-items:center;gap:.9rem;position:relative;}
.obody{padding:1.2rem 1.3rem;}
.bx{position:absolute;top:.75rem;right:.85rem;background:rgba(255,255,255,.13);border:none;color:#fff;width:29px;height:29px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.83rem;}
.bx:hover{background:var(--re);}
.fl{display:block;font-size:.77rem;font-weight:700;color:var(--mu);margin-bottom:.33rem;}
.fi,.fs,.ft{width:100%;padding:.58rem .82rem;border:1.5px solid var(--bd);border-radius:10px;font-family:'Nunito',sans-serif;font-size:.85rem;color:var(--tx);background:var(--wh);outline:none;transition:.16s;}
.fi:focus,.fs:focus,.ft:focus{border-color:var(--tl);box-shadow:0 0 0 3px rgba(29,169,154,.1);}
.ft{resize:vertical;min-height:85px;}
.bp{display:inline-flex;align-items:center;gap:.33rem;padding:.48rem .95rem;border-radius:10px;font-size:.79rem;font-weight:700;cursor:pointer;text-decoration:none;border:none;transition:.16s;}
.bt{background:var(--tl);color:#fff;}.bt:hover{background:var(--tld);color:#fff;}
.bo{background:var(--or);color:#fff;}.bo:hover{background:#d4541a;color:#fff;}
.bg{background:var(--gy);color:var(--mu);border:1px solid var(--bd);}.bg:hover{border-color:var(--tl);color:var(--tl);}
.crow{background:var(--gy);border-radius:10px;padding:.72rem .88rem;margin-bottom:.52rem;}
.strs{display:flex;gap:.22rem;}
.str{font-size:1.35rem;cursor:pointer;color:#d1d5db;transition:color .12s;user-select:none;}
.str.on{color:#F59E0B;}
.aok{background:var(--grl);border:1px solid #a7f3d0;border-left:4px solid var(--gr);border-radius:10px;padding:.65rem .95rem;font-size:.83rem;color:#065f46;margin-bottom:.9rem;display:flex;align-items:center;gap:.45rem;}
.emp{text-align:center;padding:2.5rem 1rem;color:var(--mu);}
.emp i{font-size:2.2rem;opacity:.18;display:block;margin-bottom:.7rem;}
</style>
</head>
<body>

<nav class="nav">
  <div class="container d-flex align-items-center justify-content-between gap-2">
    <a href="<?= $U ?>public/index.php"><img src="<?= $U ?>assets/img/RobotSchool.webp" alt="ROBOTSchool"/></a>
    <div class="d-flex align-items-center gap-2">
      <div style="text-align:right;min-width:0;">
        <div style="font-size:.81rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:165px;"><?= h($_SESSION['usuario_nombre']) ?></div>
        <span class="nbadge"><i class="bi bi-mortarboard-fill"></i> <?= getRolLabel($rol) ?></span>
      </div>
      <?php if (in_array($rol,['admin_general','admin_sede'])): ?>
        <a href="<?= $U ?>dashboard.php" class="nout"><i class="bi bi-grid-fill"></i> Admin</a>
      <?php endif; ?>
      <a href="<?= $U ?>logout.php" class="nout"><i class="bi bi-box-arrow-right"></i> Salir</a>
    </div>
  </div>
</nav>

<div class="layout">

  <!-- Panel grupos -->
  <div class="gpanel">
    <div class="gptitle"><i class="bi bi-people-fill"></i> Mis grupos</div>
    <?php if (empty($grupos)): ?>
      <div style="padding:1rem 1.1rem;font-size:.81rem;color:var(--mu);">Sin grupos asignados.</div>
    <?php else: ?>
      <?php foreach ($grupos as $g): ?>
      <a href="?grupo=<?= $g['id'] ?>&tab=<?= $tab ?>" class="gbtn <?= $gid==$g['id']?'on':'' ?>">
        <div class="gn"><?= h($g['cnombre']) ?></div>
        <div class="gi">
          <?= $DIAS[$g['dia_semana']]??$g['dia_semana'] ?> &middot; <?= substr($g['hora_inicio'],0,5) ?>&ndash;<?= substr($g['hora_fin'],0,5) ?><br>
          <i class="bi bi-geo-alt" style="color:var(--or);"></i> <?= h($g['snombre']) ?>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Contenido -->
  <div class="dmain">
    <?php if (!$grupo_sel): ?>
      <div class="emp" style="padding:4rem 1rem;">
        <i class="bi bi-people"></i>
        <h5>Selecciona un grupo</h5>
        <p style="font-size:.84rem;">Elige un grupo del panel izquierdo.</p>
      </div>
    <?php else: ?>

    <!-- Header grupo -->
    <div class="ghead">
      <div>
        <h2><?= h($grupo_sel['cnombre']) ?></h2>
        <p><?= h($grupo_sel['nombre']) ?> &middot; <?= $DIAS[$grupo_sel['dia_semana']]??'' ?>
           <?= substr($grupo_sel['hora_inicio'],0,5) ?>&ndash;<?= substr($grupo_sel['hora_fin'],0,5) ?>
           &middot; <?= h($grupo_sel['snombre']) ?></p>
      </div>
      <div style="font-family:'Poppins',sans-serif;font-size:1.55rem;font-weight:900;color:var(--tl);line-height:1;text-align:right;">
        <?= count($estudiantes) ?>
        <div style="font-size:.6rem;font-weight:600;color:rgba(255,255,255,.38);">estudiantes</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <a href="?grupo=<?= $gid ?>&tab=estudiantes"   class="tab <?= $tab==='estudiantes'  ?'on':'' ?>"><i class="bi bi-person-badge-fill"></i> Estudiantes</a>
      <a href="?grupo=<?= $gid ?>&tab=asistencia"    class="tab <?= $tab==='asistencia'   ?'on':'' ?>"><i class="bi bi-calendar-check-fill"></i> Asistencia</a>
      <a href="?grupo=<?= $gid ?>&tab=observaciones" class="tab <?= $tab==='observaciones'?'on':'' ?>"><i class="bi bi-journal-text"></i> Observaciones</a>
      <a href="?grupo=<?= $gid ?>&tab=evaluaciones"  class="tab <?= $tab==='evaluaciones' ?'on':'' ?>"><i class="bi bi-clipboard2-check-fill"></i> Evaluaciones</a>
    </div>

    <div class="tabbody">
      <?php if ($msg==='ok'): ?>
        <div class="aok"><i class="bi bi-check-circle-fill"></i> Observacion guardada correctamente.</div>
      <?php endif; ?>

      <!-- TAB ESTUDIANTES -->
      <?php if ($tab==='estudiantes'): ?>
        <?php if (empty($estudiantes)): ?>
          <div class="emp"><i class="bi bi-inbox"></i><p>Sin estudiantes en este grupo.</p></div>
        <?php else: ?>
          <?php if (!empty($rubricas)): ?>
            <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;margin-bottom:.9rem;font-size:.79rem;">
              <span style="color:var(--mu);font-weight:700;">Rubrica:</span>
              <?php foreach ($rubricas as $r): ?>
                <span style="background:var(--tll);color:var(--tl);padding:.18rem .65rem;border-radius:20px;font-weight:700;cursor:pointer;"
                      onclick="ra=<?= $r['id'] ?>;rn='<?= addslashes(h($r['nombre'])) ?>';">
                  <i class="bi bi-journal-check"></i> <?= h($r['nombre']) ?>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php foreach ($estudiantes as $est):
            $pct=$est['upct'];
            $col=$pct!==null?($pct>=80?'#16a34a':($pct>=60?'#ca8a04':'var(--re)')):'var(--mu)';
            $bg=$pct!==null?($pct>=80?'#dcfce7':($pct>=60?'#fef9c3':'var(--rel)')):'var(--gy)';
          ?>
          <div class="ecard" onclick="openEval(<?= $est['mid'] ?>,'<?= addslashes(h($est['nombre_completo'])) ?>','<?= $est['avatar']?$U.'uploads/estudiantes/'.h($est['avatar']):'' ?>')">
            <?php if ($est['avatar']): ?>
              <img src="<?= $U ?>uploads/estudiantes/<?= h($est['avatar']) ?>" class="eavt">
            <?php else: ?>
              <div class="eini"><?= strtoupper(substr($est['nombre_completo'],0,2)) ?></div>
            <?php endif; ?>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:700;font-size:.87rem;"><?= h($est['nombre_completo']) ?></div>
              <div style="font-size:.71rem;color:var(--mu);"><?= $est['edad'] ?> anos &middot; <?= h($est['colegio']??'Sin colegio') ?></div>
              <?php if ($est['uf']): ?><div style="font-size:.67rem;color:var(--mu);margin-top:.1rem;"><i class="bi bi-clock"></i> Ult. eval: <?= $est['uf'] ?></div><?php endif; ?>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.3rem;" onclick="event.stopPropagation()">
              <?php if ($pct!==null): ?><span class="ppill" style="background:<?= $bg ?>;color:<?= $col ?>;"><?= $pct ?>%</span><?php endif; ?>
              <button onclick="event.stopPropagation();openEval(<?= $est['mid'] ?>,'<?= addslashes(h($est['nombre_completo'])) ?>','<?= $est['avatar']?$U.'uploads/estudiantes/'.h($est['avatar']):'' ?>')" class="bp bt" style="font-size:.7rem;padding:.22rem .55rem;margin-bottom:.2rem;">
                <i class="bi bi-clipboard2-check-fill"></i> Evaluar
              </button>
              <a href="<?= $U ?>modulos/academico/informes/ver.php?matricula=<?= $est['mid'] ?>" target="_blank" class="bp bo" style="font-size:.7rem;padding:.22rem .55rem;">
                <i class="bi bi-file-earmark-text-fill"></i> Informe
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

      <!-- TAB ASISTENCIA -->
      <?php elseif ($tab==='asistencia'): ?>
        <div style="text-align:center;padding:1.8rem 1rem 1.2rem;">
          <div style="font-size:2.8rem;margin-bottom:.8rem;">&#128203;</div>
          <h5 style="margin-bottom:.4rem;">Planilla de asistencia</h5>
          <p style="color:var(--mu);font-size:.86rem;margin-bottom:1.2rem;">Registra o edita la asistencia del grupo para cualquier fecha.</p>
          <a href="<?= $U ?>modulos/academico/asistencia/index.php?grupo=<?= $gid ?>&fecha=<?= date('Y-m-d') ?>"
             class="bp bt" style="font-size:.88rem;padding:.6rem 1.5rem;display:inline-flex;">
            <i class="bi bi-calendar-check-fill"></i> Abrir planilla de hoy
          </a>
        </div>
        <?php
        $stR=$pdo->prepare("
            SELECT se.fecha,se.tema,
                SUM(a.estado='presente') AS p, SUM(a.estado='tarde') AS t,
                SUM(a.estado='excusa')  AS ex, SUM(a.estado='ausente') AS au
            FROM sesiones se LEFT JOIN asistencia a ON a.sesion_id=se.id
            WHERE se.grupo_id=? AND YEAR(se.fecha)=? AND MONTH(se.fecha)=?
            GROUP BY se.id ORDER BY se.fecha DESC
        ");
        $stR->execute([$gid, date('Y'), date('n')]);
        $res=$stR->fetchAll();
        ?>
        <?php if (!empty($res)): ?>
        <div style="margin-top:.5rem;">
          <div style="font-size:.75rem;font-weight:700;color:var(--mu);margin-bottom:.45rem;text-transform:uppercase;letter-spacing:.05em;">Sesiones de <?= date('F Y') ?></div>
          <?php foreach ($res as $r): ?>
          <div style="display:flex;align-items:center;gap:.6rem;padding:.5rem .75rem;background:var(--wh);border:1px solid var(--bd);border-radius:10px;margin-bottom:.35rem;font-size:.8rem;">
            <div style="font-weight:700;min-width:78px;"><?= date('d/m/Y',strtotime($r['fecha'])) ?></div>
            <div style="flex:1;color:var(--mu);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= h($r['tema']??'--') ?></div>
            <span style="background:var(--grl);color:var(--gr);padding:.08rem .45rem;border-radius:12px;font-size:.7rem;font-weight:700;"><?= $r['p'] ?> pres.</span>
            <span style="background:var(--rel);color:var(--re);padding:.08rem .45rem;border-radius:12px;font-size:.7rem;font-weight:700;"><?= $r['au'] ?> aus.</span>
            <a href="<?= $U ?>modulos/academico/asistencia/index.php?grupo=<?= $gid ?>&fecha=<?= $r['fecha'] ?>" class="bp bg" style="padding:.18rem .55rem;font-size:.68rem;"><i class="bi bi-pencil-fill"></i></a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      <!-- TAB OBSERVACIONES -->
      <?php elseif ($tab==='observaciones'): ?>
        <!-- Form nueva obs -->
        <div style="background:var(--wh);border:1px solid var(--bd);border-radius:14px;padding:1rem 1.1rem;margin-bottom:1.1rem;">
          <div style="font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--mu);margin-bottom:.85rem;">
            <i class="bi bi-plus-circle-fill" style="color:var(--tl);"></i> Nueva observacion
          </div>
          <form method="post">
            <input type="hidden" name="accion" value="obs">
            <input type="hidden" name="gid" value="<?= $gid ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:.7rem;">
              <div>
                <label class="fl">Para quien</label>
                <select name="mid" class="fs">
                  <option value="">Grupo completo (general)</option>
                  <?php foreach ($estudiantes as $e): ?>
                    <option value="<?= $e['mid'] ?>"><?= h($e['nombre_completo']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="fl">Fecha</label>
                <input type="date" name="fecha" class="fi" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
              </div>
            </div>
            <div style="margin-bottom:.7rem;">
              <label class="fl">Observacion <span style="color:var(--re);">*</span></label>
              <textarea name="texto" class="ft" required placeholder="Describe el logro, la situacion, la conducta o cualquier nota relevante de la clase..."></textarea>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
              <label style="display:flex;align-items:center;gap:.45rem;font-size:.81rem;cursor:pointer;">
                <input type="checkbox" name="vis" value="1" checked style="width:15px;height:15px;accent-color:var(--tl);">
                <span style="font-weight:700;">Visible al padre/acudiente en su portal</span>
              </label>
              <button type="submit" class="bp bt"><i class="bi bi-save-fill"></i> Guardar</button>
            </div>
          </form>
        </div>

        <!-- Listado -->
        <?php if (empty($obs_list)): ?>
          <div class="emp"><i class="bi bi-journal-x"></i><p>Sin observaciones para este grupo.</p></div>
        <?php else: ?>
          <div style="font-size:.75rem;font-weight:700;color:var(--mu);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em;"><?= count($obs_list) ?> observaciones</div>
          <?php foreach ($obs_list as $ob): ?>
          <div class="ocard <?= $ob['tipo']==='general'?'gen':'' ?>">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
              <div style="flex:1;">
                <?php if ($ob['tipo']==='general'): ?>
                  <span style="font-size:.68rem;font-weight:800;background:var(--or);color:#fff;padding:.1rem .45rem;border-radius:8px;display:inline-block;margin-bottom:.35rem;">General del grupo</span>
                <?php else: ?>
                  <span style="font-size:.68rem;font-weight:800;background:var(--tll);color:var(--tl);padding:.1rem .45rem;border-radius:8px;display:inline-block;margin-bottom:.35rem;">
                    <i class="bi bi-person-fill"></i> <?= h($ob['enombre']) ?>
                  </span>
                <?php endif; ?>
                <p style="font-size:.84rem;line-height:1.62;margin:.25rem 0 0;"><?= nl2br(h($ob['texto'])) ?></p>
              </div>
              <div style="flex-shrink:0;">
                <?php if ($ob['visible_padre']): ?>
                  <span style="font-size:.67rem;background:var(--grl);color:var(--gr);padding:.1rem .42rem;border-radius:8px;font-weight:700;white-space:nowrap;">
                    <i class="bi bi-eye-fill"></i> Visible al padre
                  </span>
                <?php else: ?>
                  <span style="font-size:.67rem;background:var(--gy);color:var(--mu);padding:.1rem .42rem;border-radius:8px;font-weight:700;white-space:nowrap;">
                    <i class="bi bi-eye-slash-fill"></i> Solo interno
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <div class="ometa">
              <span><i class="bi bi-calendar3"></i> <?= date('d/m/Y',strtotime($ob['fecha'])) ?></span>
              <span><i class="bi bi-person-fill"></i> <?= h($ob['unombre']) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      <!-- TAB EVALUACIONES -->
      <?php elseif ($tab==='evaluaciones'): ?>
        <?php
        $evals = $pdo->prepare("
            SELECT ev.*, r.nombre AS rubrica_nombre,
                   e.nombre_completo AS estudiante,
                   e.avatar,
                   ROUND(COALESCE(SUM(ed.puntaje),0)/NULLIF(
                       (SELECT SUM(rc2.puntaje_max) FROM rubrica_criterios rc2 WHERE rc2.rubrica_id=ev.rubrica_id),0)*100) AS pct
            FROM evaluaciones ev
            JOIN matriculas m ON m.id=ev.matricula_id
            JOIN estudiantes e ON e.id=m.estudiante_id
            JOIN rubricas r ON r.id=ev.rubrica_id
            LEFT JOIN evaluacion_detalle ed ON ed.evaluacion_id=ev.id
            WHERE m.grupo_id=?
            GROUP BY ev.id
            ORDER BY ev.fecha DESC, e.nombre_completo
        ");
        $evals->execute([$gid]);
        $evals = $evals->fetchAll();
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.6rem;margin-bottom:1rem;">
          <div style="font-size:.75rem;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:.05em;">
            <?= count($evals) ?> evaluaciones registradas
          </div>
          <button onclick="openEval(0,'Selecciona un estudiante','')" class="bp bt" style="font-size:.8rem;">
            <i class="bi bi-plus-lg"></i> Nueva evaluacion
          </button>
        </div>
        <?php if (empty($evals)): ?>
          <div class="emp">
            <i class="bi bi-clipboard2-x"></i>
            <p>No hay evaluaciones para este grupo.<br>Haz clic en un estudiante para registrar una.</p>
          </div>
        <?php else: ?>
          <?php
          // Agrupar por fecha
          $por_fecha = [];
          foreach ($evals as $ev) { $por_fecha[$ev['fecha']][] = $ev; }
          ?>
          <?php foreach ($por_fecha as $fecha => $evs): ?>
          <div style="font-size:.72rem;font-weight:800;color:var(--mu);text-transform:uppercase;letter-spacing:.06em;margin:1rem 0 .4rem;">
            <i class="bi bi-calendar3" style="color:var(--tl);"></i>
            <?= date('d/m/Y', strtotime($fecha)) ?>
            <span style="background:var(--tll);color:var(--tl);padding:.1rem .45rem;border-radius:8px;margin-left:.3rem;"><?= count($evs) ?></span>
          </div>
          <?php foreach ($evs as $ev):
            $pct = $ev['pct'];
            $col = $pct!==null ? ($pct>=80?'#16a34a':($pct>=60?'#ca8a04':'var(--re)')) : 'var(--mu)';
            $bg  = $pct!==null ? ($pct>=80?'#dcfce7':($pct>=60?'#fef9c3':'var(--rel)')) : 'var(--gy)';
          ?>
          <div style="background:var(--wh);border:1.5px solid var(--bd);border-radius:12px;padding:.75rem .95rem;display:flex;align-items:center;gap:.75rem;margin-bottom:.45rem;">
            <?php if ($ev['avatar']): ?>
              <img src="<?= $U ?>uploads/estudiantes/<?= h($ev['avatar']) ?>" class="eavt">
            <?php else: ?>
              <div class="eini"><?= strtoupper(substr($ev['estudiante'],0,2)) ?></div>
            <?php endif; ?>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:700;font-size:.86rem;"><?= h($ev['estudiante']) ?></div>
              <div style="font-size:.72rem;color:var(--mu);">
                <i class="bi bi-journal-check"></i> <?= h($ev['rubrica_nombre']) ?>
                <?php if ($ev['observaciones']): ?>
                  &middot; <?= h(mb_substr($ev['observaciones'],0,50)) ?><?= strlen($ev['observaciones'])>50?'...':'' ?>
                <?php endif; ?>
              </div>
            </div>
            <?php if ($pct!==null): ?>
              <span class="ppill" style="background:<?= $bg ?>;color:<?= $col ?>;"><?= $pct ?>%</span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>
        <?php endif; ?>

      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal evaluacion -->
<div class="ovl" id="eovl">
  <div class="obox">
    <div class="ohead">
      <div id="eavt2" style="width:48px;height:48px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid rgba(255,255,255,.22);"></div>
      <div>
        <div id="enombre" style="font-family:'Poppins',sans-serif;font-size:.96rem;font-weight:800;color:#fff;"></div>
        <div style="font-size:.7rem;color:rgba(255,255,255,.48);">Registrar evaluacion</div>
      </div>
      <button class="bx" onclick="closeEval()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="obody">
      <form method="POST" action="<?= $U ?>docente/guardar_eval.php">
        <input type="hidden" name="matricula_id" id="emid">
        <input type="hidden" name="grupo_id" value="<?= $gid ?>">
        <div id="estWrap" style="margin-bottom:.75rem;display:none;">
          <label class="fl">Estudiante <span style="color:var(--re);">*</span></label>
          <select id="estSel" class="fs" onchange="document.getElementById('emid').value=this.value">
            <option value="">-- Selecciona estudiante --</option>
            <?php foreach ($estudiantes as $est): ?>
              <option value="<?= $est['mid'] ?>"><?= h($est['nombre_completo']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;margin-bottom:.85rem;">
          <div>
            <label class="fl">Rubrica</label>
            <select name="rubrica_id" id="esel" class="fs" onchange="loadCrit(this.value)">
              <option value="">Selecciona...</option>
              <?php foreach ($rubricas as $r): ?>
                <option value="<?= $r['id'] ?>"><?= h($r['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="fl">Fecha</label>
            <input type="date" name="fecha" class="fi" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div id="critWrap"></div>
        <div style="margin-bottom:.9rem;">
          <label class="fl">Observaciones</label>
          <textarea name="observaciones" class="ft" rows="2" placeholder="Comentarios sobre el desempeno..."></textarea>
        </div>
        <button type="submit" class="bp bt" style="width:100%;justify-content:center;padding:.72rem;">
          <i class="bi bi-check-circle-fill"></i> Guardar evaluacion
        </button>
      </form>
    </div>
  </div>
</div>

<script>
let ra=<?= $rubricas[0]['id'] ?? 0 ?>, rn='<?= addslashes($rubricas[0]['nombre'] ?? '') ?>';

function openEval(mid, nombre, av) {
  const eWrap = document.getElementById('estWrap');
  if (!mid) {
    // Abierto desde "Nueva evaluacion" - mostrar selector
    eWrap.style.display = 'block';
    document.getElementById('emid').value = '';
    document.getElementById('enombre').textContent = 'Nueva evaluacion';
    document.getElementById('eavt2').innerHTML = '<div style="width:48px;height:48px;background:linear-gradient(135deg,var(--tl),var(--tld));display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;"><i class="bi bi-clipboard2-check-fill"></i></div>';
  } else {
    eWrap.style.display = 'none';
    document.getElementById('emid').value = mid;
    document.getElementById('enombre').textContent = nombre;
    const d = document.getElementById('eavt2');
    d.innerHTML = av
      ? `<img src="${av}" style="width:100%;height:100%;object-fit:cover;">`
      : `<div style="width:48px;height:48px;background:linear-gradient(135deg,var(--tl),var(--tld));display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:.8rem;font-weight:800;color:#fff;">${nombre.substring(0,2).toUpperCase()}</div>`;
  }
  if (ra) { document.getElementById('esel').value = ra; loadCrit(ra); }
  document.getElementById('eovl').style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function closeEval() {
  document.getElementById('eovl').style.display = 'none';
  document.body.style.overflow = '';
}

function loadCrit(rid) {
  if (!rid) { document.getElementById('critWrap').innerHTML=''; return; }
  fetch('<?= $U ?>modulos/academico/evaluaciones/criterios_ajax.php?rubrica_id='+rid)
    .then(r=>r.json()).then(cs=>{
      let h='';
      cs.forEach(c=>{
        h+=`<div class="crow">
          <div style="font-size:.82rem;font-weight:700;margin-bottom:.42rem;">${c.criterio} <span style="font-size:.67rem;color:var(--mu);font-weight:400;">(max ${c.puntaje_max})</span></div>
          <div class="strs">${[...Array(parseInt(c.puntaje_max))].map((_,i)=>`<span class="str" data-c="${c.id}" data-v="${i+1}" onclick="setSt('${c.id}',${i+1},${c.puntaje_max})">&#9734;</span>`).join('')}</div>
          <input type="hidden" name="criterio_id[]" value="${c.id}">
          <input type="hidden" name="puntaje[]" id="pt_${c.id}" value="0">
        </div>`;
      });
      document.getElementById('critWrap').innerHTML=h;
    });
}

function setSt(cid, val) {
  document.getElementById('pt_'+cid).value=val;
  document.querySelectorAll(`.str[data-c="${cid}"]`).forEach(s=>{
    const v=parseInt(s.dataset.v);
    s.textContent=v<=val?'&#9733;':'&#9734;';
    s.classList.toggle('on',v<=val);
  });
}

document.getElementById('eovl').addEventListener('click',e=>{
  if(e.target===document.getElementById('eovl')) closeEval();
});
</script>
</body>
</html>
