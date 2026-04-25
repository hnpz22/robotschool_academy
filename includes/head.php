<?php
// includes/head.php
// BASE_URL viene definido en config.php autom&aacute;ticamente
// Siempre apunta a http://localhost/robotschool_academy/
// $titulo debe definirse antes de incluir este archivo
$titulo = $titulo ?? 'ROBOTSchool Academy Learning';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= h($titulo) ?> &mdash; RSAL</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<link href="<?= BASE_URL ?>assets/css/rsal.css" rel="stylesheet"/>
</head>
<body>
