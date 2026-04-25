<?php
// config/config.php &mdash; ROBOTSchool Academy Learning
// Configuraci&oacute;n principal del sistema

define('RSAL_VERSION', '1.0.0');
define('RSAL_NOMBRE',  'ROBOTSchool Academy Learning');
define('RSAL_URL',     'http://academy.robotschool.com.co');

// &#9472;&#9472; RUTAS ABSOLUTAS DEL SERVIDOR &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
// ROOT apunta siempre a la carpeta ra&iacute;z del proyecto
// sin importar desde qu&eacute; m&oacute;dulo se incluya este archivo
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

// &#9472;&#9472; URL BASE PARA HTML (im&aacute;genes, CSS, links) &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
// Usa el nombre real de la carpeta del proyecto (respeta may&uacute;sculas)
// ROOT = /Applications/XAMPP/.../htdocs/ROBOTSchool_Academy
// basename(ROOT) = ROBOTSchool_Academy  &#8592; nombre real de la carpeta
if (!defined('BASE_URL')) {
    $_protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_carpeta   = basename(ROOT);  // nombre exacto de la carpeta en htdocs
    define('BASE_URL', $_protocol . '://' . $_host . '/' . $_carpeta . '/');
    unset($_protocol, $_host, $_carpeta);
}

// &#9472;&#9472; BASE DE DATOS &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
define('DB_HOST', 'localhost');
define('DB_NAME', 'robotschool_academy');
define('DB_USER', 'root');        // Cambiar en producci&oacute;n
define('DB_PASS', '');            // Cambiar en producci&oacute;n
define('DB_CHARSET', 'utf8mb4');

// &#9472;&#9472; SESI&Oacute;N &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_name('RSAL_SESSION');
    session_start();
}

// &#9472;&#9472; CONEXI&Oacute;N PDO &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // En producci&oacute;n: loguear sin mostrar detalles
    die('<div style="font-family:sans-serif;padding:2rem;color:#c00;">
        <strong>Error de conexi&oacute;n a la base de datos.</strong><br>
        Verifica las credenciales en config/config.php
    </div>');
}

// &#9472;&#9472; ZONA HORARIA &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
date_default_timezone_set('America/Bogota');

// &#9472;&#9472; HELPERS &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function formatCOP($valor) {
    return '$ ' . number_format($valor, 0, ',', '.');
}

function formatFecha($fecha) {
    if (!$fecha) return '&mdash;';
    return date('d/m/Y', strtotime($fecha));
}

// tienePermiso() se define en config/auth.php
