<?php
// includes/sidebar.php
$menu_activo = $menu_activo ?? '';
$rol_actual  = $_SESSION['usuario_rol'] ?? '';

$sedes_nav = $pdo->query("SELECT * FROM sedes WHERE activa = 1 ORDER BY nombre")->fetchAll();
$sede_nombre_activa = 'Todas las sedes';
if ($rol_actual !== 'admin_general' && !empty($_SESSION['sede_id'])) {
    foreach ($sedes_nav as $s) {
        if ($s['id'] == $_SESSION['sede_id']) { $sede_nombre_activa = $s['nombre']; break; }
    }
}
$iniciales = strtoupper(substr($_SESSION['usuario_nombre'] ?? 'A', 0, 2));

function menu_class($nombre) {
    global $menu_activo;
    return $menu_activo === $nombre ? 'menu-item active' : 'menu-item';
}

$U = BASE_URL;

// Permisos por secci&oacute;n
$es_admin    = in_array($rol_actual, ['admin_general','admin_sede']);
$es_coord    = in_array($rol_actual, ['admin_general','admin_sede','coordinador_pedagogico']);
$es_docente  = in_array($rol_actual, ['admin_general','admin_sede','coordinador_pedagogico','docente']);
$solo_docente = $rol_actual === 'docente';
?>
<nav class="sidebar" id="sidebar">

  <div class="sidebar-brand">
    <img src="<?= $U ?>assets/img/logo R sin fondo.png" alt="RSAL"/>
    <div class="sidebar-brand-text">
      <strong>ROBOTSchool</strong>
      <small>Academy Learning</small>
    </div>
  </div>

  <div class="sede-selector">
    <i class="bi bi-building"></i>
    <span><?= h($sede_nombre_activa) ?></span>
  </div>

  <div class="sidebar-menu">

    <?php if ($es_admin): ?>
    <div class="menu-section">Principal</div>
    <a href="<?= $U ?>dashboard.php" class="<?= menu_class('dashboard') ?>">
      <i class="bi bi-grid-fill"></i> Dashboard
    </a>
    <?php elseif ($rol_actual === 'coordinador_pedagogico'): ?>
    <div class="menu-section">Principal</div>
    <a href="<?= $U ?>modulos/academico/dashboard.php" class="<?= menu_class('dashboard') ?>">
      <i class="bi bi-grid-fill"></i> Dashboard Acad&eacute;mico
    </a>
    <?php endif; ?>

    <?php if ($es_docente): ?>
    <div class="menu-section">Mi Portal</div>
    <a href="<?= $U ?>docente/index.php" class="<?= menu_class('docente') ?>">
      <i class="bi bi-person-workspace"></i> Mis Grupos
    </a>
    <a href="<?= $U ?>modulos/extracurriculares/asistencia/mis_sesiones.php" class="<?= menu_class('ec_mis_sesiones') ?>">
      <i class="bi bi-calendar-check-fill"></i> Mis sesiones EC
    </a>
    <?php endif; ?>

    <?php if ($es_coord): ?>
    <div class="menu-section">Acad&eacute;mico &middot; Dise&ntilde;o curricular</div>
    <a href="<?= $U ?>modulos/academico/cursos/index.php" class="<?= menu_class('cursos') ?>">
      <i class="bi bi-journal-richtext"></i> Cursos
    </a>
    <a href="<?= $U ?>modulos/academico/temas/index.php" class="<?= menu_class('temas') ?>">
      <i class="bi bi-bookmark-fill"></i> Temas
    </a>
    <a href="<?= $U ?>modulos/academico/actividades/index.php" class="<?= menu_class('actividades') ?>">
      <i class="bi bi-puzzle-fill"></i> Actividades
    </a>
    <a href="<?= $U ?>modulos/academico/rubricas/index.php" class="<?= menu_class('rubricas') ?>">
      <i class="bi bi-list-check"></i> R&uacute;bricas
    </a>
    <?php endif; ?>

    <?php if ($es_docente): ?>
    <div class="menu-section">Acad&eacute;mico &middot; Aula</div>
    <a href="<?= $U ?>modulos/academico/grupos/index.php" class="<?= menu_class('grupos') ?>">
      <i class="bi bi-calendar3"></i> Grupos y Horarios
    </a>
    <a href="<?= $U ?>modulos/academico/asistencia/index.php" class="<?= menu_class('asistencia') ?>">
      <i class="bi bi-calendar-check-fill"></i> Asistencia
    </a>
    <a href="<?= $U ?>modulos/academico/evaluaciones/index.php" class="<?= menu_class('evaluaciones') ?>">
      <i class="bi bi-star-fill"></i> Evaluaciones
    </a>
    <?php endif; ?>

    <?php if ($es_coord): ?>
    <div class="menu-section">Matr&iacute;cula</div>
    <a href="<?= $U ?>modulos/estudiantes/index.php" class="<?= menu_class('estudiantes') ?>">
      <i class="bi bi-person-badge-fill"></i> Estudiantes
    </a>
    <a href="<?= $U ?>modulos/academico/estudiantes_por_curso.php" class="<?= menu_class('estudiantes_curso') ?>">
      <i class="bi bi-diagram-3-fill"></i> Estudiantes por curso
    </a>
    <?php if ($es_admin): ?>
    <a href="<?= $U ?>modulos/padres/index.php" class="<?= menu_class('padres') ?>">
      <i class="bi bi-people-fill"></i> Padres / Acudientes
    </a>
    <a href="<?= $U ?>modulos/matriculas/index.php" class="<?= menu_class('matriculas') ?>">
      <i class="bi bi-clipboard2-check-fill"></i> Matr&iacute;culas
    </a>
    <?php endif; ?>

    <div class="menu-section">Seguimiento</div>
    <a href="<?= $U ?>modulos/academico/talleristas.php" class="<?= menu_class('talleristas') ?>">
      <i class="bi bi-person-workspace"></i> Talleristas
    </a>
    <a href="<?= $U ?>modulos/academico/informes/index.php" class="<?= menu_class('informes') ?>">
      <i class="bi bi-file-earmark-text-fill"></i> Informes acad&eacute;micos
    </a>
    <a href="<?= $U ?>modulos/reportes/index.php" class="<?= menu_class('reportes') ?>">
      <i class="bi bi-bar-chart-fill"></i> Reportes
    </a>

    <div class="menu-section">Extracurriculares</div>
    <a href="<?= $U ?>modulos/extracurriculares/index.php" class="<?= menu_class('extracurriculares') ?>">
      <i class="bi bi-grid-1x2-fill"></i> Panel extracurriculares
    </a>
    <a href="<?= $U ?>modulos/extracurriculares/clientes/index.php" class="<?= menu_class('ec_clientes') ?>">
      <i class="bi bi-building-fill"></i> Clientes
    </a>
    <a href="<?= $U ?>modulos/extracurriculares/contratos/index.php" class="<?= menu_class('ec_contratos') ?>">
      <i class="bi bi-file-earmark-text-fill"></i> Contratos
    </a>
    <a href="<?= $U ?>modulos/extracurriculares/programas/index.php" class="<?= menu_class('ec_programas') ?>">
      <i class="bi bi-bookmark-check-fill"></i> Programas
    </a>
    <a href="<?= $U ?>modulos/extracurriculares/calendario/index.php" class="<?= menu_class('ec_calendario') ?>">
      <i class="bi bi-calendar3"></i> Calendario
    </a>
    <a href="<?= $U ?>modulos/extracurriculares/rutas/index.php" class="<?= menu_class('ec_rutas') ?>">
      <i class="bi bi-geo-alt-fill"></i> Rutas del d&iacute;a
    </a>
    <?php endif; ?>

    <?php if ($es_admin): ?>
    <div class="menu-section">Finanzas</div>
    <a href="<?= $U ?>modulos/pagos/index.php" class="<?= menu_class('pagos') ?>">
      <i class="bi bi-cash-stack"></i> Pagos
    </a>
    <a href="<?= $U ?>modulos/reportes/cartera.php" class="<?= menu_class('cartera') ?>">
      <i class="bi bi-wallet2"></i> Cartera
    </a>
    <a href="<?= $U ?>modulos/reportes/index.php" class="<?= menu_class('reportes') ?>">
      <i class="bi bi-bar-chart-fill"></i> Reportes
    </a>

    <div class="menu-section">Administraci&oacute;n</div>
    <a href="<?= $U ?>modulos/equipos/index.php" class="<?= menu_class('equipos') ?>">
      <i class="bi bi-cpu-fill"></i> Equipos
    </a>
    <a href="<?= $U ?>modulos/sedes/index.php" class="<?= menu_class('sedes') ?>">
      <i class="bi bi-building-fill"></i> Sedes
    </a>
    <a href="<?= $U ?>modulos/usuarios/index.php" class="<?= menu_class('usuarios') ?>">
      <i class="bi bi-shield-lock-fill"></i> Usuarios
    </a>
    <?php endif; ?>

  </div>

  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar"><?= $iniciales ?></div>
      <div class="user-info">
        <strong><?= h($_SESSION['usuario_nombre'] ?? '') ?></strong>
        <small><?= h(getRolLabel($rol_actual)) ?></small>
      </div>
      <button class="btn-logout" title="Cerrar sesi&oacute;n"
              onclick="window.location='<?= $U ?>logout.php'">
        <i class="bi bi-box-arrow-right"></i>
      </button>
    </div>
  </div>

</nav>
