<?php
// config/config.php &mdash; ROBOTSchool Academy Learning
// Configuraci&oacute;n principal del sistema
//
// Lee variables de entorno (Docker / .env del servidor) con fallback
// a defaults locales para que XAMPP siga funcionando sin tocar nada.

// -- HELPER ENV ----------------------------------------------------------
if (!function_exists('rsal_env')) {
    function rsal_env($key, $default = null) {
        $v = getenv($key);
        if ($v === false || $v === '') {
            return $default;
        }
        return $v;
    }
}

define('RSAL_VERSION', '1.0.0');
define('RSAL_NOMBRE',  'ROBOTSchool Academy Learning');
define('RSAL_URL',     rsal_env('RSAL_URL', 'http://academy.robotschool.com.co'));

// -- ENTORNO -------------------------------------------------------------
// 'production' o 'development' (default). Controla cookie_secure, errores.
define('RSAL_ENV', rsal_env('RSAL_ENV', 'development'));

// -- RUTAS ---------------------------------------------------------------
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

// -- URL BASE PARA HTML --------------------------------------------------
// En producci&oacute;n (subdominio dedicado): RSAL_BASE_URL=https://academy.miel-robotschool.com/
// En XAMPP local: se autocalcula como antes (http://localhost/robotschool_academy/)
if (!defined('BASE_URL')) {
    $_envBase = rsal_env('RSAL_BASE_URL');
    if ($_envBase) {
        define('BASE_URL', rtrim($_envBase, '/') . '/');
    } else {
        $_protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $_host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $_carpeta   = basename(ROOT);
        define('BASE_URL', $_protocol . '://' . $_host . '/' . $_carpeta . '/');
        unset($_protocol, $_host, $_carpeta);
    }
    unset($_envBase);
}

// -- BASE DE DATOS -------------------------------------------------------
define('DB_HOST',    rsal_env('DB_HOST', 'localhost'));
define('DB_NAME',    rsal_env('DB_NAME', 'robotschool_academy'));
define('DB_USER',    rsal_env('DB_USER', 'root'));
define('DB_PASS',    rsal_env('DB_PASS', ''));
define('DB_CHARSET', rsal_env('DB_CHARSET', 'utf8mb4'));

// -- SESI&Oacute;N -------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (RSAL_ENV === 'production') {
        ini_set('session.cookie_secure', 1);
    }
    session_name('RSAL_SESSION');
    session_start();
}

// -- CONEXI&Oacute;N PDO -------------------------------------------------
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // En producci&oacute;n: loguear sin mostrar detalles
    if (RSAL_ENV !== 'production') {
        error_log('RSAL DB error: ' . $e->getMessage());
    }
    die('<div style="font-family:sans-serif;padding:2rem;color:#c00;">
        <strong>Error de conexi&oacute;n a la base de datos.</strong><br>
        Verifica las credenciales (variables de entorno DB_HOST/DB_USER/DB_PASS o config/config.php).
    </div>');
}

// -- ZONA HORARIA --------------------------------------------------------
date_default_timezone_set('America/Bogota');

// -- HELPERS -------------------------------------------------------------
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
