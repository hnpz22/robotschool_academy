<?php
// config/auth.php &mdash; ROBOTSchool Academy Learning

if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

function requireLogin() {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Jerarqu&iacute;a: admin_general > admin_sede > coordinador_pedagogico > docente > padre
 */
function tienePermiso($rolMinimo) {
    $jerarquia = [
        'admin_general'          => 5,
        'admin_sede'             => 4,
        'coordinador_pedagogico' => 3,
        'docente'                => 2,
        'padre'                  => 1,
    ];
    $rolUsuario = $_SESSION['usuario_rol'] ?? '';
    $nivelUsuario = $jerarquia[$rolUsuario] ?? 0;
    $nivelMinimo  = $jerarquia[$rolMinimo]  ?? 99;
    return $nivelUsuario >= $nivelMinimo;
}

function requireRol($rolMinimo) {
    requireLogin();
    $rol = $_SESSION['usuario_rol'] ?? '';
    // Padres van siempre a su portal
    if ($rol === 'padre') {
        header('Location: ' . BASE_URL . 'portal/index.php');
        exit;
    }
    // Coordinador sin permiso va a su dashboard acad&eacute;mico
    if ($rol === 'coordinador_pedagogico' && !tienePermiso($rolMinimo)) {
        header('Location: ' . BASE_URL . 'modulos/academico/dashboard.php');
        exit;
    }
    // Docentes sin permiso van a su portal
    if ($rol === 'docente' && !tienePermiso($rolMinimo)) {
        header('Location: ' . BASE_URL . 'docente/index.php');
        exit;
    }
    if (!tienePermiso($rolMinimo)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:2rem;background:#fff0f1;">
            <h2 style="color:#E8192C;">Acceso denegado</h2>
            <p>No tienes permisos para acceder a esta secci&oacute;n.</p>
            <a href="' . BASE_URL . 'dashboard.php" style="color:#1E4DA1;">Volver al inicio</a>
        </div>');
    }
}

function getSedeFiltro() {
    $rol = $_SESSION['usuario_rol'] ?? '';
    // admin_general y coordinador_pedagogico ven todas las sedes
    if ($rol === 'admin_general' || $rol === 'coordinador_pedagogico') return null;
    return $_SESSION['sede_id'] ?? null;
}

function getRolLabel($rol) {
    return [
        'admin_general'          => 'Administrador General',
        'admin_sede'             => 'Administrador de Sede',
        'coordinador_pedagogico' => 'Coordinador Pedag&oacute;gico',
        'docente'                => 'Docente / Tallerista',
        'padre'                  => 'Padre / Acudiente',
    ][$rol] ?? $rol;
}

function esDocente() {
    return in_array($_SESSION['usuario_rol'] ?? '', ['docente','coordinador_pedagogico']);
}

function esCoordinador() {
    return in_array($_SESSION['usuario_rol'] ?? '', ['coordinador_pedagogico','admin_sede','admin_general']);
}
