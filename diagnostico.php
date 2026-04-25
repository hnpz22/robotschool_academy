<?php
// diagnostico.php &mdash; ROBOTSchool Academy Learning
// Coloca este archivo en la ra&iacute;z del proyecto y abre:
// http://localhost/ROBOTSchool_Academy/diagnostico.php
// ELIMINAR despu&eacute;s de resolver el problema

require_once __DIR__ . '/config/config.php';

echo '<style>body{font-family:monospace;padding:2rem;background:#f5f5f5;} 
.ok{color:green;font-weight:bold;} .err{color:red;font-weight:bold;} 
.box{background:#fff;border:1px solid #ddd;border-radius:8px;padding:1rem;margin-bottom:1rem;}
h3{margin-bottom:.5rem;font-family:sans-serif;}</style>';

echo '<h2 style="font-family:sans-serif;">&#128269; Diagn&oacute;stico RSAL</h2>';

// 1. ROOT y BASE_URL
echo '<div class="box">';
echo '<h3>Constantes</h3>';
echo 'ROOT = <strong>' . ROOT . '</strong><br>';
echo 'BASE_URL = <strong>' . BASE_URL . '</strong><br>';
echo 'basename(ROOT) = <strong>' . basename(ROOT) . '</strong>';
echo '</div>';

// 2. Verificar carpetas cr&iacute;ticas
$carpetas = [
    ROOT . '/uploads',
    ROOT . '/uploads/cursos',
    ROOT . '/uploads/estudiantes',
    ROOT . '/uploads/comprobantes',
    ROOT . '/assets/img',
    ROOT . '/assets/css',
];

echo '<div class="box"><h3>Carpetas</h3>';
foreach ($carpetas as $carpeta) {
    $existe   = is_dir($carpeta);
    $escribir = $existe && is_writable($carpeta);
    $perms    = $existe ? substr(sprintf('%o', fileperms($carpeta)), -4) : '&mdash;';
    echo '<div>';
    echo ($existe ? '<span class="ok">&#10003; EXISTS</span>' : '<span class="err">&#10007; NO EXISTE</span>');
    echo ' ';
    echo ($escribir ? '<span class="ok">&#10003; WRITABLE</span>' : '<span class="err">&#10007; NO WRITABLE</span>');
    echo " [{$perms}] {$carpeta}";
    // Crear si no existe
    if (!$existe) {
        mkdir($carpeta, 0777, true);
        echo ' &#8594; <span class="ok">CREADA</span>';
    }
    echo '</div>';
}
echo '</div>';

// 3. Prueba de escritura real
echo '<div class="box"><h3>Prueba de escritura en uploads/cursos/</h3>';
$test_file = ROOT . '/uploads/cursos/test_write_' . time() . '.txt';
$resultado = file_put_contents($test_file, 'test');
if ($resultado !== false) {
    echo '<span class="ok">&#10003; Escritura OK</span> &mdash; archivo creado: ' . basename($test_file);
    unlink($test_file);
} else {
    echo '<span class="err">&#10007; ERROR &mdash; PHP no puede escribir en esa carpeta</span>';
    echo '<br><br><strong>Soluci&oacute;n:</strong> Ejecuta en Terminal:<br>';
    echo '<code>sudo chmod -R 777 "' . ROOT . '/uploads"</code><br>';
    echo '<code>sudo chmod 777 "' . ROOT . '/assets/img"</code>';
}
echo '</div>';

// 4. PHP upload config
echo '<div class="box"><h3>Configuraci&oacute;n PHP para uploads</h3>';
echo 'upload_max_filesize: <strong>' . ini_get('upload_max_filesize') . '</strong><br>';
echo 'post_max_size: <strong>' . ini_get('post_max_size') . '</strong><br>';
echo 'file_uploads: <strong>' . (ini_get('file_uploads') ? 'ON' : 'OFF') . '</strong><br>';
echo 'upload_tmp_dir: <strong>' . (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) . '</strong>';
echo '</div>';

// 5. Comando para corregir permisos
echo '<div class="box"><h3>Comandos para corregir permisos (Terminal Mac)</h3>';
echo '<code>sudo chmod -R 777 "' . ROOT . '/uploads"</code><br>';
echo '<code>sudo chmod 777 "' . ROOT . '/assets/img"</code>';
echo '</div>';

echo '<p style="font-family:sans-serif;color:#999;font-size:.8rem;">&#9888;&#65039; Elimina este archivo despu&eacute;s de resolver el problema.</p>';
