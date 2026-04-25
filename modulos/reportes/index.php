<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Reportes';
$menu_activo = 'reportes';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;

// KPIs generales &mdash; WHERE siempre v&aacute;lido con condici&oacute;n base
$and_sede_e = $sede_filtro ? 'AND sede_id='.(int)$sede_filtro : '';
$and_sede_m = $sede_filtro ? 'AND m.sede_id='.(int)$sede_filtro : '';
$and_sede_p = $sede_filtro ? 'AND p.sede_id='.(int)$sede_filtro : '';
$and_sede_g = $sede_filtro ? 'AND g.sede_id='.(int)$sede_filtro : '';
$and_sede_c = $sede_filtro ? 'AND sede_id='.(int)$sede_filtro : '';

$kpis = [
    'estudiantes' => $pdo->query("SELECT COUNT(*) FROM estudiantes WHERE activo=1 $and_sede_e")->fetchColumn(),
    'matriculas'  => $pdo->query("SELECT COUNT(*) FROM matriculas m WHERE m.estado='activa' $and_sede_m")->fetchColumn(),
    'pagos_ok'    => $pdo->query("SELECT COUNT(*) FROM pagos p WHERE p.estado='pagado' $and_sede_p")->fetchColumn(),
    'pagos_venc'  => $pdo->query("SELECT COUNT(*) FROM pagos p WHERE p.estado='vencido' $and_sede_p")->fetchColumn(),
    'recaudado'   => $pdo->query("SELECT COALESCE(SUM(valor_pagado),0) FROM pagos p WHERE 1=1 $and_sede_p")->fetchColumn(),
    'por_cobrar'  => $pdo->query("SELECT COALESCE(SUM(valor_total-valor_pagado),0) FROM pagos p WHERE p.estado != 'exonerado' $and_sede_p")->fetchColumn(),
    'cursos'      => $pdo->query("SELECT COUNT(*) FROM cursos WHERE publicado=1 $and_sede_c")->fetchColumn(),
    'grupos'      => $pdo->query("SELECT COUNT(*) FROM grupos g WHERE g.activo=1 $and_sede_g")->fetchColumn(),
];

$where_sede2 = $sede_filtro ? 'AND e.sede_id='.(int)$sede_filtro : '';
$where_m2    = $sede_filtro ? 'AND m.sede_id='.(int)$sede_filtro : '';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Reportes <small>Estad&iacute;sticas y exportaciones</small></div>
</header>
<main class="main-content">

  <!-- KPIs -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <?php
    $kpi_config = [
      ['Estudiantes activos', $kpis['estudiantes'], 'bi-person-badge-fill', '#1DA99A', null],
      ['Matr&iacute;culas activas',  $kpis['matriculas'],  'bi-clipboard2-check-fill', '#1E4DA1', null],
      ['Pagos al d&iacute;a',        $kpis['pagos_ok'],    'bi-check-circle-fill', '#16a34a', null],
      ['Pagos vencidos',      $kpis['pagos_venc'],  'bi-x-circle-fill', '#E8192C', null],
      ['Recaudado',           formatCOP($kpis['recaudado']), 'bi-cash-stack', '#F26522', null],
      ['Por cobrar',          formatCOP($kpis['por_cobrar']),'bi-hourglass-split', '#ca8a04', null],
      ['Cursos publicados',   $kpis['cursos'],      'bi-journal-richtext', '#7c3aed', null],
      ['Grupos activos',      $kpis['grupos'],      'bi-people-fill', '#0ea5e9', null],
    ];
    foreach ($kpi_config as [$lbl,$val,$ico,$col,$_]):
    ?>
    <div class="card-rsal" style="margin:0;text-align:center;padding:1rem .8rem;">
      <i class="bi <?= $ico ?>" style="font-size:1.4rem;color:<?= $col ?>;"></i>
      <div style="font-family:'Poppins',sans-serif;font-size:1.3rem;font-weight:900;color:<?= $col ?>;margin:.3rem 0;"><?= $val ?></div>
      <div style="font-size:.7rem;color:var(--muted);font-weight:600;"><?= $lbl ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- EXPORTACIONES -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;">

    <!-- Reporte de estudiantes -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-person-badge-fill"></i> Estudiantes</div>
      <p style="font-size:.82rem;color:var(--muted);margin-bottom:1rem;">Listado completo con datos personales, m&eacute;dicos y matr&iacute;cula actual.</p>
      <div style="display:flex;flex-direction:column;gap:.5rem;">
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=estudiantes&formato=html<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           target="_blank" class="btn-rsal-secondary" style="justify-content:center;">
          <i class="bi bi-eye-fill"></i> Ver en pantalla
        </a>
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=estudiantes&formato=csv<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           class="btn-rsal-primary" style="justify-content:center;">
          <i class="bi bi-file-earmark-spreadsheet-fill"></i> Exportar CSV
        </a>
      </div>
    </div>

    <!-- Reporte de pagos -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-cash-stack"></i> Pagos y cartera</div>
      <p style="font-size:.82rem;color:var(--muted);margin-bottom:1rem;">Estado de pagos por estudiante con sem&aacute;foro, saldo y fechas.</p>
      <div style="display:flex;flex-direction:column;gap:.5rem;">
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=pagos&formato=html<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           target="_blank" class="btn-rsal-secondary" style="justify-content:center;">
          <i class="bi bi-eye-fill"></i> Ver en pantalla
        </a>
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=pagos&formato=csv<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           class="btn-rsal-primary" style="justify-content:center;">
          <i class="bi bi-file-earmark-spreadsheet-fill"></i> Exportar CSV
        </a>
      </div>
    </div>

    <!-- Reporte de matr&iacute;culas -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-clipboard2-check-fill"></i> Matr&iacute;culas por grupo</div>
      <p style="font-size:.82rem;color:var(--muted);margin-bottom:1rem;">Listado de estudiantes inscritos por curso y grupo con horario.</p>
      <div style="display:flex;flex-direction:column;gap:.5rem;">
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=matriculas&formato=html<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           target="_blank" class="btn-rsal-secondary" style="justify-content:center;">
          <i class="bi bi-eye-fill"></i> Ver en pantalla
        </a>
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=matriculas&formato=csv<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           class="btn-rsal-primary" style="justify-content:center;">
          <i class="bi bi-file-earmark-spreadsheet-fill"></i> Exportar CSV
        </a>
      </div>
    </div>

    <!-- Reporte de evaluaciones -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-star-fill"></i> Evaluaciones e informes</div>
      <p style="font-size:.82rem;color:var(--muted);margin-bottom:1rem;">Resultados de evaluaciones con puntajes y porcentajes por estudiante.</p>
      <div style="display:flex;flex-direction:column;gap:.5rem;">
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=evaluaciones&formato=html<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           target="_blank" class="btn-rsal-secondary" style="justify-content:center;">
          <i class="bi bi-eye-fill"></i> Ver en pantalla
        </a>
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=evaluaciones&formato=csv<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           class="btn-rsal-primary" style="justify-content:center;">
          <i class="bi bi-file-earmark-spreadsheet-fill"></i> Exportar CSV
        </a>
      </div>
    </div>

    <!-- Reporte asistencia por grupo -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-people-fill"></i> Ocupaci&oacute;n de grupos</div>
      <p style="font-size:.82rem;color:var(--muted);margin-bottom:1rem;">Cupos disponibles vs inscritos por grupo con sem&aacute;foro de ocupaci&oacute;n.</p>
      <div style="display:flex;flex-direction:column;gap:.5rem;">
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=grupos&formato=html<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           target="_blank" class="btn-rsal-secondary" style="justify-content:center;">
          <i class="bi bi-eye-fill"></i> Ver en pantalla
        </a>
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=grupos&formato=csv<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           class="btn-rsal-primary" style="justify-content:center;">
          <i class="bi bi-file-earmark-spreadsheet-fill"></i> Exportar CSV
        </a>
      </div>
    </div>

    <!-- Reporte padres -->
    <div class="card-rsal" style="margin:0;">
      <div class="card-rsal-title"><i class="bi bi-people-fill"></i> Padres y contactos</div>
      <p style="font-size:.82rem;color:var(--muted);margin-bottom:1rem;">Directorio de padres con tel&eacute;fonos, emails y estado de autorizaciones.</p>
      <div style="display:flex;flex-direction:column;gap:.5rem;">
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=padres&formato=html<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           target="_blank" class="btn-rsal-secondary" style="justify-content:center;">
          <i class="bi bi-eye-fill"></i> Ver en pantalla
        </a>
        <a href="<?= $U ?>modulos/reportes/exportar.php?tipo=padres&formato=csv<?= $sede_filtro?"&sede=$sede_filtro":'' ?>"
           class="btn-rsal-primary" style="justify-content:center;">
          <i class="bi bi-file-earmark-spreadsheet-fill"></i> Exportar CSV
        </a>
      </div>
    </div>

  </div>
</main>
<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
